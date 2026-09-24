<?php

namespace App\Filament\Resources\HeroSlides\Schemas;

use App\Filament\Support\Translatable;
use App\Models\SiteSetting;
use App\Support\ImageUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HeroSlideForm
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['label' => 'Label layanan', 'image_alt' => 'Teks alternatif foto'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Slide')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("label.{$locale}")
                            ->label(Translatable::label('Label layanan', $locale))
                            ->helperText('Teks yang berganti di headline hero, misalnya "Cold Storage".')
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(40)),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("image_alt.{$locale}")
                            ->label(Translatable::label('Teks alternatif foto (alt)', $locale))
                            ->helperText('Deskripsi singkat isi foto untuk pembaca layar dan SEO.')
                            ->maxLength(160)),
                        ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('image'), '1920 x 1080 px')
                            ->label('Foto latar')
                            ->collection('image')
                            ->image()
                            ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Ukuran ideal 1920 x 1080 px (landscape). JPG, PNG, atau WebP, maks. 5 MB dan sisi terpanjang maks. 4000 px.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
