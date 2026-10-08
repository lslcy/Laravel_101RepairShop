<x-app-layout>
    <div class="ui-page">
        <!-- Header Section -->
        <div class="ui-card overflow-hidden">
            <div class="h-32 bg-blue-600"></div>
            <div class="px-8 flex flex-col sm:flex-row sm:items-end justify-between -mt-12 pb-6">
                <div class="flex flex-col sm:flex-row sm:items-start gap-5">
                    <div
                        class="h-24 w-24 shrink-0 rounded-full border-4 border-white dark:border-slate-800 bg-white dark:bg-slate-800 flex items-center justify-center text-blue-600 dark:text-blue-400 text-3xl font-bold uppercase shadow-md overflow-hidden">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
                                class="h-full w-full object-cover">
                        @else
                            {{ substr($user->name, 0, 1) }}
                        @endif
                    </div>
                    <div class="mt-4 sm:mt-12 sm:ml-2 flex flex-col pt-1">
                        <h1 class="ui-page-title">{{ $user->name }}</h1>

                        <p class="ui-page-subtitle max-w-xl">
                            {{ $user->bio ?? 'Experienced professional managing operations.' }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 sm:mt-0 mb-1">
                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center px-4 py-2 border border-blue-600 rounded-lg shadow-sm text-sm font-medium text-blue-600 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-blue-400 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <div class="ui-card p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Personal Information</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Full Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">
                            {{ $user->phone ?? '+1 234 567 8900' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">
                            {{ $user->address ?? '123 Business Street, Tech City, TC 12345' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Account Details -->
            <div class="ui-card p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Account Details</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</dt>
                        <dd
                            class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ ucfirst($user->role) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Member Since</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">
                            {{ $user->created_at->format('F j, Y') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bio</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200">
                            {{ $user->bio ?? 'Experienced administrator managing repair services and team operations.' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="ui-card p-5 flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customers Served</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $customersServed }}</p>
                </div>
            </div>

            <div class="ui-card p-5 flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-200 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Services Completed</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $servicesCompleted }}</p>
                </div>
            </div>

            <div class="ui-card p-5 flex items-center">
                <div
                    class="p-3 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-200 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                        </path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Rating</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $averageRating }}</p>
                </div>
            </div>

            <div class="ui-card p-5 flex items-center">
                <div
                    class="p-3 rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-200 mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Months Active</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $monthsActive }}</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="ui-card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h3>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($recentActivity as $activity)
                    <li class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <!-- Icon based on activity -->
                                <span
                                    class="inline-flex items-center justify-center h-2 w-2 rounded-full bg-blue-400"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncated">
                                    Created transaction #{{ $activity->id }} for
                                    {{ $activity->report->customer->name ?? 'Customer' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </li>
                @endforeach
                @if($recentActivity->isEmpty())
                    <li class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        No recent activity.
                    </li>
                @endif
            </ul>
        </div>
    </div>
</x-app-layout>