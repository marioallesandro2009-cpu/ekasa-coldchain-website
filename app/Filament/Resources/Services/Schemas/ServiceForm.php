<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Support\Translatable;
use App\Support\IconLibrary;
use App\Support\ServiceIcons;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['title' => 'Judul', 'short_description' => 'Deskripsi singkat'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail layanan')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                            ->label(Translatable::label('Judul', $locale))
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(100)
                            ->when(Translatable::isRequired($locale), fn (TextInput $input): TextInput => $input
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug((string) $state));
                                        $set('anchor', Str::slug((string) $state));
                                    }
                                }))),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("short_description.{$locale}")
                            ->label(Translatable::label('Deskripsi singkat', $locale))
                            ->maxLength(60)
                            ->helperText('Maks. 60 karakter, tampil di mega menu.')),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(120)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
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
                        TextInput::make('anchor')
                            ->label('Anchor tujuan')
                            ->prefix('#')
                            ->maxLength(120)
                            ->helperText('ID section tujuan di halaman depan. Jika section belum ada, tautan menuju #services.'),
                        Toggle::make('is_featured')
                            ->label('Layanan utama di mega menu')
                            ->helperText('Aktif: tampil di grid 2 kolom. Nonaktif: tampil di baris bawah, di bawah garis pemisah.')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
