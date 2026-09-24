<?php

/**
 * Stock of the original Ekasa content icons (resources/icons/ekasa/<key>.svg).
 * Drawn for this project: viewBox 0 0 24 24, 2px margin, no fill, stroke
 * currentColor 1.75 with round caps and joins. The icon seeder copies them into
 * the admin icon library; interface icons (arrows, menu, close...) are not here.
 *
 * key => [label [en, id], category]
 */
return [
    'categories' => [
        'temperature' => ['en' => 'Temperature & storage', 'id' => 'Suhu & penyimpanan'],
        'transport' => ['en' => 'Transport & fleet', 'id' => 'Transportasi & armada'],
        'warehouse' => ['en' => 'Warehouse & workflow', 'id' => 'Gudang & alur kerja'],
        'time' => ['en' => 'Time & monitoring', 'id' => 'Waktu & pemantauan'],
        'company' => ['en' => 'Company & trust', 'id' => 'Perusahaan & kepercayaan'],
        'contact' => ['en' => 'Location & contact', 'id' => 'Lokasi & kontak'],
        'security' => ['en' => 'Security & quality', 'id' => 'Keamanan & kualitas'],
        'industry' => ['en' => 'Industries & goods', 'id' => 'Industri & komoditas'],
        'systems' => ['en' => 'Systems & technology', 'id' => 'Sistem & teknologi'],
    ],

    'icons' => [
        // Batch 1
        'snowflake' => [['Snowflake', 'Kepingan salju'], 'temperature'],
        'thermometer' => [['Thermometer', 'Termometer'], 'temperature'],
        'thermometer-snowflake' => [['Thermometer and snowflake', 'Termometer dan salju'], 'temperature'],
        'warehouse' => [['Warehouse', 'Gudang'], 'temperature'],
        'cold-cabinet' => [['Refrigerated cabinet', 'Lemari pendingin'], 'temperature'],
        'container' => [['Container', 'Kontainer'], 'temperature'],
        'box' => [['Box', 'Kardus'], 'temperature'],
        'boxes' => [['Stacked boxes', 'Tumpukan kardus'], 'temperature'],
        'reefer-truck' => [['Refrigerated box truck', 'Truk boks berpendingin'], 'transport'],
        'box-truck' => [['Dry box truck', 'Truk boks dry'], 'transport'],
        'van' => [['Van', 'Van'], 'transport'],
        'courier-motorbike' => [['Courier motorbike', 'Motor kurir'], 'transport'],
        'route' => [['Route', 'Rute'], 'transport'],
        'map-pin' => [['Location pin', 'Pin lokasi'], 'transport'],
        'clock' => [['Clock', 'Jam'], 'time'],
        'service-24h' => [['24-hour service', 'Layanan 24 jam'], 'time'],
        'office-building' => [['Office building', 'Gedung kantor'], 'company'],
        'team' => [['Team', 'Tim'], 'company'],
        'handshake' => [['Handshake', 'Jabat tangan'], 'company'],
        'shield-check' => [['Shield with check', 'Perisai tercentang'], 'company'],
        'badge' => [['Badge', 'Lencana'], 'company'],
        'star' => [['Star', 'Bintang'], 'company'],
        'chart-up' => [['Rising chart', 'Grafik naik'], 'company'],
        'headset' => [['Support headset', 'Headset dukungan'], 'company'],
        'certificate' => [['Certificate', 'Sertifikat'], 'company'],
        'document-check' => [['Checked document', 'Dokumen tercentang'], 'company'],
        'phone' => [['Phone', 'Telepon'], 'contact'],
        'email' => [['Email', 'Email'], 'contact'],
        'chat-bubble' => [['Chat bubble', 'Gelembung chat'], 'contact'],
        'opening-hours' => [['Opening hours', 'Jam operasional'], 'contact'],
    ],
];
