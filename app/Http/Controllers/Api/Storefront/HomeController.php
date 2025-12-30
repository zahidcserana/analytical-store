<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\ApiController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\Customer\StoreRequest;
use Illuminate\Support\Facades\DB;
use App\Enums\Source;


class HomeController extends ApiController
{
    public function index()
    {
        $tenant = app('tenant');
        // $customerIds = Customer::withClients($tenant->id)->pluck('id')->toArray();

        // ✅ Eager load only what’s needed
        $tags = $tenant->tags()
            ->with([
                'products' => function ($query) use ($tenant) {
                    $query->where('customer_id', $tenant->id)
                        ->latest()
                        ->take(10)
                        ->with('productImages'); // eager load images for performance
                }
            ])
            ->orderBy('name')
            ->get();

        // ✅ Prepare final structured response
        $response = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->ContactInformation->name,
                'image' => $tenant->threeplLogo?->source ?? asset('img/banner.jpg'),
                'store_image' => $tenant->storeLogo?->source ?? asset('img/store.webp'),
                'banner_image' => $tenant->bannerImages()->pluck('source'),
                'company' => $tenant->ContactInformation->company_name,
                'phone' => $tenant->ContactInformation->phone,
                'address' => $tenant->ContactInformation->address,
                'city' => $tenant->ContactInformation->city,
                'slug' => $tenant->slug,
                'store_domain' => $tenant->store_domain,
                'about' => customer_settings($tenant->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION),
                'moto' => customer_settings($tenant->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER),
                'store_tagline' => customer_settings($tenant->id, CustomerSetting::CUSTOMER_SETTING_EEL_PFC),
            ],
            'tags' => $tags->map(function ($tag) {
                $productCount = $tag->products()->count();

                $products = $tag->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'feature_product' => $product->inventory_sync,
                        'category' => $product->tags?->first()?->name,
                        'sku' => $product->sku,
                        'description' => nl2br($product->notes),
                        'price' => (float) $product->price,
                        'image_url' => $product->productImages->first()?->source ?? asset('img/product-default.png'),
                        'updated_at' => $product->updated_at->toDateTimeString(),
                    ];
                });

                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'product_count' => $productCount,
                    'products' => $products,
                    'feature_product' => $products->where('feature_product', true)->first(), // ✅ this now works properly
                ];
            }),
        ];

        return response()->json($response);
    }

    public function storeCustomer(Request $request)
    {
        try {
            
            return DB::transaction(function () use ($request) {
                $tenant = app('tenant');
                $input = $request->all();
                $input['parent_customer_id'] = $tenant->id;
                $input['is_hold'] = true;
    
                $storeRequest = StoreRequest::make($input);
                $customer = app('customer')->store($storeRequest, source: Source::PUBLIC_API);

                return response()->json([
                    'success' => true,
                    'message' => __('Customer successfully created.')
                ]);
            });
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ], 500);
        }
    }
}
