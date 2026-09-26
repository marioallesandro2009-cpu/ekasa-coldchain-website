<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Support\Sample;
use App\Filament\Support\ServiceBlockSchemas;
use App\Filament\Support\Translatable;
use App\Models\FleetVehicle;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\IconLibrary;
use App\Support\ImageUpload;
use App\Support\Locales;
use App\Support\ServiceBlocks;
use App\Support\ServiceIcons;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ServiceForm
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = [
        'title' => 'Judul',
        'short_description' => 'Deskripsi singkat',
        'intro' => 'Pengantar',
        'key_points' => 'Poin kunci',
        'seo_title' => 'Judul SEO',
        'seo_description' => 'Deskripsi SEO',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Sample::notice(),
                Tabs::make('service')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Umum')->icon(Heroicon::OutlinedInformationCircle)->schema(self::general()),
                        Tab::make('Halaman detail')->icon(Heroicon::OutlinedRectangleStack)->schema(self::detail()),
                        Tab::make('Foto')->icon(Heroicon::OutlinedPhoto)->schema(self::photos()),
                        Tab::make('SEO')->icon(Heroicon::OutlinedGlobeAlt)->schema(self::seo()),
                    ]),
            ]);
    }

    /**
     * @return list<mixed>
     */
    private static function general(): array
    {
        return [
            Section::make('Detail layanan')
                ->columns(2)
                ->schema([
                    Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                        ->label(Translatable::label('Judul', $locale))
                        ->required(Translatable::isRequired($locale))
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) use ($locale): void {
                            if ($operation !== 'create') {
                                return;
                            }

                            $set("slugs.{$locale}", Str::slug((string) $state));

                            if ($locale === Locales::default()) {
                                $set('slug', Str::slug((string) $state));
                                $set('anchor', Str::slug((string) $state));
                            }
                        })),
                    Translatable::fields(fn (string $locale): TextInput => TextInput::make("short_description.{$locale}")
                        ->label(Translatable::label('Deskripsi singkat', $locale))
                        ->maxLength(60)
                        ->helperText('Maks. 60 karakter, tampil di mega menu dan kartu beranda.')),
                    Translatable::fields(fn (string $locale): Textarea => Textarea::make("intro.{$locale}")
                        ->label(Translatable::label('Pengantar', $locale))
                        ->rows(3)
                        ->maxLength(400)
                        ->helperText('Paragraf pembuka di hero halaman detail.')),
                    Translatable::fields(fn (string $locale): Textarea => Textarea::make("key_points.{$locale}")
                        ->label(Translatable::label('Poin kunci', $locale))
                        ->rows(3)
                        ->helperText('Satu poin per baris, maks. '.Service::MAX_KEY_POINTS.'. Tampil di kartu beranda dan hero.')
                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            $lines = array_filter(array_map('trim', explode("\n", (string) $value)));

                            if (count($lines) > Service::MAX_KEY_POINTS) {
                                $fail('Maksimal '.Service::MAX_KEY_POINTS.' poin (satu per baris).');
                            }
                        })),
                    ToggleButtons::make('is_featured')
                        ->label('Jenis')
                        ->boolean('Utama', 'Pendukung')
                        ->default(true)
                        ->inline()
                        ->grouped()
                        ->helperText('Utama: kartu besar di beranda dan grid mega menu. Pendukung: pita di bawahnya.'),
                    Select::make('icon')
                        ->label('Ikon')
                        ->options(fn (): array => IconLibrary::htmlOptions())
                        ->allowHtml()
                        ->searchable()
                        ->getSearchResultsUsing(fn (?string $search): array => IconLibrary::htmlOptions($search))
                        ->getOptionLabelUsing(fn (?string $value): string => $value ? IconLibrary::optionHtml($value) : '')
                        ->required()
                        ->default(ServiceIcons::DEFAULT)
                        ->helperText('Dari Konten Beranda › Pustaka Ikon (hanya ikon aktif).'),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
            Section::make('Alamat halaman')
                ->columns(2)
                ->schema([
                    Translatable::pair(fn (string $locale): TextInput => TextInput::make("slugs.{$locale}")
                        ->label('Slug URL ('.strtoupper($locale).')')
                        ->prefix(self::urlPrefix($locale))
                        ->required()
                        ->maxLength(120)
                        ->alphaDash()
                        ->rule(fn (?Service $record): Closure => self::uniqueSlug($locale, $record))),
                    TextInput::make('slug')
                        ->label('Kunci internal')
                        ->required()
                        ->maxLength(120)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->helperText('Dipakai seeder dan sebagai anchor bawaan, tidak tampil di URL.'),
                    TextInput::make('anchor')
                        ->label('Anchor di beranda')
                        ->prefix('#')
                        ->maxLength(120)
                        ->helperText('Tujuan tautan selama halaman detail belum terbit.'),
                ]),
        ];
    }

    /**
     * @return list<mixed>
     */
    private static function detail(): array
    {
        return [
            ToggleButtons::make('detail_status')
                ->label('Status halaman detail')
                ->options(Service::STATUSES)
                ->colors([Service::DRAFT => 'gray', Service::PUBLISHED => 'success'])
                ->default(Service::DRAFT)
                ->inline()
                ->grouped()
                ->helperText('Draf: halaman hanya bisa dibuka admin (tombol Pratinjau); kartu beranda mengarah ke anchor.'),
            Builder::make('blocks')
                ->label('Blok halaman')
                ->blocks(ServiceBlockSchemas::all())
                ->default(ServiceBlocks::defaultLayout())
                ->blockPreviews()
                ->collapsible()
                ->cloneable()
                ->blockPickerColumns(3)
                ->blockNumbers(false)
                ->addActionLabel('Tambah blok')
                ->helperText('Klik blok untuk mengubah isinya; seret untuk mengubah urutan. Blok kosong tidak tampil di situs.'),
        ];
    }

    /**
     * @return list<mixed>
     */
    private static function photos(): array
    {
        return [
            Section::make('Hero halaman detail')->schema([
                self::upload('hero', 'Foto hero', '1920 x 1080 px')
                    ->helperText('Landscape, sisi kiri sebaiknya tenang/gelap (judul di atasnya). Ideal 1920 x 1080 px; JPG, PNG, atau WebP, maks. 5 MB.'),
            ]),
            Section::make('Kartu beranda (4:3)')
                ->columns(2)
                ->schema([
                    self::upload('card', 'Foto kartu', '1200 x 900 px')
                        ->helperText('Rasio 4:3, ideal 1200 x 900 px. Diabaikan jika memakai foto armada.'),
                    Grid::make(1)->schema([
                        Select::make('card_fleet_vehicle_id')
                            ->label('Atau pakai foto armada')
                            ->options(fn (): array => FleetVehicle::query()->ordered()->get()->mapWithKeys(fn (FleetVehicle $vehicle): array => [$vehicle->id => $vehicle->name()])->all())
                            ->placeholder('Tidak (pakai foto kartu)')
                            ->helperText('Foto tampak samping dari Data Layanan › Armada, dipotong 4:3 hanya di tampilan (file asli tidak diubah).'),
                        Grid::make(2)->schema([
                            TextInput::make('card_focus_x')->label('Titik fokus horizontal')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(50),
                            TextInput::make('card_focus_y')->label('Titik fokus vertikal')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(50),
                        ]),
                        Toggle::make('card_photo_light')
                            ->label('Foto ini terang/ramai')
                            ->helperText('Aktifkan kalau foto kartu ini cukup terang atau ramai (misal gudang berpenerangan terang). Di halaman Our Services, nomor urut besar yang menumpuk di foto akan otomatis dibuat lebih gelap dan lebih pekat supaya tetap terlihat — kalau tetap tidak aktif, nomor tetap putih pudar seperti biasa (cocok untuk foto gelap/seragam).'),
                    ]),
                ]),
            Section::make('Galeri')->schema([
                self::upload('gallery', 'Foto galeri', '1600 x 1200 px')
                    ->multiple()
                    ->reorderable()
                    ->maxFiles(12)
                    ->required(false)
                    ->helperText('Dipakai blok Galeri dan Teks + gambar. Maks. 12 foto; JPG, PNG, atau WebP, maks. 5 MB.'),
            ]),
        ];
    }

    /**
     * @return list<mixed>
     */
    private static function seo(): array
    {
        return [
            Section::make('Mesin pencari')->schema([
                Translatable::fields(fn (string $locale): TextInput => TextInput::make("seo_title.{$locale}")
                    ->label(Translatable::label('Judul SEO', $locale))
                    ->maxLength(70)
                    ->helperText('Kosong: "<Judul layanan> | <nama perusahaan>". Maks. 70 karakter.')),
                Translatable::fields(fn (string $locale): Textarea => Textarea::make("seo_description.{$locale}")
                    ->label(Translatable::label('Deskripsi SEO', $locale))
                    ->rows(2)
                    ->maxLength(160)
                    ->helperText('Kosong: pengantar layanan. Maks. 160 karakter.')),
            ]),
        ];
    }

    private static function upload(string $collection, string $label, string $idealSize): SpatieMediaLibraryFileUpload
    {
        return ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make($collection), $idealSize)
            ->label($label)
            ->collection($collection)
            ->image()
            ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
            ->maxSize(5120);
    }

    private static function urlPrefix(string $locale): string
    {
        return rtrim(Locales::path($locale, '/'.config("site.locales.{$locale}.segments.services", 'services')), '/').'/';
    }

    /**
     * The slug must be unique among the services in that language.
     */
    private static function uniqueSlug(string $locale, ?Service $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($locale, $record): void {
            $taken = Service::query()
                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->get(['id', 'slugs'])
                ->contains(fn (Service $service): bool => ($service->slugs[$locale] ?? null) === $value);

            if ($taken) {
                $fail('Slug ini sudah dipakai layanan lain ('.strtoupper($locale).').');
            }
        };
    }
}
