<?php
// database/migrations/2024_xx_xx_xxxxxx_create_user_production_lines_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_production_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['user_id', 'production_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_production_lines');
    }
};