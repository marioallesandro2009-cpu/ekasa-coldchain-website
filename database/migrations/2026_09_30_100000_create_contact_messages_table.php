<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 40)->nullable();
            $table->string('company', 150)->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            // Copy of the service title at submission time, so history stays readable if the service is removed.
            $table->string('service_title')->nullable();
            $table->text('message');
            $table->string('locale', 5);
            $table->string('status', 10)->default('new')->index();
            $table->boolean('email_failed')->default(false);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->json('contact_recipients')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('contact_recipients');
        });

        Schema::dropIfExists('contact_messages');
    }
};
