<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Drive Backup Configuration
    |--------------------------------------------------------------------------
    |
    | To enable Google Drive backups:
    | 1. Create a Google Cloud project at https://console.cloud.google.com
    | 2. Enable Google Drive API
    | 3. Create a Service Account, download credentials JSON
    | 4. Place the JSON file at storage/app/google-credentials.json
    | 5. Create a folder in Google Drive and share it with the service account email
    | 6. Set GOOGLE_DRIVE_FOLDER_ID in your .env to the folder ID
    |
    */

    'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),

    'credentials_path' => storage_path('app/google-credentials.json'),
];
