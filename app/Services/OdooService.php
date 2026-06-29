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

                if (! $response->successful()) {
                    throw new \Exception(
                        'Failed login Odoo : ' . $response->body()
                    );
                }

                $json = $response->json();

                if (isset($json['error'])) {
                    throw new \Exception(
                        ($json['error']['message'] ?? 'Login Error') .
                        PHP_EOL .
                        ($json['error']['data']['message'] ?? '')
                    );
                }

                return $json['result'];
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

        // HTTP Error
        if (! $response->successful()) {
            throw new \Exception(
                'Odoo HTTP Error : ' . $response->body()
            );
        }

        $json = $response->json();

        // JSON-RPC Error dari Odoo
        if (isset($json['error'])) {

            $message = $json['error']['message'] ?? 'Unknown Odoo Error';

            if (! empty($json['error']['data']['message'])) {
                $message .= PHP_EOL . $json['error']['data']['message'];
            }

            if (! empty($json['error']['data']['debug'])) {
                $message .= PHP_EOL . PHP_EOL . $json['error']['data']['debug'];
            }

            throw new \Exception($message);
        }

        return $json['result'];
    }
}