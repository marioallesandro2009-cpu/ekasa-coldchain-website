<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Block types of the service detail pages (services.blocks, Filament Builder).
 * Every block stores its fields in "data"; texts are {"en": ..., "id": ...}.
 * Shared keys: hidden (not rendered), id_reviewed (show the Indonesian texts;
 * otherwise /id shows the English ones), anchor, eyebrow, title, description.
 * Admin forms: App\Filament\Support\ServiceBlockSchemas.
 */
final class ServiceBlocks
{
    /** type => [admin label, group, Heroicon name] */
    public const TYPES = [
        'hero_layanan' => ['Hero layanan', 'umum', 'photo'],
        'pita_spesifikasi' => ['Pita spesifikasi', 'umum', 'chart-bar'],
        'keunggulan' => ['Keunggulan', 'umum', 'sparkles'],
        'teks_gambar' => ['Teks + gambar', 'umum', 'document-text'],
        'cara_kerja' => ['Cara kerja', 'umum', 'list-bullet'],
        'industri' => ['Industri', 'umum', 'building-office-2'],
        'galeri' => ['Galeri', 'umum', 'rectangle-stack'],
        'faq' => ['FAQ', 'umum', 'question-mark-circle'],
        'cta_penutup' => ['CTA penutup', 'umum', 'megaphone'],
        'zona_suhu' => ['Zona suhu', 'khusus', 'beaker'],
        'lokasi_gudang' => ['Lokasi gudang', 'khusus', 'map-pin'],
        'katalog_armada' => ['Katalog armada', 'khusus', 'truck'],
        'skema_sewa' => ['Skema sewa', 'khusus', 'clipboard-document-list'],
        'diagram_rute' => ['Diagram rute', 'khusus', 'map'],
        'alur_end_to_end' => ['Alur end-to-end', 'khusus', 'arrows-right-left'],
    ];

    /** Layout of a new service (admin "Buat"); empty blocks are not rendered. */
    public const DEFAULT_LAYOUT = ['hero_layanan', 'keunggulan', 'cara_kerja', 'faq', 'cta_penutup'];

    public static function label(string $type): string
    {
        return self::TYPES[$type][0] ?? Str::headline($type);
    }

    /**
     * A block with the shared keys filled in.
     *
     * @param  array<string, mixed>  $data
     * @return array{type: string, data: array<string, mixed>}
     */
    public static function make(string $type, array $data = []): array
    {
        return ['type' => $type, 'data' => [
            'hidden' => false,
            'id_reviewed' => true,
            ...$data,
        ]];
    }

    /**
     * Empty blocks for a new service.
     *
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    public static function defaultLayout(): array
    {
        return array_map(fn (string $type): array => self::make($type, ['id_reviewed' => false]), self::DEFAULT_LAYOUT);
    }

    /**
     * One-line summary of a block for the admin preview ("4 item", "Semua lokasi: hub"...).
     *
     * @param  array<string, mixed>  $data
     */
    public static function summary(string $type, array $data): string
    {
        $count = fn (string $key): int => is_array($data[$key] ?? null) ? count($data[$key]) : 0;
        $picked = fn (string $key, string $all): string => $count($key) > 0 ? $count($key).' dipilih' : $all;

        return match ($type) {
            'hero_layanan' => 'Foto hero, judul, pengantar'.(($data['show_key_points'] ?? true) ? ', poin kunci' : '').', tombol CTA',
            'pita_spesifikasi' => $count('items').' angka',
            'keunggulan' => $count('items').' keunggulan',
            'teks_gambar' => 'Teks + '.(filled($data['image'] ?? null) ? '1 foto galeri' : 'tanpa foto').', foto di '.(($data['image_position'] ?? 'right') === 'left' ? 'kiri' : 'kanan'),
            'cara_kerja' => $count('steps').' langkah',
            'industri' => $picked('industry_ids', 'Semua industri aktif'),
            'galeri' => $picked('images', 'Semua foto galeri layanan'),
            'faq' => $count('faq_ids').' pertanyaan',
            'cta_penutup' => 'Tombol ke '.(($data['target'] ?? 'contact') === 'whatsapp' ? 'WhatsApp' : '#contact'),
            'zona_suhu' => $count('zones').' zona',
            'lokasi_gudang' => $picked('location_ids', 'Semua lokasi aktif'.(in_array($data['kind'] ?? 'all', ['warehouse', 'hub'], true) ? ' (jenis '.($data['kind'] === 'hub' ? 'hub' : 'gudang').')' : '')),
            'katalog_armada' => 'Tata letak '.(($data['layout'] ?? 'showcase') === 'grid' ? 'Grid' : 'Showcase').', '.$picked('vehicle_ids', 'semua truk aktif'),
            'skema_sewa' => $picked('scheme_ids', 'Semua skema aktif'),
            'diagram_rute' => 'Hub '.($data['hub'] ?? '-').', '.$picked('route_ids', 'semua rute aktif'),
            'alur_end_to_end' => $count('steps').' langkah',
            default => '',
        };
    }
}
