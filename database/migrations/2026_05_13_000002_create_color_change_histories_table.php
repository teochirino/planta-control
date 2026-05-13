<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('color_change_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_work_center');
            $table->unsignedBigInteger('id_attribute');
            $table->unsignedBigInteger('user_id');
            $table->string('previous_color', 255);
            $table->string('new_color', 255);
            $table->string('comment', 100)->nullable();
            $table->timestamps();
            
            $table->foreign('id_work_center')
                  ->references('id')
                  ->on('work_centers')
                  ->onDelete('cascade');
                  
            $table->foreign('id_attribute')
                  ->references('id')
                  ->on('attributes')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('color_change_histories');
    }
};
