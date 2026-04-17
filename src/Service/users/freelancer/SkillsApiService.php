<?php

namespace App\Service\users\freelancer;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SkillsApiService
{
    private HttpClientInterface $httpClient;

    // Fallback list in case API is unavailable
    private const FALLBACK_SKILLS = [
        "Java", "Python", "JavaScript", "TypeScript", "C++", "C#",
        "React", "Angular", "Vue.js", "Node.js", "Spring Boot", "Django",
        "Flutter", "Android", "iOS", "MySQL", "PostgreSQL", "MongoDB",
        "Docker", "Kubernetes", "AWS", "Machine Learning", "Git", "Linux"
    ];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function fetchSkills(string $query): array
    {
        if (empty(trim($query))) {
            return $this->filterFallback('');
        }

        try {
            $url = "https://api.github.com/search/topics?q=" . urlencode($query) . "&per_page=30";

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/vnd.github.mercy-preview+json',
                    'User-Agent' => 'UniEarn-App'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return $this->parseSkills($response->toArray());
            } else {
                return $this->filterFallback($query);
            }
        } catch (\Throwable $e) {
            return $this->filterFallback($query);
        }
    }

    private function parseSkills(array $data): array
    {
        $skills = [];
        $items = $data['items'] ?? [];

        foreach ($items as $item) {
            $name = null;

            // Try display_name first, fall back to name
            if (!empty($item['display_name'])) {
                $name = $item['display_name'];
            } elseif (!empty($item['name'])) {
                $name = $item['name'];
            }

            if (!empty($name)) {
                $skills[] = ucfirst($name);
            }
        }
        return $skills;
    }

    private function filterFallback(string $query): array
    {
        $queryLower = strtolower($query);
        return array_values(array_filter(self::FALLBACK_SKILLS, function($s) use ($queryLower) {
            if ($queryLower === '') {
                return true;
            }
            return str_contains(strtolower($s), $queryLower);
        }));
    }
}
