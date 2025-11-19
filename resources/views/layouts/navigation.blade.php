<nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-900 to-blue-800 border-b border-blue-700 shadow-lg">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Primera fila: Logo/Título y Cerrar Sesión -->
        <div class="flex justify-between items-center h-16 border-b border-blue-700/50">
            <!-- Logo y Título -->
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/logo_fondotrasnparente.png') }}" alt="Logo" class="h-12 w-auto">
                <div>
                    <h2 class="text-white font-bold text-xl">Panel de Administración - InterFlow</h2>
                    <p class="text-blue-300 text-xs">Sistema de Gestión de Transporte</p>
                </div>
            </div>

            <!-- Cerrar Sesión (Desktop) -->
            <div class="hidden sm:flex items-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium text-white transition-all duration-200 shadow-md hover:shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>

            <!-- Hamburger (Mobile) -->
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-blue-200 hover:text-white hover:bg-blue-700 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Segunda fila: Enlaces de Navegación -->
        <div class="flex justify-between items-center h-14">
            <div class="flex">
                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:flex items-center">
                    {{-- 1. Inicio --}}
                    <a href="{{ route('admin.dashboard') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.dashboard')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        Inicio
                    </a>

                    {{-- 2. Usuarios --}}
                    <a href="{{ route('admin.users.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.users.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                        Usuarios
                    </a>

                    {{-- 3. Tarjetas --}}
                    <a href="{{ route('admin.cards.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.cards.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                        </svg>
                        Tarjetas
                    </a>

                    {{-- 4. Líneas --}}
                    <a href="{{ route('admin.rutas.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.rutas.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        Lineas
                    </a>

                    {{-- 5. Buses --}}
                    <a href="{{ route('admin.buses.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.buses.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
                        </svg>
                        Buses
                    </a>

                    {{-- 6. Viajes --}}
                    <a href="{{ route('admin.trips.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.trips.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        Viajes
                    </a>

                    {{-- 7. Transacciones --}}
                    <a href="{{ route('admin.transactions.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.transactions.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                        </svg>
                        Transacciones
                    </a>

                    {{-- 8. Tiempo Real --}}
                    <a href="{{ route('admin.realtime') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.realtime')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        Tiempo Real
                    </a>

                    {{-- 9. Quejas --}}
                    <a href="{{ route('admin.complaints.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.complaints.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Quejas
                    </a>

                    {{-- 10. Reportes --}}
                    <a href="{{ route('admin.reportes.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.reportes.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        Reportes
                    </a>

                    {{-- 11. Devoluciones --}}
                    <a href="{{ route('admin.devoluciones.index') }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('admin.devoluciones.*')
                           ? 'bg-blue-700 text-white shadow-lg'
                           : 'text-blue-100 hover:bg-blue-700/50 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Devoluciones
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-blue-800">
        <div class="pt-2 pb-3 space-y-1">
            {{-- 1. Inicio --}}
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" class="text-blue-100">
                Inicio
            </x-responsive-nav-link>
            {{-- 2. Usuarios --}}
            <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="text-blue-100">
                Usuarios
            </x-responsive-nav-link>
            {{-- 3. Tarjetas --}}
            <x-responsive-nav-link :href="route('admin.cards.index')" :active="request()->routeIs('admin.cards.*')" class="text-blue-100">
                Tarjetas
            </x-responsive-nav-link>
            {{-- 4. Líneas --}}
            <x-responsive-nav-link :href="route('admin.rutas.index')" :active="request()->routeIs('admin.rutas.*')" class="text-blue-100">
                Lineas
            </x-responsive-nav-link>
            {{-- 5. Buses --}}
            <x-responsive-nav-link :href="route('admin.buses.index')" :active="request()->routeIs('admin.buses.*')" class="text-blue-100">
                Buses
            </x-responsive-nav-link>
            {{-- 6. Viajes --}}
            <x-responsive-nav-link :href="route('admin.trips.index')" :active="request()->routeIs('admin.trips.*')" class="text-blue-100">
                Viajes
            </x-responsive-nav-link>
            {{-- 7. Transacciones --}}
            <x-responsive-nav-link :href="route('admin.transactions.index')" :active="request()->routeIs('admin.transactions.*')" class="text-blue-100">
                Transacciones
            </x-responsive-nav-link>
            {{-- 8. Tiempo Real --}}
            <x-responsive-nav-link :href="route('admin.realtime')" :active="request()->routeIs('admin.realtime')" class="text-blue-100">
                Tiempo Real
            </x-responsive-nav-link>
            {{-- 9. Quejas --}}
            <x-responsive-nav-link :href="route('admin.complaints.index')" :active="request()->routeIs('admin.complaints.*')" class="text-blue-100">
                Quejas
            </x-responsive-nav-link>
            {{-- 10. Reportes --}}
            <x-responsive-nav-link :href="route('admin.reportes.index')" :active="request()->routeIs('admin.reportes.*')" class="text-blue-100">
                Reportes
            </x-responsive-nav-link>
            {{-- 11. Devoluciones --}}
            <x-responsive-nav-link :href="route('admin.devoluciones.index')" :active="request()->routeIs('admin.devoluciones.*')" class="text-blue-100">
                Devoluciones
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-blue-700">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-blue-300">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();"
                            class="text-blue-100">
                        Cerrar Sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
