<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('ID autoincremental');
            $table->string('modelo', 20);
            $table->foreignId('id_work_center')->constrained('work_centers')->onDelete('cascade');
            $table->decimal('tiempo', 8, 5)->comment('Tiempo con 5 decimales');
            $table->integer('piezas')->unsigned();
            $table->timestamps();
            
            $table->index(['modelo', 'id_work_center']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
