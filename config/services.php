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

    'transcribe' => [
        'url' => env('TRANSCRIBE_SERVICE_URL', 'http://host.docker.internal:8001'),
    ],

    'audio_analysis' => [
        'url' => env('AUDIO_ANALYSIS_SERVICE_URL', 'http://host.docker.internal:8002'),
        'file_field' => env('AUDIO_ANALYSIS_FILE_FIELD', 'file'),
    ],

    'whisper' => [
        'url' => env('WHISPER_URL', 'http://localhost:9001'),
        'model' => env('WHISPER_MODEL', 'small'),
        'language' => env('WHISPER_LANGUAGE', 'ru'),
        'timeout' => (int) env('WHISPER_TIMEOUT', 90),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://192.168.0.52:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:7b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 180),
    ],

    'llama_vision' => [
        'url' => env('LLAMA_VISION_URL', 'http://192.168.0.52:11434'),
        'model' => env('LLAMA_VISION_MODEL', 'llama3.2-vision'),
        'timeout' => (int) env('LLAMA_VISION_TIMEOUT', 180),
    ],
];
