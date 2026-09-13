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
        // MALIYET KONTROLU (Patron AI sohbet karakteri):
        'sohbet_acik' => (bool) env('PATRON_AI_SOHBET', true),        // 0 -> sohbet kapali, sadece bedava kural+tespit
        'sohbet_gunluk_limit' => (int) env('PATRON_AI_GUNLUK_LIMIT', 400), // gunluk LLM soru tavani (sube basi); onbellekli tekrarlar saymaz
    ],

    // SEF GARSON AI — garsonun gozu: acik masalari tarar, satis uyarilari uretir (BEDAVA kural motoru)
    // + "bu masaya ne satayim" derin oneride Haiku (anthropic anahtarini paylasir). Esikler sube icin ortak.
    'sefgarson' => [
        'acik'                 => (bool) env('SEF_GARSON_ACIK', true),           // 0 -> gozcu kapali
        'bos_masa_dk'          => (int) env('SEF_GARSON_BOS_MASA_DK', 12),       // acildi, siparis yok -> uyar
        'durgun_dk'            => (int) env('SEF_GARSON_DURGUN_DK', 18),         // son kalemden bu kadar dk gecti -> uyar
        'tatli_dk'             => (int) env('SEF_GARSON_TATLI_DK', 22),          // ana yemekten sonra tatli firsati
        'kalabalik_kisi'       => (int) env('SEF_GARSON_KALABALIK_KISI', 4),     // bu ve ustu -> paylasimlik oner
        'kapatma_cooldown_dk'  => (int) env('SEF_GARSON_KAPATMA_DK', 15),        // garson kapatinca kac dk sussun
        'gunluk_limit'         => (int) env('SEF_GARSON_GUNLUK_LIMIT', 200),     // derin oneri (Haiku) gunluk tavan (sube basi)
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
