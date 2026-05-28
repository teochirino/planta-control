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
        Schema::create('work_center_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_work_center')->constrained('work_centers')->onDelete('cascade');
            $table->integer('accumulated_backwardness')->default(0)->comment('Atraso acumulado del centro de trabajo');
            $table->integer('accumulated_advanced')->default(0)->comment('Adelanto acumulado del centro de trabajo');
            $table->timestamp('last_calculated_at')->nullable()->comment('Última vez que se calculó el balance');
            $table->timestamps();
            
            $table->unique('id_work_center');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_balances');
    }
};
