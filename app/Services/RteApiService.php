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
        if ($this->token) {
            return; // Already authenticated
        }

        // Check if token is cached
        if (config('services.rte.cache_token', true) && cache()->has('rte_api_token')) {
            $this->token = cache()->get('rte_api_token');
            return; // Use cached token
        }

        // Authenticate with RTE API
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post("$this->baseUrl/token/oauth/");

        // Check for successful response
        if ($response->failed()) {
            throw new \Exception('Failed to authenticate with RTE API: ' . $response->body());
        }

        // Parse the response to get the access token
        $response = $response->json();
        if (!isset($response['access_token'])) {
            throw new \Exception('Access token not found in RTE API response.');
        }

        // Store the token for future use
        $this->token = $response['access_token'];

        if (config('services.rte.cache_token', false)) {
            // Store the token in cache for 30 minutes
            cache()->put('rte_api_token', $this->token, now()->addMinutes(30));
            
        }
    }

    public function fetchGenerationPerUnit(?Carbon $start = null, ?Carbon $end = null): ?array
    {
        // Ensure we have a valid token
        if (!$this->token) {
            $this->authenticate();
        }

        $url = "$this->baseUrl/open_api/actual_generation/v1/actual_generations_per_unit";

        // Append start and end dates if provided
        if ($start && $end) {
            $url .= '?' . http_build_query([
                'start_date' => $start->format(DATE_ATOM),
                'end_date' => $end->format(DATE_ATOM),
            ]);
        }

        // Make the API request
        $response = Http::withToken($this->token)->get($url);

        // Check for successful response
        if ($response->failed()) {
            throw new \Exception('Failed to fetch data from RTE API (' . $url . ', token ' . $this->token . '): Code ' . $response->status() . ' - ' . $response->body());
        }

        // Parse and return the data
        return $response->json('actual_generations_per_unit');
    }

    public function fetchGenerationForUnit(string $eicCode, ?Carbon $date = null): ?array
    {
        if (!$date) {
            $date = now()->subDays(6);
        }

        $url = "https://www.services-rte.com/cms/open_data/v1/actual_generation_by_group?date={$date->format('d/m/Y')}&unit_eic_code={$eicCode}";

        $response = Http::get($url);
        if ($response->failed()) {
            throw new \Exception('Failed to fetch data from RTE API: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data)) {
            return null;
        }

        return $data['values'] ?? null;
    }
}