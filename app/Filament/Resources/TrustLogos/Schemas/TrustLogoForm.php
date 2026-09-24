<?php

namespace App\Filament\Resources\TrustLogos\Schemas;

use App\Models\SiteSetting;
use App\Support\ImageUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrustLogoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Logo klien')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama klien')
                            ->helperText('Dipakai sebagai teks alternatif logo (pembaca layar dan SEO).')
                            ->required()
                            ->maxLength(120),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false),
                        ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('logo'), 'tinggi 48-96 px')
                            ->label('Logo')
                            ->collection('logo')
                            ->acceptedFileTypes(SiteSetting::LOGO_MIMES)
                            ->maxSize(1024)
                            ->required()
                            ->helperText('SVG atau PNG transparan, sebaiknya versi putih. Tampil 24 px tinggi dan otomatis dibuat putih agar seragam. Maks. 1 MB.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
