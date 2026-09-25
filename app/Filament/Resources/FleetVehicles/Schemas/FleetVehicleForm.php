<?php

namespace App\Filament\Resources\FleetVehicles\Schemas;

use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\FleetVehicle;
use App\Models\SiteSetting;
use App\Support\ImageUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FleetVehicleForm
{
    public const LABELS = ['description' => 'Deskripsi', 'notes' => 'Catatan'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Sample::notice(),
                Section::make('Truk')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('model')->label('Model')->required()->maxLength(100)->placeholder('FE 71 Long'),
                        TextInput::make('brand')->label('Merek')->maxLength(100),
                        ToggleButtons::make('type')->label('Jenis')->options(FleetVehicle::TYPES)->default('reefer')->inline()->grouped()->required(),
                        Translatable::fields(fn (string $locale): Textarea => Textarea::make("description.{$locale}")
                            ->label(Translatable::label('Deskripsi', $locale))
                            ->rows(3)
                            ->maxLength(400)),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),
                Section::make('Spesifikasi')
                    ->description('Field yang kosong tidak ditampilkan di situs.')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('temp_min')->label('Suhu min')->numeric()->suffix('°C'),
                        TextInput::make('temp_max')->label('Suhu maks')->numeric()->suffix('°C')->gte('temp_min'),
                        TextInput::make('capacity_kg')->label('Kapasitas')->numeric()->minValue(0)->suffix('kg'),
                        TextInput::make('volume_m3')->label('Volume')->numeric()->minValue(0)->suffix('m³')->helperText('Kosong: dihitung dari dimensi boks.'),
                        TextInput::make('box_length')->label('Panjang boks')->numeric()->minValue(0)->suffix('m'),
                        TextInput::make('box_width')->label('Lebar boks')->numeric()->minValue(0)->suffix('m'),
                        TextInput::make('box_height')->label('Tinggi boks')->numeric()->minValue(0)->suffix('m'),
                        Translatable::fields(fn (string $locale): Textarea => Textarea::make("notes.{$locale}")
                            ->label(Translatable::label('Catatan', $locale))
                            ->rows(2)
                            ->maxLength(300)),
                    ]),
                Section::make('Gambar')
                    ->columnSpanFull()
                    ->schema([
                        Callout::make('Gambar tampak samping kurang lebar')
                            ->description(fn (?FleetVehicle $record): string => 'Lebar gambar saat ini '.$record?->sideWidth().' px. Di layar besar gambar akan tampak kurang tajam; unggah versi minimal '.FleetVehicle::MIN_SIDE_WIDTH.' px jika ada.')
                            ->warning()
                            ->visible(fn (?FleetVehicle $record): bool => ($width = $record?->sideWidth()) !== null && $width < FleetVehicle::MIN_SIDE_WIDTH),
                        ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('side'), '2400 x 1350 px')
                            ->label('Gambar tampak samping')
                            ->collection('side')
                            ->image()
                            ->acceptedFileTypes(SiteSetting::LOGO_MIMES)
                            ->maxSize(5120)
                            ->helperText('Latar abu-abu polos, truk menghadap kiri. Minimal '.FleetVehicle::MIN_SIDE_WIDTH.' px lebarnya. File tidak diubah; potongan tampilan diatur di bawah.'),
                        Grid::make(3)->schema([
                            TextInput::make('focus_x')->label('Titik fokus horizontal')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(50),
                            TextInput::make('focus_y')->label('Titik fokus vertikal')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(50),
                            TextInput::make('zoom')->label('Perbesaran tampilan')->numeric()->minValue(100)->maxValue(200)->suffix('%')->default(100),
                        ]),
                        ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('photos'), '1600 x 1200 px')
                            ->label('Foto tambahan')
                            ->collection('photos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(8)
                            ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                            ->maxSize(5120),
                    ]),
            ]);
    }
}
