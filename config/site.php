<?php

return [

    'image_conversions' => env('IMAGE_CONVERSIONS', 'auto'),

    'favicon_ico_path' => env('FAVICON_ICO_PATH'),

    'locales' => [
        'en' => ['prefix' => '', 'og' => 'en_US', 'native' => 'English', 'segments' => ['services' => 'services', 'blog' => 'blog', 'contact' => 'contact']],
        'id' => ['prefix' => 'id', 'og' => 'id_ID', 'native' => 'Bahasa Indonesia', 'segments' => ['services' => 'layanan', 'blog' => 'blog', 'contact' => 'kontak']],
    ],

    'locale_switch_order' => ['id', 'en'],

    'cache_key' => 'frontend.data.v6',

    'whatsapp_number' => env('WHATSAPP_NUMBER'),

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
