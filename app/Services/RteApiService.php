<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class RteApiService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('services.rte.base_url', 'https://digital.iservices.rte-france.com');
        $this->clientId = config('services.rte.client_id');
        $this->clientSecret = config('services.rte.client_secret');
    }

    public function authenticate()
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post("$this->baseUrl/token/oauth/");

        if ($response->failed()) {
            throw new \Exception('Failed to authenticate with RTE API: ' . $response->body());
        }

        $response = $response->json();
        if (!isset($response['access_token'])) {
            throw new \Exception('Access token not found in RTE API response.');
        }

        $this->token = $response['access_token'];
    }

    public function fetchGenerationPerUnit(?Carbon $start = null, ?Carbon $end = null): ?array
    {
        if (!$this->token) {
            $this->authenticate();
        }

        $url = "$this->baseUrl/open_api/actual_generation/v1/actual_generations_per_unit";

        if ($start && $end) {
            $url .= '?' . http_build_query([
                'start_date' => $start->format(DATE_ATOM),
                'end_date' => $end->format(DATE_ATOM),
            ]);
        }

        $response = Http::withToken($this->token)
            ->get($url);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch data from RTE API (' . $url . ', token ' . $this->token . '): Code ' . $response->status() . ' - ' . $response->body());
        }

        return $response->json('actual_generations_per_unit');
    }
}