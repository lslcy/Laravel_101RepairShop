<aside id="app-sidebar" tabindex="-1" aria-label="Main navigation" :inert="isMobile && !sidebarOpen"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
    class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col h-screen fixed left-0 top-0 z-50 -translate-x-full transform transition-transform duration-200 lg:translate-x-0">
    <button type="button" @click="sidebarOpen = false; $nextTick(() => document.getElementById('sidebar-toggle').focus())" aria-label="Close navigation"
        class="absolute right-3 top-3 rounded-md p-2 text-slate-300 hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 lg:hidden">
        <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" /></svg>
    </button>
    <!-- Logo -->
    <div class="flex items-center justify-center px-4 py-4 border-b border-slate-800">
        <a href="{{ route('dashboard') }}" class="flex items-center justify-center w-full">
            <div class="p-2 w-full flex justify-center">
                <!-- Light Mode Logo (Blue) -->
                <img src="{{ asset('img/repairservicelogoblue.png') }}" alt="101 Repair Shop Logo"
                    class="w-40 h-auto max-h-32 object-contain block dark:hidden">
                <!-- Dark Mode Logo (Gray/Red) -->
                <img src="{{ asset('img/repairservicelogogray.png') }}" alt="101 Repair Shop Logo"
                    class="w-40 h-auto max-h-32 object-contain hidden dark:block">
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-0.5">
        <!-- Main -->
        <p class="px-3 pt-2 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Main</p>
        <a href="{{ route('dashboard') }}"
            class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                </path>
            </svg>
            <span class="font-medium">Dashboard</span>
        </a>

        <!-- Management -->
        <p class="px-3 pt-4 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Management</p>
        @if(in_array(auth()->user()->role, ['Administrator', 'Secretary', 'Cashier']))
            <a href="{{ route('customers.index') }}"
                class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('customers.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('customers.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                <span class="font-medium">Customer Info</span>
            </a>
        @endif

        <!-- Service Reports -->
        <a href="{{ route('services.index') }}"
            class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('services.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('services.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <span class="font-medium">Service Reports</span>
        </a>

        <!-- Appointments (booked from the mobile app) -->
        @if(in_array(auth()->user()->role, ['Administrator', 'Secretary']))
            @php
                $pendingAppointments = cache()->remember('sidebar_pending_appointments', 60, fn () => \App\Models\Appointment::where('status', 'Pending')->count());
            @endphp
            <a href="{{ route('appointments.index') }}"
                class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('appointments.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('appointments.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="font-medium flex-1">Appointments</span>
                @if($pendingAppointments > 0)
                    <span class="ml-2 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold {{ request()->routeIs('appointments.*') ? 'bg-white text-blue-600' : 'bg-orange-500 text-white' }}">{{ $pendingAppointments }}</span>
                @endif
            </a>
        @endif

        <!-- Transactions -->
        @if(in_array(auth()->user()->role, ['Administrator', 'Cashier']))
            <a href="{{ route('transactions.index') }}"
                class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('transactions.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('transactions.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                <span class="font-medium">Transactions</span>
            </a>
        @endif

        <!-- Parts -->
        @if(in_array(auth()->user()->role, ['Administrator', 'Secretary']))
            <a href="{{ route('inventory.index') }}"
                class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('inventory.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('inventory.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span class="font-medium">Parts</span>
            </a>
        @endif

        @if(auth()->user()->role === 'Administrator')
            <!-- Administration -->
            <p class="px-3 pt-4 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Administration</p>

            <!-- Users (formerly Staff) -->
            <a href="{{ route('staff.index') }}"
                class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('staff.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('staff.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="font-medium">Users</span>
            </a>

            <!-- Service Prices -->
            <a href="{{ route('prices.index') }}"
                class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('prices.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('prices.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
                <span class="font-medium">Service Prices</span>
            </a>

            <!-- Archive -->
            <a href="{{ route('archive.index') }}"
                class="flex items-center px-3 py-2.5 rounded-lg group {{ request()->routeIs('archive.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('archive.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-300' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                <span class="font-medium">Archive</span>
            </a>
        @endif
    </nav>


</aside>
