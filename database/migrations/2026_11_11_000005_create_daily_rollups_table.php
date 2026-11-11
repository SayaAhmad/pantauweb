<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->unsignedInteger('total_checks')->default(0);
            $table->unsignedInteger('successful_checks')->default(0);
            $table->unsignedInteger('avg_response_ms')->nullable();
            $table->unsignedInteger('max_response_ms')->nullable();
            $table->timestamps();

            $table->unique(['monitor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_rollups');
    }
};
