// database/migrations/2024_01_01_000008_create_daily_programs_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_programs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('id_production_lines')->constrained('production_lines')->onDelete('cascade');
            $table->enum('shift', ['matutino', 'vespertino', 'nocturno']);
            $table->integer('programmed')->comment('Piezas programadas');
            $table->integer('backwardness')->default(0)->comment('Retraso');
            $table->integer('advanced')->default(0)->comment('Adelanto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_programs');
    }
};