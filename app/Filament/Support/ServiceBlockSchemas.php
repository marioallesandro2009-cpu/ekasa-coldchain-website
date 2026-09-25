<?php

namespace App\Filament\Support;

use App\Models\Faq;
use App\Models\FleetVehicle;
use App\Models\Industry;
use App\Models\Location;
use App\Models\RentalScheme;
use App\Models\Service;
use App\Models\TransportRoute;
use App\Support\IconLibrary;
use App\Support\Locales;
use App\Support\ServiceBlocks;
use App\Support\TemperatureZones;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Filament Builder blocks of a service detail page (types: App\Support\ServiceBlocks).
 * Texts are pairs of fields (EN | ID); one "Versi ID sudah dicek" switch per block.
 */
final class ServiceBlockSchemas
{
    /**
     * @return list<Block>
     */
    public static function all(): array
    {
        $blocks = [];

        foreach (ServiceBlocks::TYPES as $type => [$label, $group, $icon]) {
            $blocks[] = Block::make($type)
                ->label(fn (?array $state): string => self::heading($type, $state))
                ->icon(constant(Heroicon::class.'::Outlined'.Str::studly($icon)))
                ->preview("filament.service-blocks.{$type}")
                ->schema([...self::shared($type), ...self::fields($type)]);
        }

        return $blocks;
    }

    /**
     * "Keunggulan · Mengapa kami" (· tersembunyi) in the collapsed list.
     *
     * @param  array<string, mixed>|null  $state
     */
    private static function heading(string $type, ?array $state): string
    {
        $title = trim((string) ($state['title'][Locales::default()] ?? ''));
        $label = ServiceBlocks::label($type).($title !== '' ? ' · '.Str::limit($title, 40) : '');

        return ($state['hidden'] ?? false) ? $label.' (disembunyikan)' : $label;
    }

    /**
     * Fields every block has: visibility, review switch, heading texts, anchor.
     *
     * @return list<mixed>
     */
    private static function shared(string $type): array
    {
        $isHero = $type === 'hero_layanan';

        return [
            Grid::make(3)->schema([
                Toggle::make('hidden')
                    ->label('Sembunyikan blok')
                    ->helperText('Tidak tampil di situs, isi tetap tersimpan.')
                    ->default(false),
                Toggle::make('id_reviewed')
                    ->label('Versi ID sudah dicek')
                    ->helperText('Mati: halaman /id menampilkan teks EN blok ini.')
                    ->default(false),
                TextInput::make('anchor')
                    ->label('Anchor (opsional)')
                    ->prefix('#')
                    ->alphaDash()
                    ->maxLength(60),
            ]),
            self::text('eyebrow', 'Label kecil', maxLength: 60),
            self::text('title', $isHero ? 'Judul (kosong = nama layanan)' : 'Judul', maxLength: 120),
            self::text('description', $isHero ? 'Pengantar (kosong = pengantar layanan)' : 'Deskripsi', textarea: true, maxLength: 400),
        ];
    }

