<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type', 20)->index();

            // Konfigurasi per jenis
            $table->string('url', 2048)->nullable();
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('method', 10)->default('GET');
            $table->unsignedSmallInteger('expected_status')->nullable();
            $table->string('expected_keyword')->nullable();

            // Penjadwalan dan ambang
            $table->unsignedInteger('interval_seconds')->default(300);
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->unsignedSmallInteger('confirmation_threshold')->default(2);
            $table->unsignedSmallInteger('recovery_threshold')->default(2);
            $table->boolean('is_active')->default(true)->index();

            // Status terkonfirmasi dan rentetan pemeriksaan
            $table->string('status', 20)->default('unknown')->index();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->unsignedInteger('consecutive_successes')->default(0);
            $table->timestamp('failure_streak_started_at')->nullable();
            $table->timestamp('recovery_streak_started_at')->nullable();

            // Ringkasan pemeriksaan terakhir
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable()->index();
            $table->unsignedInteger('last_response_ms')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->text('last_error')->nullable();

            // SSL
            $table->timestamp('ssl_expires_at')->nullable();
            $table->string('ssl_issuer')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
