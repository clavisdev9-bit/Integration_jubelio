<?php

namespace App\Http\Controllers\Api\Jubelio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\JubelioService;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

class JubelioProductController extends Controller
{
    #[ExcludeRouteFromDocs]
    public function index(JubelioService $jubelio)
    {
        $response = $jubelio
            ->client()
            ->get(
                'https://open.jubelio.com/core-api/inventory/v2/items/masters/',
                [
                    'page' => 1,
                    'page_size' => 25,
                    'sort_by' => 'last_modified',
                    'sort_direction' => 'DESC',
                    'bundle_filter' => 1,
                ]
            );

        // jika token expired
        if ($response->status() === 401) {

            $jubelio->refreshToken();

            $response = $jubelio
                ->client()
                ->get(
                    'https://open.jubelio.com/core-api/inventory/v2/items/masters/',
                    [
                        'page' => 1,
                        'page_size' => 25,
                        'sort_by' => 'last_modified',
                        'sort_direction' => 'DESC',
                        'bundle_filter' => 1,
                    ]
                );
        }

        return response()->json([
            'success' => true,
            'message' => 'Success get products',
            'data' => $response->json(),
        ]);
    }
}
