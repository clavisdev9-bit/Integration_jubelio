<?php

namespace App\Services\Jubelio;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class JubelioClient
{
    protected string $authUrl;
    protected string $baseUrl;
    protected string $email;
    protected string $password;

    public function __construct()
    {
        $this->authUrl  = env('JUBELIO_AUTH_URL');
        $this->baseUrl  = env('JUBELIO_BASE_URL');
        $this->email    = env('JUBELIO_EMAIL');
        $this->password = env('JUBELIO_PASSWORD');
    }

    protected function login(): string
    {
        $response = Http::withoutVerifying()
            ->acceptJson()
            ->post($this->authUrl, [
                'email'    => $this->email,
                'password' => $this->password,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed login Jubelio: ' . $response->body());
        }

        return $response->json('token');
    }

    public function token(): string
    {
        return Cache::remember(
            'jubelio_token',
            now()->addMinutes(50),
            fn () => $this->login()
        );
    }

    public function refreshToken(): string
    {
        Cache::forget('jubelio_token');
        return $this->token();
    }

    public function client()
{
    $token = $this->token(); // ambil dulu, simpan ke variable

    return Http::withoutVerifying()
        ->withHeaders([
            'Authorization' => $token,
            'Accept'        => 'application/json',
        ]);
}

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * GET request dengan auto-refresh jika token expired (401)
     */
    // public function get(string $endpoint, array $query = []): array
    // {
    //     $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

    //     $response = $this->client()->get($url, $query);

    //     if ($response->status() === 401) {
    //         $this->refreshToken();
    //         $response = $this->client()->get($url, $query);
    //     }

    //     if (! $response->successful()) {
    //         throw new \Exception("Jubelio GET {$endpoint} failed [{$response->status()}]: {$response->body()}");
    //     }

    //     return $response->json();
    // }
//     public function get(string $endpoint, array $query = []): array
// {
//     $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/') . '/';

//     $response = $this->client()->get($url, $query);

//     if ($response->status() === 401) {
//         $this->refreshToken();
//         $response = $this->client()->get($url, $query);
//     }

//     if (! $response->successful()) {
//         throw new \Exception("Jubelio GET {$endpoint} failed [{$response->status()}]: {$response->body()}");
//     }

//     return $response->json();
// }



public function get(string $endpoint, array $query = []): array
{
    $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

    $response = $this->client()->get($url, $query);

    if ($response->status() === 401) {
        Cache::forget('jubelio_token'); // paksa refresh
        $response = $this->client()->get($url, $query);
    }

    if (! $response->successful()) {
        throw new \Exception("Jubelio GET {$endpoint} failed [{$response->status()}]: {$response->body()}");
    }

    return $response->json();
}
}