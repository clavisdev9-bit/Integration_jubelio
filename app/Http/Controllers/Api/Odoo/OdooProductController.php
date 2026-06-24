<?php

namespace App\Http\Controllers\Api\Odoo;

use App\Http\Controllers\Controller;
use App\Services\OdooService;

class OdooProductController extends Controller
{
    // public function index(OdooService $odoo)
    // {
    //     $products = $odoo->execute(
    //         'product.template',
    //         'search_read',
    //         [
    //             []
    //         ],
    //         [
    //             'fields' => [
    //                 'id',
    //                 'name',
    //                 'default_code',
    //                 'list_price',
    //             ],
    //             'limit' => 10,
    //         ]
    //     );

    //     return response()->json([
    //         'success' => true,
    //         'data' => $products,
    //     ]);
    // }

     public function index(OdooService $odoo)
    {
        $product = $odoo->execute(
    'product.template',
    'search_read',
    [
        []
    ],
    [
        'fields' => [
            'id',
            'name',
            'default_code',
            'list_price',
        ],
        'limit' => 20,
    ]
);

        

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }
}