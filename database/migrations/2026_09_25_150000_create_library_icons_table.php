<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin icon library (Pustaka Ikon): the Ekasa stock from
     * resources/icons/ekasa plus SVG icons uploaded in the admin.
     */
    public function up(): void
    {
        Schema::create('library_icons', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('label_en', 100);
            $table->string('label_id', 100)->nullable();
            $table->string('category', 40);
            $table->text('svg');
            $table->string('source', 20)->default('upload');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_icons');
    }
};
