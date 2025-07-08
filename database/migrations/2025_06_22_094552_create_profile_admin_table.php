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
        Schema::create('profile_admin', function (Blueprint $table) {
            $table->id('laundry_id');
            $table->unsignedBigInteger('user_id'); 
            $table->string('name'); 
            $table->text('address');    
            $table->decimal('latitude', 10, 6)->nullable();  
            $table->decimal('longitude', 10, 6)->nullable();
            $table->timestamps();  

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_admin');
    }
};
