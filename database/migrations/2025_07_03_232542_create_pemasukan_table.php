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
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['offline', 'online']); 
            $table->unsignedBigInteger('transaction_id')->nullable()->unique(); 
            $table->decimal('amount', 15, 2); 
            $table->text('description')->nullable(); 
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
            $table->foreign('transaction_id')->references('id')->on('confirmation_payments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemasukan');
    }
};
