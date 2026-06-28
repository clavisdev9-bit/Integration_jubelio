<?php

namespace App\Http\Controllers\Api\Reports\Master;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\MasterSupplier;
use App\Http\Requests\SupplierValidationIndex;
use App\Http\Resources\SupplierResourcesCollection;
use Dedoc\Scramble\Attributes\Group;

#[Group('URL API Master')]
class SupplierController extends Controller
{
    public function __construct(
        protected MasterSupplier $masterSupplier
    ) {}

    /**
     * Get Master Supplier
     */
    public function index(SupplierValidationIndex $request)
    {
        $validated = $request->validated();

        $search      = $validated['search'] ?? null;
        $sortBy      = $validated['sort_by'] ?? 'created_at';
        $sortDir     = $validated['sort_dir'] ?? 'desc';
        $onlyDeleted = (bool) ($validated['only_deleted'] ?? false);

        $results = $this->masterSupplier
    ->when($onlyDeleted, fn ($q) => $q->onlyTrashed())
    ->search($search)
    ->sort($sortBy, $sortDir)
    ->get();

        $message = $results->isEmpty()
            ? 'Data yang Anda cari tidak ditemukan'
            : 'Success';

        return ApiResponse::success(
            new SupplierResourcesCollection($results),
            $message
        );
    }
}