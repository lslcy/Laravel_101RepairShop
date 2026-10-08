<x-app-layout>
    <div class="ui-page" x-data="{
        showDeleteModal: false,
        deleteUrl: '',
        deletePassword: '',
        openDeleteModal(url) {
            this.deleteUrl = url;
            this.deletePassword = '';
            this.showDeleteModal = true;
        }
    }">
        <!-- Header -->
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">Archive Records</h2>
                <p class="ui-page-subtitle">View and restore archived items</p>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="ui-card p-4 space-y-4">
            <form method="GET" action="{{ route('archive.index') }}" class="flex flex-col sm:flex-row gap-4">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $search }}"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 placeholder-gray-400 focus:outline-none focus:placeholder-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Search archive...">
                </div>
                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('archive.index', ['type' => 'all', 'search' => $search]) }}"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type == 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200' }}">
                        All
                    </a>
                    <a href="{{ route('archive.index', ['type' => 'services', 'search' => $search]) }}"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type == 'services' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200' }}">
                        Services
                    </a>
                    <a href="{{ route('archive.index', ['type' => 'inventory', 'search' => $search]) }}"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type == 'inventory' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200' }}">
                        Inventory
                    </a>
                    <a href="{{ route('archive.index', ['type' => 'customers', 'search' => $search]) }}"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type == 'customers' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200' }}">
                        Customers
                    </a>
                    @can('admin-only')
                    <a href="{{ route('archive.index', ['type' => 'users', 'search' => $search]) }}"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type == 'users' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200' }}">
                        Users
                    </a>
                    <a href="{{ route('archive.index', ['type' => 'transactions', 'search' => $search]) }}"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type == 'transactions' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200' }}">
                        Transactions
                    </a>
                    @endcan
                </div>
            </form>
        </div>

        @if($paginatedArchives->isEmpty())
            <!-- Empty State -->
            <div class="ui-card p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-50 dark:bg-slate-700/50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No archived records found</h3>
                <p class="mt-1 text-gray-500 dark:text-slate-400 max-w-sm mx-auto">Deleted items will appear here for recovery.</p>
            </div>
        @else
            <!-- Table -->
            <div class="ui-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Type
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Details
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Deleted At
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Deleted By
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200">
                            @foreach($paginatedArchives as $item)
                                <tr class="table-row-hover">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $item->type }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $item->details }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $item->deleted_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $item->deleted_by_name ?? $item->deleted_by ?? 'Unknown' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3">
                                            <form
                                                action="{{ route('archive.restore', ['type' => $item->type, 'id' => $item->id]) }}"
                                                method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit"
                                                    class="text-green-600 hover:text-green-900 transition-colors"
                                                    title="Restore">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                            <button type="button" @click="openDeleteModal('{{ route('archive.destroy', ['type' => $item->type, 'id' => $item->id]) }}')"
                                                class="text-red-500 hover:text-red-700 transition-colors"
                                                title="Force Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="ui-pagination">
                    <div class="w-full">{{ $paginatedArchives->links() }}</div>
                    <!-- Note: Pagination links might need custom view for Tailwind, usually handled globally in AppServiceProvider or vendor:publish -->
                </div>
            </div>
        @endif

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDeleteModal" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDeleteModal" x-transition class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="deleteUrl" method="POST" data-confirm-skip="true">
                        @csrf
                        @method('DELETE')
                        <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                        Permanently Delete Record
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-slate-400">
                                            Please enter your password to confirm permanent deletion. This action cannot be undone.
                                        </p>
                                        <input type="password" name="password" x-model="deletePassword" required
                                            class="mt-3 block w-full border-gray-300 dark:border-slate-500 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm bg-white dark:bg-slate-700" 
                                            placeholder="Enter your admin password">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm" :disabled="!deletePassword">
                                Permanently Delete
                            </button>
                            <button type="button" @click="showDeleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-slate-500 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
