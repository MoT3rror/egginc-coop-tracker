<?php

use App\Models\Guild;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GuildsRoleToAddToCoopToJson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('guilds', function (Blueprint $table) {
            $guilds = Guild::all();
            foreach ($guilds as $guild) {
                if ($guild->role_to_add_to_coop) {
                    $guild->role_to_add_to_coop = [(string) $guild->role_to_add_to_coop];
                    $guild->save();
                }
            }
            
            $table->json('role_to_add_to_coop')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('guilds', function (Blueprint $table) {
            //
        });
    }
}
