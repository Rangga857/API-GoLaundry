<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
{
    Schema::create('orders_laundries', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('id_profile');  
        $table->unsignedBigInteger('jenis_pewangi_id');      
        $table->unsignedBigInteger('service_id');           
        $table->enum('status', [
            'pending', 
            'menuju lokasi', 
            'proses penimbangan', 
        ])->default('pending'); 
        $table->timestamps();

        $table->foreign('id_profile')->references('id_profile')->on('profiles')->onDelete('cascade');
        $table->foreign('jenis_pewangi_id')->references('id')->on('jenis_pewangi')->onDelete('cascade');
        $table->foreign('service_id')->references('id')->on('services_laundry')->onDelete('cascade');
    });
}

public function down()
{
    Schema::dropIfExists('orders_laundries');
}

};
