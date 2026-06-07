<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\ColorChangeHistory;
use App\Models\NotificationRecipient;
use App\Mail\MateriaPrimaNotification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AttributeController extends Controller
{
    public function changeColor(Request $request, $attributeId)
    {
        $request->validate([
            'color' => 'required|in:rojo,amarillo,verde,gris',
            'comment' => 'nullable|string|max:100',
        ]);
        
        $attribute = Attribute::findOrFail($attributeId);
        $previousColor = $attribute->color;
        
        ColorChangeHistory::create([
            'id_work_center' => $attribute->id_work_center,
            'id_attribute' => $attribute->id,
            'user_id' => auth()->id(),
            'previous_color' => $previousColor,
            'new_color' => $request->color,
            'comment' => $request->comment,
        ]);
        
        $attribute->update([
            'color' => $request->color,
            'color_changed_at' => Carbon::now(),
        ]);

        // Enviar notificación por email para Materia Prima cuando cambia a rojo o amarillo
        if ($attribute->name === 'Materia Prima' && in_array($request->color, ['rojo', 'amarillo'])) {
            $recipients = NotificationRecipient::where('name', 'Compras')
                ->where('is_active', true)
                ->get();

            if ($recipients->isNotEmpty()) {
                $workCenter = $attribute->workCenter;
                $user = auth()->user();

                foreach ($recipients as $recipient) {
                    Mail::to($recipient->email)->send(new MateriaPrimaNotification(
                        $workCenter->name,
                        $user->name,
                        $request->comment,
                        $request->color
                    ));
                }
            }
        }

        return response()->json([
            'success' => true,
            'attribute' => $attribute->fresh(),
            'elapsed_time' => $attribute->elapsed_time,
        ]);
    }
    
    public function getHistory($attributeId)
    {
        $attribute = Attribute::findOrFail($attributeId);
        
        $history = ColorChangeHistory::with('user')
            ->where('id_attribute', $attributeId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'previous_color' => $item->previous_color,
                    'new_color' => $item->new_color,
                    'comment' => $item->comment,
                    'user_name' => $item->user->name ?? 'Usuario desconocido',
                    'created_at' => $item->created_at->format('d/m/Y H:i:s'),
                    'created_at_human' => $item->created_at->diffForHumans(),
                ];
            });
        
        return response()->json([
            'success' => true,
            'attribute' => $attribute,
            'history' => $history,
        ]);
    }
    
    public function getRecentChanges(Request $request)
    {
        $user = $request->user();
        $workCenterIds = $user->workCenters->pluck('id');
        
        $changes = ColorChangeHistory::with(['attribute', 'user'])
            ->whereIn('id_work_center', $workCenterIds)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'attribute_name' => $item->attribute->name ?? 'Desconocido',
                    'previous_color' => $item->previous_color,
                    'new_color' => $item->new_color,
                    'comment' => $item->comment,
                    'user_name' => $item->user->name ?? 'Usuario desconocido',
                    'created_at' => $item->created_at->toISOString(),
                ];
            });
        
        return response()->json(['changes' => $changes]);
    }
}
