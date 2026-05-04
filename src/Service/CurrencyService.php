<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyService
{
    private $httpClient;
    // Using a reliable public API: exchangerate-api.com
    private const PUBLIC_API = 'https://open.er-api.com/v6/latest/TND';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Converts a budget from TND to other common currencies.
     */
    public function convertFromTnd(float $amount): array
    {
        try {
            $response = $this->httpClient->request('GET', self::PUBLIC_API);
            $data = $response->toArray();

            $rates = $data['rates'] ?? [];
            return [
                'TND' => $amount,
                'USD' => isset($rates['USD']) ? round($amount * $rates['USD'], 2) : null,
                'EUR' => isset($rates['EUR']) ? round($amount * $rates['EUR'], 2) : null,
                'last_updated' => $data['time_last_update_utc'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            // Fallback rates if API is down
            return [
                'TND' => $amount,
                'USD' => round($amount * 0.32, 2),
                'EUR' => round($amount * 0.30, 2),
                'last_updated' => 'Fallback (Offline)'
            ];
        }
    }
}