<?php

namespace App\Http\Controllers;

use App\Models\DataExportRequest;
use App\Models\DataDeletionRequest;
use App\Models\UserConsent;
use App\Jobs\ProcessDataExport;
use App\Jobs\ProcessDataDeletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GdprController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show privacy settings page
     */
    public function privacySettings()
    {
        $user = auth()->user();
        $locale = app()->getLocale();
        
        // Get current consents
        $consents = [
            'privacy_policy' => $user->hasConsent('privacy_policy'),
            'terms_of_service' => $user->hasConsent('terms_of_service'),
            'newsletter' => $user->hasConsent('newsletter'),
            'cookies' => $user->hasConsent('cookies'),
        ];

        // Get consent history
        $consentHistory = $user->consents()
            ->orderBy('consent_date', 'desc')
            ->get()
            ->map(function ($consent) use ($locale) {
                return [
                    'type' => $consent->consent_type,
                    'given' => $consent->consent_given,
                    'date' => $consent->consent_date->format(__('date.format')),
                    'withdrawn_date' => $consent->withdrawn_date?->format(__('date.format')),
                    'version' => $consent->consent_version,
                ];
            });

        // Get data requests
        $exportRequests = DataExportRequest::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $deletionRequests = DataDeletionRequest::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(1)
            ->get();

        return view('gdpr.privacy-settings', compact(
            'consents',
            'consentHistory',
            'exportRequests',
            'deletionRequests'
        ));
    }

    /**
     * Update consent settings
     */
    public function updateConsent(Request $request)
    {
        $validated = $request->validate([
            'consent_type' => 'required|in:privacy_policy,terms_of_service,newsletter,cookies',
            'consent_given' => 'required|boolean'
        ]);

        $user = auth()->user();
        
        if ($validated['consent_given']) {
            $user->giveConsent($validated['consent_type'], '1.0', $request->ip());
            $message = __('gdpr.consent_given', ['type' => __('gdpr.consent_types.' . $validated['consent_type'])]);
        } else {
            $user->withdrawConsent($validated['consent_type']);
            $message = __('gdpr.consent_withdrawn', ['type' => __('gdpr.consent_types.' . $validated['consent_type'])]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Request data export
     */
    public function requestDataExport(Request $request)
    {
        $user = auth()->user();
        
        // Check if there's already a pending request
        $pendingRequest = DataExportRequest::where('user_id', $user->user_id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($pendingRequest) {
            $message = __('gdpr.export_already_pending');
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }
            
            return back()->with('info', $message);
        }

        // Create new export request
        $exportRequest = DataExportRequest::create([
            'user_id' => $user->user_id,
            'status' => 'pending'
        ]);

        // Dispatch job to process export
        ProcessDataExport::dispatch($exportRequest)->delay(now()->addMinutes(1));

        $message = __('gdpr.export_requested');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'request_id' => $exportRequest->request_id
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Download exported data
     */
    public function downloadExport($locale, $token)
    {
        // Find export request by token
        $exportRequest = DataExportRequest::where('download_token', $token)
            ->where('status', 'completed')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        // Verify user owns this export
        if ($exportRequest->user_id !== auth()->id()) {
            abort(403);
        }

        $filePath = $exportRequest->file_path;
        
        if (!Storage::exists($filePath)) {
            abort(404, __('gdpr.export_not_found'));
        }

        // Log download
        activity()
            ->performedOn($exportRequest)
            ->log('Data export downloaded');

        return Storage::download($filePath, 'my-data-' . now()->format('Y-m-d') . '.json');
    }

    /**
     * Request account deletion
     */
    public function requestDataDeletion(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'confirmation' => 'required|accepted',
            'password' => 'required|current_password'
        ]);

        $user = auth()->user();

        // Check if there's already a pending deletion request
        $pendingDeletion = DataDeletionRequest::where('user_id', $user->user_id)
            ->where('status', 'pending')
            ->first();

        if ($pendingDeletion) {
            $message = __('gdpr.deletion_already_pending');
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }
            
            return back()->with('info', $message);
        }

        // Create deletion request with 30-day grace period
        $deletionRequest = DataDeletionRequest::create([
            'user_id' => $user->user_id,
            'reason' => $validated['reason'],
            'scheduled_for' => now()->addDays(30),
            'status' => 'pending'
        ]);

        // Send confirmation email
        \Mail::to($user->email)
            ->send(new \App\Mail\AccountDeletionRequested($deletionRequest));

        // Log the request
        activity()
            ->performedOn($user)
            ->withProperties(['reason' => $validated['reason']])
            ->log('Account deletion requested');

        // Log out the user
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = __('gdpr.deletion_requested');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('home', app()->getLocale())
            ]);
        }

        return redirect()->route('home', app()->getLocale())
            ->with('success', $message);
    }

    /**
     * Cancel deletion request
     */
    public function cancelDeletion(Request $request, $locale, $token)
    {
        // Find deletion request by token
        $deletionRequest = DataDeletionRequest::where('cancellation_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        // Cancel the request
        $deletionRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        // Reactivate user account if needed
        $user = $deletionRequest->user;
        if ($user && !$user->is_active) {
            $user->update(['is_active' => true]);
        }

        $message = __('gdpr.deletion_cancelled');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('login', $locale)
            ->with('success', $message);
    }

    /**
     * Cookie settings (for cookie banner)
     */
    public function cookieSettings(Request $request)
    {
        $validated = $request->validate([
            'necessary' => 'required|boolean',
            'analytics' => 'required|boolean',
            'marketing' => 'required|boolean',
        ]);

        // Store cookie preferences
        $preferences = [
            'necessary' => true, // Always true
            'analytics' => $validated['analytics'],
            'marketing' => $validated['marketing'],
        ];

        // Set cookie with preferences
        cookie()->queue('cookie_preferences', json_encode($preferences), 60 * 24 * 365);

        // If user is logged in, update their consent
        if (auth()->check()) {
            auth()->user()->giveConsent('cookies', '1.0', $request->ip());
        }

        return response()->json([
            'success' => true,
            'message' => __('gdpr.cookie_preferences_saved')
        ]);
    }

    /**
     * Get GDPR compliance info (for footer link)
     */
    public function complianceInfo()
    {
        $locale = app()->getLocale();
        
        $info = [
            'data_controller' => config('gdpr.data_controller'),
            'contact_email' => config('gdpr.contact_email'),
            'data_protection_officer' => config('gdpr.dpo_email'),
            'retention_periods' => [
                'orders' => __('gdpr.retention.orders'), // 10 years for tax
                'user_data' => __('gdpr.retention.user_data'), // Until deletion
                'logs' => __('gdpr.retention.logs'), // 90 days
            ],
            'user_rights' => [
                'access' => __('gdpr.rights.access'),
                'rectification' => __('gdpr.rights.rectification'),
                'erasure' => __('gdpr.rights.erasure'),
                'portability' => __('gdpr.rights.portability'),
                'objection' => __('gdpr.rights.objection'),
            ],
            'legal_basis' => [
                'contract' => __('gdpr.legal_basis.contract'),
                'consent' => __('gdpr.legal_basis.consent'),
                'legitimate_interest' => __('gdpr.legal_basis.legitimate_interest'),
            ]
        ];

        return view('gdpr.compliance-info', compact('info'));
    }
}