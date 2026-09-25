<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public const LABELS = ['question' => 'Pertanyaan', 'answer' => 'Jawaban'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Sample::notice(),
                Section::make('Pertanyaan')
                    ->columnSpanFull()
                    ->description('Pilih pertanyaan per layanan lewat blok FAQ di halaman detail.')
                    ->schema([
                        Translatable::fields(fn (string $locale): TextInput => TextInput::make("question.{$locale}")
                            ->label(Translatable::label('Pertanyaan', $locale))
                            ->required(Translatable::isRequired($locale))
                            ->maxLength(160)),
                        Translatable::fields(fn (string $locale): Textarea => Textarea::make("answer.{$locale}")
                            ->label(Translatable::label('Jawaban', $locale))
                            ->required(Translatable::isRequired($locale))
                            ->rows(4)
                            ->maxLength(1000)),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),
            ]);
    }
}
