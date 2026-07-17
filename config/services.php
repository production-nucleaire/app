<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Bluesky (AT Protocol) posting for the live-production share bot. Use an
    // app-password (Settings → App Passwords), never the account password.
    'bluesky' => [
        'identifier' => env('BLUESKY_IDENTIFIER'),
        'password' => env('BLUESKY_PASSWORD'),
        'base_url' => env('BLUESKY_BASE_URL', 'https://bsky.social'),
    ],

    'rte' => [
        'cache_token' => env('RTE_CACHE_TOKEN', false),
        'client_id' => env('RTE_CLIENT_ID'),
        'client_secret' => env('RTE_CLIENT_SECRET'),
        'base_url' => env('RTE_API_BASE_URL', 'https://digital.iservices.rte-france.com'),
    ],

    // Headless-Chrome rendering (Spatie Browsershot) used to rasterise the
    // Open Graph share images. The scheduled prod import runs with a minimal
    // PATH, so the node/Chrome binaries usually have to be set explicitly.
    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        'node_module_path' => env('BROWSERSHOT_NODE_MODULE_PATH', base_path('node_modules')),
        'chromium_arguments' => env('BROWSERSHOT_CHROMIUM_ARGUMENTS', []),
    ],

];
