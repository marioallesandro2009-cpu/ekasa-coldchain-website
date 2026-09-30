<?php

namespace App\Filament\Resources\AboutValues\Schemas;

use App\Filament\Support\Translatable;
use App\Support\IconLibrary;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AboutValueForm
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['title' => 'Judul', 'description' => 'Deskripsi'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nilai / Keunggulan')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('icon')
                            ->label('Ikon')
                            ->options(fn (): array => IconLibrary::htmlOptions())
                            ->allowHtml()
                            ->searchable()
                            ->getSearchResultsUsing(fn (?string $search): array => IconLibrary::htmlOptions($search))
                            ->getOptionLabelUsing(fn (?string $value): string => $value ? IconLibrary::optionHtml($value) : '')
                            ->placeholder('Tanpa ikon')
                            ->columnSpanFull()
                            ->helperText('Dari Konten Beranda › Pustaka Ikon (hanya ikon aktif).'),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                            ->label(Translatable::label('Judul', $locale))
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(80)),
                        Translatable::fields(fn (string $locale): Textarea => Textarea::make("description.{$locale}")
                            ->label(Translatable::label('Deskripsi', $locale))
                            ->rows(3)
                            ->maxLength(200)),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
