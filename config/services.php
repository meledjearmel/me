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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * Base GeoLite2 (MaxMind) qui situe un téléchargement du CV (pays, ville).
     * La commande `geoip:update` la télécharge avec ces identifiants.
     */
    /*
     * Jeton GitHub facultatif pour `github:sync` : sans lui, l'API limite à 60 appels
     * par heure, largement assez pour une synchronisation horaire.
     */
    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],

    'maxmind' => [
        'account_id' => env('MAXMIND_ACCOUNT_ID'),
        'license_key' => env('MAXMIND_LICENSE_KEY'),
        'database' => env('MAXMIND_DATABASE', storage_path('app/geoip/GeoLite2-City.mmdb')),
    ],

];
