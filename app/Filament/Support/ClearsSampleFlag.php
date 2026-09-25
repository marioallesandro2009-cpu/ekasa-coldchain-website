<?php

namespace App\Filament\Support;

use Filament\Resources\Pages\EditRecord;

/**
 * For edit pages of models with is_sample: saving from the admin means the row
 * has been looked at, so the "Contoh" marker goes away.
 *
 * @mixin EditRecord
 */
trait ClearsSampleFlag
{
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record->getAttribute('is_sample')) {
            $record->forceFill(['is_sample' => false])->saveQuietly();
        }
    }
}
