<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <h2 class="ui-page-title">Add New Customer</h2>
            <a href="{{ route('customers.index') }}"
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
                <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <p class="text-sm text-gray-500 dark:text-slate-400">Use one customer record per person. Full names, phone numbers, and email addresses must be unique.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-slate-200">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}"
                                required maxlength="255" autocomplete="given-name"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required maxlength="255" autocomplete="family-name"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-span-1">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Email Address
                                (Optional)</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="customer@example.com">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Profile Picture -->
                        <div class="col-span-1">
                            <label for="profile_picture" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Profile Picture (Optional)</label>
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-lg file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100 dark:file:bg-slate-700 dark:file:text-slate-300 dark:hover:file:bg-slate-600">
                            @error('profile_picture')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Phone Number -->
                        <div class="md:col-span-2">
                            <label for="phone_no" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Phone Number</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                </div>
                                <input type="tel" name="phone_no" id="phone_no" value="{{ old('phone_no') }}" required autocomplete="tel"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg"
                                    placeholder="09xxxxxxxxx">
                            </div>
                            @error('phone_no')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Address with Map Search -->
                        <div class="md:col-span-2" x-data="{
                            mapInstance: null,
                            marker: null,
                            searchQuery: '',
                            searchResults: [],
                            searching: false,
                            initMap() {
                                let lat = 10.3157, lng = 123.8854; // Default: Cebu City
                                this.mapInstance = L.map('customer-map').setView([lat, lng], 13);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '© OpenStreetMap contributors'
                                }).addTo(this.mapInstance);
                                
                                const existingAddress = document.getElementById('address').value;
                                if (existingAddress) {
                                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(existingAddress)}&limit=1`)
                                        .then(r => r.json())
                                        .then(results => {
                                            if (results && results.length > 0) {
                                                this.setMarker(parseFloat(results[0].lat), parseFloat(results[0].lon));
                                            }
                                        });
                                }

                                this.mapInstance.on('click', (e) => {
                                    this.setMarker(e.latlng.lat, e.latlng.lng);
                                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
                                        .then(r => r.json())
                                        .then(data => {
                                            if (data.display_name) {
                                                document.getElementById('address').value = data.display_name;
                                            }
                                        });
                                });
                            },
                            setMarker(lat, lng) {
                                if (this.marker) this.mapInstance.removeLayer(this.marker);
                                this.marker = L.marker([lat, lng]).addTo(this.mapInstance);
                                this.mapInstance.setView([lat, lng], 16);
                            },
                            searchAddress() {
                                if (!this.searchQuery.trim()) return;
                                this.searching = true;
                                this.searchResults = [];
                                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&limit=5&countrycodes=ph`)
                                    .then(r => r.json())
                                    .then(results => {
                                        this.searchResults = results;
                                        this.searching = false;
                                    });
                            },
                            selectResult(result) {
                                document.getElementById('address').value = result.display_name;
                                this.setMarker(parseFloat(result.lat), parseFloat(result.lon));
                                this.searchResults = [];
                                this.searchQuery = '';
                            }
                        }" x-init="$nextTick(() => initMap())">
                            <label for="address" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Address</label>
                            
                            <!-- Address text area -->
                            <div class="mt-1">
                                <textarea id="address" name="address" rows="2"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg"
                                    placeholder="Full address will auto-fill when you select from map or search results">{{ old('address') }}</textarea>
                            </div>

                            <!-- Map search -->
                            <div class="mt-2 relative">
                                <div class="flex gap-2">
                                    <input type="text" x-model="searchQuery"
                                        @keydown.enter.prevent="searchAddress()"
                                        class="block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Search address on map (e.g., SM City Cebu)...">
                                    <button type="button" @click="searchAddress()"
                                        class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-1 whitespace-nowrap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        Search
                                    </button>
                                </div>
                                <!-- Search results dropdown -->
                                <div x-show="searchResults.length > 0"
                                    class="absolute z-20 mt-1 w-full bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-slate-600 overflow-hidden" style="display:none;">
                                    <template x-for="result in searchResults" :key="result.place_id">
                                        <button type="button" @click="selectResult(result)"
                                            class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-slate-200 hover:bg-blue-50 border-b border-gray-100 dark:border-slate-700 last:border-b-0 transition-colors">
                                            <svg class="w-3 h-3 inline mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            </svg>
                                            <span x-text="result.display_name"></span>
                                        </button>
                                    </template>
                                </div>
                                <p x-show="searching" class="text-xs text-gray-500 dark:text-slate-400 mt-1">Searching...</p>
                            </div>

                            <!-- Leaflet Map -->
                            <div id="customer-map" class="mt-2 rounded-lg border border-gray-200 dark:border-slate-600 overflow-hidden" style="height: 250px; width: 100%; z-index: 0;"></div>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">💡 Click anywhere on the map or search above to auto-fill the address.</p>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-100 dark:border-slate-700">
                        <a href="{{ route('customers.index') }}"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
