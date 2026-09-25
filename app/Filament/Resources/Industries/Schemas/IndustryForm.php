<?php

namespace App\Filament\Resources\Industries\Schemas;

use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Support\IconLibrary;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IndustryForm
{
    public const LABELS = ['name' => 'Nama', 'description' => 'Deskripsi'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Sample::notice(),
                Section::make('Industri')
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
                            ->maxLength(200)),
                        Select::make('icon')
                            ->label('Ikon')
                            ->options(fn (): array => IconLibrary::htmlOptions())
                            ->allowHtml()
                            ->searchable()
                            ->getSearchResultsUsing(fn (?string $search): array => IconLibrary::htmlOptions($search))
                            ->getOptionLabelUsing(fn (?string $value): string => $value ? IconLibrary::optionHtml($value) : ''),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),
            ]);
    }
}
