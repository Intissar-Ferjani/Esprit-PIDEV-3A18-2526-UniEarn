<?php

namespace App\Service\users\user;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Filesystem;

class AvatarService
{
    private HttpClientInterface $httpClient;
    private string $profilesDirectory;

    public function __construct(HttpClientInterface $httpClient, string $profilesDirectory)
    {
        $this->httpClient = $httpClient;
        $this->profilesDirectory = $profilesDirectory;
    }

    /**
     * Downloads a Google avatar and returns the relative path from public/
     */
    public function downloadGoogleAvatar(string $pictureUrl, string $email): ?string
    {
        if (empty($pictureUrl)) {
            return null;
        }

        try {
            // Request a larger size (256px)
            $sizedUrl = str_contains($pictureUrl, '=s')
                ? preg_replace('/=s\d+/', '=s256', $pictureUrl)
                : $pictureUrl . '=s256';

            $response = $this->httpClient->request('GET', $sizedUrl);
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $fs = new Filesystem();
            if (!$fs->exists($this->profilesDirectory)) {
                $fs->mkdir($this->profilesDirectory);
            }

            $safeEmail = preg_replace('/[^a-zA-Z0-9]/', '_', $email);
            $newFilename = 'google_' . $safeEmail . '.jpg';
            $fullPath = $this->profilesDirectory . '/' . $newFilename;

            file_put_contents($fullPath, $response->getContent());

            return 'uploads/profiles/' . $newFilename;

        } catch (\Exception $e) {
            // Log error or ignore
            return null;
        }
    }
}
