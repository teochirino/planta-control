<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campos de rechazo a la tabla schedules
        Schema::table('schedules', function (Blueprint $table) {
            $table->integer('rejected')->default(0)->after('produced');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            
            // Foreign key a la tabla users
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });
        
        // Agregar campo de total de rechazos a la tabla daily_programs
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->integer('total_rejected')->default(0)->after('total_produced');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['rejected', 'rejected_by', 'rejected_at']);
        });
        
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->dropColumn('total_rejected');
        });
    }
};
