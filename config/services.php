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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'ocr' => ['url' => env('OCR_API_URL', 'http://127.0.0.1:18001'), 'key' => env('OCR_API_KEY')],
    'tts' => [
        'enabled' => env('TTS_ENABLED', false),
        'engine' => env('TTS_ENGINE', 'openai'),
        'url' => env('TTS_API_URL', ''),
        'key' => env('TTS_API_KEY', ''),
        'model' => env('TTS_API_MODEL', ''),
        'voice' => env('TTS_API_VOICE', ''),
        'binary' => env('TTS_ESPEAK_BINARY', ''),
        'data_path' => env('TTS_ESPEAK_DATA_PATH', ''),
        'python' => env('TTS_PYTHON', ''),
        'model_path' => env('TTS_MMS_MODEL_PATH', ''),
    ],

    'learner_analysis' => [
        'enabled' => env('LEARNER_ANALYSIS_ENABLED', true),
        'provider' => env('LEARNER_ANALYSIS_PROVIDER', 'ollama'),
        'url' => env('LEARNER_ANALYSIS_URL', 'http://127.0.0.1:11434'),
        'model' => env('LEARNER_ANALYSIS_MODEL', ''),
        'key' => env('LEARNER_ANALYSIS_KEY', ''),
    ],

    'ai' => [
        'url' => env('AI_API_URL', 'http://127.0.0.1:20128/v1'),
        'key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'ag/gemini-3.8-flash-medium'),
    ],

];
