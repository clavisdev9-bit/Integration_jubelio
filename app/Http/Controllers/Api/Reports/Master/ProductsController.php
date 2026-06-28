<?php

namespace App\Http\Controllers\Api\Reports\Master;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\MasterProduct;
use App\Http\Requests\ProductsValidationIndex;
use App\Http\Resources\ProductsResourcesCollection;
use Dedoc\Scramble\Attributes\Group;

#[Group('URL API Master')]
class ProductsController extends Controller
{
    public function __construct(
        protected MasterProduct $masterProduct
    ) {}

    /**
     * Get Master Product
     */
    public function index(ProductsValidationIndex $request)
    {
        $validated = $request->validated();

        $search      = $validated['search'] ?? null;
        $sortBy      = $validated['sort_by'] ?? 'created_at';
        $sortDir     = $validated['sort_dir'] ?? 'desc';
        $onlyDeleted = (bool) ($validated['only_deleted'] ?? false);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo   = $validated['date_to'] ?? null;

        $results = $this->masterProduct
            ->with(['category', 'variants'])
            ->when($onlyDeleted, fn ($q) => $q->onlyTrashed())
            ->search($search)
            ->filterDate($dateFrom, $dateTo)
            ->sort($sortBy, $sortDir)
            ->get();

        $message = $results->isEmpty()
            ? 'Data yang Anda cari tidak ditemukan'
            : 'Success';

        return ApiResponse::success(
            new ProductsResourcesCollection($results),
            $message
        );
    }
}