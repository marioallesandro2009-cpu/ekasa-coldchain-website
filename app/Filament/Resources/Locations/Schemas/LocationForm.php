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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                        TextInput::make('lat')->label('Lintang (lat)')->numeric()->minValue(-90)->maxValue(90)->step('0.000001')->placeholder('-6.2'),
                        TextInput::make('lng')->label('Bujur (lng)')->numeric()->minValue(-180)->maxValue(180)->step('0.000001')->placeholder('106.8')
                            ->helperText('Dari Google Maps: klik kanan titik > salin koordinat.'),
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
}
