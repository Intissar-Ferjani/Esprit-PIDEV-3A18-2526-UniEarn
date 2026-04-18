<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AISuggestionService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function suggestTasks(string $projectTitle, string $projectDescription): array
    {
        $prompt = <<<PROMPT
You are an expert project manager. Based on this project title and description, suggest 3 to 5 logical sub-tasks for a freelancer to complete.
Ensure good handling of logical deadlines for each task, and assign a priority (High, Medium, Low).
Project Title: {$projectTitle}
Project Description: {$projectDescription}

Output ONLY a valid JSON object with the {"tasks": []} structure exactly like this:
{
  "tasks": [
    {
      "title": "string",
      "description": "string (detail what needs to be done)",
      "priority": "High" | "Medium" | "Low",
      "deadlineDaysFromNow": number (integer)
    }
  ]
}
Do not include any other text, just the JSON payload.
PROMPT;

        try {
            $url = 'https://text.pollinations.ai/' . urlencode($prompt) . '?json=true';
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 20
            ]);

            $content = $response->getContent();
            $content = preg_replace('/```json|```/', '', $content);
            $data = json_decode(trim($content), true);
            
            if (isset($data['tasks']) && is_array($data['tasks'])) {
                return $data['tasks'];
            } elseif (is_array($data) && isset($data[0]['title'])) {
                return $data;
            }
        } catch (\Exception $e) {
            // Request failed or parsing failed
        }

        return [];
    }
}
