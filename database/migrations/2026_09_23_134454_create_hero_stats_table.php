<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_stats', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 12, 2)->nullable();
            $table->string('prefix', 16)->nullable();
            $table->string('suffix', 16)->nullable();
            $table->string('text_value', 60)->nullable();
            $table->string('label', 60);
            $table->boolean('use_count_up')->default(true);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_stats');
    }
};
