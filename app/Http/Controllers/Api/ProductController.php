<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ProductController extends Controller
{
    /**
     * Get product listing
     */
    public function index(Request $request)
    {
        // Get locale from header or default
        $locale = $request->header('Accept-Language', 'de');
        if (!in_array($locale, ['de', 'en', 'ar'])) {
            $locale = 'de';
        }
        
        App::setLocale($locale);
        
        $query = Product::active()
            ->with(['images', 'variants', 'subcategory.category']);

        // Apply filters
        if ($request->has('category')) {
            $query->inCategory($request->category);
        }
        
        if ($request->has('subcategory')) {
            $query->inSubcategory($request->subcategory);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereTranslation('product_name', $search, $locale);
        }
        
        if ($request->has('min_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }
        
        if ($request->boolean('on_sale')) {
            $query->onSale();
        }
        
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }
        
        // Sorting
        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(sale_price, regular_price) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(sale_price, regular_price) DESC');
                break;
            case 'popular':
                $query->orderBy('is_featured', 'desc')->latest();
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }
        
        $products = $query->paginate($request->get('per_page', 20));
        
        // Transform products for API response
        $products->through(function ($product) use ($locale) {
            return $this->transformProduct($product, $locale);
        });
        
        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'locale' => $locale,
                'direction' => $locale === 'ar' ? 'rtl' : 'ltr'
            ],
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl()
            ]
        ]);
    }

    /**
     * Get single product
     */
    public function show($id, Request $request)
    {
        $locale = $request->header('Accept-Language', 'de');
        if (!in_array($locale, ['de', 'en', 'ar'])) {
            $locale = 'de';
        }
        
        App::setLocale($locale);
        
        $product = Product::active()
            ->with([
                'images',
                'variants.color',
                'variants.size',
                'subcategory.category',
                'reviews' => function ($query) {
                    $query->where('is_approved', true)->latest()->limit(5);
                }
            ])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $this->transformProduct($product, $locale, true)
        ]);
    }

    /**
     * Get categories
     */
    public function categories(Request $request)
    {
        $locale = $request->header('Accept-Language', 'de');
        if (!in_array($locale, ['de', 'en', 'ar'])) {
            $locale = 'de';
        }
        
        App::setLocale($locale);
        
        $categories = Category::active()
            ->with(['subcategories' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();
        
        $categories = $categories->map(function ($category) use ($locale) {
            return [
                'id' => $category->category_id,
                'name' => $category->getTranslation('category_name', $locale),
                'slug' => $category->getTranslation('category_slug', $locale),
                'subcategories' => $category->subcategories->map(function ($sub) use ($locale) {
                    return [
                        'id' => $sub->subcategory_id,
                        'name' => $sub->getTranslation('subcategory_name', $locale),
                        'slug' => $sub->getTranslation('subcategory_slug', $locale),
                        'product_count' => $sub->products()->active()->count()
                    ];
                })
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $categories,
            'locale' => $locale
        ]);
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);
        
        $locale = $request->header('Accept-Language', 'de');
        if (!in_array($locale, ['de', 'en', 'ar'])) {
            $locale = 'de';
        }
        
        App::setLocale($locale);
        
        $query = $request->get('q');
        
        $products = Product::active()
            ->whereTranslation('product_name', $query, $locale)
            ->orWhere('sku', 'LIKE', "%{$query}%")
            ->with(['images' => function ($q) {
                $q->where('is_primary', true);
            }])
            ->limit(10)
            ->get();
        
        $results = $products->map(function ($product) use ($locale) {
            return [
                'id' => $product->product_id,
                'name' => $product->getTranslation('product_name', $locale),
                'price' => $product->current_price,
                'image' => $product->primary_image?->image_url,
                'url' => $product->getUrl($locale)
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $results,
            'query' => $query,
            'locale' => $locale
        ]);
    }

    /**
     * Transform product for API response
     */
    private function transformProduct($product, $locale, $detailed = false)
    {
        $data = [
            'id' => $product->product_id,
            'sku' => $product->sku,
            'name' => $product->getTranslation('product_name', $locale),
            'slug' => $product->getTranslation('product_slug', $locale),
            'description' => $product->getTranslation('description', $locale),
            'short_description' => $product->getTranslation('short_description', $locale),
            'regular_price' => $product->regular_price,
            'sale_price' => $product->sale_price,
            'current_price' => $product->current_price,
            'is_on_sale' => $product->is_on_sale,
            'discount_percentage' => $product->discount_percentage,
            'in_stock' => $product->in_stock,
            'total_stock' => $product->total_stock,
            'category' => [
                'id' => $product->subcategory->category->category_id,
                'name' => $product->subcategory->category->getTranslation('category_name', $locale),
                'slug' => $product->subcategory->category->getTranslation('category_slug', $locale)
            ],
            'subcategory' => [
                'id' => $product->subcategory->subcategory_id,
                'name' => $product->subcategory->getTranslation('subcategory_name', $locale),
                'slug' => $product->subcategory->getTranslation('subcategory_slug', $locale)
            ],
            'images' => $product->images->map(function ($image) use ($locale) {
                return [
                    'url' => $image->image_url,
                    'alt' => $image->getTranslation('image_alt', $locale) ?? $image->image_alt,
                    'is_primary' => $image->is_primary
                ];
            }),
            'rating' => [
                'average' => round($product->average_rating, 1),
                'count' => $product->review_count
            ]
        ];
        
        if ($detailed) {
            $data['material'] = $product->getTranslation('material', $locale);
            $data['care_instructions'] = $product->getTranslation('care_instructions', $locale);
            $data['variants'] = $product->variants->map(function ($variant) use ($locale) {
                return [
                    'id' => $variant->variant_id,
                    'sku' => $variant->variant_sku,
                    'color' => $variant->color ? [
                        'id' => $variant->color->color_id,
                        'name' => $variant->color->getTranslation('color_name', $locale),
                        'code' => $variant->color->color_code
                    ] : null,
                    'size' => [
                        'id' => $variant->size->size_id,
                        'name' => $variant->size->size_name,
                        'measurements' => $variant->size->measurements
                    ],
                    'stock' => $variant->stock_quantity,
                    'available' => $variant->is_available
                ];
            });
            
            if ($product->reviews) {
                $data['reviews'] = $product->reviews->map(function ($review) use ($locale) {
                    return [
                        'id' => $review->review_id,
                        'rating' => $review->rating,
                        'text' => $review->review_text,
                        'language' => $review->review_language,
                        'date' => $review->created_at->toIso8601String(),
                        'user' => $review->user ? [
                            'name' => $review->user->first_name . ' ' . substr($review->user->last_name, 0, 1) . '.'
                        ] : null
                    ];
                });
            }
        }
        
        return $data;
    }
}