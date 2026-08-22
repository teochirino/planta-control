<?php

namespace App\Services;

use App\Models\DailyProgram;
use App\Models\WorkCenterBalance;
use App\Models\ProductionAdjustment;
use App\Models\RejectedPiece;
use App\Models\BalanceHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Calcular balance del día anterior para transferir
     */
    public function calculatePreviousDayBalance($workCenterId, $date, $shift)
    {
        // Buscar el programa del día anterior (mismo turno)
        $previousProgram = DailyProgram::where('id_work_center', $workCenterId)
            ->where('date', '<', $date)
            ->where('shift', $shift)
            ->where('balance_processed', true) // Solo si ya fue procesado
            ->orderBy('date', 'desc')
            ->first();
        
        if (!$previousProgram) {
            return ['backwardness' => 0, 'advanced' => 0];
        }
        
        // Calcular la diferencia final del día anterior
        $totalProduced = $previousProgram->total_produced ?? 0;
        $totalRejected = $previousProgram->total_rejected ?? 0;
        
        // Considerar resoluciones de rechazos
        $resolvedPieces = RejectedPiece::where('id_daily_program', $previousProgram->id)
            ->resolved()
            ->get();
        
        $repairedCount = $resolvedPieces->where('resolution_status', 'reparada')->sum('quantity');
        $replacedCount = $resolvedPieces->where('resolution_status', 'reemplazada')->sum('new_pieces_quantity');
        
        $netProduced = $totalProduced - $totalRejected + $repairedCount + $replacedCount;
        $totalToProduce = $previousProgram->programmed + $previousProgram->backwardness - $previousProgram->advanced;
        $difference = $netProduced - $totalToProduce;
        
        // Determinar atraso o adelanto
        if ($difference < 0) {
            return ['backwardness' => abs($difference), 'advanced' => 0];
        } elseif ($difference > 0) {
            return ['backwardness' => 0, 'advanced' => $difference];
        }
        
        return ['backwardness' => 0, 'advanced' => 0];
    }
    
    /**
     * Procesar balance al final del turno
     */
    public function processEndOfShiftBalance(DailyProgram $program)
    {
        // Calcular diferencia
        $totalProduced = $program->total_produced ?? 0;
        $totalRejected = $program->total_rejected ?? 0;

        // Considerar resoluciones de rechazos
        $resolvedPieces = RejectedPiece::where('id_daily_program', $program->id)
            ->resolved()
            ->get();

        $repairedCount = $resolvedPieces->where('resolution_status', 'reparada')->sum('quantity');
        $replacedCount = $resolvedPieces->where('resolution_status', 'reemplazada')->sum('new_pieces_quantity');

        $netProduced = $totalProduced - $totalRejected + $repairedCount + $replacedCount;
        $totalToProduce = $program->programmed + $program->backwardness - $program->advanced;
        $difference = $netProduced - $totalToProduce;

        // Obtener o crear balance del centro
        $balance = WorkCenterBalance::getOrCreateForWorkCenter($program->id_work_center);

        // Si el programa fue editado manualmente por ingeniería o supervisor, registrar este hecho
        // y usar los valores manuales para el cálculo del balance
        if ($program->manually_edited_by_engineering) {
            // Registrar ajuste de balance indicando que se usaron valores manuales
            ProductionAdjustment::create([
                'id_daily_program' => $program->id,
                'id_work_center' => $program->id_work_center,
                'adjustment_type' => 'correction',
                'field_adjusted' => 'backwardness',
                'previous_value' => $balance->accumulated_backwardness,
                'new_value' => $balance->accumulated_backwardness, // Se actualizará después
                'difference' => 0,
                'adjustment_category' => 'correction',
                'reason' => 'Balance calculado con valores manuales de ingeniería (backwardness: ' . $program->backwardness . ', advanced: ' . $program->advanced . ')',
                'adjusted_by' => auth()->id(),
                'notes' => 'El programa fue editado manualmente por ingeniería el ' . $program->engineering_edited_at,
            ]);
        } elseif ($program->manually_edited_by_supervisor) {
            // Registrar ajuste de balance indicando que se usaron valores manuales del supervisor
            ProductionAdjustment::create([
                'id_daily_program' => $program->id,
                'id_work_center' => $program->id_work_center,
                'adjustment_type' => 'correction',
                'field_adjusted' => 'backwardness',
                'previous_value' => $balance->accumulated_backwardness,
                'new_value' => $balance->accumulated_backwardness, // Se actualizará después
                'difference' => 0,
                'adjustment_category' => 'correction',
                'reason' => 'Balance calculado con valores manuales de supervisor (backwardness: ' . $program->backwardness . ', advanced: ' . $program->advanced . ')',
                'adjusted_by' => auth()->id(),
                'notes' => 'El programa fue editado manualmente por supervisor el ' . $program->supervisor_edited_at,
            ]);
        }

        // Lógica simple de cálculo de balance acumulado
        // Nuevo atraso acumulado = faltante del día
        $faltante = max(0, $totalToProduce - $netProduced);
        $newAccumulatedBackwardness = $faltante;

        // Nuevo adelanto acumulado = exceso del día
        $exceso = max(0, $netProduced - $totalToProduce);
        $newAccumulatedAdvanced = $exceso;

        // Log de depuración
        \Log::info('BalanceService - Cálculo simple de balance', [
            'program_id' => $program->id,
            'work_center_id' => $program->id_work_center,
            'programmed' => $program->programmed,
            'backwardness' => $program->backwardness,
            'advanced' => $program->advanced,
            'total_to_produce' => $totalToProduce,
            'net_produced' => $netProduced,
            'total_produced' => $totalProduced,
            'total_rejected' => $totalRejected,
            'faltante' => $faltante,
            'exceso' => $exceso,
            'new_accumulated_backwardness' => $newAccumulatedBackwardness,
            'new_accumulated_advanced' => $newAccumulatedAdvanced,
            'old_accumulated_backwardness' => $balance->accumulated_backwardness,
            'old_accumulated_advanced' => $balance->accumulated_advanced,
        ]);

        // Actualizar balance acumulado
        $balance->accumulated_backwardness = $newAccumulatedBackwardness;
        $balance->accumulated_advanced = $newAccumulatedAdvanced;
        $balance->last_calculated_at = now('America/Mexico_City');
        $balance->save();

        // Registrar historial de procesamiento de balance
        BalanceHistory::create([
            'id_work_center' => $program->id_work_center,
            'id_daily_program' => $program->id,
            'processed_by' => auth()->id(),
            'programmed' => $program->programmed,
            'backwardness' => $program->backwardness,
            'advanced' => $program->advanced,
            'total_to_produce' => $totalToProduce,
            'total_produced' => $totalProduced,
            'total_rejected' => $totalRejected,
            'final_backwardness' => $newAccumulatedBackwardness,
            'final_advanced' => $newAccumulatedAdvanced,
            'processed_at' => now('America/Mexico_City'),
        ]);

        // Si el programa fue editado manualmente, actualizar el registro de ajuste con los nuevos valores
        if ($program->manually_edited_by_engineering) {
            $lastAdjustment = ProductionAdjustment::where('id_daily_program', $program->id)
                ->where('field_adjusted', 'backwardness')
                ->where('adjustment_type', 'correction')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastAdjustment) {
                $lastAdjustment->update([
                    'new_value' => $newAccumulatedBackwardness,
                    'difference' => $newAccumulatedBackwardness - $lastAdjustment->previous_value,
                ]);
            }
        } elseif ($program->manually_edited_by_supervisor) {
            $lastAdjustment = ProductionAdjustment::where('id_daily_program', $program->id)
                ->where('field_adjusted', 'backwardness')
                ->where('adjustment_type', 'correction')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastAdjustment) {
                $lastAdjustment->update([
                    'new_value' => $newAccumulatedBackwardness,
                    'difference' => $newAccumulatedBackwardness - $lastAdjustment->previous_value,
                ]);
            }
        }

        // Marcar programa como procesado
        $program->update([
            'balance_processed' => true,
            'balance_processed_at' => now('America/Mexico_City'),
            'balance_processed_by' => auth()->id(),
        ]);

        return $balance;
    }
    
    /**
     * Obtener balance acumulado del centro
     */
    public function getWorkCenterBalance($workCenterId)
    {
        return WorkCenterBalance::where('id_work_center', $workCenterId)->first();
    }
    
    /**
     * Registrar ajuste manual
     */
    public function registerManualAdjustment($dailyProgramId, $data)
    {
        DB::beginTransaction();
        
        try {
            $adjustment = ProductionAdjustment::create([
                'id_daily_program' => $dailyProgramId,
                'id_work_center' => $data['id_work_center'],
                'adjustment_type' => $data['adjustment_type'] ?? 'correction',
                'previous_value' => $data['previous_value'],
                'new_value' => $data['new_value'],
                'difference' => $data['new_value'] - $data['previous_value'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reason' => $data['reason'],
                'adjusted_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);
            
            DB::commit();
            
            return $adjustment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener historial de ajustes
     */
    public function getAdjustmentsHistory($workCenterId, $startDate = null, $endDate = null)
    {
        $query = ProductionAdjustment::with(['dailyProgram', 'workCenter', 'adjustedBy'])
            ->where('id_work_center', $workCenterId);
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
}
