<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();

            $table->index(['monitor_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_results');
    }
};
