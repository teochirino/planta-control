<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IngenieroProcesosController extends Controller
{
    public function index()
    {
        $programs = Program::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('IngenieroProcesos/Index', [
            'programs' => $programs,
        ]);
    }
    
    public function create()
    {
        $products = Product::with('workCenter')
            ->orderBy('modelo')
            ->get()
            ->groupBy('modelo');
        
        return Inertia::render('IngenieroProcesos/CreateProgram', [
            'products' => $products,
            'minDeliveryDate' => Program::addWorkingDays(now(), 4)->format('Y-m-d'),
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'fecha_entrega' => 'required|date|after_or_equal:' . Program::addWorkingDays(now(), 4)->format('Y-m-d'),
            'productos' => 'required|array|min:1',
            'productos.*.modelo' => 'required|string',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);
        
        DB::beginTransaction();
        
        try {
            $phaseDates = Program::calculatePhaseDates($request->fecha_entrega);
            
            $program = Program::create([
                'codigo' => Program::generateUniqueCode(),
                'fecha_entrega' => $request->fecha_entrega,
                'fecha_fase1' => $phaseDates['fase1']->format('Y-m-d'),
                'fecha_fase2' => $phaseDates['fase2']->format('Y-m-d'),
                'fecha_fase3' => $phaseDates['fase3']->format('Y-m-d'),
                'fecha_fase4' => $phaseDates['fase4']->format('Y-m-d'),
                'created_by' => auth()->id(),
            ]);
            
            foreach ($request->productos as $producto) {
                ProgramDetail::create([
                    'program_id' => $program->id,
                    'modelo' => $producto['modelo'],
                    'cantidad_solicitada' => $producto['cantidad'],
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('ingeniero-procesos.show', $program->id)
                ->with('success', 'Programa creado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el programa: ' . $e->getMessage());
        }
    }
    
    public function show(Program $program)
    {
        $program->load('creator');
        
        // Formatear fechas para la vista (evitar problemas de zona horaria)
        $programData = [
            'id' => $program->id,
            'codigo' => $program->codigo,
            'fecha_entrega' => $program->fecha_entrega,
            'fecha_fase1' => $program->fecha_fase1,
            'fecha_fase2' => $program->fecha_fase2,
            'fecha_fase3' => $program->fecha_fase3,
            'fecha_fase4' => $program->fecha_fase4,
            'fecha_entrega_formatted' => $program->fecha_entrega ? \Carbon\Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
            'fecha_fase1_formatted' => $program->fecha_fase1 ? \Carbon\Carbon::parse($program->fecha_fase1)->format('d/m/Y') : null,
            'fecha_fase2_formatted' => $program->fecha_fase2 ? \Carbon\Carbon::parse($program->fecha_fase2)->format('d/m/Y') : null,
            'fecha_fase3_formatted' => $program->fecha_fase3 ? \Carbon\Carbon::parse($program->fecha_fase3)->format('d/m/Y') : null,
            'fecha_fase4_formatted' => $program->fecha_fase4 ? \Carbon\Carbon::parse($program->fecha_fase4)->format('d/m/Y') : null,
            'created_at' => $program->created_at,
            'creator' => $program->creator,
        ];
        
        $allDetails = ProgramDetail::where('program_id', $program->id)
            ->get()
            ->flatMap(function ($detail) {
                // Buscar todos los productos con ese modelo (pueden estar en múltiples work centers)
                $products = Product::where('modelo', $detail->modelo)
                    ->with('workCenter')
                    ->get();
                
                // Crear una entrada por cada work center donde se produce este modelo
                return $products->map(function ($product) use ($detail) {
                    return [
                        'id' => $detail->id,
                        'modelo' => $detail->modelo,
                        'cantidad_solicitada' => $detail->cantidad_solicitada,
                        'work_center' => $product->workCenter->name,
                        'phase' => $product->workCenter->phase,
                        'piezas_por_centro' => $product->piezas,
                        'tiempo_por_centro' => $product->tiempo,
                        'total_pieces' => $detail->cantidad_solicitada * $product->piezas,
                        'total_time' => $detail->cantidad_solicitada * $product->tiempo,
                    ];
                });
            });

        // Agrupar por fase para las tablas individuales
        $details = $allDetails->sortBy('phase')->groupBy('phase');

        // Calcular totales por fecha y centro de trabajo
        $phaseDates = [
            1 => $program->fecha_fase1 ? \Carbon\Carbon::parse($program->fecha_fase1) : null,
            2 => $program->fecha_fase2 ? \Carbon\Carbon::parse($program->fecha_fase2) : null,
            3 => $program->fecha_fase3 ? \Carbon\Carbon::parse($program->fecha_fase3) : null,
            4 => $program->fecha_fase4 ? \Carbon\Carbon::parse($program->fecha_fase4) : null,
        ];

        $totalsByDate = $allDetails->map(function ($detail) use ($phaseDates) {
            $phaseDate = $phaseDates[$detail['phase']] ?? null;
            return [
                'fecha' => $phaseDate ? $phaseDate->format('d/m/Y') : null,
                'work_center' => $detail['work_center'],
                'total_pieces' => $detail['total_pieces'],
                'total_time' => $detail['total_time'],
            ];
        })->groupBy('fecha')->map(function ($dateGroup) {
            return $dateGroup->groupBy('work_center')->map(function ($workCenterGroup) {
                return [
                    'total_pieces' => $workCenterGroup->sum('total_pieces'),
                    'total_time' => round($workCenterGroup->sum('total_time'), 4),
                ];
            });
        });
        
        return Inertia::render('IngenieroProcesos/ViewProgram', [
            'program' => $programData,
            'details' => $details,
            'totalsByDate' => $totalsByDate,
        ]);
    }
}
