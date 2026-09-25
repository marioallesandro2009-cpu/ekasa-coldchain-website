<?php

/**
 * Stock of the original Ekasa content icons (resources/icons/ekasa/<key>.svg).
 * Drawn for this project: viewBox 0 0 24 24, 2px margin, no fill, stroke
 * currentColor 1.75 with round caps and joins. The icon seeder copies them into
 * the admin icon library; interface icons (arrows, menu, close...) are not here.
 *
 * key => [label [en, id], category]. Keys under 'review' are drawn but wait for
 * the client's approval: the library adds them switched off (not selectable).
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
        'chain-link' => [['Linked partnership', 'Kemitraan terhubung'], 'company'],
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

        // Batch 2
        'network' => [['Network', 'Jaringan'], 'systems'],
        'forklift' => [['Forklift', 'Forklift'], 'warehouse'],
        'pallet' => [['Pallet with boxes', 'Palet berisi kardus'], 'warehouse'],
        'barcode-scan' => [['Barcode scan', 'Pindai barcode'], 'warehouse'],
        'clipboard-check' => [['Checklist', 'Daftar periksa'], 'warehouse'],
        'cross-dock' => [['Cross-docking', 'Bongkar muat silang'], 'warehouse'],
        'calendar-check' => [['Confirmed schedule', 'Jadwal terkonfirmasi'], 'time'],
        'stopwatch' => [['Stopwatch', 'Stopwatch'], 'time'],
        'monitor-pulse' => [['Live monitoring', 'Pemantauan langsung'], 'time'],
        'bell' => [['Alert', 'Peringatan'], 'time'],
        'lock' => [['Lock', 'Gembok'], 'security'],
        'cctv' => [['CCTV camera', 'Kamera CCTV'], 'security'],
        'droplet' => [['Hygiene', 'Higienis'], 'security'],
        'fish' => [['Seafood', 'Hasil laut'], 'industry'],
        'leaf' => [['Fresh produce', 'Produk segar'], 'industry'],
        'milk-carton' => [['Dairy', 'Produk susu'], 'industry'],
        'ice-cream' => [['Ice cream', 'Es krim'], 'industry'],
        'capsule' => [['Pharmaceuticals', 'Farmasi'], 'industry'],
        'iot-sensor' => [['IoT sensor', 'Sensor IoT'], 'systems'],
        'cloud-sync' => [['Cloud sync', 'Sinkronisasi cloud'], 'systems'],
        'gauge' => [['Dashboard gauge', 'Indikator dasbor'], 'systems'],
        'plug' => [['Integration', 'Integrasi'], 'systems'],
    ],

    // Batch 2 awaiting approval ("network" is already the Omnichannel default).
    'review' => [
        'forklift', 'pallet', 'barcode-scan', 'clipboard-check', 'cross-dock',
        'calendar-check', 'stopwatch', 'monitor-pulse', 'bell',
        'lock', 'cctv', 'droplet',
        'fish', 'leaf', 'milk-carton', 'ice-cream', 'capsule',
        'iot-sensor', 'cloud-sync', 'gauge', 'plug',
    ],
];
