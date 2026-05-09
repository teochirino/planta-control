<?php
// database/migrations/2024_xx_xx_xxxxxx_create_user_work_centers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_center_id')->constrained('work_centers')->onDelete('cascade');
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['user_id', 'work_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_work_centers');
    }
};