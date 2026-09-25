<?php

namespace App\Filament\Resources\LibraryIcons\Pages;

use App\Filament\Resources\LibraryIcons\LibraryIconResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLibraryIcons extends ListRecords
{
    protected static string $resource = LibraryIconResource::class;

    public function getSubheading(): ?string
    {
        return 'Ikon yang bisa dipilih untuk Layanan dan Statistik Hero. Ikon Ekasa digambar khusus untuk situs ini; ikon nonaktif tidak muncul di pilihan, tetapi tetap tampil di konten yang sudah memakainya. Ikon batch 2 masih nonaktif sampai disetujui.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Unggah ikon SVG'),
        ];
    }
}
