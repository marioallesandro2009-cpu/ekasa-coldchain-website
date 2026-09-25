<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Omnichannel Integration gets the new "network" icon (batch 2) instead of
     * "route". Only when it still has the old default, so an admin choice stays.
     */
    public function up(): void
    {
        DB::table('services')->where('slug', 'omnichannel-integration')->where('icon', 'route')->update(['icon' => 'network']);
    }

    public function down(): void
    {
        DB::table('services')->where('slug', 'omnichannel-integration')->where('icon', 'network')->update(['icon' => 'route']);
    }
};
