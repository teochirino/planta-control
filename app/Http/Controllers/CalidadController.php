<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\DailyProgram;
use App\Models\Schedule;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CalidadController extends Controller
{
    // Vista principal - Registrar Rechazo
    public function registrarRechazo(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $productionLineId = $request->get('production_line_id');
        $shift = $request->get('shift', 'matutino');
        
        $productionLines = ProductionLine::with('workCenter')->get();
        
        $schedules = [];
        
        if ($productionLineId) {
            $productionLine = ProductionLine::findOrFail($productionLineId);
            $workCenterId = $productionLine->id_work_center;
            
            // Buscar el programa diario
            $dailyProgram = DailyProgram::where('id_work_center', $workCenterId)
                ->where('date', $date)
                ->first();
            
            if ($dailyProgram) {
                // Obtener schedules con produced > 0
                $schedules = Schedule::with(['productionLine', 'rejectedByUser'])
                    ->where('id_daily_program', $dailyProgram->id)
                    ->where('id_production_line', $productionLineId)
                    ->where('produced', '>', 0)
                    ->get()
                    ->map(function ($schedule) {
                        return [
                            'id' => $schedule->id,
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                            'time_range' => $schedule->time_range,
                            'produced' => $schedule->produced,
                            'rejected' => $schedule->rejected ?? 0,
                            'rejected_by' => $schedule->rejectedByUser ? $schedule->rejectedByUser->name : null,
                            'rejected_at' => $schedule->rejected_at ? $schedule->rejected_at->format('d/m/Y H:i') : null,
                        ];
                    });
            }
        }
        
        return Inertia::render('Calidad/RegistrarRechazo', [
            'productionLines' => $productionLines,
            'selectedDate' => $date,
            'selectedProductionLineId' => $productionLineId,
            'selectedShift' => $shift,
            'schedules' => $schedules,
        ]);
    }
    
    // Guardar rechazo
    public function storeRechazo(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'rejected' => 'required|integer|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            $schedule = Schedule::findOrFail($request->schedule_id);
            
            // Actualizar schedule
            $schedule->update([
                'rejected' => $request->rejected,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
            ]);
            
            // Actualizar total_rejected en daily_program
            $dailyProgram = $schedule->dailyProgram;
            $totalRejected = Schedule::where('id_daily_program', $dailyProgram->id)
                ->sum('rejected');
            
            $dailyProgram->update([
                'total_rejected' => $totalRejected,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Rechazo registrado correctamente',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el rechazo: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    // Vista de Rechazos
    public function rechazos(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        
        // Obtener centros de trabajo con rechazos para la fecha
        $workCentersWithRejections = DailyProgram::with(['workCenter', 'schedules.productionLine', 'schedules.rejectedByUser'])
            ->where('date', $date)
            ->where('total_rejected', '>', 0)
            ->get()
            ->map(function ($dailyProgram) {
                $totalProduced = $dailyProgram->total_produced ?? 0;
                $totalRejected = $dailyProgram->total_rejected ?? 0;
                $rejectionPercentage = $totalProduced > 0 
                    ? round(($totalRejected / $totalProduced) * 100, 2) 
                    : 0;
                
                // Obtener detalles de rechazos por línea
                $rejectionDetails = $dailyProgram->schedules()
                    ->where('rejected', '>', 0)
                    ->with(['productionLine', 'rejectedByUser'])
                    ->get()
                    ->map(function ($schedule) {
                        return [
                            'production_line' => $schedule->productionLine->title,
                            'time_range' => $schedule->time_range,
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                            'produced' => $schedule->produced,
                            'rejected' => $schedule->rejected,
                            'rejected_by' => $schedule->rejectedByUser ? $schedule->rejectedByUser->name : 'N/A',
                            'rejected_at' => $schedule->rejected_at ? $schedule->rejected_at->format('d/m/Y H:i') : 'N/A',
                        ];
                    });
                
                return [
                    'work_center_id' => $dailyProgram->workCenter->id,
                    'work_center_name' => $dailyProgram->workCenter->name,
                    'shift' => $dailyProgram->shift,
                    'total_produced' => $totalProduced,
                    'total_rejected' => $totalRejected,
                    'rejection_percentage' => $rejectionPercentage,
                    'rejection_details' => $rejectionDetails,
                ];
            });
        
        return Inertia::render('Calidad/Rechazos', [
            'selectedDate' => $date,
            'workCentersWithRejections' => $workCentersWithRejections,
        ]);
    }
}
