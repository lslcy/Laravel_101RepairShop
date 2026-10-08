<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Leaflet CSS (Maps) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Automatic Resolution Scaling for Small Monitors -->
    <style>
        @media screen and (max-width: 1440px) {
            html {
                zoom: 0.85;
            }
        }

        @media screen and (max-width: 1280px) {
            html {
                zoom: 0.80;
            }
        }

        @media screen and (max-width: 1024px) {
            html {
                zoom: 1;
            }

            /* Reset for tablets/mobile, where Tailwind flex wrap handles it normally */
        }

        html.text-sm-size {
            font-size: 14px;
        }

        html.text-md-size {
            font-size: 16px;
        }

        html.text-lg-size {
            font-size: 18px;
        }
    </style>

    <!-- Scripts & Styles (Offline TailWind via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Check for dark mode preference to prevent FOUC
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Apply font size preference
        const savedSize = localStorage.getItem('font-size') || 'md';
        document.documentElement.classList.add('text-' + savedSize + '-size');

        function changeFontSize(size) {
            document.documentElement.classList.remove('text-sm-size', 'text-md-size', 'text-lg-size');
            document.documentElement.classList.add('text-' + size + '-size');
            localStorage.setItem('font-size', size);
        }
    </script>
    <!-- Leaflet JS (Maps) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased" x-data="{
        sidebarOpen: false,
        isMobile: window.innerWidth < 1024,
        confirmModal: false,
        confirmTitle: 'Confirm Action',
        confirmMessage: '',
        confirmAction: null,
        confirmVariant: 'danger', // danger | warning | info | success
        confirmConfirmText: 'Confirm',
        confirmCancelText: 'Cancel',
        askConfirm(message, action, options = {}) {
            this.confirmMessage = message;
            this.confirmAction = action;
            this.confirmTitle = options.title || 'Confirm Action';
            this.confirmVariant = options.variant || 'danger';
            this.confirmConfirmText = options.confirmText || 'Confirm';
            this.confirmCancelText = options.cancelText || 'Cancel';
            this.confirmModal = true;
        },
        doConfirm() {
            if(this.confirmAction) this.confirmAction();
            this.confirmModal = false;
            this.confirmAction = null;
        },
        variantTheme() {
            const theme = {
                danger: {
                    iconBg: 'bg-red-100 dark:bg-red-900/30',
                    iconText: 'text-red-600 dark:text-red-400',
                    confirmBtn: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
                },
                warning: {
                    iconBg: 'bg-amber-100 dark:bg-amber-900/30',
                    iconText: 'text-amber-700 dark:text-amber-400',
                    confirmBtn: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
                },
                info: {
                    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
                    iconText: 'text-blue-600 dark:text-blue-400',
                    confirmBtn: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
                },
                success: {
                    iconBg: 'bg-green-100 dark:bg-green-900/30',
                    iconText: 'text-green-600 dark:text-green-400',
                    confirmBtn: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
                },
            };
            return theme[this.confirmVariant] || theme.danger;
        }
    }" @open-confirm.window="askConfirm($event.detail.message, $event.detail.action, $event.detail)"
        @resize.window="isMobile = window.innerWidth < 1024; if (!isMobile) sidebarOpen = false"
        @keydown.escape.window="if (sidebarOpen) { sidebarOpen = false; $nextTick(() => document.getElementById('sidebar-toggle').focus()) }">

    <div class="flex h-screen overflow-hidden">
        <div x-show="sidebarOpen && isMobile" x-cloak @click="sidebarOpen = false; $nextTick(() => document.getElementById('sidebar-toggle').focus())"
            class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" aria-hidden="true"></div>
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content Wrapper -->
        <div class="flex-1 min-w-0 flex flex-col lg:ml-64 transition-all duration-300" :inert="isMobile && sidebarOpen">
            <!-- Topbar -->
            @include('layouts.topbar')

            <!-- Main Content -->
            <main class="flex-1 min-w-0 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-4 sm:p-6">
                @if(isset($header))
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Global Flash Notification (Toast) -->
    @if(session()->has('success') || session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            class="fixed top-16 right-6 z-50 flex items-center p-3 text-sm text-gray-700 bg-white rounded-lg shadow-lg border border-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:border-gray-700"
            role="alert" x-transition>
            @if(session()->has('success'))
                <div
                    class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                    </svg>
                    <span class="sr-only">Check icon</span>
                </div>
                <div class="ms-3 text-sm font-normal">{{ session('success') }}</div>
            @endif
            @if(session()->has('error'))
                <div
                    class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-200">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m13 7-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="sr-only">Error icon</span>
                </div>
                <div class="ms-3 text-sm font-normal">{{ session('error') }}</div>
            @endif
            <button type="button" @click="show = false"
                class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700 mx-1"
                aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Global Confirmation Modal -->
    <div x-show="confirmModal" class="fixed inset-0 z-[999] overflow-y-auto" style="display:none;"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @keydown.escape.window="confirmModal = false">
        <div class="flex min-h-screen items-center justify-center px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/40" @click="confirmModal = false"></div>
            <!-- Modal Card -->
            <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-lg px-6 py-6 max-w-md w-full z-10">
                <div class="flex items-center gap-4 mb-4">
                    <div class="h-12 w-12 rounded-full flex items-center justify-center flex-shrink-0"
                        :class="variantTheme().iconBg">
                        <template x-if="confirmVariant === 'success'">
                            <svg class="h-6 w-6" :class="variantTheme().iconText" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                        <template x-if="confirmVariant === 'info'">
                            <svg class="h-6 w-6" :class="variantTheme().iconText" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                        </template>
                        <template x-if="confirmVariant !== 'success' && confirmVariant !== 'info'">
                            <svg class="h-6 w-6" :class="variantTheme().iconText" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </template>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="confirmTitle"></h3>
                        <p class="text-sm text-gray-500 dark:text-slate-300 mt-0.5" x-text="confirmMessage"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="confirmModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors"
                        x-text="confirmCancelText">
                    </button>
                    <button @click="doConfirm()"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                        :class="variantTheme().confirmBtn" x-text="confirmConfirmText">
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Change the icons inside the button based on previous settings
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            if (themeToggleLightIcon) themeToggleLightIcon.classList.remove('hidden');
        } else {
            if (themeToggleDarkIcon) themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function () {
                // toggle icons inside button
                themeToggleDarkIcon.classList.toggle('hidden');
                themeToggleLightIcon.classList.toggle('hidden');

                // if set via local storage previously
                if (localStorage.getItem('color-theme')) {
                    if (localStorage.getItem('color-theme') === 'light') {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    }
                    // if NOT set via local storage previously
                } else {
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    }
                }
            });
        }

        // Global Interceptor for Back, Cancel, Save, Edit, Remove, Restore actions
        document.addEventListener('DOMContentLoaded', () => {
            const dispatchConfirm = (detail) => {
                window.dispatchEvent(new CustomEvent('open-confirm', { detail }));
            };

            // Intercept form submissions for Save / Remove / Restore (skip auth, GET, and forms that already provide their own confirmation modal)
            document.querySelectorAll('form').forEach(form => {
                const isGet = form.method.toUpperCase() === 'GET';
                const isAuth = (form.action || '').includes('login') || (form.action || '').includes('logout');
                const skipByAttr = form.hasAttribute('data-confirm-skip') || form.dataset.confirmSkip === 'true';
                const insideAppModal = !!form.closest('[x-on\\:open-modal\\.window]'); // x-modal component wrapper

                if (isGet || isAuth || skipByAttr || insideAppModal) return;

                form.addEventListener('submit', function (e) {
                    if (this.dataset.confirmed === 'true') return;
                    e.preventDefault();

                    const submitter = e.submitter;
                    const submitterLabel = submitter ? (submitter.dataset.confirmConfirmText || (submitter.textContent || '').trim()) : '';

                    const methodInput = this.querySelector('input[name="_method"]');
                    const methodOverride = methodInput ? (methodInput.value || '').toUpperCase() : '';

                    const actionUrl = this.action || '';
                    const isDelete = methodOverride === 'DELETE';
                    const isRestore = /\/restore\b/i.test(actionUrl);

                    const title =
                        this.dataset.confirmTitle ||
                        (isRestore ? 'Restore Item' : isDelete ? 'Remove Item' : 'Save Changes');

                    const message =
                        this.dataset.confirmMessage ||
                        (isRestore
                            ? 'Restore this item and make it active again?'
                            : isDelete
                                ? 'Are you sure you want to remove this item?'
                                : 'Are you sure you want to save these changes?');

                    const variant =
                        this.dataset.confirmVariant ||
                        (isRestore ? 'success' : isDelete ? 'danger' : 'info');

                    const confirmText =
                        this.dataset.confirmConfirmText ||
                        (submitterLabel ? submitterLabel : (isRestore ? 'Restore' : isDelete ? 'Remove' : 'Save'));

                    const cancelText = this.dataset.confirmCancelText || 'Cancel';

                    dispatchConfirm({
                        title,
                        message,
                        variant,
                        confirmText,
                        cancelText,
                        action: () => {
                            this.dataset.confirmed = 'true';
                            // Prefer requestSubmit so validation + submitter semantics stay correct.
                            if (typeof this.requestSubmit === 'function') {
                                if (submitter) this.requestSubmit(submitter);
                                else this.requestSubmit();
                            } else {
                                this.submit();
                            }
                        }
                    });
                });
            });


        });
    </script>
</body>

</html>
