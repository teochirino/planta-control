<nav class="bg-white border-b border-[#d4dee8] shadow-sm">
    <div class="px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6">
                <div>
                    <span class="text-[10px] font-bold tracking-widest uppercase text-[#174060]">Sistema Control de Planta</span>
                    <h1 class="text-2xl font-extrabold text-[#0b2a40] leading-none">{{ config('app.name', 'Planta Control') }}</h1>
                </div>
                
                @auth
                    <div class="flex gap-2">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.users.index') }}" 
                               class="px-4 py-2 text-xs font-bold rounded {{ request()->routeIs('admin.*') ? 'bg-[#0b2a40] text-white' : 'bg-[#f4f7fa] text-[#4e6070] hover:bg-[#e8edf2]' }}">
                                👥 Administrador
                            </a>
                        @endif
                        
                        @if(auth()->user()->isSupervisor())
                            <a href="{{ route('supervisor.dashboard') }}" 
                               class="px-4 py-2 text-xs font-bold rounded {{ request()->routeIs('supervisor.*') ? 'bg-[#0b2a40] text-white' : 'bg-[#f4f7fa] text-[#4e6070] hover:bg-[#e8edf2]' }}">
                                📊 Supervisor
                            </a>
                        @endif
                    </div>
                @endauth
            </div>
            
            <div class="flex items-center gap-3">
                @auth
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1.5 rounded-full bg-[#f4f7fa] border border-[#d4dee8] text-xs font-bold text-[#4e6070]">
                            {{ auth()->user()->profile->title ?? 'Usuario' }}
                        </span>
                        <span class="px-3 py-1.5 rounded-full bg-[#0b2a40] text-white text-xs font-bold">
                            {{ auth()->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded bg-[#fce9e8] text-[#ba2418] border border-[#ebbab8] text-xs font-bold hover:bg-[#ba2418] hover:text-white transition">
                                Salir
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>
