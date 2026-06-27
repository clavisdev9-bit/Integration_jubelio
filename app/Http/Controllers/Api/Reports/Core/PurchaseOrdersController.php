<?php

namespace App\Http\Controllers\Api\Reports\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
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

use Dedoc\Scramble\Attributes\Group;
#[Group('URL API PURCHASE ORDER')]

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



public function dashboard()
    {
        $summary = [

            'total_purchase_orders' => Po::count(),

            'open_purchase_orders' => Po::where('is_closed', false)->count(),

            'closed_purchase_orders' => Po::where('is_closed', true)->count(),

            'total_suppliers' => Po::distinct('contact_id')->count('contact_id'),

            'total_amount' => Po::sum('grand_total'),

            'today_purchase_orders' => Po::whereDate(
                'transaction_date',
                today()
            )->count(),

            'this_month_purchase_orders' => Po::whereMonth(
                'transaction_date',
                now()->month
            )
            ->whereYear(
                'transaction_date',
                now()->year
            )
            ->count(),

        ];

        $integration = [

            'waiting_fetch_detail' => Po::where('detail_fetched', false)->count(),

            'success_fetch_detail' => Po::where('detail_fetched', true)->count(),

            'failed_fetch_detail' => Po::where('detail_fetched', false)
                ->whereNotNull('sync_from_jubelio_error')
                ->count(),

            'waiting_sync_to_odoo' => Po::where('sync_to_odoo', false)
                ->whereNull('sync_error')
                ->count(),

            'success_sync_to_odoo' => Po::where('sync_to_odoo', true)
                ->count(),

            'failed_sync_to_odoo' => Po::whereNotNull('sync_error')
                ->count(),

            'success_sync_from_jubelio' => Po::where('sync_from_jubelio', true)
                ->count(),

            'failed_sync_from_jubelio' => Po::whereNotNull('sync_from_jubelio_error')
                ->count(),

        ];

        $monthlyChart = Po::selectRaw("
                EXTRACT(MONTH FROM transaction_date) as month,
                COUNT(*) as total_po,
                COALESCE(SUM(grand_total),0) as total_amount
            ")
            ->whereYear('transaction_date', now()->year)
            ->groupByRaw("EXTRACT(MONTH FROM transaction_date)")
            ->orderByRaw("EXTRACT(MONTH FROM transaction_date)")
            ->get();

        $topSuppliers = Po::selectRaw("
                supplier_name,
                COUNT(*) as total_po,
                COALESCE(SUM(grand_total),0) as total_amount
            ")
            ->whereNotNull('supplier_name')
            ->groupBy('supplier_name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        return ApiResponse::success([
            'summary' => $summary,
            'integration' => $integration,
            'monthly_chart' => $monthlyChart,
            'top_suppliers' => $topSuppliers,
        ], 'Success');
    }
}

