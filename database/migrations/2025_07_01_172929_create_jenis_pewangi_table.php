<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jenis_pewangi', function (Blueprint $table) {
            $table->id(); // ID Jenis Pewangi (Primary Key)
            $table->string('nama');
            $table->string('deskripsi');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jenis_pewangi');
    }
};