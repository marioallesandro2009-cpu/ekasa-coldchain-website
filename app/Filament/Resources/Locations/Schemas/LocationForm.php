<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\Location;
use App\Models\SiteSetting;
use App\Support\ImageUpload;
use App\Support\TemperatureZones;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class LocationForm
{
    public const LABELS = ['commodities' => 'Komoditas', 'capacity' => 'Kapasitas', 'opening_hours' => 'Jam operasional'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Sample::notice(),
                Section::make('Lokasi')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nama')->required()->maxLength(120),
                        ToggleButtons::make('kind')->label('Jenis')->options(Location::KINDS)->default('warehouse')->inline()->grouped()->required(),
                        Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
                        TextInput::make('city')->label('Kota')->required()->maxLength(100),
                        TextInput::make('area')->label('Area')->maxLength(100)->placeholder('Jakarta Timur'),
                        TextInput::make('phone')->label('Telepon')->tel()->maxLength(40),
                        TextInput::make('address')->label('Alamat')->maxLength(255)->columnSpanFull(),
                        TextInput::make('lat')->label('Lintang (lat)')->numeric()->minValue(-90)->maxValue(90)->step('0.000001')->placeholder('-6.2')
                            ->live(onBlur: true),
                        TextInput::make('lng')->label('Bujur (lng)')->numeric()->minValue(-180)->maxValue(180)->step('0.000001')->placeholder('106.8')
                            ->helperText('Dari Google Maps: klik kanan titik > salin koordinat.')
                            ->live(onBlur: true),
                        Html::make(fn (Get $get): HtmlString => self::mapPreview($get('lat'), $get('lng')))
                            ->columnSpanFull(),
                    ]),
                Section::make('Layanan di lokasi ini')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        CheckboxList::make('temperature_zones')
                            ->label('Zona suhu')
                            ->options(TemperatureZones::options())
                            ->columns(2)
                            ->columnSpanFull(),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("commodities.{$locale}")
                            ->label(Translatable::label('Komoditas', $locale))
                            ->maxLength(200)
                            ->placeholder($locale === 'id' ? 'Daging beku, hasil laut, produk susu' : 'Frozen meat, seafood, dairy')),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("capacity.{$locale}")
                            ->label(Translatable::label('Kapasitas', $locale))
                            ->maxLength(80)
                            ->placeholder($locale === 'id' ? '3.000 palet' : '3,000 pallets')),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("opening_hours.{$locale}")
                            ->label(Translatable::label('Jam operasional', $locale))
                            ->maxLength(80)
                            ->placeholder($locale === 'id' ? 'Senin-Sabtu, 07.00-21.00' : 'Mon-Sat, 7am-9pm')),
                        ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('photo'), '1600 x 1200 px')
                            ->label('Foto')
                            ->collection('photo')
                            ->image()
                            ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Mini-map preview (lat/lng are ->live(onBlur: true) above, so this re-runs
     * whenever either changes): confirms the pin lands where the admin expects
     * before saving — same coordinate validation as the fields themselves
     * (-90..90 / -180..180), so an out-of-range value never reaches Leaflet.
     *
     * Built with Alpine x-init, not a plain inline <script>: Livewire's re-render
     * on ->live(onBlur:) replaces this HTML via DOM morphing, and a <script> tag
     * inserted that way is inert per the DOM spec (only a script present at
     * initial page parse ever executes) — it would work once on first load and
     * silently do nothing on every coordinate change after that. Alpine's own
     * Livewire integration re-runs x-init on morphed-in elements, so this fires
     * every time. A unique element id per render (not a fixed one) also
     * sidesteps "map container is already initialized" the same way. Leaflet's
     * JS/CSS are fetched once (cached on `window`) and reused across renders.
     */
    private static function mapPreview(mixed $lat, mixed $lng): HtmlString
    {
        $lat = is_numeric($lat) ? (float) $lat : null;
        $lng = is_numeric($lng) ? (float) $lng : null;

        if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return new HtmlString(
                '<p style="font-size:.875rem;color:#6b7280">Isi lintang dan bujur yang valid untuk melihat pratinjau titik di peta.</p>'
            );
        }

        $id = 'loc-preview-'.Str::random(8);
        $latJs = json_encode($lat);
        $lngJs = json_encode($lng);

        $init = <<<JS
            (function (el) {
                window.__leafletReady ??= Promise.all([
                    new Promise(function (resolve) {
                        var link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        link.onload = resolve;
                        document.head.appendChild(link);
                    }),
                    new Promise(function (resolve) {
                        var script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.onload = resolve;
                        document.head.appendChild(script);
                    }),
                ]);
                window.__leafletReady.then(function () {
                    var map = L.map(el, {attributionControl: false}).setView([{$latJs}, {$lngJs}], 14);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19}).addTo(map);
                    L.marker([{$latJs}, {$lngJs}]).addTo(map);
                });
            })(\$el)
            JS;

        return new HtmlString(
            '<div>'
            .'<p style="font-size:.875rem;font-weight:600;margin-bottom:.5rem">Pratinjau titik di peta — pastikan pin ini benar sebelum menyimpan</p>'
            ."<div id=\"{$id}\" x-data x-init=\"{$init}\" wire:ignore style=\"height:220px;border-radius:.5rem;overflow:hidden\"></div>"
            .'</div>'
        );
    }
}
