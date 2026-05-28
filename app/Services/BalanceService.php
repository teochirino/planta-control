<?php

namespace App\Services;

use App\Models\DailyProgram;
use App\Models\WorkCenterBalance;
use App\Models\ProductionAdjustment;
use App\Models\RejectedPiece;
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
        
        // Actualizar balance acumulado
        if ($difference < 0) {
            $balance->accumulated_backwardness += abs($difference);
        } elseif ($difference > 0) {
            $balance->accumulated_advanced += $difference;
        }
        
        $balance->last_calculated_at = now();
        $balance->save();
        
        // Marcar programa como procesado
        $program->update([
            'balance_processed' => true,
            'balance_processed_at' => now(),
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
