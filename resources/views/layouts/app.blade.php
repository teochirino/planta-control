<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Control de Planta') }} - @yield('title')</title>
    
    <!-- Favicons -->
    <link rel="icon" href="/logo-cliente.png">
    <link rel="apple-touch-icon" href="/logo-cliente.png">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            --bg: #eaf0f5;
            --panel: #ffffff;
            --soft: #f4f7fa;
            --text: #0c1c28;
            --muted: #4e6070;
            --border: #d4dee8;
            --navy: #0b2a40;
            --navy2: #174060;
            --steel: #6a8090;
            --green: #0a7c3e;
            --gm: #14a852;
            --gs: #e4f5ec;
            --gg: rgba(10,124,62,.25);
            --amber: #a87000;
            --am: #cf9000;
            --as: #fff6da;
            --ag: rgba(168,112,0,.25);
            --red: #ba2418;
            --rm: #dc3020;
            --rs: #fce9e8;
            --rg: rgba(186,36,24,.25);
            --sh: 0 2px 12px rgba(11,28,40,.08);
        }
        
        body {
            font-family: "Segoe UI", system-ui, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
    </style>
    
    @stack('styles')
</head>
<body class="min-h-screen">
    <div class="min-h-screen flex flex-col">
        @include('layouts.navigation')
        
        @if(session('success'))
            <div class="mx-4 mt-4">
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                    <p class="text-green-800 font-semibold">{{ session('success') }}</p>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mx-4 mt-4">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <p class="text-red-800 font-semibold">{{ session('error') }}</p>
                </div>
            </div>
        @endif
        
        <main class="flex-1 p-2.5">
            @yield('content')
        </main>
    </div>
    
    @stack('scripts')
</body>
</html>
