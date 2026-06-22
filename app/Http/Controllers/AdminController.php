<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\User;
use App\Models\ItalianetUser;
use App\Models\Profile;
use App\Models\WorkCenter;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Lista de usuarios del sistema planta_control
   public function index(Request $request)
{
    $profileFilter = $request->input('profile');
    $search = $request->input('search');
    
    $query = User::with(['profile', 'workCenters', 'productionLines']);
    
    if ($profileFilter) {
        $query->where('id_profile', $profileFilter);
    }
    
    if ($search) {
        $query->where('name', 'like', '%' . $search . '%');
    }
    
    $users = $query->paginate(15)->appends(['profile' => $profileFilter, 'search' => $search]);
    
    // Forzar autenticación
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    $profiles = Profile::all();
    
    return Inertia::render('Admin/Users/Index', [
        'users' => $users,
        'profiles' => $profiles,
        'filters' => [
            'profile' => $profileFilter,
            'search' => $search,
        ],
        'auth' => [
            'user' => auth()->user() ? [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'id_profile' => auth()->user()->id_profile,
            ] : null,
        ],
    ]);
}
    
    // Mostrar usuarios de italianet_users para importar
    public function importView(Request $request)
    {
        $search = $request->input('search');
        
        $italianetUsers = ItalianetUser::activeWithEmail()
            ->search($search)
            ->paginate(15)
            ->appends(['search' => $search]);
            
        $profiles = Profile::all();
        $workCenters = WorkCenter::all();
        $productionLines = ProductionLine::with('workCenter')->get();
        
        return Inertia::render('Admin/Users/Import', [
            'italianetUsers' => $italianetUsers,
            'profiles' => $profiles,
            'workCenters' => $workCenters,
            'productionLines' => $productionLines,
            'search' => $search,
        ]);
    }
    
    // Importar usuario desde italianet_users
    public function importUser(Request $request)
    {
        $request->validate([
            'user_main_id' => 'required|exists:italianet_users.users,id',
            'id_profile' => 'required|exists:profiles,id_profile',
            'work_centers' => 'nullable|array',
            'work_centers.*' => 'exists:work_centers,id',
            'production_lines' => 'nullable|array',
            'production_lines.*' => 'exists:production_lines,id'
        ]);
        
        $italianetUser = ItalianetUser::find($request->user_main_id);
        
        // Verificar si ya existe
        $existingUser = User::where('user_main_id', $italianetUser->id)->first();
        if ($existingUser) {
            return redirect()->route('admin.users.import')
                ->with('error', 'Este usuario ya ha sido importado.');
        }
        
        // Crear usuario en planta_control
        $user = User::create([
            'name' => $italianetUser->name,
            'email' => $italianetUser->email,
            'user_main_id' => $italianetUser->id,
            'id_profile' => $request->id_profile,
            'password' => Hash::make('password123'),
        ]);
        
        // Asignar centros de trabajo si es supervisor
        if ($request->id_profile == 5 && $request->work_centers) {
            $user->workCenters()->attach($request->work_centers);
        }
        
        // Asignar líneas de producción si es operador
        if ($request->id_profile == 8 && $request->production_lines) {
            foreach ($request->production_lines as $lineId) {
                $user->productionLines()->attach($lineId, [
                    'can_view' => true,
                    'can_edit' => true,
                    'can_delete' => false
                ]);
            }
        }
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario importado correctamente.');
    }
    
    // Editar usuario
    public function edit(User $user)
    {
        $profiles = Profile::all();
        $workCenters = WorkCenter::all();
        $productionLines = ProductionLine::with('workCenter')->get();
        
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'profiles' => $profiles,
            'workCenters' => $workCenters,
            'productionLines' => $productionLines,
        ]);
    }
    
    // Actualizar usuario
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'id_profile' => 'required|exists:profiles,id_profile',
            'work_centers' => 'nullable|array',
            'work_centers.*' => 'exists:work_centers,id',
            'production_lines' => 'nullable|array',
            'production_lines.*' => 'exists:production_lines,id'
        ]);
        
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'id_profile' => $request->id_profile,
        ]);
        
        // Sincronizar centros de trabajo
        if ($request->id_profile == 5) {
            $user->workCenters()->sync($request->work_centers ?? []);
        } else {
            $user->workCenters()->detach();
        }
        
        // Sincronizar líneas de producción
        if ($request->id_profile == 8) {
            $syncData = [];
            foreach ($request->production_lines ?? [] as $lineId) {
                $syncData[$lineId] = [
                    'can_view' => true,
                    'can_edit' => true,
                    'can_delete' => false
                ];
            }
            $user->productionLines()->sync($syncData);
        } else {
            $user->productionLines()->detach();
        }
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }
    
    // Eliminar usuario
    public function destroy(User $user)
    {
        $user->workCenters()->detach();
        $user->productionLines()->detach();
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
    
    // Vista para asignar centros de trabajo
    public function assignWorkCenters()
    {
        $supervisors = User::where('id_profile', 5)->with('workCenters')->get();
        $workCenters = WorkCenter::all();
        
        return Inertia::render('Admin/Users/AssignWorkCenters', [
            'supervisors' => $supervisors,
            'workCenters' => $workCenters,
        ]);
    }
    
    // Actualizar centros de trabajo de un supervisor
    public function updateWorkCenters(Request $request, User $user)
    {
        $request->validate([
            'work_centers' => 'required|array',
            'work_centers.*' => 'exists:work_centers,id'
        ]);
        
        if ($user->id_profile != 5) {
            return redirect()->route('admin.work-centers.assign')
                ->with('error', 'Solo se pueden asignar centros a supervisores de área.');
        }
        
        $user->workCenters()->sync($request->work_centers);
        
        return redirect()->route('admin.work-centers.assign')
            ->with('success', 'Centros de trabajo actualizados correctamente.');
    }
    
    // Vista para asignar líneas de producción
    public function assignProductionLines()
    {
        $operadores = User::where('id_profile', 8)->with('productionLines')->get();
        $productionLines = ProductionLine::with('workCenter')->get();
        
        return Inertia::render('Admin/Users/AssignProductionLines', [
            'operadores' => $operadores,
            'productionLines' => $productionLines,
        ]);
    }
    
    // Actualizar líneas de producción de un operador
    public function updateProductionLines(Request $request, User $user)
    {
        $request->validate([
            'production_lines' => 'required|array',
            'production_lines.*' => 'exists:production_lines,id'
        ]);
        
        if ($user->id_profile != 8) {
            return redirect()->route('admin.production-lines.assign')
                ->with('error', 'Solo se pueden asignar líneas a operadores de área.');
        }
        
        $syncData = [];
        foreach ($request->production_lines as $lineId) {
            $syncData[$lineId] = [
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => false
            ];
        }
        
        $user->productionLines()->sync($syncData);
        
        return redirect()->route('admin.production-lines.assign')
            ->with('success', 'Líneas de producción actualizadas correctamente.');
    }
}
