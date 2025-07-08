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
        Schema::table('confirmation_payments', function (Blueprint $table) {
            $table->dropColumn('is_recorded_as_income');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confirmation_payments', function (Blueprint $table) {
            // Tambahkan kembali kolom jika di-rollback
            $table->boolean('is_recorded_as_income')->default(false)->after('keterangan');
        });
    }
};