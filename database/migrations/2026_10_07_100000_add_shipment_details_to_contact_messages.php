<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permintaan klien: form Kontak juga menanyakan kategori barang, jumlah (ton), ukuran kontainer
     * (20 atau 40 feet), dan negara tujuan pengiriman. Semuanya opsional.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->string('category', 30)->nullable()->after('service_title');
            $table->decimal('quantity_tons', 10, 2)->nullable()->after('category');
            $table->string('container_size', 10)->nullable()->after('quantity_tons');
            $table->string('destination_country', 80)->nullable()->after('container_size');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropColumn(['category', 'quantity_tons', 'container_size', 'destination_country']);
        });
    }
};
