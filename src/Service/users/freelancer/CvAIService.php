<?php

namespace App\Service\users\freelancer;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Smalot\PdfParser\Parser as PdfParser;

class CvAIService
{
    private const CHAT_URL   = 'https://openrouter.ai/api/v1/chat/completions';
    private const MODELS_URL = 'https://openrouter.ai/api/v1/models';
    private const EMERGENCY_FALLBACKS = [
        'meta-llama/llama-3.3-70b-instruct:free',
        'nousresearch/hermes-3-llama-3.1-405b:free',
        'qwen/qwen-2.5-coder-32b-instruct:free',
        'google/gemini-2.0-pro-exp-02-05:free',
        'nvidia/llama-3.1-nemotron-70b-instruct:free'
    ];
    private const MAX_MODELS_TO_TRY = 30;

    private string $apiKey;
    private string $executablePath;
    private string $gsPath;
    private string $tessdataPath;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ) {
        $this->apiKey         = $params->get('openrouter_api_key');
        $this->executablePath = $params->get('tesseract_executable_path');
        $this->gsPath         = $params->get('ghostscript_executable_path');
        $this->tessdataPath   = $params->get('tessdata_path');
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * @return array{success: bool, bio?: string, failReason?: string, failMessage?: string}
     */
    public function generateBioFromCv(string $filePath, ?string $registeredName): array
    {
        @set_time_limit(60); // Allow more time for multiple AI retries
        error_log("=== CvAIService: Processing CV: " . basename($filePath) . " ===");

        $cvText = $this->extractText($filePath);

        if (!$cvText || trim($cvText) === '') {
            error_log("CvAIService: No text extracted from CV.");
            return $this->failure('EXTRACTION_FAILED',
                'Could not read your CV. Please ensure it is a valid PDF or a clear image.');
        }

        $previewText = mb_substr(trim($cvText), 0, 200);
        error_log("CvAIService: Extracted " . strlen($cvText) . " chars. Preview: [$previewText...]");

        // Name verification (same logic as Java)
        if ($registeredName && trim($registeredName) !== '') {
            if (!$this->doesNameAppearInCv($cvText, $registeredName)) {
                error_log("CvAIService: Name mismatch for registered name: $registeredName");
                return $this->failure('NAME_MISMATCH',
                    "The name on your CV doesn't match your registered name ($registeredName). Please upload your own CV.");
            }
            error_log("CvAIService: Name verified in CV.");
        }

        // Truncate to 3000 chars like Java
        $trimmedText = mb_strlen($cvText) > 3000
            ? mb_substr($cvText, 0, 3000) . '...'
            : $cvText;

        $bio = $this->callWithFallback($trimmedText);

        if (!$bio) {
            return $this->failure('API_FAILED',
                'Could not generate bio. Please write it manually.');
        }

        return ['success' => true, 'bio' => $bio];
    }

    // ── Text Extraction ────────────────────────────────────────────────────────

    private function extractText(string $filePath): ?string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            // Step 1: Try native PDF text extraction (for text-based PDFs)
            $text = $this->extractPdfNative($filePath);
            if ($text && strlen(trim($text)) > 50) {
                error_log("CvAIService: Native PDF extraction succeeded (" . strlen($text) . " chars).");
                return $text;
            }

            // Step 2: Fall back to Ghostscript + Tesseract OCR (for image-based PDFs)
            error_log("CvAIService: Native extraction got little/no text, trying Ghostscript OCR...");
            return $this->extractPdfWithOcr($filePath);
        }

        if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            return $this->extractWithTesseract($filePath);
        }

        error_log("CvAIService: Unsupported file type: $ext");
        return null;
    }

    private function extractPdfNative(string $filePath): ?string
    {
        try {
            $parser   = new PdfParser();
            $pdf      = $parser->parseFile($filePath);
            $text     = $pdf->getText();

            return $text ?: null;
        } catch (\Throwable $e) {
            error_log("CvAIService: PdfParser error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Converts PDF pages to images using Ghostscript, then OCRs them with Tesseract.
     */
    private function extractPdfWithOcr(string $filePath): ?string
    {
        $tempPrefix = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cv_ocr_' . uniqid();
        $outputPattern = $tempPrefix . '_%d.png';
        
        // Command to convert first 2 pages of PDF to high-res PNGs
        // -dFirstPage=1 -dLastPage=2 to keep it fast
        $gsCmd = sprintf(
            '"%s" -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=2 -sOutputFile="%s" "%s" 2>&1',
            $this->gsPath,
            $outputPattern,
            $filePath
        );

        error_log("CvAIService: Running GS: $gsCmd");
        exec($gsCmd, $output, $returnVar);

        if ($returnVar !== 0) {
            error_log("CvAIService: Ghostscript failed with code $returnVar. Output: " . implode("\n", $output));
            return null;
        }

        // Find generated images and OCR them
        $generatedImages = glob($tempPrefix . '_*.png');
        $fullText = "";

        foreach ($generatedImages as $imagePath) {
            error_log("CvAIService: OCRing page image: $imagePath");
            $pageText = $this->extractWithTesseract($imagePath);
            if ($pageText) {
                $fullText .= $pageText . "\n\n";
            }
            @unlink($imagePath); // Cleanup image immediately
        }

        return $fullText !== "" ? $fullText : null;
    }

    private function extractWithTesseract(string $filePath): ?string
    {
        try {
            $tesseract = new TesseractOCR($filePath);
            $tesseract->executable($this->executablePath);
            $tesseract->tessdataDir($this->tessdataPath);
            $tesseract->lang('fra', 'eng', 'ara');
            $tesseract->psm(3);
            $tesseract->oem(1);
            $tesseract->userDefinedDpi(300);

            $result = $tesseract->run();
            error_log("CvAIService: Tesseract extracted " . strlen($result ?? '') . " chars.");
            return $result ?: null;
        } catch (\Throwable $e) {
            error_log("CvAIService: Tesseract error: " . $e->getMessage());
            return null;
        }
    }

    // ── Name Matching ──────────────────────────────────────────────────────────

    private function doesNameAppearInCv(string $cvText, string $registeredName): bool
    {
        $normalizedCv   = $this->normalize($cvText);
        $normalizedName = trim($this->normalize($registeredName));

        if (str_contains($normalizedCv, $normalizedName)) {
            error_log("CvAIService: Full name matched: $normalizedName");
            return true;
        }

        $parts      = preg_split('/\s+/', $normalizedName, -1, PREG_SPLIT_NO_EMPTY);
        $matchCount = 0;

        foreach ($parts as $part) {
            if (strlen($part) > 2 && str_contains($normalizedCv, $part)) {
                $matchCount++;
            }
        }

        $required     = (int) ceil(count($parts) / 2.0);
        $partialMatch = $matchCount >= $required;

        error_log("CvAIService: Name parts matched $matchCount/" . count($parts) . " (required: $required)");
        return $partialMatch;
    }

    private function normalize(string $text): string
    {
        // Uppercase (MB-safe)
        $text = mb_strtoupper($text, 'UTF-8');

        // Remove accents (French-safe)
        $search  = ['À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý'];
        $replace = ['A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','Y'];
        $text    = str_replace($search, $replace, $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    // ── OpenRouter API ─────────────────────────────────────────────────────────

    private function callWithFallback(string $cvText): ?string
    {
        $models = $this->fetchFreeModelIds();

        foreach ($models as $i => $model) {
            error_log("CvAIService: Trying model: $model (" . ($i + 1) . "/" . count($models) . ")");
            try {
                $response = $this->sendRequest($model, $cvText);
                $status   = $response['status'];

                if ($status === 200) {
                    error_log("CvAIService: RAW Response from $model: " . mb_substr($response['body'], 0, 1000));
                    $bio = $this->parseResponse($response['body']);
                    if ($bio && trim($bio) !== '') {
                        return $bio;
                    }
                    error_log("CvAIService: Bio rejected or empty from $model, trying next...");
                    continue;
                }

                if ($status === 429) {
                    error_log("CvAIService: 429 on $model, trying next...");
                    continue;
                }

                error_log("CvAIService: Error $status on $model — trying next...");
            } catch (\Throwable $e) {
                error_log("CvAIService: Request failed for $model: " . $e->getMessage());
            }
        }

        error_log("CvAIService: All models failed.");
        return null;
    }

    private function fetchFreeModelIds(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::MODELS_URL, [
                'headers' => ['Authorization' => 'Bearer ' . $this->apiKey],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) {
                error_log("CvAIService: Models API returned " . $response->getStatusCode() . " — using fallbacks");
                return self::EMERGENCY_FALLBACKS;
            }

            $body     = $response->getContent();
            $freeIds  = [];
            $entries  = explode('"id":"', $body);

            for ($i = 1; $i < count($entries); $i++) {
                $chunk    = $entries[$i];
                $endQuote = strpos($chunk, '"');
                if ($endQuote === false) continue;
                $modelId  = substr($chunk, 0, $endQuote);

                if (!str_ends_with($modelId, ':free')) continue;
                if (str_contains($modelId, 'thinking') || str_contains($modelId, 'reasoner')) continue;

                $freeIds[] = $modelId;
            }

            if (empty($freeIds)) {
                return self::EMERGENCY_FALLBACKS;
            }

            // Shuffle well and prioritize high quality ones over random small models if possible
            // But shuffling is simple and avoids hitting the same downed server
            shuffle($freeIds);
            
            // Limit to MAX_MODELS_TO_TRY
            $freeIds = array_slice($freeIds, 0, self::MAX_MODELS_TO_TRY);

            error_log("CvAIService: Fetched " . count($freeIds) . " free model(s).");
            return $freeIds;

        } catch (\Throwable $e) {
            error_log("CvAIService: Could not fetch models: " . $e->getMessage() . " — using fallbacks");
            return self::EMERGENCY_FALLBACKS;
        }
    }

    private function sendRequest(string $model, string $cvText): array
    {
        $prompt = "Based on the following CV content, write a professional freelancer bio in 2-3 sentences "
            . "(between 80 and 300 characters). "
            . "Requirements:\n"
            . "- CRITICAL: Detect the language of the CV and write the bio in that EXACT same language. "
            . "If the CV is in French, respond in French. If in English, respond in English. "
            . "If in Arabic, respond in Arabic. Match the language precisely.\n"
            . "- Written in first person\n"
            . "- Highlights the most relevant skills and experience\n"
            . "- Sounds professional but approachable\n"
            . "- Ready to paste directly into a profile, no intro like 'Here is your bio:'\n\n"
            . "CV Content:\n" . $cvText;

        $body = json_encode([
            'model'       => $model,
            'messages'    => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens'  => 300,
            'temperature' => 0.7, // Matching Java
        ]);

        $response = $this->httpClient->request('POST', self::CHAT_URL, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer'  => 'https://uniearn.app',
                'X-Title'       => 'UniEarn Professional Bio Generator',
            ],
            'body'    => $body,
            'timeout' => 15, // Lower timeout to move quickly between models
        ]);

        return [
            'status' => $response->getStatusCode(),
            'body'   => $response->getContent(false),
        ];
    }

    private function parseResponse(string $json): ?string
    {
        try {
            $data = json_decode($json, true);

            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content || trim($content) === '') {
                error_log("CvAIService: Empty content in response.");
                return null;
            }

            // Cleanup: remove potential <thought> tags
            $content = preg_replace('/<thought>.*?<\/thought>/is', '', $content);
            $content = trim($content);

            // Reasoning detection: Reject if it contains common meta-talk markers
            $metaMarkers = ['let\'s count', 'character count', 'cv is french', 'cv is english', 'we need to produce', 'i will write', 'count manually', 'not liable', 'consequences arising'];
            $lowContent  = mb_strtolower($content);
            foreach ($metaMarkers as $marker) {
                if (str_contains($lowContent, $marker)) {
                    error_log("CvAIService: Output contains reasoning/meta-talk/refusal: [$marker]. Rejecting.");
                    return null;
                }
            }

            // Sanity check: length
            if (mb_strlen($content) < 80) {
                error_log("CvAIService: Bio too short (" . mb_strlen($content) . " chars). Rejecting.");
                return null;
            }

            if (mb_strlen($content) > 600) {
                error_log("CvAIService: Bio too long (" . mb_strlen($content) . " chars), likely contains reasoning. Rejecting.");
                return null;
            }

            error_log("CvAIService: Bio generated successfully and validated.");
            return $content;
        } catch (\Throwable $e) {
            error_log("CvAIService: Failed to parse response: " . $e->getMessage());
            return null;
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function failure(string $reason, string $message): array
    {
        return ['success' => false, 'failReason' => $reason, 'failMessage' => $message];
    }
}
