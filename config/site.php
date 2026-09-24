<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image conversions (WebP)
    |--------------------------------------------------------------------------
    |
    | auto: conversions run only when the GD or Imagick extension is loaded.
    | on:   always register conversions (uploads fail if no driver exists).
    | off:  never register conversions; the frontend uses original files.
    |
    */

    'image_conversions' => env('IMAGE_CONVERSIONS', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | The first language is the default: it lives at the root (/) and is the
    | fallback for empty translations. The others live under their prefix.
    |
    */

    'locales' => [
        'en' => ['prefix' => '', 'og' => 'en_US', 'native' => 'English'],
        'id' => ['prefix' => 'id', 'og' => 'id_ID', 'native' => 'Bahasa Indonesia'],
    ],

    // Order in the navbar language switch ("ID | EN").
    'locale_switch_order' => ['id', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Front page cache key
    |--------------------------------------------------------------------------
    */

    // Bumped when the cached front page data changes shape (v5: tracker card removed).
    'cache_key' => 'frontend.data.v5',

    /*
    |--------------------------------------------------------------------------
    | Initial WhatsApp number used by the settings seeder (optional)
    |--------------------------------------------------------------------------
    */

    'whatsapp_number' => env('WHATSAPP_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Seeded administrator (read from .env, never hardcoded)
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
