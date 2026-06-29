<?php

namespace App\Http\Controllers\Api\Reports\Master;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;

use App\Models\MasterLocations;

use App\Http\Requests\LocationValidationIndex;

use App\Http\Resources\LocationResourceCollection;
use App\Http\Resources\LocationDetailResource;

use Dedoc\Scramble\Attributes\Group;

#[Group('URL API MASTER LOCATION')]
class LocationsController extends Controller
{
    protected $MasterLocations;

    public function __construct(MasterLocations $MasterLocations)
    {
        $this->MasterLocations = $MasterLocations;
    }

    /**
     * List Location
     */
    public function index(LocationValidationIndex $request)
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $onlyDeleted = $validated['only_deleted'] ?? false;

        $locations = $this->MasterLocations
            ->select([
    'id',
    'jubelio_location_id',
    'location_code',
    'location_name',
    'location_type',
    'city',
    'province',
    'contact_name',
    'phone',
    'email',
    'is_active',
    'is_warehouse',
    'odoo_id',
    'sync_from_jubelio',
    'created_at',
    'updated_at',
])
            ->onlyDeleted($onlyDeleted)
            ->search($search)
            ->sort($sortBy, $sortDir)
            ->get();

        return ApiResponse::success(

            new LocationResourceCollection($locations),

            $locations->isEmpty()
                ? 'Data tidak ditemukan'
                : 'Success'
        );
    }

    /**
     * Detail Location by ID
     */
    public function showById(int $id)
    {
        return $this->findLocation(
            'id',
            $id
        );
    }

    /**
     * Detail Location by Code
     */
    public function showByCode(string $locationCode)
    {
        return $this->findLocation(
            'location_code',
            $locationCode
        );
    }

    /**
     * Reusable Finder
     */
    private function findLocation(
        string $field,
        string|int $value
    ) {
        $location = $this->MasterLocations
            ->where($field, $value)
            ->first();

        if (!$location) {

            return ApiResponse::error(
                'Location tidak ditemukan.',
                404
            );

        }

        return ApiResponse::success(

            new LocationDetailResource(
                $location
            ),

            'Success'

        );
    }

  
   /**
 * Dashboard Location
 */
public function dashboard()
{
    $summary = [

        // Total
        'total_locations' => MasterLocations::count(),

        // Status
        'active_locations' => MasterLocations::where('is_active', true)->count(),

        'inactive_locations' => MasterLocations::where('is_active', false)->count(),

        // Jenis lokasi
        'warehouse' => MasterLocations::where('is_warehouse', true)->count(),

        'pos_outlet' => MasterLocations::where('is_pos_outlet', true)->count(),

        'multi_origin' => MasterLocations::where('is_multi_origin', true)->count(),

        // Sinkronisasi Jubelio
        'sync_success' => MasterLocations::where(
            'sync_from_jubelio',
            true
        )->count(),

        'sync_failed' => MasterLocations::whereNotNull(
            'sync_from_jubelio_error'
        )->count(),

    ];

    return ApiResponse::success([
        'summary' => $summary,
    ], 'Success');
}
}