    /**
     * @return list<mixed>
     */
    private static function fields(string $type): array
    {
        return match ($type) {
            'hero_layanan' => [
                Toggle::make('show_key_points')->label('Tampilkan poin kunci layanan')->default(true),
                self::text('cta_label', 'Label tombol utama (kosong = label CTA situs)', maxLength: 40),
                self::target('cta_target'),
                self::text('secondary_label', 'Label tombol kedua (kosong = tidak ada)', maxLength: 40),
            ],
            'pita_spesifikasi' => [
                Repeater::make('items')
                    ->label('Angka')
                    ->maxItems(6)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => trim(($state['prefix'] ?? '').($state['value'] ?? '').' '.($state['suffix'] ?? '').' '.($state['label']['en'] ?? '')) ?: null)
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('prefix')->label('Prefiks')->maxLength(8),
                            TextInput::make('value')->label('Nilai')->numeric()->required(),
                            TextInput::make('suffix')->label('Sufiks')->maxLength(12)->placeholder('°C, +, m³'),
                            Toggle::make('count_up')->label('Hitung naik')->default(true)->inline(false),
                        ]),
                        self::text('label', 'Label', maxLength: 60),
                    ]),
            ],
            'keunggulan' => [
                Repeater::make('items')
                    ->label('Keunggulan')
                    ->maxItems(8)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title']['en'] ?? null)
                    ->schema([
                        self::icon(),
                        self::text('title', 'Judul', maxLength: 80),
                        self::text('text', 'Teks', textarea: true, maxLength: 240),
                    ]),
            ],
            'teks_gambar' => [
                self::text('text', 'Teks', textarea: true, maxLength: 1200),
                self::text('points', 'Poin (satu per baris)', textarea: true, maxLength: 600),
                Grid::make(2)->schema([
                    Select::make('image')
                        ->label('Foto (dari galeri layanan)')
                        ->options(fn (?Service $record): array => self::galleryOptions($record))
                        ->helperText('Unggah foto di tab Foto > Galeri, simpan, lalu pilih di sini.'),
                    ToggleButtons::make('image_position')
                        ->label('Posisi foto')
                        ->options(['left' => 'Kiri', 'right' => 'Kanan'])
                        ->default('right')
                        ->inline()
                        ->grouped(),
                ]),
            ],
            'cara_kerja', 'alur_end_to_end' => [
                Repeater::make('steps')
                    ->label('Langkah')
                    ->maxItems(8)
                    ->collapsible()
                    ->reorderableWithDragAndDrop()
                    ->itemLabel(fn (array $state): ?string => $state['title']['en'] ?? null)
                    ->schema(array_values(array_filter([
                        $type === 'alur_end_to_end' ? self::icon() : null,
                        self::text('title', 'Judul', maxLength: 80),
                        self::text('text', 'Teks', textarea: true, maxLength: 240),
                    ]))),
            ],
            'industri' => [
                self::pick('industry_ids', 'Industri', fn (): array => Industry::query()->ordered()->get()
                    ->mapWithKeys(fn (Industry $industry): array => [$industry->id => (string) $industry->translate('name', Locales::default())])->all(), 'Kosong: semua industri aktif.'),
            ],
            'galeri' => [
                Select::make('images')
                    ->label('Foto')
                    ->multiple()
                    ->options(fn (?Service $record): array => self::galleryOptions($record))
                    ->helperText('Kosong: semua foto galeri layanan (tab Foto > Galeri).'),
            ],
            'faq' => [
                self::pick('faq_ids', 'Pertanyaan', fn (): array => Faq::query()->ordered()->get()
                    ->mapWithKeys(fn (Faq $faq): array => [$faq->id => (string) $faq->translate('question', Locales::default())])->all(), 'Urutan tampil mengikuti urutan pilihan. Kelola di Data Layanan > FAQ.'),
            ],
            'cta_penutup' => [
                self::text('button_label', 'Label tombol (kosong = label CTA situs)', maxLength: 40),
                self::target('target'),
            ],
            'zona_suhu' => [
                Repeater::make('zones')
                    ->label('Zona')
                    ->maxItems(6)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => isset($state['label']['en']) ? $state['label']['en'].' ('.($state['min'] ?? '?').' s/d '.($state['max'] ?? '?').' °C)' : null)
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('zone')->label('Jenis (warna)')->options(TemperatureZones::options())->required(),
                            TextInput::make('min')->label('Suhu min (°C)')->numeric()->required(),
                            TextInput::make('max')->label('Suhu maks (°C)')->numeric()->required()->gte('min'),
                        ]),
                        self::text('label', 'Label', maxLength: 40),
                        self::text('commodities', 'Contoh komoditas', maxLength: 160),
                    ]),
            ],
            'lokasi_gudang' => [
                ToggleButtons::make('kind')
                    ->label('Jenis lokasi')
                    ->options(['all' => 'Semua', ...Location::KINDS])
                    ->default('all')
                    ->inline()
                    ->grouped(),
                self::pick('location_ids', 'Lokasi tertentu', fn (): array => Location::query()->ordered()->get()
                    ->mapWithKeys(fn (Location $location): array => [$location->id => "{$location->name} ({$location->city})"])->all(), 'Kosong: semua lokasi aktif dengan jenis di atas.'),
            ],
            'katalog_armada' => [
                ToggleButtons::make('layout')
                    ->label('Tata letak')
                    ->options(['showcase' => 'Showcase', 'grid' => 'Grid'])
                    ->default('showcase')
                    ->inline()
                    ->grouped(),
                self::pick('vehicle_ids', 'Truk', fn (): array => FleetVehicle::query()->ordered()->get()
                    ->mapWithKeys(fn (FleetVehicle $vehicle): array => [$vehicle->id => $vehicle->name()])->all(), 'Kosong: semua truk aktif.'),
                self::text('ask_label', 'Label tombol tanya ketersediaan (kosong = "Tanya ketersediaan")', maxLength: 40),
            ],
            'skema_sewa' => [
                self::pick('scheme_ids', 'Skema', fn (): array => RentalScheme::query()->ordered()->get()
                    ->mapWithKeys(fn (RentalScheme $scheme): array => [$scheme->id => (string) $scheme->translate('name', Locales::default())])->all(), 'Kosong: semua skema aktif.'),
            ],
            'diagram_rute' => [
                TextInput::make('hub')->label('Nama hub asal')->maxLength(60)->default('Jakarta')->required(),
                self::pick('route_ids', 'Rute', fn (): array => TransportRoute::query()->ordered()->get()
                    ->mapWithKeys(fn (TransportRoute $route): array => [$route->id => $route->label()])->all(), 'Kosong: semua rute aktif.'),
            ],
            default => [],
        };
    }

    private static function text(string $name, string $label, bool $textarea = false, int $maxLength = 255): Grid
    {
        return Translatable::pair(fn (string $locale): TextInput|Textarea => ($textarea ? Textarea::make("{$name}.{$locale}")->rows(3) : TextInput::make("{$name}.{$locale}"))
            ->label(Translatable::label($label, $locale))
            ->maxLength($maxLength));
    }

    private static function target(string $name): ToggleButtons
    {
        return ToggleButtons::make($name)
            ->label('Tujuan tombol')
            ->options(['contact' => 'Form kontak (#contact)', 'whatsapp' => 'WhatsApp'])
            ->default('contact')
            ->inline()
            ->grouped();
    }

    private static function icon(): Select
    {
        return Select::make('icon')
            ->label('Ikon')
            ->options(fn (): array => IconLibrary::htmlOptions())
            ->allowHtml()
            ->searchable()
            ->getSearchResultsUsing(fn (?string $search): array => IconLibrary::htmlOptions($search))
            ->getOptionLabelUsing(fn (?string $value): string => $value ? IconLibrary::optionHtml($value) : '');
    }

    /**
     * @param  \Closure(): array<int|string, string>  $options
     */
    private static function pick(string $name, string $label, \Closure $options, string $help): Select
    {
        return Select::make($name)
            ->label($label)
            ->multiple()
            ->searchable()
            ->options($options)
            ->helperText($help);
    }

    /**
     * Gallery photos of the service being edited: media uuid => file name.
     *
     * @return array<string, string>
     */
    private static function galleryOptions(?Service $record): array
    {
        if (! $record) {
            return [];
        }

        return $record->getMedia('gallery')->mapWithKeys(fn ($media): array => [$media->uuid => $media->name ?: $media->file_name])->all();
    }
}
