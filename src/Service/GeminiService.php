<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GeminiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct(
        HttpClientInterface $httpClient,
        #[Autowire(env: 'GEMINI_API_KEY')] string $apiKey,
        #[Autowire(env: 'GEMINI_BASE_URL')] string $baseUrl,
        #[Autowire(env: 'GEMINI_MODEL')] string $model
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->model = $model;
    }

    public function generateResponse(string $prompt): string
    {
        try {
            $url = sprintf('%s/models/%s:generateContent?key=%s', $this->baseUrl, $this->model, $this->apiKey);

            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();

            // Extract the text from Gemini's response structure
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            return "Je suis désolé, je n'ai pas pu générer de réponse pour le moment.";
        } catch (\Exception $e) {
            return "Une erreur est survenue lors de la communication avec l'IA : " . $e->getMessage();
        }
    }
}
