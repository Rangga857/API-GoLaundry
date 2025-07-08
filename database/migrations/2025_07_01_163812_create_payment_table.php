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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->enum('metode_pembayaran', ['cash', 'bank_transfer']);
            $table->string('bukti_pembayaran', 255);
            $table->enum('status', ['pending', 'confirmed', 'not confirmed'])->default('pending'); 
            $table->unsignedBigInteger('confirmation_payment_id');
            $table->timestamps();

            $table->foreign('confirmation_payment_id')->references('id')->on('confirmation_payments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
