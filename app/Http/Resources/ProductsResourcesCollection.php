<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\CostumersResources;
use App\Http\Resources\ProductsResources;

class ProductsResourcesCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    // public function toArray(Request $request): array
    // {
    //     return [
    //         'data' => ProductsResources::collection($this->collection),
    //         'pagination' => [
    //             'total' => $this->total(),
    //             'per_page' => $this->perPage(),
    //             'current_page' => $this->currentPage(),
    //             'last_page' => $this->lastPage(),
    //             'next_page_url' => $this->nextPageUrl(),
    //             'prev_page_url' => $this->previousPageUrl(),
    //         ],
    //     ];
    // }
    
    public function toArray(Request $request): array
    {
        return [
            'data' => ProductsResources::collection($this->collection),
        ];
    }

}
