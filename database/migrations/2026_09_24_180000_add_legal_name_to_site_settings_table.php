<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registered company name (e.g. "PT ..."): used in the footer copyright and
     * JSON-LD legalName; the brand name (company_name) is used everywhere else.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('legal_name');
        });
    }
};
