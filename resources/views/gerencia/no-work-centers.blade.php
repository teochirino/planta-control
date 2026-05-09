<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin Centros de Trabajo</title>
    
    <!-- Favicons -->
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.ico">
    
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <svg class="w-24 h-24 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h1 class="text-2xl font-bold mb-2">No hay centros de trabajo disponibles</h1>
            <p class="text-gray-400 mb-6">Contacte al administrador para configurar los centros de trabajo.</p>
            <a href="{{ route('dashboard') }}" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg inline-block">
                Volver al Dashboard
            </a>
        </div>
    </div>
</body>
</html>
