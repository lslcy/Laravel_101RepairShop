<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <div>
                <h2 class="ui-page-title">Service Prices</h2>
                <p class="ui-page-subtitle">Manage pricing for different service types</p>
            </div>
            <a href="{{ route('prices.create') }}"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Price
            </a>
        </div>

        @if($prices->isEmpty())
            <!-- Empty State -->
            <div class="ui-card p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-50 dark:bg-slate-700/50 rounded-lg flex items-center justify-center mb-4 text-gray-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No service prices found</h3>
                <p class="mt-1 text-gray-500 dark:text-slate-400 max-w-sm mx-auto">Start by adding pricing for your services.</p>
                <div class="mt-6">
                    <a href="{{ route('prices.create') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700  transition-colors">
                        Add First Price
                    </a>
                </div>
            </div>
        @else
            <!-- Search -->
            <div class="ui-card p-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="searchInput"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg leading-5 bg-white dark:bg-slate-800 placeholder-gray-400 focus:outline-none focus:placeholder-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Search service prices...">
                </div>
            </div>

            <!-- Table -->
            <div class="ui-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="ui-table">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Service Name
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Price
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200" id="pricesTableBody">
                            @foreach($prices as $price)
                                <tr class="table-row-hover">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $price->service_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right tabular-nums text-sm text-gray-500 dark:text-slate-400">
                                        ₱{{ number_format($price->service_price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3">
                                            <a href="{{ route('prices.edit', $price) }}"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </a>
                                            <form action="{{ route('prices.destroy', $price) }}" method="POST"
                                                class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"
                                                    title="Delete">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Search Script -->
            <script>
                document.getElementById('searchInput').addEventListener('keyup', function () {
                    let filter = this.value.toLowerCase();
                    let rows = document.querySelectorAll('#pricesTableBody tr');

                    rows.forEach(function (row) {
                        let text = row.textContent.toLowerCase();
                        if (text.includes(filter)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            </script>
        @endif
    </div>
</x-app-layout>
