<?php

namespace App\Filament\Resources\HeroSlides\Tables;

use App\Filament\Resources\HeroSlides\Schemas\HeroSlideForm;
use App\Filament\Support\Translatable;
use App\Support\FrontendData;
use App\Support\ImageConversions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HeroSlidesTable
{
    public static function configure(Table $table): Table
    {
        $preview = SpatieMediaLibraryImageColumn::make('image')
            ->label('Foto')
            ->collection('image')
            ->imageWidth(128)
            ->imageHeight(72);

        if (ImageConversions::enabled()) {
            $preview->conversion('sm');
        }

        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                $preview,
                Translatable::column('label', 'Label')
                    ->weight('medium'),
                Translatable::column('image_alt', 'Alt foto')
                    ->limit(50)
                    ->color('gray'),
                ...Translatable::statusColumns(HeroSlideForm::LABELS),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
