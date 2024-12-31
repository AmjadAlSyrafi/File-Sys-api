<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFolderUserPermissionsTable extends Migration
{
    public function up()
    {
        Schema::create('folder_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('permissions')->default('read'); // read, write, full_access
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('folder_user_permissions');
    }
}
