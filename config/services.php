<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook Integration
    |--------------------------------------------------------------------------
    */
    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
        'verify_token' => env('FACEBOOK_VERIFY_TOKEN'),
        'graph_api_version' => env('FACEBOOK_GRAPH_API_VERSION', 'v18.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IndiaMART Integration
    |--------------------------------------------------------------------------
    */
    'indiamart' => [
        'default_fetch_window' => env('INDIAMART_DEFAULT_FETCH_WINDOW', 15),
        'scheduler_interval' => env('INDIAMART_SCHEDULER_INTERVAL', 5),
    ],

];
