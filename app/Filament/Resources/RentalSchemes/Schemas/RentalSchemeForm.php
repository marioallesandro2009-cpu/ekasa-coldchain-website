<?php

namespace App\Filament\Resources\RentalSchemes\Schemas;

use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\RentalScheme;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RentalSchemeForm
{
    public const LABELS = ['name' => 'Nama', 'description' => 'Deskripsi', 'includes' => 'Termasuk'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Sample::notice(),
                Section::make('Skema sewa')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("name.{$locale}")
                            ->label(Translatable::label('Nama', $locale))
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(60)),
                        Translatable::fields(fn (string $locale): Textarea => Textarea::make("description.{$locale}")
                            ->label(Translatable::label('Deskripsi', $locale))
                            ->rows(2)
                            ->maxLength(240)),
                        Translatable::fields(fn (string $locale): Textarea => Textarea::make("includes.{$locale}")
                            ->label(Translatable::label('Termasuk', $locale))
                            ->rows(4)
                            ->helperText('Satu item per baris, misalnya "Sopir berpengalaman".')),
                        CheckboxList::make('durations')
                            ->label('Durasi')
                            ->options(array_map(fn (array $label): string => $label[1], RentalScheme::DURATIONS))
                            ->columns(3),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),
            ]);
    }
}
