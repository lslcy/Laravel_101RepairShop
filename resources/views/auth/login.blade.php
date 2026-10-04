<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '101 Repair Service') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS (via CDN for simplicity as existing auth views do) -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Hide default browser reveal icon for password fields (e.g., in Edge) */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
    </style>

    <script>
        // Force light mode for login page
        document.documentElement.classList.remove('dark');
    </script>
</head>

<body
    class="font-sans antialiased text-gray-900 bg-gray-50 flex items-center justify-center min-h-screen p-4 sm:p-8 transition-colors duration-200">

    <div
        class="w-full max-w-5xl bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden flex flex-col md:flex-row min-h-[560px]">

        <!-- Left Pane: Login Form -->
        <div
            class="w-full md:w-1/2 p-8 sm:p-12 lg:p-16 flex flex-col justify-center bg-white relative transition-colors duration-200">

            <div class="max-w-md w-full mx-auto">
                <!-- Logo & Header -->
                <div class="text-center mb-10">
                    <!-- Light Mode Logo (Blue) -->
                    <img src="{{ asset('img/repairservicelogoblue.png') }}" alt="101 Repair Shop Logo"
                        class="h-24 md:h-28 mx-auto object-contain mb-4 block">
                    <!-- Dark Mode Logo (Gray/Red) -->
                    <img src="{{ asset('img/repairservicelogogray.png') }}" alt="101 Repair Shop Logo"
                        class="h-32 md:h-40 mx-auto object-contain mb-4 hidden">
                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">101 Repair Service</h2>
                    <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 sm:text-sm placeholder-gray-400 transition-shadow"
                            placeholder="Enter your email">
                        <x-input-error :messages="$errors->get('email')"
                            class="mt-2 text-red-600 text-xs font-medium" />
                    </div>

                    <!-- Password -->
                    <div x-data="{ showPassword: false }">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                                autocomplete="current-password"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 sm:text-sm placeholder-gray-400 transition-shadow pr-10"
                                placeholder="Enter your password">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                <!-- Eye open (password hidden) -->
                                <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                                <!-- Eye off (password shown) -->
                                <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21">
                                    </path>
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')"
                            class="mt-2 text-red-600 text-xs font-medium" />
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between pt-2">
                        <div class="flex items-center">
                            <input id="remember_me" type="checkbox" name="remember"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                            <label for="remember_me"
                                class="ml-2 block text-sm text-gray-600 cursor-pointer select-none">Remember me</label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                                class="text-sm font-semibold text-blue-600 hover:text-blue-500 transition-colors">
                                Forgot Password?
                            </a>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit"
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors">
                            Sign in
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Pane: Image Carousel & Quote -->
        <div class="w-full md:w-1/2 relative hidden md:block" x-data="{ 
                activeSlide: 1, 
                slides: [
                    '{{ asset('img/slider/hero-1.jpg') }}',
                    '{{ asset('img/slider/hero-2.jpg') }}'
                ],
                init() {
                    setInterval(() => {
                        this.activeSlide = this.activeSlide === this.slides.length ? 1 : this.activeSlide + 1;
                    }, 5000);
                }
             }">

            <!-- Background Images -->
            <template x-for="(slide, index) in slides" :key="index">
                <img :src="slide" alt="Repair Shop Work Area"
                    class="absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 ease-in-out"
                    :class="activeSlide === index + 1 ? 'opacity-100' : 'opacity-0'" x-cloak>
            </template>

            <!-- Dark Blue Overlay with Gradient -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-[#1e3a8a] via-[#1e3a8a]/80 to-[#1e3a8a]/40 z-10 transition-colors duration-500">
            </div>

            <!-- Content Container -->
            <div class="absolute inset-0 flex flex-col justify-end p-12 text-white z-20">
                <!-- Carousel Controls -->
                <div class="flex items-center justify-between w-full">
                    <div class="flex space-x-3">
                        <button type="button" @click="activeSlide = activeSlide === 1 ? slides.length : activeSlide - 1"
                            class="w-10 h-10 rounded-full border border-white/30 flex items-center justify-center hover:bg-white  transition-colors focus:outline-none">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button type="button" @click="activeSlide = activeSlide === slides.length ? 1 : activeSlide + 1"
                            class="w-10 h-10 rounded-full border border-white/30 flex items-center justify-center hover:bg-white  transition-colors focus:outline-none">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </button>
                    </div>

                    <!-- Slide Indicators -->
                    <div class="flex space-x-2">
                        <template x-for="i in slides.length">
                            <button @click="activeSlide = i"
                                class="h-1.5 rounded-full transition-all duration-300 focus:outline-none"
                                :class="activeSlide === i ? 'w-6 bg-white ' : 'w-1.5 bg-white  hover:bg-white '"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>

</html>