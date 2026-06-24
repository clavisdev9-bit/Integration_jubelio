<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OdooService
{
    protected string $url;
    protected string $db;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->url = env('ODOO_URL');
        $this->db = env('ODOO_DB');
        $this->username = env('ODOO_USERNAME');
        $this->password = env('ODOO_PASSWORD');
    }

    /**
     * Login Odoo dan ambil UID
     */
    public function uid()
    {
        return Cache::remember(
            'odoo_uid',
            now()->addHours(12),
            function () {

                $response = Http::withoutVerifying()
                    ->acceptJson()
                    ->post(
                        $this->url . '/jsonrpc',
                        [
                            'jsonrpc' => '2.0',
                            'method' => 'call',
                            'params' => [
                                'service' => 'common',
                                'method' => 'login',
                                'args' => [
                                    $this->db,
                                    $this->username,
                                    $this->password,
                                ],
                            ],
                            'id' => rand(1, 999999),
                        ]
                    );

                if (!$response->successful()) {

                    throw new \Exception(
                        'Failed login Odoo : ' .
                        $response->body()
                    );
                }

                return $response->json('result');
            }
        );
    }

    /**
     * Generic execute_kw
     */
    public function execute(
        string $model,
        string $method,
        array $args = [],
        array $kwargs = []
    ) {

        $response = Http::withoutVerifying()
            ->acceptJson()
            ->post(
                $this->url . '/jsonrpc',
                [
                    'jsonrpc' => '2.0',
                    'method' => 'call',
                    'params' => [
                        'service' => 'object',
                        'method' => 'execute_kw',
                        'args' => [
                            $this->db,
                            $this->uid(),
                            $this->password,
                            $model,
                            $method,
                            $args,
                            $kwargs,
                        ],
                    ],
                    'id' => rand(1, 999999),
                ]
            );

        if (!$response->successful()) {

            throw new \Exception(
                'Odoo API Error : ' .
                $response->body()
            );
        }

        return $response->json('result');
    }
}