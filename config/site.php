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
    | favicon.ico
    |--------------------------------------------------------------------------
    |
    | Rebuilt (16/32/48 px) every time a favicon is uploaded in the admin.
    | Empty: public/favicon.ico. The tests point it elsewhere.
    |
    */

    'favicon_ico_path' => env('FAVICON_ICO_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | The first language is the default: it lives at the root (/) and is the
    | fallback for empty translations. The others live under their prefix.
    | segments: translated first URL segment of a page type (/services/..., /id/layanan/...).
    |
    */

    'locales' => [
        'en' => ['prefix' => '', 'og' => 'en_US', 'native' => 'English', 'segments' => ['services' => 'services']],
        'id' => ['prefix' => 'id', 'og' => 'id_ID', 'native' => 'Bahasa Indonesia', 'segments' => ['services' => 'layanan']],
    ],

    // Order in the navbar language switch ("ID | EN").
    'locale_switch_order' => ['id', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Front page cache key
    |--------------------------------------------------------------------------
    */

    // Bumped when the cached front page data changes shape (v6: service cards).
    'cache_key' => 'frontend.data.v6',

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
