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

    /**
     * Reaches out to the Pollinations AI text API to automatically break down a project
     * description into smaller, manageable sub-tasks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function suggestTasks(string $projectTitle, string $projectDescription): array
    {
        // 1. Engineering the Prompt
        // We instruct the LLM to behave like a Project Manager and demand ONLY a JSON payload.
        // Interpolating the actual project title and description into the message.
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
            // 2. Making the HTTP Data Request
            // Calling a free/open language model endpoint (text.pollinations.ai).
            // We append "?json=true" and URL-encode the prompt so it parses cleanly over GET.
            $url = 'https://text.pollinations.ai/' . urlencode($prompt) . '?json=true';
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 20
            ]);

            $content = $response->getContent();
            
            // 3. Decoding and Cleaning the Response
            // The AI might incorrectly wrap the response in markdown blocks (e.g., ```json ... ```)
            // We use Regex to strip those out before parsing.
            $content = preg_replace('/```json|```/', '', $content);
            
            // Decode the stringified JSON into a searchable PHP associative array.
            $data = json_decode(trim((string) $content), true);
            
            // 4. Fallback Validations
            // Ensure the data didn't break and is exactly the format we requested.
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
