<?php
// app/Http/Controllers/UserPermissionController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkCenter;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserPermissionController extends Controller
{
    // Vista para asignar permisos
    public function index()
    {
        $users = User::with(['profile', 'workCenters', 'productionLines'])->get();
        $workCenters = WorkCenter::all();
        $productionLines = ProductionLine::with('workCenter')->get();
        
        return Inertia::render('Permissions/Index', [
            'users' => $users,
            'workCenters' => $workCenters,
            'productionLines' => $productionLines,
        ]);
    }
    
    // Asignar centros de trabajo a usuario
    public function assignWorkCenters(Request $request, User $user)
    {
        $request->validate([
            'work_centers' => 'array',
            'work_centers.*.id' => 'exists:work_centers,id',
            'work_centers.*.can_view' => 'boolean',
            'work_centers.*.can_edit' => 'boolean',
        ]);
        
        $syncData = [];
        foreach ($request->work_centers as $wc) {
            $syncData[$wc['id']] = [
                'can_view' => $wc['can_view'] ?? true,
                'can_edit' => $wc['can_edit'] ?? false,
                'can_delete' => $wc['can_delete'] ?? false,
            ];
        }
        
        $user->workCenters()->sync($syncData);
        
        return back()->with('success', 'Permisos actualizados');
    }
    
    // Asignar líneas de producción a usuario
    public function assignProductionLines(Request $request, User $user)
    {
        $request->validate([
            'production_lines' => 'array',
            'production_lines.*.id' => 'exists:production_lines,id',
            'production_lines.*.can_view' => 'boolean',
            'production_lines.*.can_edit' => 'boolean',
        ]);
        
        $syncData = [];
        foreach ($request->production_lines as $pl) {
            $syncData[$pl['id']] = [
                'can_view' => $pl['can_view'] ?? true,
                'can_edit' => $pl['can_edit'] ?? false,
                'can_delete' => $pl['can_delete'] ?? false,
            ];
        }
        
        $user->productionLines()->sync($syncData);
        
        return back()->with('success', 'Permisos actualizados');
    }
}