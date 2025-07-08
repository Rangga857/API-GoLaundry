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
        Schema::table('pemasukan', function (Blueprint $table) {
            // Hapus foreign key constraint terlebih dahulu jika ada
            $table->dropForeign(['transaction_id']);
            $table->dropColumn(['type', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemasukan', function (Blueprint $table) {
            // Tambahkan kembali kolom jika di-rollback (sesuaikan dengan definisi asli Anda)
            $table->enum('type', ['offline', 'online'])->after('id');
            $table->unsignedBigInteger('transaction_id')->nullable()->unique()->after('type');
            // Tambahkan kembali foreign key constraint jika diperlukan
            $table->foreign('transaction_id')->references('id')->on('confirmation_payments')->onDelete('set null');
        });
    }
};