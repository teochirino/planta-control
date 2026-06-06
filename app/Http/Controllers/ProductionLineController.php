<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionLineController extends Controller
{
    /**
     * Mostrar listado de líneas de producción
     */
    public function index(Request $request)
    {
        $query = ProductionLine::with('workCenter');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhereHas('workCenter', function ($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $productionLines = $query
            ->orderBy('id_work_center')
            ->orderBy('title')
            ->paginate(15);

        return Inertia::render('IngenieroProcesos/ProductionLines/Index', [
            'productionLines' => $productionLines,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Mostrar formulario para crear línea de producción
     */
    public function create()
    {
        $workCenters = WorkCenter::orderBy('phase')->orderBy('name')->get();

        return Inertia::render('IngenieroProcesos/ProductionLines/Create', [
            'workCenters' => $workCenters,
        ]);
    }

    /**
     * Guardar nueva línea de producción
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_work_center' => 'required|exists:work_centers,id',
            'title' => 'required|string|max:255',
            'installed_capacity' => 'required|integer|min:1',
            'cost' => 'required|numeric|min:0',
        ]);

        ProductionLine::create([
            'id_work_center' => $request->id_work_center,
            'title' => $request->title,
            'installed_capacity' => $request->installed_capacity,
            'cost' => $request->cost,
        ]);

        return redirect()->route('ingeniero-procesos.production-lines.index')
            ->with('success', 'Línea de producción creada correctamente.');
    }

    /**
     * Mostrar formulario para editar línea de producción
     */
    public function edit(ProductionLine $productionLine)
    {
        $productionLine->load('workCenter');
        $workCenters = WorkCenter::orderBy('phase')->orderBy('name')->get();

        return Inertia::render('IngenieroProcesos/ProductionLines/Edit', [
            'productionLine' => $productionLine,
            'workCenters' => $workCenters,
        ]);
    }

    /**
     * Actualizar línea de producción
     */
    public function update(Request $request, ProductionLine $productionLine)
    {
        $request->validate([
            'id_work_center' => 'required|exists:work_centers,id',
            'title' => 'required|string|max:255',
            'installed_capacity' => 'required|integer|min:1',
            'cost' => 'required|numeric|min:0',
        ]);

        $productionLine->update([
            'id_work_center' => $request->id_work_center,
            'title' => $request->title,
            'installed_capacity' => $request->installed_capacity,
            'cost' => $request->cost,
        ]);

        return redirect()->route('ingeniero-procesos.production-lines.index')
            ->with('success', 'Línea de producción actualizada correctamente.');
    }

    /**
     * Eliminar línea de producción
     */
    public function destroy(ProductionLine $productionLine)
    {
        $productionLine->delete();

        return redirect()->route('ingeniero-procesos.production-lines.index')
            ->with('success', 'Línea de producción eliminada correctamente.');
    }
}
