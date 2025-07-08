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
        Schema::create('comments', function (Blueprint $table) {
            $table->id(); // Ini akan membuat kolom 'id' BIGINT UNSIGNED dan auto-increment

            // Foreign Key ke orders_laundries
            $table->unsignedBigInteger('order_id')->unique(); // Unique agar 1 order hanya punya 1 komentar
            $table->foreign('order_id')->references('id')->on('orders_laundries')->onDelete('cascade');

            // Foreign Key ke users
            // Asumsi primary key di tabel 'users' adalah 'user_id'
            $table->unsignedBigInteger('user_id'); 
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');

            $table->text('comment_text'); // Kolom untuk isi komentar

            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
