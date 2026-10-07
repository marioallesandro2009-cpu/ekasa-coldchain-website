<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Cabut akses saat seseorang keluar dari perusahaan: ia langsung keluar dari panel, sesi login-nya berakhir, dan emailnya dihapus dari daftar penerima pesan. Email dan file Excel yang sudah ia terima di kotak suratnya sendiri tidak bisa ditarik kembali dari sini.';
    }
}
