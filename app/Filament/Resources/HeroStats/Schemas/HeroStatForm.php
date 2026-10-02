<?php

namespace App\Filament\Resources\HeroStats\Schemas;

use App\Filament\Support\Translatable;
use App\Support\IconLibrary;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HeroStatForm
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['label' => 'Label', 'note' => 'Catatan', 'text_value' => 'Nilai teks'];

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
                            ->helperText('Contoh: "Delivery Points".')
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(60)),
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("note.{$locale}")
                            ->label(Translatable::label('Catatan kecil', $locale))
                            ->helperText('Opsional, tampil kecil di bawah label. Contoh: "Dapat disesuaikan dengan vendor".')
                            ->maxLength(80)),
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
                            ->helperText('Untuk nilai non-angka, misalnya "-25°C to 6°C". Dipakai jika nilai angka kosong.')
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
