<?php

namespace App\Service\users\freelancer;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StudentCardOCRService
{
    private string $executablePath;
    private string $tessdataPath;

    public function __construct(ParameterBagInterface $params)
    {
        $this->executablePath = $params->get('tesseract_executable_path');
        $this->tessdataPath   = $params->get('tessdata_path');
    }

    /**
     * Verifies a student card image against a registered name.
     * Ported from StudentCardOCRService.java
     */
    public function verifyStudentCard(string $imagePath, ?string $registeredName): array
    {
        error_log("=== Starting OCR Verification for Card: " . basename($imagePath) . " ===");
        
        $extractedText = $this->extractText($imagePath);

        if (!$extractedText || trim($extractedText) === '') {
            error_log("OCR Error: No text extracted or Tesseract failed.");
            return [
                'verified' => false,
                'message'  => 'Could not read text from the image. Please upload a clearer photo.',
                'rawText'  => $extractedText
            ];
        }

        error_log("Raw Extracted Text: \n" . $extractedText);

        // Normalize text: collapse whitespace and uppercase, but keep Unicode (Arabic) characters
        $normalizedText = $this->normalize($extractedText);
        error_log("Normalized Text: \n" . $normalizedText);

        $isStudentCard    = $this->isStudentCard($normalizedText, $extractedText);
        $currentYearValid = $this->isCurrentAcademicYearPresent($extractedText);
        $nameMatches      = $this->doesNameMatch($normalizedText, $registeredName);

        error_log("Validation Results - isStudentCard: " . ($isStudentCard ? 'YES' : 'NO') . 
                  ", yearValid: " . ($currentYearValid ? 'YES' : 'NO') . 
                  ", nameMatches: " . ($nameMatches ? 'YES' : 'NO'));

        if (!$isStudentCard) {
            return [
                'verified' => false,
                'message'  => 'This does not appear to be a student card. Please upload your official student card.',
                'rawText'  => $extractedText
            ];
        }

        if (!$currentYearValid) {
            $yearStr = $this->getCurrentAcademicYearString();
            return [
                'verified' => false,
                'message'  => "Your card must be valid for the current academic year ($yearStr). Please upload a current card.",
                'rawText'  => $extractedText
            ];
        }

        if (!$nameMatches) {
            return [
                'verified' => false,
                'message'  => "The name on the card does not match your registered name ($registeredName). Please upload your own student card.",
                'rawText'  => $extractedText
            ];
        }

        return [
            'verified' => true,
            'message'  => 'Student card verified — card, academic year, and name all confirmed.',
            'rawText'  => $extractedText
        ];
    }

    // ── Text extraction ────────────────────────────────────────────────────────

    private function extractText(string $imagePath): ?string
    {
        try {
            $tesseract = new TesseractOCR($imagePath);
            $tesseract->executable($this->executablePath);
            /** @phpstan-ignore-next-line */
            $tesseract->tessdataDir($this->tessdataPath);
            /** @phpstan-ignore-next-line */
            $tesseract->lang('fra', 'eng', 'ara');
            /** @phpstan-ignore-next-line */
            $tesseract->psm(3);
            /** @phpstan-ignore-next-line */
            $tesseract->oem(1);
            /** @phpstan-ignore-next-line */
            $tesseract->userDefinedDpi(300);

            return $tesseract->run();
        } catch (\Throwable $e) {
            error_log("Tesseract Execution Exception: " . $e->getMessage());
            return null;
        }
    }

    // ── Normalizer ─────────────────────────────────────────────────────────────

    private function normalize(string $text): string
    {
        if (!$text) return "";

        // 1. Convert to Uppercase (MB-safe)
        $text = mb_strtoupper($text, 'UTF-8');

        // 2. Collapse whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // 3. Remove common accents manually since intl might be missing
        // This keeps Arabic but cleans up French accents
        $search  = ['À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý','à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ð','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ'];
        $replace = ['A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','Y','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','o','o','o','o','o','o','u','u','u','u','y','y'];
        $text = str_replace($search, $replace, $text);

        return trim($text);
    }

    // ── Validator 1: Is it a student card? ────────────────────────────────────

    private function isStudentCard(string $normalizedText, string $rawText): bool
    {
        $keywords = [
            'CARTE ETUDIANT',
            'CARTE D ETUDIANT',
            "CARTE D'ETUDIANT",
            'ETUDIANT',
            'ANNEE UNIVERSITAIRE',
            'IDENTIFIANT',
            'HONORIS'
        ];

        foreach ($keywords as $kw) {
            if (str_contains($normalizedText, $kw)) {
                return true;
            }
        }

        // Arabic equivalents
        if (str_contains($rawText, 'بطاقة') || str_contains($rawText, 'طالب')) {
            return true;
        }

        return false;
    }

    // ── Validator 2: Current academic year ────────────────────────────────────

    private function isCurrentAcademicYearPresent(string $text): bool
    {
        $now = new \DateTime();
        $currentYear = (int)$now->format('Y');
        $academicYearStart = ((int)$now->format('n') >= 9) ? $currentYear : $currentYear - 1;
        $academicYearEnd   = $academicYearStart + 1;

        // Pattern for 20xx / 20xx
        $pattern = "/(20\d{2})[\/\s\\\\\-–]?(20\d{2})/";
        
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $y1 = (int)$m[1];
                $y2 = (int)$m[2];
                
                if (($y1 === $academicYearStart && $y2 === $academicYearEnd) ||
                    ($y1 === $academicYearEnd   && $y2 === $academicYearStart)) {
                    return true;
                }
            }
        }

        return false;
    }

    // ── Validator 3: Name match ────────────────────────────────────────────────

    private function doesNameMatch(string $normalizedText, ?string $registeredName): bool
    {
        if (!$registeredName) return true;

        $normalizedName = $this->normalize($registeredName);

        // Try full name match
        if (str_contains($normalizedText, $normalizedName)) {
            return true;
        }

        // Partial match: each word > 2 chars
        $parts = preg_split('/\s+/', $normalizedName, -1, PREG_SPLIT_NO_EMPTY);
        $matchCount = 0;
        $validParts = 0;

        foreach ($parts as $part) {
            if (strlen($part) > 2) {
                $validParts++;
                if (str_contains($normalizedText, $part)) {
                    $matchCount++;
                }
            }
        }

        if ($validParts === 0) return true;

        $required = (int) ceil($validParts / 2.0);
        return $matchCount >= $required;
    }

    private function getCurrentAcademicYearString(): string
    {
        $now = new \DateTime();
        $currentYear = (int)$now->format('Y');
        $start = ((int)$now->format('n') >= 9) ? $currentYear : $currentYear - 1;
        return $start . "-" . ($start + 1);
    }
}
