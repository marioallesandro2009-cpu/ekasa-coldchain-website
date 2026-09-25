<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Lucide icon names used before => closest original Ekasa icon. */
    private const MAP = [
        'truck' => 'reefer-truck',
        'network' => 'route',
        'workflow' => 'route',
        'globe' => 'route',
        'refrigerator' => 'cold-cabinet',
        'package' => 'box',
        'package-check' => 'box',
        'shopping-cart' => 'box',
        'layers' => 'boxes',
        'store' => 'office-building',
        'building-2' => 'office-building',
        'zap' => 'service-24h',
    ];

    /**
     * Services and hero statistics switch from Lucide to the original Ekasa icons.
     * The handshake stays in the stock but is no longer a default: Logistic
     * Partnership gets the chain link, the "Trusted Brands" statistic the badge.
     */
    public function up(): void
    {
        foreach (['services', 'hero_stats'] as $table) {
            foreach (self::MAP as $from => $to) {
                DB::table($table)->where('icon', $from)->update(['icon' => $to]);
            }
        }

        DB::table('services')->where('icon', 'handshake')->update(['icon' => 'chain-link']);
        DB::table('hero_stats')->where('icon', 'handshake')->update(['icon' => 'badge']);
    }

    public function down(): void
    {
        // The Lucide content icons are removed; nothing to restore.
    }
};
