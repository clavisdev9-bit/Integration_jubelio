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
   /**
 * List Purchase Order
 */
public function index(PurchaseOrderValidationIndex $request)
{
    $validated = $request->validated();

    $search       = $validated['search'] ?? null;
    $status       = $validated['status'] ?? null;
    $supplier     = $validated['supplier'] ?? null;
    $location     = $validated['location'] ?? null;
    $dateFrom     = $validated['date_from'] ?? null;
    $dateTo       = $validated['date_to'] ?? null;
    $sortBy       = $validated['sort_by'] ?? 'created_at';
    $sortDir      = $validated['sort_dir'] ?? 'desc';
    $onlyDeleted  = $validated['only_deleted'] ?? false;

    $query = $this->Po
        ->select([
            'id',
            'purchaseorder_no',
            'ref_no',

            'contact_id',
            'supplier_name',
            'supplier_email',

            'transaction_date',

            'status',

            'location_id',
            'location_name',

            'sub_total',
            'total_disc',
            'total_tax',
            'grand_total',

            'is_closed',

            'detail_fetched',

            'sync_from_jubelio',
            'sync_to_odoo',
            'sync_error',

            'created_at',
            'updated_at',
        ])
        ->onlyDeleted($onlyDeleted)
        ->search($search)
        ->status($status)
        ->supplier($supplier)
        ->location($location)
        ->dateBetween($dateFrom, $dateTo)
        ->sort($sortBy, $sortDir);

    $results = $query->get();

    $summary = [

        'total_data' => $results->count(),

        'total_amount' => $results->sum('grand_total'),

        'average_amount' => round($results->avg('grand_total'), 2),

        'open' => $results->where('is_closed', false)->count(),

        'closed' => $results->where('is_closed', true)->count(),

        'waiting_fetch_detail' => $results
            ->where('detail_fetched', false)
            ->count(),

        'success_fetch_detail' => $results
            ->where('detail_fetched', true)
            ->count(),

        'waiting_sync_odoo' => $results
            ->where('sync_to_odoo', false)
            ->count(),

        'success_sync_odoo' => $results
            ->where('sync_to_odoo', true)
            ->count(),

        'failed_sync_odoo' => $results
            ->filter(fn ($po) => filled($po->sync_error))
            ->count(),

    ];

    return ApiResponse::success(

        new PurchaseOrderResourcesCollection(
            $results,
            $summary
        ),

        $results->isEmpty()
            ? 'Data tidak ditemukan'
            : 'Success'

    );
}

    
    /**
     * Detail Purchase Order by ID
    */
    public function showById(int $id)
    {
        return $this->findPurchaseOrder('id', $id);
    }

    /**
     * Detail Purchase Order by Number
     */
    public function showByNumber(string $purchaseorder_no)
    {
        return $this->findPurchaseOrder(
            'purchaseorder_no',
            $purchaseorder_no
        );
    }

    /**
     * Detail Purchase Order by Reference Number
    */
    public function showByRef(string $ref_no)
    {
        return $this->findPurchaseOrder(
            'ref_no',
            urldecode($ref_no)
        );
    }

    /**
     * Find Purchase Order
     */
    private function findPurchaseOrder(
        string $field,
        string|int $value
    )
    {
        $purchaseOrder = $this->Po
            ->with('items')
            ->where($field, $value)
            ->first();

        if (!$purchaseOrder) {

            return ApiResponse::error(
                'Purchase Order tidak ditemukan.',
                404
            );

        }

        return ApiResponse::success(
            new PurchaseOrderDetailResource($purchaseOrder),
            'Success'
        );
    }


    /**
 * Dashboard Purchase Order
 */
public function dashboard()
{
    $summary = [

        // Purchase Order
        'total_purchase_orders' => Po::count(),

        'open_purchase_orders' => Po::where('is_closed', false)->count(),

        'closed_purchase_orders' => Po::where('is_closed', true)->count(),

        // Supplier
        'total_suppliers' => Po::distinct('contact_id')->count('contact_id'),

        // Item
        'total_items' => PoItem::count(),

        'total_qty' => PoItem::sum('qty'),

        // Amount
        'total_amount' => Po::sum('grand_total'),

        'average_amount' => round(Po::avg('grand_total'), 2),

        'highest_purchase' => Po::max('grand_total'),

        'lowest_purchase' => Po::min('grand_total'),

        // Today
        'today_purchase_orders' => Po::whereDate(
            'transaction_date',
            today()
        )->count(),

        // This Month
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

        // Detail Fetch
        'waiting_fetch_detail' => Po::where(
            'detail_fetched',
            false
        )->count(),

        'success_fetch_detail' => Po::where(
            'detail_fetched',
            true
        )->count(),

        'failed_fetch_detail' => Po::whereNotNull(
            'sync_from_jubelio_error'
        )->count(),

        // Sync Odoo
        'waiting_sync_to_odoo' => Po::where(
                'sync_to_odoo',
                false
            )
            ->whereNull('sync_error')
            ->count(),

        'success_sync_to_odoo' => Po::where(
            'sync_to_odoo',
            true
        )->count(),

        'failed_sync_to_odoo' => Po::whereNotNull(
            'sync_error'
        )->count(),

        // Sync Jubelio
        'success_sync_from_jubelio' => Po::where(
            'sync_from_jubelio',
            true
        )->count(),

        'failed_sync_from_jubelio' => Po::whereNotNull(
            'sync_from_jubelio_error'
        )->count(),
    ];

    // Grafik Purchase Order per Bulan
    $monthlyChart = Po::selectRaw("
            EXTRACT(MONTH FROM transaction_date) AS month,
            COUNT(*) AS total_purchase_orders,
            COALESCE(SUM(grand_total),0) AS total_amount
        ")
        ->whereYear(
            'transaction_date',
            now()->year
        )
        ->groupByRaw("
            EXTRACT(MONTH FROM transaction_date)
        ")
        ->orderByRaw("
            EXTRACT(MONTH FROM transaction_date)
        ")
        ->get();

    // Top Supplier
    $topSuppliers = Po::selectRaw("
            supplier_name,
            COUNT(*) AS total_purchase_orders,
            COALESCE(SUM(grand_total),0) AS total_amount
        ")
        ->whereNotNull('supplier_name')
        ->groupBy('supplier_name')
        ->orderByDesc('total_amount')
        ->limit(10)
        ->get();

    // Top Item Purchase
    $topItems = PoItem::selectRaw("
            item_code,
            item_name,
            SUM(qty) AS total_qty,
            SUM(amount) AS total_amount
        ")
        ->groupBy(
            'item_code',
            'item_name'
        )
        ->orderByDesc('total_qty')
        ->limit(10)
        ->get();

    return ApiResponse::success([

        'summary' => $summary,

        'integration' => $integration,

        'monthly_chart' => $monthlyChart,

        'top_suppliers' => $topSuppliers,

        'top_items' => $topItems,

    ], 'Success');
}
}

