<?php
// app/Http/Controllers/PermisosController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkCenter;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PermisosController extends Controller
{
    // Mostrar la interfaz principal de permisos
    public function index()
    {
         //dd(User::with('profile')->get());
        $users = User::with('profile')->get();
        $workCenters = WorkCenter::all();
        $productionLines = ProductionLine::with('workCenter')->get();

        return Inertia::render('Permissions/Index', [
            'users' => $users,
            'workCenters' => $workCenters,
            'productionLines' => $productionLines,
        ]);
    }

    // Obtener permisos de un usuario específico
    public function getUserPermissions($userId)
    {
        $user = User::with('profile')->findOrFail($userId);

        $workCentersAssigned = DB::table('user_work_centers')
            ->where('user_id', $userId)
            ->get()
            ->keyBy('work_center_id');

        $productionLinesAssigned = DB::table('user_production_lines')
            ->where('user_id', $userId)
            ->get()
            ->keyBy('production_line_id');

        return response()->json([
            'user' => $user,
            'work_centers' => $workCentersAssigned,
            'production_lines' => $productionLinesAssigned,
        ]);
    }

    // Guardar permisos de centros de trabajo
    public function saveWorkCenterPermissions(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*.work_center_id' => 'required|exists:work_centers,id',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
        ]);
        
        $user = User::findOrFail($userId);

        $syncData = [];
        foreach ($request->permissions as $perm) {
            if ($perm['can_view'] || $perm['can_edit'] || $perm['can_delete']) {
                $syncData[$perm['work_center_id']] = [
                    'can_view' => $perm['can_view'] ?? false,
                    'can_edit' => $perm['can_edit'] ?? false,
                    'can_delete' => $perm['can_delete'] ?? false,
                    'updated_at' => now('America/Mexico_City'),
                ];
            }
        }

        $user->workCenters()->sync($syncData);

        return response()->json(['success' => true, 'message' => 'Permisos de centros de trabajo guardados']);
    }

    // Guardar permisos de líneas de producción
    public function saveProductionLinePermissions(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*.production_line_id' => 'required|exists:production_lines,id',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
        ]);

        $user = User::findOrFail($userId);

        $syncData = [];
        foreach ($request->permissions as $perm) {
            if ($perm['can_view'] || $perm['can_edit'] || $perm['can_delete']) {
                $syncData[$perm['production_line_id']] = [
                    'can_view' => $perm['can_view'] ?? false,
                    'can_edit' => $perm['can_edit'] ?? false,
                    'can_delete' => $perm['can_delete'] ?? false,
                    'updated_at' => now('America/Mexico_City'),
                ];
            }
        }

        $user->productionLines()->sync($syncData);

        return response()->json(['success' => true, 'message' => 'Permisos de líneas de producción guardados']);
    }
}