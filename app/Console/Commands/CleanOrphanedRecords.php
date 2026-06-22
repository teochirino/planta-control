<?php

namespace App\Console\Commands;

use App\Models\DailyProgram;
use App\Models\Program;
use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOrphanedRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-orphaned-records {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar registros huérfanos en daily_programs y schedules que no tienen programas asociados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando registros huérfanos...');

        // Contar schedules huérfanos (sin daily_program válido)
        $orphanedSchedules = DB::table('schedules')
            ->leftJoin('daily_programs', 'schedules.id_daily_program', '=', 'daily_programs.id')
            ->whereNull('daily_programs.id')
            ->count();

        // Contar daily_programs huérfanos (sin program asociado o con program_id NULL)
        $orphanedDailyPrograms = DB::table('daily_programs')
            ->leftJoin('programs', 'daily_programs.program_id', '=', 'programs.id')
            ->where(function($query) {
                $query->whereNull('programs.id')
                      ->orWhereNull('daily_programs.program_id');
            })
            ->count();

        $this->info("Schedules huérfanos encontrados: {$orphanedSchedules}");
        $this->info("Daily programs huérfanos encontrados: {$orphanedDailyPrograms}");

        if ($orphanedSchedules === 0 && $orphanedDailyPrograms === 0) {
            $this->info('No se encontraron registros huérfanos. Nada que limpiar.');
            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('¿Deseas eliminar estos registros huérfanos?')) {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();

        try {
            // Eliminar schedules huérfanos
            if ($orphanedSchedules > 0) {
                $deletedSchedules = DB::table('schedules')
                    ->leftJoin('daily_programs', 'schedules.id_daily_program', '=', 'daily_programs.id')
                    ->whereNull('daily_programs.id')
                    ->delete();
                $this->info("Schedules eliminados: {$deletedSchedules}");
            }

            // Eliminar daily_programs huérfanos
            if ($orphanedDailyPrograms > 0) {
                $deletedDailyPrograms = DB::table('daily_programs')
                    ->leftJoin('programs', 'daily_programs.program_id', '=', 'programs.id')
                    ->where(function($query) {
                        $query->whereNull('programs.id')
                              ->orWhereNull('daily_programs.program_id');
                    })
                    ->delete();
                $this->info("Daily programs eliminados: {$deletedDailyPrograms}");
            }

            DB::commit();
            $this->info('Limpieza completada exitosamente.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error durante la limpieza: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
