<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <h2 class="ui-page-title">Edit Customer</h2>
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
                <form action="{{ route('customers.update', $customer) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-slate-200">First Name</label>
                            <input type="text" name="first_name" id="first_name"
                                value="{{ old('first_name', $customer->first_name) }}" required
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
                            <input type="text" name="last_name" id="last_name"
                                value="{{ old('last_name', $customer->last_name) }}" required
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
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Email Address (Optional)</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}"
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
                            <div class="mt-1 flex items-center space-x-4">
                                @if($customer->profile_picture)
                                    <img src="{{ str_starts_with($customer->profile_picture, 'http') ? $customer->profile_picture : asset($customer->profile_picture) }}" alt="Profile" class="h-10 w-10 rounded-full object-cover shadow-sm border border-gray-200 dark:border-slate-600">
                                @endif
                                <div class="flex-1">
                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*"
                                        class="block w-full text-sm text-gray-500 dark:text-gray-400
                                        file:mr-4 file:py-1.5 file:px-3
                                        file:rounded-lg file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100 dark:file:bg-slate-700 dark:file:text-slate-300 dark:hover:file:bg-slate-600">
                                </div>
                            </div>
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
                                <input type="text" name="phone_no" id="phone_no"
                                    value="{{ old('phone_no', $customer->phone_no) }}" required
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg">
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

                        <!-- Mobile App Account (Supabase Auth) -->
                        <div class="md:col-span-2">
                            <label for="auth_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                                Mobile App Account ID
                                @if($customer->auth_id)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Linked</span>
                                @else
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300">Not linked</span>
                                @endif
                            </label>
                            <input type="text" name="auth_id" id="auth_id" value="{{ old('auth_id', $customer->auth_id) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono"
                                placeholder="e.g. 3f1c2a9e-0000-0000-0000-000000000000">
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                The customer's Supabase Auth user ID. It is filled automatically when the customer signs in on the mobile app with the same email. Clear it to unlink the account.
                            </p>
                            @error('auth_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                    placeholder="Full address will auto-fill when you select from map or search results">{{ old('address', $customer->address) }}</textarea>
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
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Update Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Appliance Information Card -->
        <div x-data="{ editModal: false, editAppId: null }" class="ui-card overflow-hidden mt-6">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Appliances</h3>
                
                <!-- Existing Appliances List -->
                @if($customer->appliances->count() > 0)
                    <div class="overflow-x-auto mb-6">
                        <table class="ui-table">
                            <thead class="bg-gray-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Appliance Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Brand</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Model No</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Serial No</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Size</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Date Received</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Warranty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200">
                                @foreach($customer->appliances as $app)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $app->product }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $app->brand }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $app->category }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $app->model_no }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $app->serial_no }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $app->appliance_size ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $app->date_in ? \Carbon\Carbon::parse($app->date_in)->format('M d, Y') : '' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($app->warranty_end)
                                                @if(\Carbon\Carbon::parse($app->warranty_end)->isPast())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                        Expired ({{ \Carbon\Carbon::parse($app->warranty_end)->format('M d, Y') }})
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        Active until {{ \Carbon\Carbon::parse($app->warranty_end)->format('M d, Y') }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-gray-400 text-xs">No Warranty</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <div class="flex justify-end space-x-3">
                                                <button type="button" @click="editAppId = {{ $app->id }}; editModal = true" class="text-blue-600 hover:text-blue-900 font-medium">Continue Edit</button>
                                                <form action="{{ route('appliances.destroy', $app) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 font-medium">Remove</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Edit Modals -->
                    @foreach($customer->appliances as $app)
                        <div x-show="editModal && editAppId === {{ $app->id }}" class="fixed inset-0 z-[110] overflow-y-auto" style="display:none;" x-transition>
                            <div class="flex min-h-screen items-center justify-center px-4">
                                <div class="fixed inset-0 bg-black/40" @click="editModal = false"></div>
                                <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-lg w-full max-w-2xl z-10 p-6">
                                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Appliance</h3>
                                        <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <form action="{{ route('appliances.update', $app) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Appliance Type</label>
                                                <input type="text" name="product" value="{{ old('product', $app->product) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Brand</label>
                                                <input type="text" name="brand" value="{{ old('brand', $app->brand) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Category</label>
                                                <input type="text" name="category" value="{{ old('category', $app->category) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Model No.</label>
                                                <input type="text" name="model_no" value="{{ old('model_no', $app->model_no) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Serial No.</label>
                                                <input type="text" name="serial_no" value="{{ old('serial_no', $app->serial_no) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Appliance Size</label>
                                                <select name="appliance_size" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                    <option value="">Select Size</option>
                                                    <option value="Small" {{ old('appliance_size', $app->appliance_size) == 'Small' ? 'selected' : '' }}>Small (1mo Warranty)</option>
                                                    <option value="Medium" {{ old('appliance_size', $app->appliance_size) == 'Medium' ? 'selected' : '' }}>Medium (3mo Warranty)</option>
                                                    <option value="Large" {{ old('appliance_size', $app->appliance_size) == 'Large' ? 'selected' : '' }}>Large (6mo Warranty)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Date Received</label>
                                                <input type="date" name="date_in" value="{{ old('date_in', $app->date_in ? \Carbon\Carbon::parse($app->date_in)->format('Y-m-d') : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Status</label>
                                                @php $appStatuses = array_unique(array_filter(array_merge(\App\Models\Appliance::STATUSES, [$app->status]))); @endphp
                                                <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                    @foreach($appStatuses as $st)
                                                        <option value="{{ $st }}" {{ old('status', $app->status ?? 'Active') == $st ? 'selected' : '' }}>{{ $st }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Warranty End</label>
                                                <input type="date" name="warranty_end" value="{{ old('warranty_end', $app->warranty_end ? \Carbon\Carbon::parse($app->warranty_end)->format('Y-m-d') : '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                <p class="mt-1 text-[11px] text-gray-400">Set automatically when a transaction is paid.</p>
                                            </div>
                                        </div>
                                        <div class="flex justify-end gap-3 mt-4">
                                            <button type="button" @click="editModal = false" class="px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg text-sm text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none transition-colors">Cancel</button>
                                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400 mb-6 italic">No appliances linked to this customer yet.</p>
                @endif

                <!-- Add New Appliance Form -->
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-5 border border-gray-200 dark:border-slate-600">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Add New Appliance</h4>
                    
                    <form action="{{ route('customers.appliances.store', $customer) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Appliance Type</label>
                                <input type="text" name="product" placeholder="e.g. Refrigerator" required value="{{ old('product') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('product')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Brand</label>
                                <input type="text" name="brand" placeholder="e.g. Panasonic" required value="{{ old('brand') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('brand')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Category</label>
                                <input type="text" name="category" placeholder="e.g. Cooling System" value="{{ old('category') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('category')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Model No.</label>
                                <input type="text" name="model_no" placeholder="Optional" value="{{ old('model_no') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('model_no')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Serial No.</label>
                                <input type="text" name="serial_no" placeholder="Optional" value="{{ old('serial_no') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('serial_no')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Appliance Size</label>
                                <select name="appliance_size" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Select Size</option>
                                    <option value="Small" {{ old('appliance_size') == 'Small' ? 'selected' : '' }}>Small (1mo Warranty)</option>
                                    <option value="Medium" {{ old('appliance_size') == 'Medium' ? 'selected' : '' }}>Medium (3mo Warranty)</option>
                                    <option value="Large" {{ old('appliance_size') == 'Large' ? 'selected' : '' }}>Large (6mo Warranty)</option>
                                </select>
                                @error('appliance_size')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Date Received</label>
                                <input type="date" name="date_in" value="{{ old('date_in', date('Y-m-d')) }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('date_in')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Status</label>
                                <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @foreach(\App\Models\Appliance::STATUSES as $st)
                                        <option value="{{ $st }}" {{ old('status', 'Active') == $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                Add Appliance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
