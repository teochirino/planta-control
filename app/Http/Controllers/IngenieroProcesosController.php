<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramDetail;
use App\Models\Product;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IngenieroProcesosController extends Controller
{
    public function index()
    {
        $programs = Program::with('creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($program) {
                return [
                    'id' => $program->id,
                    'codigo' => $program->codigo,
                    'fecha_entrega' => $program->fecha_entrega,
                    'fecha_entrega_formatted' => $program->fecha_entrega ? \Carbon\Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
                    'created_at' => $program->created_at,
                    'created_at_formatted' => $program->created_at ? \Carbon\Carbon::parse($program->created_at)->format('d/m/Y') : null,
                    'creator' => $program->creator,
                ];
            });
        
        return Inertia::render('IngenieroProcesos/Index', [
            'programs' => $programs,
        ]);
    }
    
    public function create()
    {
        $products = Product::with('workCenter')
            ->where('piezas', '>', 0)
            ->orderBy('modelo')
            ->get()
            ->groupBy('modelo');
        
        $minDeliveryDate = Program::addWorkingDays(now(), 4);
        
        return Inertia::render('IngenieroProcesos/CreateProgram', [
            'products' => $products,
            'minDeliveryDate' => $minDeliveryDate->format('Y-m-d'),
            'minDeliveryDateFormatted' => $minDeliveryDate->format('d/m/Y'),
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
    
    public function show(Program $program, Request $request)
    {
        // ============================================
        // ROLLBACK: Eliminar este bloque condicional para volver al comportamiento original
        // ============================================
        // Si es un programa de recuperación, mostrar vista especial
        if (isset($program->program_type) && $program->program_type === 'recovery') {
            return $this->showRecoveryProgram($program, $request);
        }
        // ============================================
        // FIN ROLLBACK
        // ============================================

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
                // Buscar todos los productos con ese modelo y piezas > 0 (pueden estar en múltiples work centers)
                $products = Product::where('modelo', $detail->modelo)
                    ->where('piezas', '>', 0)
                    ->with('workCenter')
                    ->get();
                
                // Crear una entrada por cada work center donde se produce este modelo
                return $products->map(function ($product) use ($detail) {
                    return [
                        'id' => $detail->id,
                        'modelo' => $detail->modelo,
                        'cantidad_solicitada' => $detail->cantidad_solicitada,
                        'work_center' => $product->workCenter->name,
                        'work_center_id' => $product->workCenter->id,
                        'phase' => $product->workCenter->phase,
                        'piezas_por_centro' => $product->piezas,
                        'tiempo_por_centro' => $product->tiempo,
                        'total_pieces' => $detail->cantidad_solicitada * $product->piezas,
                        'total_time' => $detail->cantidad_solicitada * $product->tiempo,
                    ];
                });
            });

        // Aplicar filtro por centro de trabajo si se proporciona
        $selectedWorkCenterId = $request->input('work_center_id');
        if ($selectedWorkCenterId) {
            $allDetails = $allDetails->filter(function ($detail) use ($selectedWorkCenterId) {
                return $detail['work_center_id'] == $selectedWorkCenterId;
            });
        }

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
        
        // Obtener todos los centros de trabajo disponibles para el filtro
        $workCenters = WorkCenter::orderBy('phase')->orderBy('name')->get();
        
        return Inertia::render('IngenieroProcesos/ViewProgram', [
            'program' => $programData,
            'details' => $details,
            'totalsByDate' => $totalsByDate,
            'workCenters' => $workCenters,
            'filters' => $request->only(['work_center_id']),
        ]);
    }
    
    public function destroy(Program $program)
    {
        DB::beginTransaction();
        
        try {
            // Obtener los daily programs asociados a este programa
            $dailyPrograms = \App\Models\DailyProgram::where('program_id', $program->id)->get();
            
            // Verificar si hay strikes asociados a los daily programs
            $hasStrikes = \App\Models\Strike::whereIn('id_daily_program', $dailyPrograms->pluck('id'))->exists();
            if ($hasStrikes) {
                return back()->with('error', 'No se puede eliminar el programa porque tiene paros registrados.');
            }
            
            // Verificar si hay rejected pieces asociados a los daily programs
            $hasRejectedPieces = \App\Models\RejectedPiece::whereIn('id_daily_program', $dailyPrograms->pluck('id'))->exists();
            if ($hasRejectedPieces) {
                return back()->with('error', 'No se puede eliminar el programa porque tiene piezas rechazadas registradas.');
            }
            
            // Verificar si hay production adjustments que referencian este programa
            $hasAdjustments = \App\Models\ProductionAdjustment::where('source_program_id', $program->id)
                ->orWhere('target_program_id', $program->id)
                ->exists();
            if ($hasAdjustments) {
                return back()->with('error', 'No se puede eliminar el programa porque tiene ajustes de producción asociados.');
            }
            
            // Eliminar schedules de cada daily program
            foreach ($dailyPrograms as $dailyProgram) {
                \App\Models\Schedule::where('id_daily_program', $dailyProgram->id)->delete();
            }
            
            // Eliminar daily programs
            \App\Models\DailyProgram::where('program_id', $program->id)->delete();
            
            // Eliminar program details
            ProgramDetail::where('program_id', $program->id)->delete();
            
            // Eliminar el programa
            $program->delete();
            
            DB::commit();
            
            return redirect()->route('ingeniero-procesos.index')
                ->with('success', 'Programa eliminado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar el programa: ' . $e->getMessage());
        }
    }
    
    // ============================================
    // ACTUALIZAR FECHA DE ENTREGA
    // ============================================
    
    public function updateDeliveryDate(Request $request, Program $program)
    {
        $request->validate([
            'fecha_entrega' => 'required|date',
            'include_saturdays' => 'sometimes|boolean',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Calcular nuevas fases
            $includeSaturdays = $request->input('include_saturdays', false);
            $phaseDates = Program::calculatePhaseDates($request->fecha_entrega, $includeSaturdays);
            
            // Actualizar el programa
            $program->update([
                'fecha_entrega' => $request->fecha_entrega,
                'fecha_fase1' => $phaseDates['fase1']->format('Y-m-d'),
                'fecha_fase2' => $phaseDates['fase2']->format('Y-m-d'),
                'fecha_fase3' => $phaseDates['fase3']->format('Y-m-d'),
                'fecha_fase4' => $phaseDates['fase4']->format('Y-m-d'),
            ]);
            
            // Eliminar daily programs y schedules existentes para este programa
            $dailyPrograms = \App\Models\DailyProgram::where('program_id', $program->id)->get();
            foreach ($dailyPrograms as $dailyProgram) {
                \App\Models\Schedule::where('id_daily_program', $dailyProgram->id)->delete();
            }
            \App\Models\DailyProgram::where('program_id', $program->id)->delete();
            
            // Recrear daily programs y schedules con las nuevas fechas
            $phases = [
                1 => $phaseDates['fase1']->format('Y-m-d'),
                2 => $phaseDates['fase2']->format('Y-m-d'),
                3 => $phaseDates['fase3']->format('Y-m-d'),
                4 => $phaseDates['fase4']->format('Y-m-d'),
            ];
            
            // Obtener detalles del programa para recalcular piezas
            $programDetails = ProgramDetail::where('program_id', $program->id)->get();
            $modelos = $programDetails->pluck('modelo')->unique();
            $allProducts = \App\Models\Product::whereIn('modelo', $modelos)->where('piezas', '>', 0)->get();
            
            foreach ($phases as $phaseNumber => $phaseDate) {
                $workCenters = \App\Models\WorkCenter::where('phase', $phaseNumber)->get();
                
                foreach ($workCenters as $workCenter) {
                    $centerPiezas = 0;
                    foreach ($programDetails as $detail) {
                        $productsForModel = $allProducts->where('modelo', $detail->modelo);
                        foreach ($productsForModel as $product) {
                            if ($product->id_work_center == $workCenter->id) {
                                $centerPiezas += $product->piezas * $detail->cantidad_solicitada;
                            }
                        }
                    }
                    
                    if ($centerPiezas > 0) {
                        $dailyProgram = \App\Models\DailyProgram::create([
                            'date' => $phaseDate,
                            'id_work_center' => $workCenter->id,
                            'shift' => 'matutino',
                            'programmed' => $centerPiezas,
                            'backwardness' => 0,
                            'advanced' => 0,
                            'shift_hours' => 9.0,
                            'program_id' => $program->id,
                        ]);
                        
                        $productionLines = $workCenter->productionLines;
                        $this->generateSchedulesForProgram($dailyProgram, $productionLines);
                    }
                }
            }
            
            DB::commit();
            
            return back()->with('success', 'Fecha de entrega actualizada exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar la fecha de entrega: ' . $e->getMessage());
        }
    }
    
    public function checkSaturdayInPhases(Request $request)
    {
        \Log::info('checkSaturdayInPhases called', [
            'fecha_entrega' => $request->fecha_entrega,
            'all_data' => $request->all()
        ]);

        $request->validate([
            'fecha_entrega' => 'required|date',
        ]);

        try {
            // Calcular fases sin incluir sábados
            $phaseDates = Program::calculatePhaseDates($request->fecha_entrega, false);

            // Calcular fases incluyendo sábados
            $phaseDatesWithSaturdays = Program::calculatePhaseDates($request->fecha_entrega, true);

            // Comparar para detectar sábados
            $hasSaturdayInPhases = false;
            $phasesWithSaturday = [];

            foreach ($phaseDates as $phaseName => $dateWithoutSaturday) {
                $dateWithSaturday = $phaseDatesWithSaturdays[$phaseName];

                if ($dateWithoutSaturday->format('Y-m-d') !== $dateWithSaturday->format('Y-m-d')) {
                    $hasSaturdayInPhases = true;

                    $current = $dateWithSaturday->copy();
                    $saturdaysFound = [];

                    while ($current->gt($dateWithoutSaturday)) {
                        if ($current->isSaturday()) {
                            $saturdaysFound[] = $current->format('d/m/Y');
                        }
                        $current->subDay();
                    }

                    $phasesWithSaturday[] = [
                        'fase' => $phaseName,
                        'fecha_sin_sabado' => $dateWithoutSaturday->format('d/m/Y'),
                        'fecha_con_sabado' => $dateWithSaturday->format('d/m/Y'),
                        'sabados_saltados' => $saturdaysFound,
                    ];
                }
            }

            \Log::info('checkSaturdayInPhases success', [
                'has_saturday_in_phases' => $hasSaturdayInPhases,
                'phases_count' => count($phasesWithSaturday)
            ]);

            return response()->json([
                'has_saturday_in_phases' => $hasSaturdayInPhases,
                'phases_with_saturday' => $phasesWithSaturday,
            ]);

        } catch (\Exception $e) {
            \Log::error('checkSaturdayInPhases error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // ============================================
    // CRUD DE PRODUCTOS
    // ============================================
    
    public function productsIndex(Request $request)
    {
        $query = Product::with('workCenter')->where('piezas', '>=', 0);

        if ($request->has('search')) {
            $query->where('modelo', 'like', '%' . $request->input('search') . '%');
        }

        $products = $query->orderBy('modelo')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'modelo' => $product->modelo,
                    'id_work_center' => $product->id_work_center,
                    'tiempo' => $product->tiempo,
                    'piezas' => $product->piezas,
                    'workCenter' => $product->workCenter ? [
                        'id' => $product->workCenter->id,
                        'name' => $product->workCenter->name,
                        'phase' => $product->workCenter->phase,
                    ] : null,
                ];
            })
            ->groupBy('modelo');
        
        return Inertia::render('IngenieroProcesos/IndexProducts', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }
    
    public function productCreate()
    {
        $workCenters = WorkCenter::orderBy('phase')->orderBy('name')->get();
        
        return Inertia::render('IngenieroProcesos/CreateProduct', [
            'workCenters' => $workCenters,
        ]);
    }
    
    public function productStore(Request $request)
    {
        $request->validate([
            'modelo' => 'required|string|max:20',
            'work_centers' => 'required|array|min:1',
            'work_centers.*.id_work_center' => 'required|exists:work_centers,id',
            'work_centers.*.tiempo' => 'required|numeric|min:0',
            'work_centers.*.piezas' => 'required|integer|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($request->work_centers as $wc) {
                Product::create([
                    'modelo' => $request->modelo,
                    'id_work_center' => $wc['id_work_center'],
                    'tiempo' => $wc['tiempo'],
                    'piezas' => $wc['piezas'],
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('ingeniero-procesos.products.index')
                ->with('success', 'Producto creado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el producto: ' . $e->getMessage());
        }
    }
    
    public function productEdit($modelo)
    {
        $modelo = urldecode($modelo);
        $products = Product::where('modelo', $modelo)
            ->with('workCenter')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'modelo' => $product->modelo,
                    'id_work_center' => $product->id_work_center,
                    'tiempo' => $product->tiempo,
                    'piezas' => $product->piezas,
                    'workCenter' => $product->workCenter ? [
                        'id' => $product->workCenter->id,
                        'name' => $product->workCenter->name,
                        'phase' => $product->workCenter->phase,
                    ] : null,
                ];
            });
        
        if ($products->isEmpty()) {
            return redirect()->route('ingeniero-procesos.products.index')
                ->with('error', 'Producto no encontrado.');
        }
        
        $workCenters = WorkCenter::orderBy('phase')->orderBy('name')->get();
        
        return Inertia::render('IngenieroProcesos/EditProduct', [
            'modelo' => $modelo,
            'products' => $products,
            'workCenters' => $workCenters,
        ]);
    }
    
    public function productUpdate(Request $request, $modelo)
    {
        $modelo = urldecode($modelo);
        $request->validate([
            'modelo' => 'required|string|max:20',
            'work_centers' => 'required|array|min:1',
            'work_centers.*.id_work_center' => 'required|exists:work_centers,id',
            'work_centers.*.tiempo' => 'required|numeric|min:0',
            'work_centers.*.piezas' => 'required|integer|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Eliminar todos los productos con el modelo actual
            Product::where('modelo', $modelo)->delete();
            
            // Crear los nuevos registros
            foreach ($request->work_centers as $wc) {
                Product::create([
                    'modelo' => $request->modelo,
                    'id_work_center' => $wc['id_work_center'],
                    'tiempo' => $wc['tiempo'],
                    'piezas' => $wc['piezas'],
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('ingeniero-procesos.products.index')
                ->with('success', 'Producto actualizado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el producto: ' . $e->getMessage());
        }
    }
    
    public function productDestroy($modelo)
    {
        $modelo = urldecode($modelo);
        try {
            Product::where('modelo', $modelo)->delete();
            
            return redirect()->route('ingeniero-procesos.products.index')
                ->with('success', 'Producto eliminado exitosamente.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }
    
    // ============================================
    // IMPORTACIÓN DE PRODUCTOS DESDE EXCEL
    // ============================================
    
    public function importProductsView()
    {
        return Inertia::render('IngenieroProcesos/ImportProducts');
    }
    
    public function importProducts(Request $request)
    {
        \Log::info('importProducts called');
        \Log::info('Request data:', $request->all());
        
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);
        
        try {
            $file = $request->file('archivo');
            \Log::info('File received:', ['name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);
            
            // Usar el archivo temporal directamente
            $tempPath = $file->getRealPath();
            \Log::info('Temp file path:', ['path' => $tempPath]);
            \Log::info('File exists:', ['exists' => file_exists($tempPath)]);
            
            try {
                $spreadsheet = IOFactory::load($tempPath);
                $worksheet = $spreadsheet->getActiveSheet();
                \Log::info('Excel loaded successfully');
            } catch (\Exception $e) {
                \Log::error('Error loading Excel:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                throw $e;
            }
            
            // Obtener todos los modelos existentes en la base de datos
            $existingModels = Product::pluck('modelo')->toArray();
            \Log::info('Existing models count:', ['count' => count($existingModels)]);
            
            $data = [];
            $highestRow = $worksheet->getHighestRow();
            \Log::info('Highest row:', ['row' => $highestRow]);
            
            // Leer desde fila 2 (la fila 1 son títulos)
            for ($row = 2; $row <= $highestRow; $row++) {
                // Columna G = columna 7 (modelo del producto)
                $modelo = $worksheet->getCell('G' . $row)->getValue();
                
                // Columna H = columna 8 (cantidad solicitada)
                $cantidad = $worksheet->getCell('H' . $row)->getValue();
                
                // Columna P = columna 16 (fecha de vencimiento)
                $fechaVencimiento = $worksheet->getCell('P' . $row)->getValue();
                
                // Solo procesar si hay un modelo
                if (!empty($modelo)) {
                    $modelo = trim($modelo);
                    $exists = in_array($modelo, $existingModels);
                    
                    $data[] = [
                        'row' => $row,
                        'modelo' => $modelo,
                        'cantidad' => $cantidad,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'existe' => $exists,
                    ];
                }
            }
            
            \Log::info('Data processed:', ['count' => count($data)]);
            
            // Calcular estadísticas
            $total = count($data);
            $coincidencias = count(array_filter($data, fn($item) => $item['existe']));
            $noCoincidencias = $total - $coincidencias;
            $modelosNoExistentes = array_filter($data, fn($item) => !$item['existe']);
            
            \Log::info('Statistics:', [
                'total' => $total,
                'coincidencias' => $coincidencias,
                'no_coincidencias' => $noCoincidencias,
            ]);
            
            // Detectar si hay sábados entre las fases (solo si todos los productos existen)
            $hasSaturdayInPhases = false;
            $phasesWithSaturday = [];
            
            if ($noCoincidencias === 0 && !empty($data)) {
                // Obtener la fecha de vencimiento del primer registro
                $fechaVencimiento = $data[0]['fecha_vencimiento'];
                
                // Convertir la fecha de Excel a formato Y-m-d
                if (is_numeric($fechaVencimiento)) {
                    $fechaEntrega = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaVencimiento)
                        ->format('Y-m-d');
                } else {
                    $fechaEntrega = \Carbon\Carbon::parse($fechaVencimiento)->format('Y-m-d');
                }
                
                // Calcular fases sin incluir sábados (comportamiento actual)
                $phaseDates = Program::calculatePhaseDates($fechaEntrega, false);
                
                // Calcular fases incluyendo sábados para comparación
                $phaseDatesWithSaturdays = Program::calculatePhaseDates($fechaEntrega, true);
                
                // Comparar las dos calculaciones para detectar sábados que se están saltando
                foreach ($phaseDates as $phaseName => $dateWithoutSaturday) {
                    $dateWithSaturday = $phaseDatesWithSaturdays[$phaseName];
                    
                    // Si las fechas son diferentes, significa que hay sábados en medio
                    if ($dateWithoutSaturday->format('Y-m-d') !== $dateWithSaturday->format('Y-m-d')) {
                        $hasSaturdayInPhases = true;
                        
                        // Encontrar los sábados entre las dos fechas
                        $current = $dateWithSaturday->copy();
                        $saturdaysFound = [];
                        
                        while ($current->gt($dateWithoutSaturday)) {
                            if ($current->isSaturday()) {
                                $saturdaysFound[] = $current->format('d/m/Y');
                            }
                            $current->subDay();
                        }
                        
                        $phasesWithSaturday[] = [
                            'fase' => $phaseName,
                            'fecha_sin_sabado' => $dateWithoutSaturday->format('d/m/Y'),
                            'fecha_con_sabado' => $dateWithSaturday->format('d/m/Y'),
                            'sabados_saltados' => $saturdaysFound,
                        ];
                    }
                }
                
                \Log::info('Saturday detection:', [
                    'has_saturday' => $hasSaturdayInPhases,
                    'phases_with_saturday' => $phasesWithSaturday,
                ]);
            }
            
            return back()->with([
                'success' => 'Archivo procesado exitosamente.',
                'import_data' => [
                    'total' => $total,
                    'coincidencias' => $coincidencias,
                    'no_coincidencias' => $noCoincidencias,
                    'modelos_no_existentes' => array_values($modelosNoExistentes),
                    'data' => $data,
                    'has_saturday_in_phases' => $hasSaturdayInPhases,
                    'phases_with_saturday' => $phasesWithSaturday,
                    'fecha_entrega' => $noCoincidencias === 0 && !empty($data) ? 
                        (is_numeric($data[0]['fecha_vencimiento']) ? 
                            \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[0]['fecha_vencimiento'])->format('Y-m-d') :
                            \Carbon\Carbon::parse($data[0]['fecha_vencimiento'])->format('Y-m-d')) : null,
                ],
                'program_created' => null,
            ]);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo Excel: ' . $e->getMessage());
        }
    }
    
    public function createProgramFromExcel(Request $request)
    {
        \Log::info('createProgramFromExcel called');
        \Log::info('Request method:', ['method' => $request->method()]);
        \Log::info('Request data:', $request->all());
        \Log::info('Request headers:', ['content-type' => $request->header('Content-Type')]);
        
        try {
            $request->validate([
                'data' => 'required|array',
                'data.*.modelo' => 'required|string',
                'data.*.cantidad' => 'required|numeric|min:1',
                'data.*.fecha_vencimiento' => 'required',
                'include_saturdays' => 'sometimes|boolean',
            ]);
            
            \Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', ['errors' => $e->errors()]);
            throw $e;
        }
        
        DB::beginTransaction();
        
        try {
            \Log::info('Starting program creation');
            
            // Obtener la fecha de vencimiento (es la misma para todos los productos)
            $fechaVencimiento = $request->data[0]['fecha_vencimiento'];
            \Log::info('Fecha vencimiento from Excel:', ['fecha' => $fechaVencimiento]);
            
            // Convertir la fecha de Excel a formato Y-m-d
            if (is_numeric($fechaVencimiento)) {
                // Excel stores dates as serial numbers (base date is 1900-01-01 for Windows)
                $fechaEntrega = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaVencimiento)
                    ->format('Y-m-d');
            } else {
                // Try to parse as string
                $fechaEntrega = \Carbon\Carbon::parse($fechaVencimiento)->format('Y-m-d');
            }
            \Log::info('Fecha entrega converted:', ['fecha' => $fechaEntrega]);
            
            // NOTA: No validamos fecha mínima para importación desde Excel
            // ya que el usuario está importando datos con una fecha específica
            
            // Calcular fechas de fases (incluyendo sábados si el usuario lo seleccionó)
            $includeSaturdays = $request->input('include_saturdays', false);
            $phaseDates = Program::calculatePhaseDates($fechaEntrega, $includeSaturdays);
            \Log::info('Phase dates calculated:', ['dates' => $phaseDates, 'include_saturdays' => $includeSaturdays]);
            
            // Generar código del programa: fecha de vencimiento + 3 números aleatorios
            $codigo = \Carbon\Carbon::parse($fechaEntrega)->format('d-m-Y') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            \Log::info('Program code generated:', ['codigo' => $codigo]);
            
            // Calcular total_time y total_piezas
            $totalTime = 0;
            $totalPiezas = 0;
            
            // Obtener solo los productos con piezas > 0 (no usar keyBy porque hay múltiples por modelo)
            $modelos = array_unique(array_column($request->data, 'modelo'));
            $allProducts = \App\Models\Product::whereIn('modelo', $modelos)->where('piezas', '>', 0)->get();
            
            // Para el cálculo de totales del programa, sumamos de TODOS los productos (independientemente del centro)
            foreach ($request->data as $item) {
                // Tomar el primer producto encontrado con ese modelo para el cálculo general
                $product = $allProducts->where('modelo', $item['modelo'])->first();
                if ($product) {
                    $totalTime += $product->tiempo * $item['cantidad'];
                    $totalPiezas += $product->piezas * $item['cantidad'];
                }
            }
            
            \Log::info('Totals calculated:', ['total_time' => $totalTime, 'total_piezas' => $totalPiezas]);
            
            // Crear el programa
            $program = Program::create([
                'codigo' => $codigo,
                'fecha_entrega' => $fechaEntrega,
                'fecha_fase1' => $phaseDates['fase1']->format('Y-m-d'),
                'fecha_fase2' => $phaseDates['fase2']->format('Y-m-d'),
                'fecha_fase3' => $phaseDates['fase3']->format('Y-m-d'),
                'fecha_fase4' => $phaseDates['fase4']->format('Y-m-d'),
                'total_time' => $totalTime,
                'total_piezas' => $totalPiezas,
                'created_by' => auth()->id(),
            ]);
            \Log::info('Program created:', ['id' => $program->id]);
            
            // Crear los detalles del programa
            foreach ($request->data as $item) {
                ProgramDetail::create([
                    'program_id' => $program->id,
                    'modelo' => $item['modelo'],
                    'cantidad_solicitada' => $item['cantidad'],
                ]);
            }
            \Log::info('Program details created');
            
            // Crear daily_programs y schedules para cada fase
            $phases = [
                1 => $phaseDates['fase1']->format('Y-m-d'),
                2 => $phaseDates['fase2']->format('Y-m-d'),
                3 => $phaseDates['fase3']->format('Y-m-d'),
                4 => $phaseDates['fase4']->format('Y-m-d'),
            ];
            
            foreach ($phases as $phaseNumber => $phaseDate) {
                \Log::info('Processing phase:', ['phase' => $phaseNumber, 'date' => $phaseDate]);
                
                // Obtener centros de trabajo de esta fase
                $workCenters = \App\Models\WorkCenter::where('phase', $phaseNumber)->get();
                \Log::info('Work centers for phase:', ['count' => $workCenters->count()]);
                
                foreach ($workCenters as $workCenter) {
                    // Calcular sumatoria de piezas para este centro de trabajo
                    $centerPiezas = 0;
                    foreach ($request->data as $item) {
                        // Obtener TODOS los productos con este modelo
                        $productsForModel = $allProducts->where('modelo', $item['modelo']);
                        // Sumar piezas de los productos que corresponden a este centro
                        foreach ($productsForModel as $product) {
                            if ($product->id_work_center == $workCenter->id) {
                                $centerPiezas += $product->piezas * $item['cantidad'];
                            }
                        }
                    }
                    
                    \Log::info('Center piezas:', ['center_id' => $workCenter->id, 'piezas' => $centerPiezas]);
                    
                    if ($centerPiezas > 0) {
                        // Crear daily_program
                        $dailyProgram = \App\Models\DailyProgram::create([
                            'date' => $phaseDate,
                            'id_work_center' => $workCenter->id,
                            'shift' => 'matutino',
                            'programmed' => $centerPiezas,
                            'backwardness' => 0,
                            'advanced' => 0,
                            'shift_hours' => 9.0,
                            'program_id' => $program->id,
                        ]);
                        \Log::info('Daily program created:', ['id' => $dailyProgram->id]);
                        
                        // Generar schedules para todas las líneas del centro
                        $productionLines = $workCenter->productionLines;
                        $this->generateSchedulesForProgram($dailyProgram, $productionLines);
                        \Log::info('Schedules generated for center:', ['center_id' => $workCenter->id]);
                    }
                }
            }
            
            DB::commit();
            \Log::info('Transaction committed');
            
            return back()->with([
                'success' => "Programa creado exitosamente. Código: {$codigo}",
                'program_created' => [
                    'id' => $program->id,
                    'codigo' => $codigo,
                ],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error creating program:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return back()->with('error', 'Error al crear el programa: ' . $e->getMessage());
        }
    }
    
    private function generateSchedulesForProgram(\App\Models\DailyProgram $program, $productionLines)
    {
        $startTime = $program->shift === 'matutino' ? '08:00' : 
                    ($program->shift === 'vespertino' ? '16:00' : '00:00');
        
        $hours = $this->generateHourlySchedule($startTime, (int)$program->shift_hours);
        
        // Generar schedules para cada línea del centro
        foreach ($productionLines as $line) {
            foreach ($hours as $hour) {
                \App\Models\Schedule::firstOrCreate(
                    [
                        'id_daily_program' => $program->id,
                        'id_production_line' => $line->id,
                        'start_time' => $hour['start'],
                        'end_time' => $hour['end'],
                    ],
                    [
                        'produced' => 0,
                    ]
                );
            }
        }
    }
    
    private function generateHourlySchedule($startTime, $hours)
    {
        $schedule = [];
        $current = \Carbon\Carbon::parse($startTime);

        for ($i = 0; $i < $hours; $i++) {
            $start = $current->format('H:i');
            $end = $current->addHour()->format('H:i');
            $schedule[] = ['start' => $start, 'end' => $end];
        }

        return $schedule;
    }

    /**
     * Calcular fechas de fase para programas de recuperación
     * Basado en la fase del centro de trabajo seleccionado
     */
    private function calculateRecoveryPhaseDates($selectedDate, $workCenterPhase)
    {
        $selectedDate = \Carbon\Carbon::parse($selectedDate);
        
        // Definir las fechas de fase basadas en la fase del centro de trabajo
        // La fecha seleccionada es la fecha de la fase del centro
        switch ($workCenterPhase) {
            case 1:
                // Fase 1: fecha seleccionada
                $fase1 = $selectedDate->copy();
                $fase2 = Program::addWorkingDays($fase1, 1);
                $fase3 = Program::addWorkingDays($fase2, 1);
                $fechaEntrega = Program::addWorkingDays($fase3, 1);
                break;
            case 2:
                // Fase 2: fecha seleccionada
                $fase2 = $selectedDate->copy();
                $fase1 = Program::addWorkingDays($fase2, -1);
                $fase3 = Program::addWorkingDays($fase2, 1);
                $fechaEntrega = Program::addWorkingDays($fase3, 1);
                break;
            case 3:
                // Fase 3: fecha seleccionada
                $fase3 = $selectedDate->copy();
                $fase2 = Program::addWorkingDays($fase3, -1);
                $fase1 = Program::addWorkingDays($fase2, -1);
                $fechaEntrega = Program::addWorkingDays($fase3, 1);
                break;
            case 4:
                // Fase 4 (entrega): fecha seleccionada
                $fechaEntrega = $selectedDate->copy();
                $fase3 = Program::addWorkingDays($fechaEntrega, -1);
                $fase2 = Program::addWorkingDays($fase3, -1);
                $fase1 = Program::addWorkingDays($fase2, -1);
                break;
            default:
                // Por defecto, fase 1
                $fase1 = $selectedDate->copy();
                $fase2 = Program::addWorkingDays($fase1, 1);
                $fase3 = Program::addWorkingDays($fase2, 1);
                $fechaEntrega = Program::addWorkingDays($fase3, 1);
                break;
        }
        
        return [
            'fecha_entrega' => $fechaEntrega->format('Y-m-d'),
            'fase1' => $fase1->format('Y-m-d'),
            'fase2' => $fase2->format('Y-m-d'),
            'fase3' => $fase3->format('Y-m-d'),
            'fase4' => $fechaEntrega->format('Y-m-d'),
        ];
    }

    // ============================================
    // AJUSTES DE PRODUCCIÓN
    // ============================================

    public function registerAdjustmentsView()
    {
        $programs = Program::select('id', 'codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4')
            ->orderBy('fecha_entrega', 'desc')
            ->get()
            ->map(function ($program) {
                return [
                    'id' => $program->id,
                    'codigo' => $program->codigo,
                    'fecha_entrega' => $program->fecha_entrega,
                    'fecha_entrega_formatted' => $program->fecha_entrega ? \Carbon\Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
                    'fecha_fase1' => $program->fecha_fase1,
                    'fecha_fase1_formatted' => $program->fecha_fase1 ? \Carbon\Carbon::parse($program->fecha_fase1)->format('d/m/Y') : null,
                    'fecha_fase2' => $program->fecha_fase2,
                    'fecha_fase2_formatted' => $program->fecha_fase2 ? \Carbon\Carbon::parse($program->fecha_fase2)->format('d/m/Y') : null,
                    'fecha_fase3' => $program->fecha_fase3,
                    'fecha_fase3_formatted' => $program->fecha_fase3 ? \Carbon\Carbon::parse($program->fecha_fase3)->format('d/m/Y') : null,
                    'fecha_fase4' => $program->fecha_fase4,
                    'fecha_fase4_formatted' => $program->fecha_fase4 ? \Carbon\Carbon::parse($program->fecha_fase4)->format('d/m/Y') : null,
                ];
            });
        $workCenters = WorkCenter::orderBy('name')->get();

        return Inertia::render('IngenieroProcesos/RegisterAdjustments', [
            'programs' => $programs,
            'workCenters' => $workCenters,
        ]);
    }

    public function loadDailyProgramsForAdjustment(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'work_center_id' => 'required|exists:work_centers,id',
            'phase_date' => 'required|date',
        ]);

        // Buscar daily programs por fecha y centro (sin filtrar por program_id por ahora)
        $dailyPrograms = \App\Models\DailyProgram::with(['workCenter', 'program'])
            ->where('date', $request->phase_date)
            ->where('id_work_center', $request->work_center_id)
            ->orderBy('shift')
            ->get()
            ->map(function ($dailyProgram) {
                $dailyProgram->date_formatted = $dailyProgram->date ? \Carbon\Carbon::parse($dailyProgram->date)->format('d/m/Y') : null;
                return $dailyProgram;
            });

        $programs = Program::select('id', 'codigo', 'fecha_entrega', 'fecha_fase1', 'fecha_fase2', 'fecha_fase3', 'fecha_fase4')
            ->orderBy('fecha_entrega', 'desc')
            ->get()
            ->map(function ($program) {
                return [
                    'id' => $program->id,
                    'codigo' => $program->codigo,
                    'fecha_entrega' => $program->fecha_entrega,
                    'fecha_entrega_formatted' => $program->fecha_entrega ? \Carbon\Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
                    'fecha_fase1' => $program->fecha_fase1,
                    'fecha_fase1_formatted' => $program->fecha_fase1 ? \Carbon\Carbon::parse($program->fecha_fase1)->format('d/m/Y') : null,
                    'fecha_fase2' => $program->fecha_fase2,
                    'fecha_fase2_formatted' => $program->fecha_fase2 ? \Carbon\Carbon::parse($program->fecha_fase2)->format('d/m/Y') : null,
                    'fecha_fase3' => $program->fecha_fase3,
                    'fecha_fase3_formatted' => $program->fecha_fase3 ? \Carbon\Carbon::parse($program->fecha_fase3)->format('d/m/Y') : null,
                    'fecha_fase4' => $program->fecha_fase4,
                    'fecha_fase4_formatted' => $program->fecha_fase4 ? \Carbon\Carbon::parse($program->fecha_fase4)->format('d/m/Y') : null,
                ];
            });

        return Inertia::render('IngenieroProcesos/RegisterAdjustments', [
            'programs' => $programs,
            'workCenters' => WorkCenter::orderBy('name')->get(),
            'dailyPrograms' => $dailyPrograms,
        ]);
    }

    public function productionAdjustments(Request $request)
    {
        $query = \App\Models\ProductionAdjustment::with(['dailyProgram', 'workCenter', 'adjustedBy', 'sourceProgram', 'targetProgram']);

        // Filtro por centro de trabajo
        if ($request->has('work_center_id') && $request->work_center_id) {
            $query->where('id_work_center', $request->work_center_id);
        }

        // Filtros de fecha
        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->orderBy('created_at', 'desc')->paginate(50);
        $workCenters = WorkCenter::orderBy('name')->get();

        return Inertia::render('IngenieroProcesos/ProductionAdjustments', [
            'adjustments' => $adjustments,
            'filters' => $request->only(['work_center_id', 'date_from', 'date_to']),
            'workCenters' => $workCenters,
        ]);
    }

    public function editDailyProgram($id)
    {
        $dailyProgram = \App\Models\DailyProgram::with(['workCenter', 'program', 'schedules'])->findOrFail($id);

        // Formatear fecha para la vista
        $dailyProgram->date_formatted = $dailyProgram->date ? \Carbon\Carbon::parse($dailyProgram->date)->format('d/m/Y') : null;

        return Inertia::render('IngenieroProcesos/EditDailyProgram', [
            'dailyProgram' => $dailyProgram,
        ]);
    }

    public function updateDailyProgram(Request $request, $id)
    {
        $request->validate([
            'programmed' => 'required|integer|min:0',
            'backwardness' => 'required|integer|min:0',
            'advanced' => 'required|integer|min:0',
            'total_produced' => 'required|integer|min:0',
            'total_rejected' => 'required|integer|min:0',
            'reason' => 'required|string',
        ]);

        $dailyProgram = \App\Models\DailyProgram::findOrFail($id);

        DB::beginTransaction();

        try {
            // Guardar valores anteriores
            $previousProgrammed = $dailyProgram->programmed;
            $previousBackwardness = $dailyProgram->backwardness;
            $previousAdvanced = $dailyProgram->advanced;
            $previousProduced = $dailyProgram->total_produced;
            $previousRejected = $dailyProgram->total_rejected;

            // Actualizar el daily program
            $dailyProgram->update([
                'programmed' => $request->programmed,
                'backwardness' => $request->backwardness,
                'advanced' => $request->advanced,
                'total_produced' => $request->total_produced,
                'total_rejected' => $request->total_rejected,
            ]);

            // Registrar ajustes si hubo cambios
            if ($previousProgrammed != $request->programmed) {
                \App\Models\ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'programmed',
                    'previous_value' => $previousProgrammed,
                    'new_value' => $request->programmed,
                    'difference' => $request->programmed - $previousProgrammed,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            if ($previousBackwardness != $request->backwardness) {
                \App\Models\ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'backwardness',
                    'previous_value' => $previousBackwardness,
                    'new_value' => $request->backwardness,
                    'difference' => $request->backwardness - $previousBackwardness,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            if ($previousAdvanced != $request->advanced) {
                \App\Models\ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'advanced',
                    'previous_value' => $previousAdvanced,
                    'new_value' => $request->advanced,
                    'difference' => $request->advanced - $previousAdvanced,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            if ($previousProduced != $request->total_produced) {
                \App\Models\ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'total_produced',
                    'previous_value' => $previousProduced,
                    'new_value' => $request->total_produced,
                    'difference' => $request->total_produced - $previousProduced,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            if ($previousRejected != $request->total_rejected) {
                \App\Models\ProductionAdjustment::create([
                    'id_daily_program' => $dailyProgram->id,
                    'id_work_center' => $dailyProgram->id_work_center,
                    'adjustment_type' => 'correction',
                    'field_adjusted' => 'total_rejected',
                    'previous_value' => $previousRejected,
                    'new_value' => $request->total_rejected,
                    'difference' => $request->total_rejected - $previousRejected,
                    'adjustment_category' => 'correction',
                    'reason' => $request->reason,
                    'adjusted_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Ajuste registrado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar el ajuste: ' . $e->getMessage());
        }
    }

    // ============================================
    // EXPORTACIÓN DE PRODUCTOS A EXCEL
    // ============================================

    public function exportProductsView()
    {
        return Inertia::render('IngenieroProcesos/ExportProducts');
    }

    public function exportProducts()
    {
        $products = Product::with('workCenter')
            ->orderBy('id')
            ->get()
            ->map(function ($product) {
                return [
                    'Modelo' => $product->modelo,
                    'id_centro_trabajo' => $product->id_work_center,
                    'Nombre Centro de trabajo' => $product->workCenter ? $product->workCenter->name : '',
                    'Tiempo' => $product->tiempo,
                    'Piezas' => $product->piezas,
                ];
            });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $headers = array_keys($products->first());
        $sheet->fromArray($headers, null, 'A1');

        // Datos
        $row = 2;
        foreach ($products as $product) {
            $sheet->fromArray(array_values($product), null, 'A' . $row);
            $row++;
        }

        // Estilos para encabezados
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:E1')->getFont()->getColor()->setRGB('FFFFFF');

        // Ajustar ancho de columnas
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $fileName = 'productos_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    // ============================================
    // CRUD DE PROGRAMAS DE RECUPERACIÓN (ATRASOS)
    // ============================================
    // ROLLBACK: Eliminar toda esta sección para volver al comportamiento original
    // ============================================

    /**
     * Mostrar vista especial para programas de recuperación
     */
    private function showRecoveryProgram(Program $program, Request $request)
    {
        $program->load('creator');
        
        // Obtener DailyPrograms asociados a este programa de recuperación
        $dailyPrograms = \App\Models\DailyProgram::where('program_id', $program->id)
            ->with(['workCenter', 'schedules.productionLine'])
            ->get()
            ->map(function ($dp) {
                $totalProduced = $dp->schedules->sum('produced');
                $totalRejected = $dp->schedules->sum('rejected') ?? 0;
                
                return [
                    'id' => $dp->id,
                    'date' => $dp->date,
                    'date_formatted' => $dp->date ? \Carbon\Carbon::parse($dp->date)->format('d/m/Y') : null,
                    'shift' => $dp->shift,
                    'work_center' => $dp->workCenter->name,
                    'work_center_id' => $dp->workCenter->id,
                    'programmed' => $dp->programmed,
                    'backwardness' => $dp->backwardness,
                    'advanced' => $dp->advanced,
                    'total_produced' => $totalProduced,
                    'total_rejected' => $totalRejected,
                    'shift_hours' => $dp->shift_hours,
                ];
            });

        $programData = [
            'id' => $program->id,
            'codigo' => $program->codigo,
            'fecha_entrega' => $program->fecha_entrega,
            'fecha_entrega_formatted' => $program->fecha_entrega ? \Carbon\Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
            'program_type' => $program->program_type ?? 'normal',
            'created_at' => $program->created_at,
            'created_at_formatted' => $program->created_at ? \Carbon\Carbon::parse($program->created_at)->format('d/m/Y H:i') : null,
            'creator' => $program->creator,
        ];

        return Inertia::render('IngenieroProcesos/ViewRecoveryProgram', [
            'program' => $programData,
            'dailyPrograms' => $dailyPrograms,
        ]);
    }

    /**
     * Listar programas de recuperación
     */
    public function recoveryIndex()
    {
        $recoveryPrograms = Program::where('program_type', 'recovery')
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($program) {
                return [
                    'id' => $program->id,
                    'codigo' => $program->codigo,
                    'fecha_entrega' => $program->fecha_entrega,
                    'fecha_entrega_formatted' => $program->fecha_entrega ? \Carbon\Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
                    'created_at' => $program->created_at,
                    'created_at_formatted' => $program->created_at ? \Carbon\Carbon::parse($program->created_at)->format('d/m/Y') : null,
                    'creator' => $program->creator,
                ];
            });

        return Inertia::render('IngenieroProcesos/RecoveryIndex', [
            'programs' => $recoveryPrograms,
        ]);
    }

    /**
     * Mostrar formulario para crear programa de recuperación
     */
    public function createRecovery()
    {
        $workCenters = WorkCenter::orderBy('name')->get();
        
        return Inertia::render('IngenieroProcesos/CreateRecovery', [
            'workCenters' => $workCenters,
        ]);
    }

    /**
     * Obtener balance acumulado de un centro de trabajo
     */
    public function getWorkCenterBalance($workCenterId)
    {
        $balance = \App\Models\WorkCenterBalance::where('id_work_center', $workCenterId)->first();
        
        return response()->json([
            'accumulated_backwardness' => $balance ? $balance->accumulated_backwardness : 0,
            'accumulated_advanced' => $balance ? $balance->accumulated_advanced : 0,
        ]);
    }

    /**
     * Guardar programa de recuperación
     */
    public function storeRecovery(Request $request)
    {
        $request->validate([
            'work_center_id' => 'required|exists:work_centers,id',
            'date' => 'required|date',
            'shift' => 'required|in:matutino,vespertino,nocturno',
            'cantidad_piezas' => 'required|integer|min:1',
            'shift_hours' => 'nullable|numeric|min:1|max:24',
            'observaciones' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        
        try {
            // Obtener el centro de trabajo para conocer su fase
            $workCenter = WorkCenter::findOrFail($request->work_center_id);
            $workCenterPhase = $workCenter->phase;
            
            // Calcular fechas de fase basadas en la fase del centro de trabajo
            // La fecha seleccionada es la fecha de la fase del centro
            $phaseDates = $this->calculateRecoveryPhaseDates($request->date, $workCenterPhase);
            
            // Crear Program de recuperación
            $program = Program::create([
                'codigo' => 'REC-' . now()->format('Ymd-His'),
                'fecha_entrega' => $phaseDates['fecha_entrega'],
                'fecha_fase1' => $phaseDates['fase1'],
                'fecha_fase2' => $phaseDates['fase2'],
                'fecha_fase3' => $phaseDates['fase3'],
                'fecha_fase4' => $phaseDates['fase4'],
                'program_type' => 'recovery',
                'created_by' => auth()->id(),
            ]);

            // Crear DailyProgram
            $dailyProgram = \App\Models\DailyProgram::create([
                'date' => $request->date,
                'id_work_center' => $request->work_center_id,
                'shift' => $request->shift,
                'programmed' => $request->cantidad_piezas,
                'backwardness' => 0,
                'advanced' => 0,
                'shift_hours' => $request->shift_hours ?? 9.0,
                'program_id' => $program->id,
            ]);

            // Generar schedules para todas las líneas del centro
            $productionLines = $workCenter->productionLines;
            
            if ($productionLines->count() === 0) {
                \Log::warning('No production lines found for work center', ['work_center_id' => $request->work_center_id]);
            }
            
            $this->generateSchedulesForProgram($dailyProgram, $productionLines);

            // Reducir el atraso acumulado del centro de trabajo
            $balance = \App\Models\WorkCenterBalance::getOrCreateForWorkCenter($request->work_center_id);
            $newBackwardness = max(0, $balance->accumulated_backwardness - $request->cantidad_piezas);
            $balance->accumulated_backwardness = $newBackwardness;
            $balance->last_calculated_at = now();
            $balance->save();

            DB::commit();
            
            return redirect()->route('ingeniero-procesos.recuperacion.show', $program->id)
                ->with('success', 'Programa de recuperación creado exitosamente. Atraso actualizado de ' . ($balance->accumulated_backwardness + $request->cantidad_piezas) . ' a ' . $newBackwardness . '.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el programa: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar programa de recuperación individual
     */
    public function showRecovery(Program $program)
    {
        // Verificar que sea un programa de recuperación
        if (!isset($program->program_type) || $program->program_type !== 'recovery') {
            return redirect()->route('ingeniero-procesos.show', $program->id);
        }

        return $this->showRecoveryProgram($program, request());
    }

    /**
     * Eliminar programa de recuperación
     */
    public function destroyRecovery(Program $program)
    {
        // Verificar que sea un programa de recuperación
        if (!isset($program->program_type) || $program->program_type !== 'recovery') {
            return back()->with('error', 'Solo se pueden eliminar programas de recuperación.');
        }

        DB::beginTransaction();
        
        try {
            // Obtener los daily programs asociados
            $dailyPrograms = \App\Models\DailyProgram::where('program_id', $program->id)->get();
            
            // Verificar si hay strikes asociados
            $hasStrikes = \App\Models\Strike::whereIn('id_daily_program', $dailyPrograms->pluck('id'))->exists();
            if ($hasStrikes) {
                return back()->with('error', 'No se puede eliminar el programa porque tiene paros registrados.');
            }
            
            // Verificar si hay rejected pieces asociados
            $hasRejectedPieces = \App\Models\RejectedPiece::whereIn('id_daily_program', $dailyPrograms->pluck('id'))->exists();
            if ($hasRejectedPieces) {
                return back()->with('error', 'No se puede eliminar el programa porque tiene piezas rechazadas registradas.');
            }
            
            // Eliminar schedules de cada daily program
            foreach ($dailyPrograms as $dailyProgram) {
                \App\Models\Schedule::where('id_daily_program', $dailyProgram->id)->delete();
            }
            
            // Eliminar daily programs
            \App\Models\DailyProgram::where('program_id', $program->id)->delete();
            
            // Eliminar el programa (no tiene program_details)
            $program->delete();
            
            DB::commit();
            
            return redirect()->route('ingeniero-procesos.recuperacion.index')
                ->with('success', 'Programa de recuperación eliminado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar el programa: ' . $e->getMessage());
        }
    }

    /**
     * Editar programa de recuperación
     */
    public function editRecovery(Program $program)
    {
        // Verificar que sea un programa de recuperación
        if (!isset($program->program_type) || $program->program_type !== 'recovery') {
            return back()->with('error', 'Solo se pueden editar programas de recuperación.');
        }

        $dailyPrograms = \App\Models\DailyProgram::where('program_id', $program->id)
            ->with('workCenter')
            ->get();

        $workCenters = WorkCenter::orderBy('name')->get();

        // Cargar el creador del programa
        $program->load('creator');

        // Preparar datos del programa para la vista
        $programData = [
            'id' => $program->id,
            'codigo' => $program->codigo,
            'fecha_entrega' => $program->fecha_entrega,
            'fecha_entrega_formatted' => $program->fecha_entrega ? \Carbon\Carbon::parse($program->fecha_entrega)->format('d/m/Y') : null,
            'fecha_fase1' => $program->fecha_fase1,
            'fecha_fase1_formatted' => $program->fecha_fase1 ? \Carbon\Carbon::parse($program->fecha_fase1)->format('d/m/Y') : null,
            'fecha_fase2' => $program->fecha_fase2,
            'fecha_fase2_formatted' => $program->fecha_fase2 ? \Carbon\Carbon::parse($program->fecha_fase2)->format('d/m/Y') : null,
            'fecha_fase3' => $program->fecha_fase3,
            'fecha_fase3_formatted' => $program->fecha_fase3 ? \Carbon\Carbon::parse($program->fecha_fase3)->format('d/m/Y') : null,
            'fecha_fase4' => $program->fecha_fase4,
            'fecha_fase4_formatted' => $program->fecha_fase4 ? \Carbon\Carbon::parse($program->fecha_fase4)->format('d/m/Y') : null,
            'program_type' => $program->program_type ?? 'normal',
            'created_at' => $program->created_at,
            'created_at_formatted' => $program->created_at ? \Carbon\Carbon::parse($program->created_at)->format('d/m/Y H:i') : null,
            'creator' => $program->creator,
        ];

        return Inertia::render('IngenieroProcesos/EditRecovery', [
            'program' => $programData,
            'dailyPrograms' => $dailyPrograms,
            'workCenters' => $workCenters,
        ]);
    }

    /**
     * Actualizar programa de recuperación
     */
    public function updateRecovery(Request $request, Program $program)
    {
        // Verificar que sea un programa de recuperación
        if (!isset($program->program_type) || $program->program_type !== 'recovery') {
            return back()->with('error', 'Solo se pueden actualizar programas de recuperación.');
        }

        $request->validate([
            'daily_programs' => 'required|array',
            'daily_programs.*.id' => 'required|exists:daily_programs,id',
            'daily_programs.*.programmed' => 'required|integer|min:0',
            'daily_programs.*.shift_hours' => 'nullable|numeric|min:1|max:24',
        ]);

        DB::beginTransaction();
        
        try {
            foreach ($request->daily_programs as $dpData) {
                $dailyProgram = \App\Models\DailyProgram::findOrFail($dpData['id']);
                
                // Verificar que pertenezca a este programa
                if ($dailyProgram->program_id !== $program->id) {
                    throw new \Exception('El DailyProgram no pertenece a este programa.');
                }

                $dailyProgram->update([
                    'programmed' => $dpData['programmed'],
                    'shift_hours' => $dpData['shift_hours'] ?? $dailyProgram->shift_hours,
                ]);
            }

            DB::commit();
            
            return redirect()->route('ingeniero-procesos.recuperacion.show', $program->id)
                ->with('success', 'Programa de recuperación actualizado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el programa: ' . $e->getMessage());
        }
    }

    // ============================================
    // FIN CRUD DE PROGRAMAS DE RECUPERACIÓN
    // ============================================
}
