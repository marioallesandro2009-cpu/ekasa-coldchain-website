<?php

namespace App\Filament\Resources\TransportRoutes\Schemas;

use App\Filament\Support\Sample;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransportRouteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Sample::notice(),
                Section::make('Rute')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('origin')->label('Kota asal')->required()->maxLength(100)->default('Jakarta'),
                        TextInput::make('destination')->label('Kota tujuan')->required()->maxLength(100),
                        TextInput::make('eta_hours_min')->label('Estimasi tercepat')->numeric()->minValue(1)->suffix('jam'),
                        TextInput::make('eta_hours_max')->label('Estimasi terlama')->numeric()->minValue(1)->suffix('jam')->gte('eta_hours_min'),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),
            ]);
    }
}
