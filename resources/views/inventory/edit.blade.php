<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <h2 class="ui-page-title">Edit Part</h2>
            <a href="{{ route('inventory.index') }}"
                class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-white flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>

        <!-- Form Card -->
        <div class="ui-card overflow-hidden">
            <div class="p-6">
                <form action="{{ route('inventory.update', $part) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Part Number -->
                        <div>
                            <label for="part_no" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Part Number</label>
                            <input type="text" name="part_no" id="part_no" value="{{ old('part_no', $part->part_no) }}"
                                required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('part_no')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Part Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Part Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $part->name) }}" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Price -->
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Price</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 dark:text-slate-400 sm:text-sm">&#8369;</span>
                                    </div>
                                    <input type="number" name="price" id="price" step="0.01" min="0"
                                        value="{{ old('price', $part->price) }}" required
                                        oninput="if(this.value < 0) this.value = 0;"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg">
                                </div>
                                @error('price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Current Stock -->
                            <div>
                                <label for="quantity_stock" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Current
                                    Stock</label>
                                <input type="number" name="quantity_stock" id="quantity_stock" min="0"
                                    value="{{ old('quantity_stock', $part->quantity_stock) }}" required
                                    oninput="if(this.value < 0) this.value = 0;"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('quantity_stock')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-100 dark:border-slate-700">
                        <a href="{{ route('inventory.index') }}"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Update Part
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
