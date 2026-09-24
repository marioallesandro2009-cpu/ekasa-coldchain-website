<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_featured: true = main 2-column grid of the mega menu,
     * false = full-width row below the divider.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_featured')->default(true)->after('anchor');
        });

        DB::table('services')->where('slug', 'omnichannel-integration')->update(['is_featured' => false]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
