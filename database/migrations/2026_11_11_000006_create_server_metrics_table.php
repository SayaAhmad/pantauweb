<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->decimal('cpu_percent', 5, 2)->nullable();
            $table->decimal('ram_percent', 5, 2)->nullable();
            $table->decimal('disk_percent', 5, 2)->nullable();
            $table->decimal('load_average', 8, 2)->nullable();
            $table->timestamp('collected_at')->index();
            $table->timestamps();

            $table->index(['server_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
