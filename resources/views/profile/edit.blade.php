<x-app-layout>
    <div class="ui-page" x-data="{ tab: 'account' }">
        <!-- Header -->
        <div class="ui-page-header">
            <div class="flex-1 min-w-0">
                <h2 class="ui-page-title">
                    Settings
                </h2>
                <p class="ui-page-subtitle">
                    Manage your account settings and preferences.
                </p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 dark:border-slate-700 overflow-x-auto">
            <nav class="-mb-px flex min-w-max gap-8" aria-label="Tabs">
                <button @click="tab = 'account'"
                    :class="tab === 'account' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Account
                </button>
                <button @click="tab = 'security'"
                    :class="tab === 'security' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    Security
                </button>
                <button @click="tab = 'notifications'"
                    :class="tab === 'notifications' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>
                    Notifications
                </button>
                <button @click="tab = 'privacy'"
                    :class="tab === 'privacy' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    Privacy
                </button>
            </nav>
        </div>

        <!-- Tab Content -->

        <!-- ACCOUNT TAB -->
        <div x-show="tab === 'account'" style="display: none;">


            <!-- Update Profile Info -->
            <div class="ui-card p-6">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- SECURITY TAB -->
        <div x-show="tab === 'security'" style="display: none;" class="space-y-6">
            <!-- Update Password -->
            @if(auth()->user()->role !== 'Administrator')
                <div class="ui-card p-6">
                    @include('profile.partials.update-password-form')
                </div>
            @else
                <div class="ui-card p-6">
                    <header class="mb-4">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Update Password</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Administrators are not permitted to change their password via the profile settings.
                        </p>
                    </header>
                </div>
            @endif

            <!-- Two-Factor Auth (Placeholder) -->
            <div class="ui-card p-6">
                <header class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Two-Factor Authentication</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Add an extra layer of security to your
                        account.</p>
                </header>
                <div class="rounded-md bg-blue-50 dark:bg-blue-900 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1 md:flex md:justify-between">
                            <p class="text-sm text-blue-700 dark:text-blue-200">Two-factor authentication is currently
                                not enabled.</p>
                            <p class="mt-3 text-sm md:mt-0 md:ml-6">
                                <a href="#"
                                    class="whitespace-nowrap font-medium text-blue-700 dark:text-blue-200 hover:text-blue-600 dark:text-blue-400">Enable
                                    2FA <span aria-hidden="true">&rarr;</span></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOTIFICATIONS TAB -->
        <div x-show="tab === 'notifications'" style="display: none;" class="space-y-6">
            <div class="ui-card p-6">
                <header class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Email Notifications</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Get notified about important updates.</p>
                </header>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-200">Service Updates</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get notified about service reports and
                                updates.</p>
                        </div>
                        <!-- Toggle (Visual) -->
                        <button type="button"
                            class="bg-blue-600 relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            role="switch" aria-checked="true">
                            <span aria-hidden="true"
                                class="translate-x-5 pointer-events-none inline-block h-5 w-5 rounded-full bg-white dark:bg-slate-800 shadow transform ring-0 transition ease-in-out duration-200"></span>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-200">Security Alerts</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get notified about login attempts and
                                password changes.</p>
                        </div>
                        <button type="button"
                            class="bg-blue-600 relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            role="switch" aria-checked="true">
                            <span aria-hidden="true"
                                class="translate-x-5 pointer-events-none inline-block h-5 w-5 rounded-full bg-white dark:bg-slate-800 shadow transform ring-0 transition ease-in-out duration-200"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRIVACY TAB -->
        <div x-show="tab === 'privacy'" style="display: none;" class="space-y-6">
            <!-- Delete Account -->
            <div class="ui-card p-6 border-red-100 dark:border-red-900">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

    </div>
</x-app-layout>