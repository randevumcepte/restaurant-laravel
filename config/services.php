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

    // Derin AI Analizi (Haiku). Randevumcepte ile AYNI Anthropic anahtari/bakiyesi kullanilabilir.
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    // Google Cloud TTS (musteri QR asistani icin kaliteli ERKEK ses). Randevumcepte ile AYNI anahtar.
    // Erkek Turkce WaveNet: tr-TR-Wavenet-D (varsayilan). Anahtar yoksa taray. sesine duser.
    'google_tts' => [
        'key' => env('GOOGLE_TTS_API_KEY'),
        'voice' => env('GOOGLE_TTS_VOICE', 'tr-TR-Wavenet-D'),
        // SERT AYLIK KARAKTER LIMITI: asilinca Cloud durur, bedava cihaz sesine duser (fatura koruma).
        // 900000 = WaveNet ucretsiz kotasinin (1M) altinda guvenli tampon. Standard sesde 3800000 yapabilirsin.
        'aylik_limit' => (int) env('GOOGLE_TTS_AYLIK_LIMIT', 900000),
    ],

];
