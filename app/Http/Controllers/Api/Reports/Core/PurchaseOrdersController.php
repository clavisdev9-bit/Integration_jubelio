<?php

namespace App\Http\Controllers\Api\Reports\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use App\Http\Resources\PurchaseOrderResources;
use App\Http\Resources\PurchaseOrderResourcesCollection;
use App\Http\Requests\PurchaseOrderValidationIndex;
use App\Http\Resources\PurchaseOrderDetailResource;

use App\Models\PoItem;
use App\Models\Po;


class PurchaseOrdersController extends Controller
{
    protected $Po;

    public function __construct(Po $Po)
    {
        $this->Po = $Po;
    }

    /**
     * List Purchase Order
     */
    public function index(PurchaseOrderValidationIndex $request)
    {
        $validated = $request->validated();

        $search       = $validated['search'] ?? null;
        $sortBy       = $validated['sort_by'] ?? 'created_at';
        $sortDir      = $validated['sort_dir'] ?? 'desc';
        $onlyDeleted  = $validated['only_deleted'] ?? false;

        $results = $this->Po
            ->select([
                'id',
                'purchaseorder_no',
                'ref_no',
                'supplier_name',
                'location_name',
                'status',
                'transaction_date',
                'grand_total',
                'created_at',
                'updated_at',
            ])
            ->onlyDeleted($onlyDeleted)
            ->search($search)
            ->sort($sortBy, $sortDir)
            ->get();

        return ApiResponse::success(
            new PurchaseOrderResourcesCollection($results),
            $results->isEmpty()
                ? 'Data tidak ditemukan'
                : 'Success'
        );
    }

    /**
     * Detail by ID
     */
    public function showById($id)
    {
        return $this->findPurchaseOrder('id', $id);
    }

    /**
     * Detail by Purchase Order Number
     */
    public function showByNumber($purchaseorder_no)
    {
        return $this->findPurchaseOrder('purchaseorder_no', $purchaseorder_no);
    }

    /**
     * Detail by Reference Number
     */
    public function showByRef($ref_no)
    {
        return $this->findPurchaseOrder('ref_no', urldecode($ref_no));
    }

    /**
     * Reusable finder
     */
    private function findPurchaseOrder(string $field, string $value)
    {
        $purchaseOrder = $this->Po
            ->with('items')
            ->where($field, $value)
            ->first();

        if (!$purchaseOrder) {
            return ApiResponse::error(
                'Purchase Order tidak ditemukan',
                404
            );
        }

        return ApiResponse::success(
            new PurchaseOrderDetailResource($purchaseOrder),
            'Success'
        );
    }
}

