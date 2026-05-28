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
        Schema::create('rejected_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_schedule')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('id_daily_program')->constrained('daily_programs')->onDelete('cascade');
            $table->foreignId('id_work_center')->constrained('work_centers');
            $table->foreignId('id_production_line')->constrained('production_lines');
            
            // Información del rechazo
            $table->integer('quantity')->comment('Cantidad de piezas rechazadas');
            $table->text('rejection_reason')->nullable()->comment('Motivo del rechazo');
            $table->foreignId('rejected_by')->constrained('users')->comment('Quién rechazó (Calidad)');
            $table->timestamp('rejected_at')->nullable();
            
            // Información de resolución
            $table->enum('resolution_status', ['pendiente', 'reparada', 'reemplazada', 'desechada'])
                  ->default('pendiente')
                  ->comment('Estado de resolución del rechazo');
            $table->text('resolution_notes')->nullable()->comment('Notas de resolución');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->comment('Quién resolvió (Supervisor/Calidad)');
            $table->timestamp('resolved_at')->nullable();
            
            // Información de reemplazo (si se hicieron piezas nuevas)
            $table->integer('new_pieces_quantity')->default(0)->comment('Cantidad de piezas nuevas hechas');
            $table->foreignId('new_pieces_schedule_id')->nullable()->constrained('schedules')
                  ->comment('Schedule donde se hicieron las piezas nuevas');
            
            $table->timestamps();
            
            $table->index(['id_daily_program', 'resolution_status']);
            $table->index(['id_work_center', 'rejected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rejected_pieces');
    }
};
