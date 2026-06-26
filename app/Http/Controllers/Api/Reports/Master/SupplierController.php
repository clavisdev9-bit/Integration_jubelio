<?php

namespace App\Http\Controllers\Api\Reports\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use App\Models\MasterSupplier;

use App\Http\Requests\SupplierValidationIndex;
use App\Http\Resources\SupplierResources;
use App\Http\Resources\SupplierResourcesCollection;


class SupplierController extends Controller
{

    protected $MasterSupplier;
    public function __construct(MasterSupplier $MasterSupplier) {
        $this->MasterSupplier = $MasterSupplier;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(SupplierValidationIndex $request)
    {
            $validated = $request->validated();
            $search = $validated['search'] ?? null;
            $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortDir = $validated['sort_dir'] ?? 'desc';
            $onlyDeleted = $validated['only_deleted'] ?? false;

            $query = $this->MasterSupplier
                ->onlyDeleted($onlyDeleted)
                ->search($search)
                ->sort($sortBy, $sortDir);
                $results = $query->get();

            $message = $results->isEmpty()
                ? 'Data yang Anda cari tidak ditemukan'
                : 'Success';
                return ApiResponse::success(
                    new SupplierResourcesCollection($results),
                    $message
                );
    }

   
}
