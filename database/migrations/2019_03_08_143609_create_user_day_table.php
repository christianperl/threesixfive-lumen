<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_days', function (Blueprint $table) {
            $table->unsignedInteger('pk_fk_user_id');
            $table->String('weekday');
            $table->boolean('breakfast');
            $table->boolean('lunch');
            $table->boolean('main_dish');
            $table->boolean('snack');
        });

        DB::unprepared('ALTER TABLE user_days ADD PRIMARY KEY (pk_fk_user_id, weekday)');
        DB::unprepared('ALTER TABLE user_days ADD FOREIGN KEY (pk_fk_user_id) REFERENCES users ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_days');
    }
}
