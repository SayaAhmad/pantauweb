<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->unsignedBigInteger('duration_seconds')->nullable();
            $table->text('cause')->nullable();
            $table->timestamps();

            $table->index(['monitor_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
