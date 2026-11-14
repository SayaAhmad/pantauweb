<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Data lama belum punya pemilik. Berikan ke pengguna biasa pertama yang
        // sudah ada agar riwayat monitoring tidak hilang setelah migrasi.
        $legacyOwnerId = DB::table('users')
            ->where('is_admin', false)
            ->orderBy('id')
            ->value('id');

        if ($legacyOwnerId !== null) {
            DB::table('servers')->whereNull('user_id')->update(['user_id' => $legacyOwnerId]);
            DB::table('monitors')->whereNull('user_id')->update(['user_id' => $legacyOwnerId]);
        }
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
