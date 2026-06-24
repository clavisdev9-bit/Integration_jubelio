<?php
// code servis ini hanya untuk komunikasi dengan API Jubelio, seperti login, 
//refresh token, dan request data  untuk di gunkakan di POSTMAN atau di tempat lain yang 
//butuh akses ke API Jubelio secara langsung
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class JubelioService
{
    protected string $authUrl;
    protected string $baseUrl;
    protected string $email;
    protected string $password;

    public function __construct()
    {
        $this->authUrl = env('JUBELIO_AUTH_URL');
        $this->baseUrl = env('JUBELIO_BASE_URL');

        $this->email = env('JUBELIO_EMAIL');
        $this->password = env('JUBELIO_PASSWORD');
    }

    /**
     * Login Jubelio dan ambil token
     */
    protected function login(): string
    {
        $response = Http::withoutVerifying()
            ->acceptJson()
            ->post($this->authUrl, [
                'email' => $this->email,
                'password' => $this->password,
            ]);

        if (!$response->successful()) {

            throw new \Exception(
                'Failed login Jubelio : ' . $response->body()
            );
        }

        return $response->json('token');
    }

    /**
     * Cache token
     */
    public function token(): string
    {
        return Cache::remember(
            'jubelio_token',
            now()->addMinutes(50),
            fn () => $this->login()
        );
    }

    /**
     * Refresh token
     */
    public function refreshToken(): string
    {
        Cache::forget('jubelio_token');

        return $this->token();
    }

    /**
     * HTTP Client dengan auth
     */
    public function client()
    {
        return Http::withoutVerifying()
            ->withHeaders([
                'Authorization' => $this->token(),
                'Accept' => 'application/json',
            ]);
    }

    /**
     * Base URL helper
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }
}