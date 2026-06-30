<?php

namespace App\Http\Controllers\Api\Reports\Core;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Models\So;

use App\Http\Requests\SalesOrderValidationIndex;

use App\Http\Resources\SalesOrderResourcesCollection;
use App\Http\Resources\SalesOrderDetailResource;

use Dedoc\Scramble\Attributes\Group;
#[Group('URL API SALES ORDER')]

class SalesOrdersController extends Controller
{
    protected $So;

    public function __construct(So $So)
    {
        $this->So = $So;
    }

    /**
     * List Sales Orders.
     */
    public function index(SalesOrderValidationIndex $request)
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? null;
        $customer = $validated['customer'] ?? null;
        $location = $validated['location_id'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';

        $onlyDeleted = $validated['only_deleted'] ?? false;

        $results = $this->So
            ->select([
                'id',
                'salesorder_no',
                'invoice_no',
                'ref_no',
                'customer_name',
                'customer_phone',
                'customer_email',
                'channel_name',
                'store_name',
                'transaction_date',
                'completed_date',
                'internal_status',
                'channel_status',
                'wms_status',
                'payment_method',
                'location_name',
                'is_cod',
                'sub_total',
                'total_disc',
                'total_tax',
                'grand_total',
                'is_paid',
                'is_canceled',
                'sync_from_jubelio',
                'sync_to_odoo',
                'created_at',
                'updated_at'
            ])
            ->onlyDeleted($onlyDeleted)
            ->search($search)
            ->status($status)
            ->customer($customer)
            ->location($location)
            ->dateBetween($dateFrom, $dateTo)
            ->sort($sortBy, $sortDir)
            ->get();


            $summary = [
                'total_data' => $results->count(),

                'total_amount' => $results->sum('grand_total'),

                'paid' => $results->where('is_paid', true)->count(),

                'unpaid' => $results->where('is_paid', false)->count(),

                'cancelled' => $results->where('is_canceled', true)->count(),
            ];

        return ApiResponse::success(

            new SalesOrderResourcesCollection(
                $results,
                $summary
            ),
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
        $salesOrder = So::with('items')
            ->find($id);

        if (!$salesOrder) {
            return ApiResponse::error(
                'Sales Order tidak ditemukan',
                404
            );
        }

        return ApiResponse::success(
            new SalesOrderDetailResource($salesOrder),
            'Success'
        );
    }

    /**
     * Detail by Sales Order Number
     */
    public function showByNumber($salesorder_no)
    {
        $salesOrder = So::with('items')
            ->where('salesorder_no', $salesorder_no)
            ->first();

        if (!$salesOrder) {
            return ApiResponse::error(
                'Sales Order tidak ditemukan',
                404
            );
        }

        return ApiResponse::success(
            new SalesOrderDetailResource($salesOrder),
            'Success'
        );
    }

    /**
     * Detail by Reference Number
     */
    public function showByRef($ref_no)
    {
        $salesOrder = So::with('items')
            ->where('ref_no', $ref_no)
            ->first();

        if (!$salesOrder) {
            return ApiResponse::error(
                'Sales Order tidak ditemukan',
                404
            );
        }

        return ApiResponse::success(
            new SalesOrderDetailResource($salesOrder),
            'Success'
        );
    }



     /**
        * Sales order Dashboard
    */
     public function dashboard()
    {
        $summary = [

            'total_sales_orders' => So::count(),

            'completed_sales_orders' => So::where('internal_status', 'COMPLETED')->count(),

            'canceled_sales_orders' => So::where('is_canceled', true)->count(),

            'total_customers' => So::distinct('contact_id')->count('contact_id'),

            'total_amount' => So::sum('grand_total'),

            'today_sales_orders' => So::whereDate(
                'transaction_date',
                today()
            )->count(),

            'this_month_sales_orders' => So::whereMonth(
                'transaction_date',
                now()->month
            )->whereYear(
                'transaction_date',
                now()->year
            )->count(),
        ];

        $integration = [

            'waiting_fetch_detail' => So::where('detail_fetched', false)->count(),

            'success_fetch_detail' => So::where('detail_fetched', true)->count(),

            'failed_fetch_detail' => So::where('detail_fetched', false)
                ->whereNotNull('sync_from_jubelio_error')
                ->count(),

            'waiting_sync_to_odoo' => So::where('sync_to_odoo', false)
                ->whereNull('sync_error')
                ->count(),

            'success_sync_to_odoo' => So::where('sync_to_odoo', true)
                ->count(),

            'failed_sync_to_odoo' => So::whereNotNull('sync_error')
                ->count(),

            'success_sync_from_jubelio' => So::where('sync_from_jubelio', true)
                ->count(),

            'failed_sync_from_jubelio' => So::whereNotNull('sync_from_jubelio_error')
                ->count(),
        ];

        $monthlyChart = So::selectRaw("
                EXTRACT(MONTH FROM transaction_date) as month,
                COUNT(*) as total_sales_order,
                COALESCE(SUM(grand_total),0) as total_amount
            ")
            ->whereYear('transaction_date', now()->year)
            ->groupByRaw("EXTRACT(MONTH FROM transaction_date)")
            ->orderByRaw("EXTRACT(MONTH FROM transaction_date)")
            ->get();

        $topCustomers = So::selectRaw("
                customer_name,
                COUNT(*) as total_sales_order,
                COALESCE(SUM(grand_total),0) as total_amount
            ")
            ->whereNotNull('customer_name')
            ->groupBy('customer_name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        return ApiResponse::success([
            'summary' => $summary,
            'integration' => $integration,
            'monthly_chart' => $monthlyChart,
            'top_customers' => $topCustomers,
        ], 'Success');
    }
}