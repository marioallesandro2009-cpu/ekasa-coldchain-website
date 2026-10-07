<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mencabut akses admin tanpa menghapus akun (jejak audit tetap ada): access_revoked_at terisi =
     * akses dicabut (bisa dipulihkan); access_expires_at = akses otomatis berakhir pada waktu itu.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('access_revoked_at')->nullable()->after('is_admin');
            $table->timestamp('access_expires_at')->nullable()->after('access_revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['access_revoked_at', 'access_expires_at']);
        });
    }
};
