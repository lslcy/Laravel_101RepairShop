<header
    class="h-14 shrink-0 bg-blue-700 dark:bg-slate-800 text-white border-b border-blue-800 dark:border-slate-700 flex items-center justify-between gap-3 px-4 sm:px-6 sticky top-0 z-40">
    <!-- Module Title + Greeting -->
    <div class="flex min-w-0 items-center gap-3">
        <button id="sidebar-toggle" type="button" @click="sidebarOpen = true; $nextTick(() => document.getElementById('app-sidebar').focus())"
            :aria-expanded="sidebarOpen" aria-controls="app-sidebar" aria-label="Open navigation"
            class="shrink-0 rounded-md p-1.5 text-blue-100 hover:bg-blue-600 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white lg:hidden">
            <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <div class="flex min-w-0 flex-col justify-center">
        <h1 class="truncate text-sm font-semibold text-white leading-tight">
            {{ $pageTitle ?? '101 Repair Service' }}
        </h1>
        <p class="hidden truncate text-xs text-blue-200 leading-tight mt-0.5 sm:block">
            Welcome, {{ auth()->user()->full_name ?? auth()->user()->name ?? 'Guest' }}
            &nbsp;·&nbsp;
            {{ now()->format('l, F j, Y') }}
        </p>
        </div>
    </div>

    <!-- Right Side Actions -->
    <div class="flex shrink-0 items-center space-x-3 sm:space-x-6">
        <!-- Font Size Toggle -->
        <div class="relative flex items-center justify-center" x-data="{ fontOpen: false }">
            <button @click="fontOpen = !fontOpen" @click.away="fontOpen = false"
                class="text-blue-100 hover:text-white transition-colors focus:outline-none flex items-center gap-1"
                title="Adjust Font Size">
                <span class="text-lg font-serif font-bold">A</span><span class="text-sm font-serif">a</span>
                <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="fontOpen" x-transition
                class="absolute right-0 top-full mt-1 w-36 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50 overflow-hidden"
                style="display: none;">
                <button onclick="changeFontSize('sm')"
                    class="w-full text-left px-4 py-3 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">Small</button>
                <button onclick="changeFontSize('md')"
                    class="w-full text-left px-4 py-3 text-base hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border-t border-gray-100 dark:border-gray-700">Medium</button>
                <button onclick="changeFontSize('lg')"
                    class="w-full text-left px-4 py-3 text-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border-t border-gray-100 dark:border-gray-700">Large</button>
            </div>
        </div>

        <!-- Dark Mode Toggle -->
        <button id="theme-toggle" type="button"
            class="flex items-center justify-center text-blue-100 hover:text-white transition-colors focus:outline-none">
            <svg id="theme-toggle-dark-icon" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
            </svg>
            <svg id="theme-toggle-light-icon" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                    fill-rule="evenodd" clip-rule="evenodd"></path>
            </svg>
        </button>

        <!-- Notifications -->
        @php
            $unreadNotifications = Auth::check() ? Auth::user()->unreadNotifications : collect();
            $unreadCount = $unreadNotifications->count();
            $allNotifications = Auth::check() ? Auth::user()->notifications()->take(5)->get() : collect();
        @endphp
        <div class="relative flex items-center justify-center" x-data="{ notifyOpen: false }">
            <button @click="notifyOpen = !notifyOpen" @click.away="notifyOpen = false"
                class="relative flex items-center justify-center text-blue-100 hover:text-white transition-colors focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
                @if($unreadCount > 0)
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-blue-700"></span>
                @endif
            </button>

            <!-- Dropdown Menu -->
            <div x-show="notifyOpen" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50 overflow-hidden flex flex-col max-h-[28rem]"
                style="display: none;">

                <div
                    class="px-4 py-3 shrink-0 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center z-10">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Notifications</h3>
                    @if($unreadCount > 0)
                        <form action="{{ Route::has('notifications.markRead') ? route('notifications.markRead') : '#' }}"
                            method="POST">
                            @csrf
                            <button type="submit"
                                class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">Mark all as
                                read</button>
                        </form>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto min-h-0">
                    @if($allNotifications->count() > 0)
                        @foreach($allNotifications as $notification)
                            @php
                                $isUnread = $notification->unread();
                            @endphp
                            <a href="{{ $notification->data['url'] ?? '#' }}"
                                class="flex gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-[#fcfdfd] dark:hover:bg-gray-700 transition-colors {{ $isUnread ? 'bg-[#f8fbff] dark:bg-slate-800/50' : 'bg-white dark:bg-gray-800' }}">
                                <div class="mt-1 w-2 shrink-0">
                                    @if($isUnread)
                                        <div class="h-2 w-2 rounded-full bg-blue-600"></div>
                                    @endif
                                </div>
                                <div class="flex-1 flex flex-col gap-0.5">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $notification->data['title'] ?? 'Notification' }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                        {{ $notification->data['message'] ?? 'New notification received.' }}</p>
                                    <span
                                        class="text-[11px] mt-0.5 {{ $isUnread ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                            </a>
                        @endforeach
                    @else
                        <!-- Empty State -->
                        <div class="p-6 text-center flex flex-col items-center justify-center bg-white dark:bg-gray-800">
                            <svg class="h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                </path>
                            </svg>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">No notifications</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">You're all caught up!</p>
                        </div>
                    @endif
                </div>

                <div
                    class="shrink-0 py-3 px-4 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 text-center z-10">
                    <a href="{{ Route::has('notifications.index') ? route('notifications.index') : '#' }}"
                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium tracking-wide">View
                        All Notifications</a>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="h-8 w-px bg-blue-600 dark:bg-blue-700"></div>

        <!-- User Profile Dropdown -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.away="open = false"
                class="flex items-center space-x-3 focus:outline-none">
                <div class="text-right hidden sm:block">
                    <div class="text-sm font-medium text-white">{{ Auth::user()->name }}</div>
                    <div class="text-[11px] text-blue-200/80">{{ Auth::user()->role ?? 'Staff' }}</div>
                </div>
                <div
                    class="h-8 w-8 rounded-full bg-blue-500 border-2 border-white/30 flex items-center justify-center text-white font-semibold text-xs overflow-hidden">
                    @if(Auth::user()->profile_picture)
                        <img src="{{ Auth::user()->profile_picture }}" alt="{{ Auth::user()->name }}"
                            class="h-full w-full object-cover">
                    @else
                        {{ substr(Auth::user()->name, 0, 1) }}
                    @endif
                </div>
                <svg class="w-4 h-4 text-blue-200 transition-transform duration-200" :class="{'rotate-180': open}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50 py-1"
                style="display: none;">

                <a href="{{ route('profile.show') }}"
                    class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    View Profile
                </a>

                <a href="{{ route('profile.edit') }}"
                    class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </a>

                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                <form method="POST" action="{{ route('logout') }}"
                    onsubmit="localStorage.removeItem('color-theme'); document.documentElement.classList.remove('dark');">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 transition-colors text-left">
                        <svg class="w-4 h-4 mr-3 text-gray-400 group-hover:text-red-600" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
