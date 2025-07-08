<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('confirmation_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('id_profile'); 
            $table->unsignedBigInteger('orders_id');
            $table->decimal('total_weight', 10, 2);
            $table->decimal('total_ongkir', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('keterangan');
            $table->timestamps();

            $table->foreign('orders_id')->references('id')->on('orders_laundries')->onDelete('cascade');
            $table->foreign('id_profile')->references('id_profile')->on('profiles')->onDelete('cascade');
            $table->foreign('admin_id')->references('admin_id')->on('profile_admin')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('confirmation_payments');
    }
};
