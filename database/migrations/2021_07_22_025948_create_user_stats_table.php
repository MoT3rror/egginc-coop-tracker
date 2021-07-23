<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->string('soul_eggs')->default('0');
            $table->integer('prestige_eggs')->default(0);
            $table->integer('golden_eggs')->default(0);
            $table->integer('prestiges')->default(0);
            $table->integer('drones')->default(0);
            $table->integer('elite_drones')->default(0);
            $table->integer('user_id')->unsigned();
            $table->dateTime('record_time');
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
        Schema::dropIfExists('user_stats');
    }
}
