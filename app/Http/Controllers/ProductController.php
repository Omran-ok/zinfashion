<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display product listing
     */
    public function index(Request $request)
    {
        $locale = app()->getLocale();
        
        // Get categories for filters
        $categories = Category::active()
            ->with(['subcategories' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();

        // Build products query
        $query = Product::active()
            ->with(['images' => function ($query) {
                $query->where('is_primary', true);
            }, 'variants']);

        // Filter by category
        if ($request->has('category')) {
            $category = Category::where('category_slug', $request->category)
                ->orWhereJsonContains('slug_translations', [$locale => $request->category])
                ->first();
            
            if ($category) {
                $query->inCategory($category->category_id);
            }
        }

        // Filter by subcategory
        if ($request->has('subcategory')) {
            $subcategory = Subcategory::where('subcategory_slug', $request->subcategory)
                ->orWhereJsonContains('slug_translations', [$locale => $request->subcategory])
                ->first();
            
            if ($subcategory) {
                $query->inSubcategory($subcategory->subcategory_id);
            }
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            
            $query->where(function ($q) use ($search, $locale) {
                // Search in translated fields
                $q->whereJsonContains('name_translations', [$locale => $search])
                  ->orWhereJsonContains('description_translations', [$locale => $search])
                  ->orWhere('product_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // Price range filter
        if ($request->has('min_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // On sale filter
        if ($request->boolean('on_sale')) {
            $query->onSale();
        }

        // In stock filter
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
            case 'name':
                $query->orderBy('product_name');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        // Paginate results
        $products = $query->paginate(20)->withQueryString();

        // Transform products for localization
        $products->through(function ($product) use ($locale) {
            $productArray = $product->toArray();
            
            // Add translated fields
            $productArray['localized'] = [
                'name' => $product->getTranslation('product_name', $locale),
                'description' => $product->getTranslation('description', $locale),
                'short_description' => $product->getTranslation('short_description', $locale),
                'slug' => $product->getTranslation('product_slug', $locale),
                'url' => $product->getUrl($locale)
            ];
            
            return $productArray;
        });

        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'products' => $products,
                'locale' => $locale,
                'direction' => session('text_direction', 'ltr')
            ]);
        }

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Display single product
     */
    public function show($locale, $slug)
    {
        // Find product by slug (check all language slugs)
        $product = Product::where('product_slug', $slug)
            ->orWhere(function ($query) use ($slug) {
                $query->whereJsonContains('slug_translations->de', $slug)
                      ->orWhereJsonContains('slug_translations->en', $slug)
                      ->orWhereJsonContains('slug_translations->ar', $slug);
            })
            ->active()
            ->with([
                'images',
                'variants.color',
                'variants.size',
                'subcategory.category',
                'reviews' => function ($query) {
                    $query->where('is_approved', true)
                          ->latest()
                          ->limit(10);
                }
            ])
            ->firstOrFail();

        // Get localized data
        $localizedData = $product->toLocalizedArray($locale);

        // Get related products
        $relatedProducts = Product::active()
            ->where('subcategory_id', $product->subcategory_id)
            ->where('product_id', '!=', $product->product_id)
            ->withMainImage()
            ->inStock()
            ->limit(4)
            ->get();

        // Get available variants grouped by color
        $variantsByColor = $product->variants
            ->filter(function ($variant) {
                return $variant->stock_quantity > 0;
            })
            ->groupBy('color_id')
            ->map(function ($variants) {
                return [
                    'color' => $variants->first()->color,
                    'sizes' => $variants->map(function ($variant) {
                        return [
                            'size' => $variant->size,
                            'variant_id' => $variant->variant_id,
                            'stock' => $variant->stock_quantity,
                            'sku' => $variant->variant_sku ?? $variant->product->sku . '-' . $variant->size->size_name
                        ];
                    })
                ];
            });

        // Breadcrumbs
        $breadcrumbs = [
            ['title' => __('nav.home'), 'url' => route('home', $locale)],
            [
                'title' => $product->subcategory->category->getTranslation('category_name', $locale),
                'url' => $product->subcategory->category->getUrl($locale)
            ],
            [
                'title' => $product->subcategory->getTranslation('subcategory_name', $locale),
                'url' => route('products.index', [
                    'locale' => $locale,
                    'subcategory' => $product->subcategory->getTranslation('subcategory_slug', $locale)
                ])
            ],
            ['title' => $localizedData['product_name'], 'url' => null]
        ];

        // Handle JSON requests
        if (request()->wantsJson()) {
            return response()->json([
                'product' => $localizedData,
                'variants' => $variantsByColor,
                'related' => $relatedProducts->map(function ($p) use ($locale) {
                    return $p->toLocalizedArray($locale);
                }),
                'breadcrumbs' => $breadcrumbs
            ]);
        }

        return view('products.show', compact(
            'product',
            'localizedData',
            'relatedProducts',
            'variantsByColor',
            'breadcrumbs'
        ));
    }

    /**
     * Quick view for product (AJAX)
     */
    public function quickView($locale, $id)
    {
        $product = Product::active()
            ->with(['images', 'variants.color', 'variants.size'])
            ->findOrFail($id);

        $localizedData = $product->toLocalizedArray($locale);

        return response()->json([
            'product' => $localizedData,
            'html' => view('products.quick-view', compact('product', 'localizedData'))->render()
        ]);
    }
}