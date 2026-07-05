<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuti_applications', function (Blueprint $table) {
            $table->date('tanggal_mulai')->nullable()->after('nomor_kerabat');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->foreignId('finalized_by_pengasuh')->nullable()->after('approved_at')->constrained('users');
            $table->timestamp('finalized_at')->nullable()->after('finalized_by_pengasuh');
        });

        Schema::table('cuti_applications', function (Blueprint $table) {
            // Tambah status disetujui_ortu untuk alur approval dua tahap (ortu -> pengasuh)
            $table->enum('status', ['pending', 'disetujui_ortu', 'disetujui', 'ditolak'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuti_applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending')->change();
        });

        Schema::table('cuti_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalized_by_pengasuh');
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai', 'finalized_at']);
        });
    }
};
