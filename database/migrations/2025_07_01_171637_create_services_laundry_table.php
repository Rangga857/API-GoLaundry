<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('services_laundry', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('sub_title');
            $table->decimal('price_per_kg', 10, 2);
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('services_laundry');
    }
};
