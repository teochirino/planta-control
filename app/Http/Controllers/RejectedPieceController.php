<?php

namespace App\Http\Controllers;

use App\Models\RejectedPiece;
use App\Models\WorkCenter;
use App\Models\ProductionLine;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RejectedPieceController extends Controller
{
    // Vista de seguimiento de rechazos
    public function index(Request $request)
    {
        $workCenterId = $request->get('work_center_id');
        $date = $request->get('date', now('America/Mexico_City')->format('Y-m-d'));
        $status = $request->get('status', 'pendiente');
        
        $query = RejectedPiece::with(['schedule', 'dailyProgram', 'workCenter', 'productionLine', 'rejectedBy', 'resolvedBy']);
        
        if ($workCenterId) {
            $query->where('id_work_center', $workCenterId);
        }
        
        if ($date) {
            $query->whereDate('rejected_at', $date);
        }
        
        if ($status !== 'all') {
            $query->where('resolution_status', $status);
        }
        
        $rejectedPieces = $query->orderBy('rejected_at', 'desc')->get();
        
        $workCenters = WorkCenter::all();
        
        return Inertia::render('RejectedPieces/Index', [
            'rejectedPieces' => $rejectedPieces,
            'workCenters' => $workCenters,
            'filters' => [
                'work_center_id' => $workCenterId,
                'date' => $date,
                'status' => $status,
            ],
        ]);
    }
    
    // Marcar como reparada
    public function markAsRepaired(Request $request, $id)
    {
        $request->validate([
            'resolution_notes' => 'required|string',
        ]);
        
        $rejectedPiece = RejectedPiece::findOrFail($id);
        
        $rejectedPiece->update([
            'resolution_status' => 'reparada',
            'resolution_notes' => $request->resolution_notes,
            'resolved_by' => auth()->id(),
            'resolved_at' => now('America/Mexico_City'),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pieza marcada como reparada',
            'rejected_piece' => $rejectedPiece->load('resolvedBy'),
        ]);
    }
    
    // Marcar como reemplazada (se hicieron piezas nuevas)
    public function markAsReplaced(Request $request, $id)
    {
        $request->validate([
            'new_pieces_quantity' => 'required|integer|min:1',
            'new_pieces_schedule_id' => 'required|exists:schedules,id',
            'resolution_notes' => 'nullable|string',
        ]);
        
        $rejectedPiece = RejectedPiece::findOrFail($id);
        
        $rejectedPiece->update([
            'resolution_status' => 'reemplazada',
            'new_pieces_quantity' => $request->new_pieces_quantity,
            'new_pieces_schedule_id' => $request->new_pieces_schedule_id,
            'resolution_notes' => $request->resolution_notes,
            'resolved_by' => auth()->id(),
            'resolved_at' => now('America/Mexico_City'),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pieza marcada como reemplazada',
            'rejected_piece' => $rejectedPiece->load('resolvedBy', 'replacementSchedule'),
        ]);
    }
    
    // Marcar como desechada
    public function markAsDiscarded(Request $request, $id)
    {
        $request->validate([
            'resolution_notes' => 'required|string',
        ]);
        
        $rejectedPiece = RejectedPiece::findOrFail($id);
        
        $rejectedPiece->update([
            'resolution_status' => 'desechada',
            'resolution_notes' => $request->resolution_notes,
            'resolved_by' => auth()->id(),
            'resolved_at' => now('America/Mexico_City'),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pieza marcada como desechada',
            'rejected_piece' => $rejectedPiece->load('resolvedBy'),
        ]);
    }
    
    // Obtener schedules disponibles para reemplazo
    public function getSchedulesForReplacement(Request $request)
    {
        $dailyProgramId = $request->get('daily_program_id');
        
        $schedules = Schedule::where('id_daily_program', $dailyProgramId)
            ->orderBy('start_time')
            ->get(['id', 'start_time', 'end_time']);
        
        return response()->json($schedules);
    }
}
