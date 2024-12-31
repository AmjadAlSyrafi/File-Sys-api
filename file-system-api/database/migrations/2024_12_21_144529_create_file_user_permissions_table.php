<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileUserPermissionsTable extends Migration
{
    public function up()
    {
        Schema::create('file_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('permissions'); // e.g., 'read', 'write', 'full_access', 'private'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_user_permissions');
    }
}
