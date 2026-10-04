<x-app-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">All Notifications</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">View your recent notifications and activity alerts</p>
            </div>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <form action="{{ route('notifications.markRead') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-blue-600 rounded-lg shadow-sm text-sm font-medium text-blue-600 bg-transparent hover:bg-blue-50 dark:border-blue-500 dark:text-blue-400 dark:hover:bg-blue-900/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Mark all as read
                </button>
            </form>
            @endif
        </div>

        <!-- Notifications List -->
        <div class="ui-card overflow-hidden">
            @if($notifications->count() > 0)
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($notifications as $notification)
                        @php $isUnread = $notification->unread(); @endphp
                        <li class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors {{ $isUnread ? 'bg-blue-50/50 dark:bg-slate-800/80' : '' }}">
                            <a href="{{ $notification->data['url'] ?? '#' }}" class="block px-6 py-5">
                                <div class="flex items-start gap-4">
                                    <!-- Indicator -->
                                    <div class="mt-1 flex-shrink-0 w-3">
                                        @if($isUnread)
                                            <div class="h-2.5 w-2.5 rounded-full bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-900/30"></div>
                                        @endif
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $notification->data['title'] ?? 'Notification' }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                            {{ $notification->data['message'] ?? 'You have a new notification.' }}
                                        </p>
                                    </div>
                                    <!-- Time -->
                                    <div class="flex-shrink-0 whitespace-nowrap text-xs {{ $isUnread ? 'text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-400 dark:text-gray-500' }}">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
                
                <!-- Pagination -->
                @if($notifications->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-800">
                    {{ $notifications->links() }}
                </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="p-12 text-center flex flex-col items-center justify-center">
                    <div class="h-16 w-16 bg-blue-50 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">No notifications</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">When you get notifications or activity alerts, they will show up here. You're all caught up!</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
