// database/migrations/2024_01_01_000009_create_schedules_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_daily_program')->constrained('daily_programs')->onDelete('cascade');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('produced')->default(0)->comment('Piezas producidas en esta hora');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};