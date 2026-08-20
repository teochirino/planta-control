<?php

namespace App\Http\Controllers;

use App\Models\BalanceHistory;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BalanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = BalanceHistory::with(['workCenter', 'dailyProgram', 'processedBy']);

        // Filtrar por centros asignados al usuario si es supervisor
        if ($user->id_profile === 5) { // Supervisor de área
            $userWorkCenterIds = $user->workCenters()->pluck('work_center_id')->toArray();
            $query->whereIn('id_work_center', $userWorkCenterIds);

            // También filtrar el select de centros
            $workCenters = WorkCenter::whereIn('id', $userWorkCenterIds)->orderBy('name')->get();
        } else {
            // Para otros perfiles (ingeniero, admin, gerencia, etc.), mostrar todos los centros
            $workCenters = WorkCenter::orderBy('name')->get();
        }

        // Filtros adicionales
        if ($request->work_center_id) {
            $query->where('id_work_center', $request->work_center_id);
        }

        if ($request->date_from) {
            $query->where('processed_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('processed_at', '<=', $request->date_to . ' 23:59:59');
        }

        $history = $query->orderBy('processed_at', 'desc')->paginate(50);

        // Debug
        \Log::info('BalanceHistory filter', [
            'user_profile' => $user->id_profile,
            'requested_work_center_id' => $request->work_center_id,
            'all_params' => $request->all(),
            'sql' => $query->toSql(),
            'history_count' => $history->count(),
            'history_data' => $history->items()
        ]);

        return Inertia::render('BalanceHistory/Index', [
            'history' => $history,
            'workCenters' => $workCenters,
            'filters' => $request->only(['work_center_id', 'date_from', 'date_to'])
        ]);
    }
}
