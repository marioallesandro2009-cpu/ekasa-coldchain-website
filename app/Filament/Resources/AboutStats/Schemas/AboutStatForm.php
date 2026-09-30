<?php

namespace App\Filament\Resources\AboutStats\Schemas;

use App\Filament\Support\Translatable;
use App\Support\IconLibrary;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AboutStatForm
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['label' => 'Label', 'text_value' => 'Nilai teks'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Statistik')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("label.{$locale}")
                            ->label(Translatable::label('Label', $locale))
                            ->helperText('Contoh: "Years in Operation".')
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(60)),
                        Select::make('icon')
                            ->label('Ikon')
                            ->options(fn (): array => IconLibrary::htmlOptions())
                            ->allowHtml()
                            ->searchable()
                            ->getSearchResultsUsing(fn (?string $search): array => IconLibrary::htmlOptions($search))
                            ->getOptionLabelUsing(fn (?string $value): string => $value ? IconLibrary::optionHtml($value) : '')
                            ->placeholder('Tanpa ikon')
                            ->helperText('Tampil di atas angka. Dari Konten Beranda › Pustaka Ikon (hanya ikon aktif).'),
                        TextInput::make('prefix')
                            ->label('Prefiks')
                            ->maxLength(16)
                            ->placeholder('Rp'),
                        TextInput::make('value')
                            ->label('Nilai angka')
                            ->numeric()
                            ->requiredWithout('text_value.en')
                            ->validationMessages(['required_without' => 'Isi nilai angka atau nilai teks (EN).']),
                        TextInput::make('suffix')
                            ->label('Sufiks')
                            ->maxLength(16)
                            ->placeholder('+'),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("text_value.{$locale}")
                            ->label(Translatable::label('Nilai teks', $locale))
                            ->helperText('Untuk nilai non-angka. Dipakai jika nilai angka kosong.')
                            ->maxLength(60)),
                        Toggle::make('use_count_up')
                            ->label('Animasi hitung naik (Count Up)')
                            ->helperText('Hanya berlaku untuk nilai angka.')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
