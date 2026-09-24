<?php

namespace App\Filament\Resources\TrustLogos\Pages;

use App\Filament\Resources\TrustLogos\TrustLogoResource;
use App\Models\TrustLogo;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrustLogos extends ListRecords
{
    protected static string $resource = TrustLogoResource::class;

    public function getSubheading(): ?string
    {
        return 'Tampil di hero di bawah tombol CTA. Maks. '.TrustLogo::MAX_VISIBLE.' logo aktif teratas yang ditampilkan; seret untuk mengubah urutan. Jika tidak ada logo aktif, baris ini disembunyikan.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
