<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiAnalysisService
{
    private $httpClient;
    private $apiToken; // Hugging Face API Token (optional for limited usage)

    public function __construct(HttpClientInterface $httpClient, string $apiToken = null)
    {
        $this->httpClient = $httpClient;
        $this->apiToken = $apiToken;
    }

    /**
     * Analyzes a cover letter using AI (Hugging Face).
     * Provides a summary and a professionalism score.
     */
    public function analyzeCoverLetter(string $text): array
    {
        if (strlen($text) < 50) {
            return [
                'summary' => 'The cover letter is too short for a detailed AI analysis.',
                'score' => 0.2,
                'feedback' => 'Consider adding more details about your skills and experience.'
            ];
        }

        try {
            // Using a sentiment/classification model as a proxy for "Professionalism" or "Impact"
            // Model: distilbert-base-uncased-finetuned-sst-2-english
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/distilbert-base-uncased-finetuned-sst-2-english', [
                'headers' => [
                    'Authorization' => $this->apiToken ? 'Bearer ' . $this->apiToken : '',
                ],
                'json' => ['inputs' => $text],
            ]);

            $result = $response->toArray();
            
            // Result is usually an array of arrays [[['label' => 'POSITIVE', 'score' => 0.99]]]
            $positiveScore = 0.5;
            if (isset($result[0])) {
                foreach ($result[0] as $classification) {
                    if ($classification['label'] === 'POSITIVE') {
                        $positiveScore = $classification['score'];
                    }
                }
            }

            return [
                'summary' => 'AI detected a generally ' . ($positiveScore > 0.6 ? 'professional and positive' : 'neutral') . ' tone.',
                'score' => $positiveScore,
                'feedback' => $this->generateFeedback($positiveScore)
            ];
        } catch (\Exception $e) {
            return $this->mockAiAnalysis($text);
        }
    }

    private function generateFeedback(float $score): string
    {
        if ($score > 0.8) return "Your cover letter is very persuasive and professional. Great job!";
        if ($score > 0.5) return "Good cover letter, but could be more impactful with specific examples of your past work.";
        return "The tone seems a bit weak. Try using stronger action verbs and highlighting your unique value proposition.";
    }

    private function mockAiAnalysis(string $text): array
    {
        $score = min(1.0, strlen($text) / 500); // Higher score for longer text relative to a baseline
        return [
            'summary' => '[MOCK AI] Analysis complete. Text length: ' . strlen($text) . ' chars.',
            'score' => $score,
            'feedback' => $this->generateFeedback($score)
        ];
    }
}
