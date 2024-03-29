<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('pk_user_id');
            $table->string('firstName');
            $table->string('lastName');
            $table->integer('persons')->nullable();
            $table->string('email')->unique();
            $table->string('password');
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
        Schema::dropIfExists('plans');
        Schema::dropIfExists('groceries');
        Schema::dropIfExists('nogos');
        Schema::dropIfExists('user_diet');
        Schema::dropIfExists('user_days');
        Schema::dropIfExists('users');
    }
}
