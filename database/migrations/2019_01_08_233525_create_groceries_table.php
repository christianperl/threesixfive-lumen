<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroceriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groceries', function (Blueprint $table) {
            $table->increments('pk_grocery_id');
            $table->unsignedInteger('fk_user_id');
            $table->foreign('fk_user_id')->references('pk_user_id')->on('users')->onDelete('cascade');
            $table->String('name');
            $table->date('day')->nullable();
            $table->boolean('checked');
            $table->boolean('generated');
            $table->double('serving');
            $table->String('measurement');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groceries');
    }
}
