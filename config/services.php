<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Google Sheet for new leads (form Kontak), through an Apps Script web app: docs/google-sheet-leads.md.
    | Empty URL = off. The secret must match SECRET in the Apps Script.
    */
    'google_sheets' => [
        'url' => env('GOOGLE_SHEETS_WEBHOOK_URL'),
        'secret' => env('GOOGLE_SHEETS_SECRET'),
    ],

    /*
    | Drafts of translations in the admin ("Buat draf terjemahan"). Driver: deepl
    | or google; empty = the button is hidden. TRANSLATION_API_URL is optional
    | (e.g. a DeepL Pro endpoint or a proxy).
    */
    'translation' => [
        'driver' => env('TRANSLATION_DRIVER'),
        'key' => env('TRANSLATION_API_KEY'),
        'url' => env('TRANSLATION_API_URL'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
