<?php

namespace App\Http\Controllers;

use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkCenterController extends Controller
{
    /**
     * Mostrar listado de centros de trabajo
     */
    public function index(Request $request)
    {
        $query = WorkCenter::with(['productionLines', 'machines']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phase', 'like', '%' . $search . '%');
            });
        }

        $workCenters = $query
            ->orderBy('phase')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('IngenieroProcesos/WorkCenters/Index', [
            'workCenters' => $workCenters,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Mostrar formulario para crear centro de trabajo
     */
    public function create()
    {
        return Inertia::render('IngenieroProcesos/WorkCenters/Create');
    }

    /**
     * Guardar nuevo centro de trabajo
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'installed_capacity' => 'required|integer|min:1',
            'phase' => 'required|integer|min:1|max:10',
        ]);

        WorkCenter::create([
            'name' => $request->name,
            'installed_capacity' => $request->installed_capacity,
            'phase' => $request->phase,
        ]);

        return redirect()->route('ingeniero-procesos.work-centers.index')
            ->with('success', 'Centro de trabajo creado correctamente.');
    }

    /**
     * Mostrar formulario para editar centro de trabajo
     */
    public function edit(WorkCenter $workCenter)
    {
        $workCenter->load(['productionLines', 'machines']);

        return Inertia::render('IngenieroProcesos/WorkCenters/Edit', [
            'workCenter' => $workCenter,
        ]);
    }

    /**
     * Actualizar centro de trabajo
     */
    public function update(Request $request, WorkCenter $workCenter)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'installed_capacity' => 'required|integer|min:1',
            'phase' => 'required|integer|min:1|max:10',
        ]);

        $workCenter->update([
            'name' => $request->name,
            'installed_capacity' => $request->installed_capacity,
            'phase' => $request->phase,
        ]);

        return redirect()->route('ingeniero-procesos.work-centers.index')
            ->with('success', 'Centro de trabajo actualizado correctamente.');
    }

    /**
     * Eliminar centro de trabajo
     */
    public function destroy(WorkCenter $workCenter)
    {
        // Verificar si tiene líneas de producción o máquinas asociadas
        if ($workCenter->productionLines()->count() > 0 || $workCenter->machines()->count() > 0) {
            return redirect()->route('ingeniero-procesos.work-centers.index')
                ->with('error', 'No se puede eliminar el centro de trabajo porque tiene líneas de producción o máquinas asociadas.');
        }

        $workCenter->delete();

        return redirect()->route('ingeniero-procesos.work-centers.index')
            ->with('success', 'Centro de trabajo eliminado correctamente.');
    }
}
