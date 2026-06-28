<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesOrderResourcesCollection extends ResourceCollection
{
    /**
     * Create a new resource collection.
     */
    public function __construct($resource, array $summary = [])
    {
        parent::__construct($resource);

        $this->summary = $summary;
    }

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'summary' => $this->summary,

            'data' => SalesOrderResources::collection(
                $this->collection
            ),

        ];
    }
}