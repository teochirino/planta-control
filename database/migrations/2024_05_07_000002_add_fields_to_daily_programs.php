<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_programs', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_programs', 'total_produced')) {
                $table->integer('total_produced')->default(0)->after('advanced')
                    ->comment('Acumulador de piezas producidas en el turno');
            }
            if (!Schema::hasColumn('daily_programs', 'shift_hours')) {
                $table->decimal('shift_hours', 4, 2)->default(9.0)->after('total_produced')
                    ->comment('Horas del turno');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_programs', function (Blueprint $table) {
            $table->dropColumn(['total_produced', 'shift_hours']);
        });
    }
};
