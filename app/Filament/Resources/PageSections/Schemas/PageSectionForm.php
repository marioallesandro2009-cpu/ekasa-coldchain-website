<?php

namespace App\Filament\Resources\PageSections\Schemas;

use App\Filament\Support\Translatable;
use App\Models\PageSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageSectionForm
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['nav_label' => 'Label menu', 'eyebrow' => 'Eyebrow', 'title' => 'Judul', 'description' => 'Deskripsi'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Menu navbar')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("nav_label.{$locale}")
                            ->label(Translatable::label('Label menu', $locale))
                            ->maxLength(40)
                            ->helperText('Jika kosong, dipakai teks kecil (eyebrow) atau judul.')),
                        Toggle::make('show_in_nav')
                            ->label('Tampilkan di menu navbar')
                            ->inline(false),
                    ]),
                Section::make('Teks section')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("eyebrow.{$locale}")
                            ->label(Translatable::label('Teks kecil di atas judul (eyebrow)', $locale))
                            ->maxLength(60)),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                            ->label(Translatable::label('Judul section', $locale))
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(160)),
                        Translatable::fields(fn (string $locale): Textarea => Textarea::make("description.{$locale}")
                            ->label(Translatable::label('Deskripsi', $locale))
                            ->rows(3)
                            ->maxLength(500)),
                        Select::make('background')
                            ->label('Warna latar')
                            ->options(PageSection::BACKGROUNDS)
                            ->required()
                            ->native(false),
                        TextInput::make('key')
                            ->label('ID section (anchor)')
                            ->prefix('#')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Tetap, dipakai tautan menu dan tombol.'),
                        Toggle::make('is_active')
                            ->label('Aktif (tampil di halaman)'),
                    ]),
            ]);
    }
}
