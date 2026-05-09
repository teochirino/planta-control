// database/migrations/2024_01_01_000005_create_machines_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_production_lines')->constrained('production_lines')->onDelete('cascade');
            $table->string('title');
            $table->enum('state', ['operativo', 'mantenimiento', 'averiado'])->default('operativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};