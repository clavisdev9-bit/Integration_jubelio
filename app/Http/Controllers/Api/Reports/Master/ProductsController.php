<?php

namespace App\Http\Controllers\Api\Reports\Master;

// laravel bawaaan
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

// helper 
use App\Helpers\ApiResponse;

// model
use App\Models\MasterProduct;
use App\Models\ProductCategory;
use App\Models\ProductVariant;

// request
use App\Http\Requests\ProductsValidationIndex;

// resources
use App\Http\Resources\ProductsResource;
use App\Http\Resources\ProductsResourcesCollection;

use Dedoc\Scramble\Attributes\Group;
#[Group('URL API Master')]
class ProductsController extends Controller
{
    protected $MasterProduct;
    public function __construct(MasterProduct $MasterProduct) {
        $this->MasterProduct = $MasterProduct;
    }

    /**
     * Get Master Product
     *
     * Mengambil daftar Product.
     */
    public function index(ProductsValidationIndex $request)
    {
        $validated = $request->validated();

        $search      = $validated['search'] ?? null;
        $perPage     = (int) ($validated['per_page'] ?? 10);
        $sortBy      = $validated['sort_by'] ?? 'created_at';
        $sortDir     = $validated['sort_dir'] ?? 'desc';
        $onlyDeleted = (bool) ($validated['only_deleted'] ?? false);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo   = $validated['date_to'] ?? null;

        $query = $this->MasterProduct
            ->with([
                'category',
                'variants'
            ])
            ->when($onlyDeleted, fn ($q) => $q->onlyTrashed())
            ->search($search)
             ->filterDate($dateFrom, $dateTo)
            ->sort($sortBy, $sortDir);
            // $results = $query->paginate($perPage);
            $results = $query->get();
            $message = $results->isEmpty()
                ? 'Data yang Anda cari tidak ditemukan'
                : 'Success';
        
                return ApiResponse::success(
                    new ProductsResourcesCollection($results),
                    $message
                );
    }
}
