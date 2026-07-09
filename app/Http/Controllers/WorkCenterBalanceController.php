<?php

namespace App\Http\Controllers;

use App\Models\WorkCenter;
use App\Models\WorkCenterBalance;
use App\Models\WorkCenterBalanceAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkCenterBalanceController extends Controller
{
    /**
     * Mostrar lista de balances por centro de trabajo
     */
    public function index()
    {
        $workCenters = WorkCenter::with(['balance' => function($query) {
            $query->select('id', 'id_work_center', 'accumulated_backwardness', 'accumulated_advanced', 'last_calculated_at');
        }])
        ->orderBy('phase')
        ->orderBy('name')
        ->get()
        ->map(function ($center) {
            return [
                'id' => $center->id,
                'name' => $center->name,
                'phase' => $center->phase,
                'accumulated_backwardness' => $center->balance ? $center->balance->accumulated_backwardness : 0,
                'accumulated_advanced' => $center->balance ? $center->balance->accumulated_advanced : 0,
                'last_calculated_at' => $center->balance ? $center->balance->last_calculated_at : null,
                'last_calculated_at_formatted' => $center->balance && $center->balance->last_calculated_at 
                    ? \Carbon\Carbon::parse($center->balance->last_calculated_at)->format('d/m/Y H:i') 
                    : null,
            ];
        });

        return Inertia::render('IngenieroProcesos/WorkCenterBalances/Index', [
            'workCenters' => $workCenters,
        ]);
    }

    /**
     * Mostrar formulario de edición para un centro específico
     */
    public function edit($workCenterId)
    {
        $workCenter = WorkCenter::with('balance')->findOrFail($workCenterId);

        return Inertia::render('IngenieroProcesos/WorkCenterBalances/Edit', [
            'workCenter' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'phase' => $workCenter->phase,
                'accumulated_backwardness' => $workCenter->balance ? $workCenter->balance->accumulated_backwardness : 0,
                'accumulated_advanced' => $workCenter->balance ? $workCenter->balance->accumulated_advanced : 0,
                'last_calculated_at' => $workCenter->balance ? $workCenter->balance->last_calculated_at : null,
                'last_calculated_at_formatted' => $workCenter->balance && $workCenter->balance->last_calculated_at 
                    ? \Carbon\Carbon::parse($workCenter->balance->last_calculated_at)->format('d/m/Y H:i') 
                    : null,
            ],
        ]);
    }

    /**
     * Actualizar balance de un centro de trabajo
     */
    public function update(Request $request, $workCenterId)
    {
        $request->validate([
            'accumulated_backwardness' => 'required|integer|min:0',
            'accumulated_advanced' => 'required|integer|min:0',
            'reason' => 'required|string|min:5',
            'notes' => 'nullable|string',
        ]);

        $workCenter = WorkCenter::findOrFail($workCenterId);

        DB::beginTransaction();

        try {
            // Obtener o crear balance del centro
            $balance = WorkCenterBalance::getOrCreateForWorkCenter($workCenterId);

            // Guardar valores anteriores
            $previousBackwardness = $balance->accumulated_backwardness;
            $previousAdvanced = $balance->accumulated_advanced;

            // Actualizar balance
            $balance->accumulated_backwardness = $request->accumulated_backwardness;
            $balance->accumulated_advanced = $request->accumulated_advanced;
            $balance->save();

            // Registrar historial de cambios si hubo modificaciones
            if ($previousBackwardness != $request->accumulated_backwardness) {
                WorkCenterBalanceAdjustment::create([
                    'id_work_center' => $workCenterId,
                    'field_adjusted' => 'accumulated_backwardness',
                    'previous_value' => $previousBackwardness,
                    'new_value' => $request->accumulated_backwardness,
                    'difference' => $request->accumulated_backwardness - $previousBackwardness,
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            if ($previousAdvanced != $request->accumulated_advanced) {
                WorkCenterBalanceAdjustment::create([
                    'id_work_center' => $workCenterId,
                    'field_adjusted' => 'accumulated_advanced',
                    'previous_value' => $previousAdvanced,
                    'new_value' => $request->accumulated_advanced,
                    'difference' => $request->accumulated_advanced - $previousAdvanced,
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            DB::commit();

            return redirect()->route('ingeniero-procesos.work-center-balances.index')
                ->with('success', 'Balance actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el balance: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar historial de cambios de balance
     */
    public function history(Request $request)
    {
        $query = WorkCenterBalanceAdjustment::with(['workCenter', 'adjustedBy']);

        // Filtro por centro de trabajo
        if ($request->has('work_center_id') && $request->work_center_id) {
            $query->where('id_work_center', $request->work_center_id);
        }

        // Filtro por fecha desde
        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        // Filtro por fecha hasta
        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $adjustments = $query->orderBy('created_at', 'desc')->paginate(50);
        $workCenters = WorkCenter::orderBy('name')->get();

        return Inertia::render('IngenieroProcesos/WorkCenterBalances/History', [
            'adjustments' => $adjustments,
            'filters' => $request->only(['work_center_id', 'date_from', 'date_to']),
            'workCenters' => $workCenters,
        ]);
    }
}
