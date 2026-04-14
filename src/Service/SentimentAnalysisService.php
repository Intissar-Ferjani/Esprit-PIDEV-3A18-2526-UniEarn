<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SentimentAnalysisService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Analyzes sentiment of a text.
     * Returns an array with 'label' (pos, neg, neutral) and 'score'.
     */
    public function analyze(string $text): array
    {
        if (empty(trim($text))) {
            return ['label' => 'neutral', 'score' => 0.5];
        }

        try {
            $response = $this->httpClient->request('POST', 'http://text-processing.com/api/sentiment/', [
                'body' => [
                    'text' => $text,
                ],
            ]);

            $data = $response->toArray();
            
            // The API returns labels like 'pos', 'neg', 'neutral'
            $label = $data['label'] ?? 'neutral';
            $score = $data['probability'][$label] ?? 0.5;

            return [
                'label' => $label,
                'score' => $score,
            ];
        } catch (\Exception $e) {
            // Fallback for demo purposes if API is down
            return $this->mockAnalysis($text);
        }
    }

    private function mockAnalysis(string $text): array
    {
        $positiveWords = ['excellent', 'great', 'awesome', 'good', 'perfect', 'satisfied'];
        $negativeWords = ['bad', 'poor', 'terrible', 'worst', 'disappointed', 'late'];

        $lowerText = strtolower($text);
        $posCount = 0;
        $negCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($lowerText, $word)) $posCount++;
        }
        foreach ($negativeWords as $word) {
            if (str_contains($lowerText, $word)) $negCount++;
        }

        if ($posCount > $negCount) return ['label' => 'pos', 'score' => 0.8];
        if ($negCount > $posCount) return ['label' => 'neg', 'score' => 0.8];

        return ['label' => 'neutral', 'score' => 0.5];
    }
}
