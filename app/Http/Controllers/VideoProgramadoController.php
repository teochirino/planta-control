<?php

namespace App\Http\Controllers;

use App\Models\VideoProgramado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VideoProgramadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $videos = VideoProgramado::orderBy('hora_reproduccion')->get();
        return response()->json($videos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Video store request data:', $request->all());
        \Log::info('Video file:', ['has_file' => $request->hasFile('video')]);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'video' => 'required|file|mimes:mp4,avi,mov,mkv|max:102400', // Max 100MB
            'hora_reproduccion' => 'required|date_format:H:i',
            'dias_semana' => 'required',
            'activo' => 'boolean',
        ]);

        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('videos', 'public');
        }

        // Parse dias_semana if it's a JSON string
        $diasSemana = $request->dias_semana;
        if (is_string($diasSemana)) {
            $diasSemana = json_decode($diasSemana, true);
        }

        $video = VideoProgramado::create([
            'nombre' => $request->nombre,
            'ruta_video' => $path,
            'hora_reproduccion' => $request->hora_reproduccion,
            'dias_semana' => $diasSemana,
            'activo' => $request->activo ?? true,
        ]);

        return response()->json($video, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $video = VideoProgramado::findOrFail($id);
        return response()->json($video);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        \Log::info('Video update request data:', $request->all());

        $video = VideoProgramado::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'video' => 'sometimes|file|mimes:mp4,avi,mov,mkv|max:102400',
            'hora_reproduccion' => 'sometimes|date_format:H:i',
            'dias_semana' => 'sometimes',
            'activo' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('video')) {
            // Delete old video
            if ($video->ruta_video) {
                Storage::disk('public')->delete($video->ruta_video);
            }
            $path = $request->file('video')->store('videos', 'public');
            $video->ruta_video = $path;
        }

        if ($request->has('nombre')) {
            $video->nombre = $request->nombre;
        }
        if ($request->has('hora_reproduccion')) {
            $video->hora_reproduccion = $request->hora_reproduccion;
        }
        if ($request->has('dias_semana')) {
            // Parse dias_semana if it's a JSON string
            $diasSemana = $request->dias_semana;
            if (is_string($diasSemana)) {
                $diasSemana = json_decode($diasSemana, true);
            }
            $video->dias_semana = $diasSemana;
        }
        if ($request->has('activo')) {
            $video->activo = $request->activo;
        }

        $video->save();

        return response()->json($video);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $video = VideoProgramado::findOrFail($id);

        // Delete video file
        if ($video->ruta_video) {
            Storage::disk('public')->delete($video->ruta_video);
        }

        $video->delete();

        return response()->json(null, 204);
    }

    /**
     * Get videos scheduled for current day and time
     */
    public function getScheduledVideos(Request $request)
    {
        // Detect timezone based on environment
        $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1']);
        $timezone = $isLocal ? 'America/Caracas' : 'America/Mexico_City';
        
        $currentDay = Carbon::now($timezone)->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $currentTime = Carbon::now($timezone)->format('H:i');
        $today = Carbon::now($timezone)->toDateString();

        \Log::info('getScheduledVideos - Current time:', [
            'is_local' => $isLocal,
            'timezone' => $timezone,
            'currentDay' => $currentDay,
            'currentTime' => $currentTime,
            'today' => $today,
        ]);

        $allVideos = VideoProgramado::where('activo', true)
            ->whereJsonContains('dias_semana', $currentDay)
            ->get();

        \Log::info('Active videos for current day:', $allVideos->toArray());

        $videos = $allVideos->filter(function ($video) use ($currentTime, $today, $timezone) {
            $scheduledTime = Carbon::parse($video->hora_reproduccion, $timezone)->format('H:i');

            // Check if current time matches the scheduled time exactly (HH:mm)
            $isExactTime = $scheduledTime === $currentTime;

            // Check if video was already reproduced today
            $notReproducedToday = !$video->ultima_reproduccion ||
                Carbon::parse($video->ultima_reproduccion, $timezone)->toDateString() !== $today;

            \Log::info('Video filter check:', [
                'video_id' => $video->id,
                'video_name' => $video->nombre,
                'scheduled_time' => $scheduledTime,
                'current_time' => $currentTime,
                'is_exact_time' => $isExactTime,
                'ultima_reproduccion' => $video->ultima_reproduccion,
                'not_reproduced_today' => $notReproducedToday,
                'will_play' => $isExactTime && $notReproducedToday
            ]);

            return $isExactTime && $notReproducedToday;
        })
        ->values();

        \Log::info('Final scheduled videos:', $videos->toArray());

        return response()->json($videos);
    }

    /**
     * Register video playback
     */
    public function registerPlayback(string $id)
    {
        $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1']);
        $timezone = $isLocal ? 'America/Caracas' : 'America/Mexico_City';

        $video = VideoProgramado::findOrFail($id);
        $video->ultima_reproduccion = Carbon::now($timezone);
        $video->save();

        return response()->json(['message' => 'Playback registered']);
    }

    /**
     * Get all videos scheduled for today (loaded once on page load)
     */
    public function getTodayVideos()
    {
        $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1']);
        $timezone = $isLocal ? 'America/Caracas' : 'America/Mexico_City';
        
        $currentDay = Carbon::now($timezone)->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $today = Carbon::now($timezone)->toDateString();

        $videos = VideoProgramado::where('activo', true)
            ->whereJsonContains('dias_semana', $currentDay)
            ->get()
            ->map(function ($video) use ($today, $timezone) {
                $video->was_reproduced_today = $video->ultima_reproduccion &&
                    Carbon::parse($video->ultima_reproduccion, $timezone)->toDateString() === $today;
                return $video;
            });

        return response()->json($videos);
    }
}
