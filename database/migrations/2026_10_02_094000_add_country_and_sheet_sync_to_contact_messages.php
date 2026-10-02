<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revisi 1 (klien): kolom negara di form Kontak, dan penanda sudah/belum terkirim ke Google Sheet
     * (sheet_synced_at kosong = belum; perintah leads:sync-sheet mengirim ulang yang tertinggal).
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->string('country', 80)->nullable()->after('company');
            $table->timestamp('sheet_synced_at')->nullable()->after('email_failed');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropColumn(['country', 'sheet_synced_at']);
        });
    }
};
