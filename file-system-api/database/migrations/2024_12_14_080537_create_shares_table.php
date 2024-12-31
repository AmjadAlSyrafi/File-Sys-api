<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type'); // 'file' or 'folder'
            $table->unsignedBigInteger('resource_id');
            $table->foreignId('shared_with_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permissions', ['view', 'edit'])->default('view');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
