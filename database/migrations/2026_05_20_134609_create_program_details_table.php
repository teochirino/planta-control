<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('program_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
            $table->string('modelo', 20)->comment('Modelo del producto');
            $table->integer('cantidad_solicitada')->unsigned()->comment('Cantidad solicitada');
            $table->timestamps();
            
            $table->index(['program_id', 'modelo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_details');
    }
};
