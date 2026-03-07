<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private ?string $folderId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->folderId = config('google-drive.folder_id');
        $this->credentialsPath = config('google-drive.credentials_path');
    }

    /**
     * Check if Google Drive is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->folderId)
            && !empty($this->credentialsPath)
            && file_exists($this->credentialsPath);
    }

    /**
     * Upload a file to Google Drive.
     */
    public function upload(string $localPath, string $fileName): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Google Drive is not configured. Set GOOGLE_DRIVE_FOLDER_ID and place credentials.json in storage/app.');
        }

        $client = new \Google\Client();
        $client->setAuthConfig($this->credentialsPath);
        $client->addScope(\Google\Service\Drive::DRIVE_FILE);

        $service = new \Google\Service\Drive($client);

        $fileMetadata = new \Google\Service\Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$this->folderId],
        ]);

        // Check if file with same name exists → update instead of create
        $existing = $service->files->listFiles([
            'q' => "name='{$fileName}' and '{$this->folderId}' in parents and trashed=false",
            'spaces' => 'drive',
            'fields' => 'files(id)',
        ]);

        $content = file_get_contents($localPath);

        if (count($existing->getFiles()) > 0) {
            // Update existing
            $existingId = $existing->getFiles()[0]->getId();
            $service->files->update($existingId, new \Google\Service\Drive\DriveFile(), [
                'data' => $content,
                'mimeType' => 'application/json',
                'uploadType' => 'multipart',
            ]);
        } else {
            // Create new
            $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => 'application/json',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);
        }
    }

    /**
     * Download a file from Google Drive by name.
     */
    public function download(string $fileName): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $client = new \Google\Client();
        $client->setAuthConfig($this->credentialsPath);
        $client->addScope(\Google\Service\Drive::DRIVE_FILE);

        $service = new \Google\Service\Drive($client);

        $files = $service->files->listFiles([
            'q' => "name='{$fileName}' and '{$this->folderId}' in parents and trashed=false",
            'spaces' => 'drive',
            'fields' => 'files(id)',
        ]);

        if (count($files->getFiles()) === 0) {
            return null;
        }

        $fileId = $files->getFiles()[0]->getId();
        $response = $service->files->get($fileId, ['alt' => 'media']);

        return $response->getBody()->getContents();
    }
}
