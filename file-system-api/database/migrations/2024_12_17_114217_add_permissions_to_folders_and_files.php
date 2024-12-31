<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermissionsToFoldersAndFiles extends Migration
{
    public function up()
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->string('permissions')->default('read'); // Default permission
        });

        Schema::table('files', function (Blueprint $table) {
            $table->string('permissions')->default('read'); // Default permission
        });
    }

    public function down()
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
}
