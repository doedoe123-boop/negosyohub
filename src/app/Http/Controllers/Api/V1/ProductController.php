<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\ReviewEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only product browsing endpoints for the customer storefront.
 *
 * Products belong to stores and are managed in the Lunar panel.
 * This controller exposes them publicly with pagination and filtering.
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private ReviewEligibilityService $reviewEligibilityService
    ) {}

    /**
     * List all published products across all approved stores.
     *
     * Supports optional ?search= and ?per_page= query params.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->productService->browse(
                $request->only(['search', 'per_page'])
            )
        );
    }

    /**
     * Show a single published product with all variants and pricing.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findModelOrFail($id);
        $data = $this->productService->formatProduct($product, detailed: true);

        if ($requestUser = request()->user('sanctum')) {
            $data['review_eligibility'] = $this->reviewEligibilityService->forProduct($requestUser, $product);
        }

        return response()->json($data);
    }

    /**
     * List published products scoped to a specific approved store.
     *
     * Resolves the store by slug. Returns 404 if the store is not approved.
     */
    public function storeProducts(Store $store, Request $request): JsonResponse
    {
        abort_if(! $store->isApproved(), 404);

        return response()->json(
            $this->productService->browseForStore(
                $store,
                $request->only(['search', 'per_page'])
            )
        );
    }
}
