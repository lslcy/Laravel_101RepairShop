<x-app-layout>
    <div class="w-full mx-auto space-y-6" x-data="{
        customers: {{ Js::from($customers) }},
        parts: {{ Js::from($parts) }},
        servicePrices: {{ Js::from($servicePrices) }},
        techniciansList: {{ Js::from($technicians) }},
        searchTech: '',
        filterTech: '',
        selectedTechs: {{ Js::from(old('technicians', [])) }},
        selectedCustomerId: '{{ old('customer_id') }}',
        selectedApplianceId: '{{ old('appliance_id') }}',
        selectedPartId: '',
        partQuantity: 1,
        miscCost: {{ old('miscellaneous_cost', 0) }},
        selectedParts: [],
        laborBase: {{ old('labor_cost', 0) }},
        checkedTypes: {{ Js::from(old('service_types', [])) }},
        get currentCustomer() {
            return this.customers.find(c => c.id == this.selectedCustomerId) || null;
        },
        get customerAppliances() {
            return this.currentCustomer ? this.currentCustomer.appliances : [];
        },
        get totalPartsCost() {
            return this.selectedParts.reduce((total, part) => {
                if (part.is_not_working) return total;
                return total + (part.price * part.quantity);
            }, 0);
        },
        get computedLabor() {
            let base = 0;
            this.servicePrices.forEach(sp => {
                if (this.checkedTypes.includes(sp.service_name)) {
                    base += parseFloat(sp.service_price);
                }
            });
            return base;
        },
        toggleServiceType(name) {
            const idx = this.checkedTypes.indexOf(name);
            if (idx === -1) {
                this.checkedTypes.push(name);
            } else {
                this.checkedTypes.splice(idx, 1);
            }
        },
        get filteredTechnicians() {
            let filtered = this.techniciansList;
            if (this.filterTech !== '') {
                filtered = filtered.filter(t => (t.availability_status || '').toLowerCase() === this.filterTech.toLowerCase());
            }
            if (this.searchTech.trim() !== '') {
                let s = this.searchTech.toLowerCase();
                filtered = filtered.filter(t => 
                    ((t.first_name || '') + ' ' + (t.last_name || '')).toLowerCase().includes(s) || 
                    (t.role_title || '').toLowerCase().includes(s)
                );
            }
            return filtered;
        },
        toggleTech(name) {
            let idx = this.selectedTechs.indexOf(name);
            if (idx === -1) {
                if (this.selectedTechs.length < 3) {
                    this.selectedTechs.push(name);
                } else {
                    alert('You can only assign a maximum of 3 technicians.');
                }
            } else {
                this.selectedTechs.splice(idx, 1);
            }
        },
        getInitials(firstName, lastName) {
            let f = (firstName || '').charAt(0);
            let l = (lastName || '').charAt(0);
            return (f + l).toUpperCase() || '?';
        },
        addPart() {
            if (!this.selectedPartId || this.partQuantity < 1) return;
            const partIndex = this.parts.findIndex(p => p.id == this.selectedPartId);
            if (partIndex === -1) return;
            const part = this.parts[partIndex];
            const existingIndex = this.selectedParts.findIndex(p => p.id === part.id);
            if (existingIndex !== -1) {
                this.selectedParts[existingIndex].quantity += parseInt(this.partQuantity);
            } else {
                this.selectedParts.push({
                    id: part.id,
                    name: part.name,
                    part_no: part.part_no,
                    price: parseFloat(part.price),
                    quantity: parseInt(this.partQuantity),
                    is_not_working: false
                });
            }
            this.selectedPartId = '';
            this.partQuantity = 1;
        },
        removePart(id) {
            this.selectedParts = this.selectedParts.filter(p => p.id !== id);
        },
        init() {
            this.$watch('selectedCustomerId', () => {
                this.selectedApplianceId = '';
            });
            // Re-populate selectedParts from old input if validation fails
            let oldParts = {{ Js::from(old('parts', [])) }};
            if (oldParts.length > 0) {
                oldParts.forEach(oldPart => {
                    const p = this.parts.find(px => px.id == oldPart.id);
                    if (p) {
                         this.selectedParts.push({
                            id: p.id,
                            name: p.name,
                            part_no: p.part_no,
                            price: parseFloat(oldPart.price || p.price),
                            quantity: parseInt(oldPart.quantity),
                            is_not_working: oldPart.is_not_working === '1' || oldPart.is_not_working === true
                        });
                    }
                });
            }
        }
    }">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Service Report</h2>
            <a href="{{ route('services.index') }}"
                class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-white flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6">
                <form action="{{ route('services.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Customer -->
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Customer</label>
                            <select name="customer_id" id="customer_id" x-model="selectedCustomerId" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">-- Select Customer --</option>
                                <template x-for="customer in customers" :key="customer.id">
                                    <option :value="customer.id"
                                        x-text="customer.first_name + ' ' + (customer.last_name || '') + (customer.email ? ' ('+customer.email+')' : '')"
                                        :selected="customer.id == selectedCustomerId"></option>
                                </template>
                            </select>
                            @error('customer_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date Received -> Repair Date -->
                        <div>
                            <label for="date_in" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Repair Date</label>
                            <input type="date" name="date_in" id="date_in" value="{{ old('date_in', date('Y-m-d')) }}"
                                required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('date_in')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Appliance -->
                        <div>
                            <label for="appliance_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Appliance</label>
                            <select name="appliance_id" id="appliance_id" x-model="selectedApplianceId" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                :disabled="!customerAppliances.length">
                                <option value="">-- Select Appliance --</option>
                                <template x-for="app in customerAppliances" :key="app.id">
                                    <option :value="app.id"
                                        x-text="app.product + ' - ' + app.brand + (app.model_no ? ' ('+app.model_no+')' : '')">
                                    </option>
                                </template>
                            </select>
                            <p x-show="selectedCustomerId && !customerAppliances.length"
                                class="text-xs text-red-500 mt-1">This customer has no appliances. Please add one in
                                their profile first.</p>
                            @error('appliance_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Dealer -->
                        <div>
                            <label for="dealer" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Dealer</label>
                            <input type="text" name="dealer" id="dealer" value="{{ old('dealer') }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="e.g. SM Appliance">
                            @error('dealer')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date of Purchase -->
                        <div>
                            <label for="dop" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Date of Purchase</label>
                            <input type="date" name="dop" id="dop" value="{{ old('dop') }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('dop')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Assigned Technicians -->
                        <div class="md:col-span-2 mt-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                                    Assigned Technicians (<span x-text="selectedTechs.length"></span>/3)
                                </label>

                                <!-- Search & Filter Controls -->
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" x-model="searchTech" placeholder="Search technician..."
                                            class="block w-full pl-8 pr-3 py-1.5 border border-gray-300 dark:border-slate-500 rounded-md text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-700 dark:text-white placeholder-gray-400 dark:placeholder-slate-400">
                                    </div>
                                    <select x-model="filterTech" class="block pl-3 pr-8 py-1.5 border border-gray-300 dark:border-slate-500 rounded-md text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-700 dark:text-white">
                                        <option value="">All Statuses</option>
                                        <option value="Available">Available</option>
                                        <option value="Busy">Busy</option>
                                        <option value="Off-Duty">Off-Duty</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Grid of Technicians -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-80 overflow-y-auto p-1">
                                <template x-for="tech in filteredTechnicians" :key="tech.id">
                                    <label class="relative flex items-start p-4 rounded-xl border cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors"
                                        :class="selectedTechs.includes((tech.first_name + ' ' + (tech.last_name || '')).trim()) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800'">

                                        <!-- Hidden Input Array for Form Submission and Interaction -->
                                        <input type="checkbox" :value="(tech.first_name + ' ' + (tech.last_name || '')).trim()"
                                            class="hidden"
                                            :checked="selectedTechs.includes((tech.first_name + ' ' + (tech.last_name || '')).trim())"
                                            @click.prevent="toggleTech((tech.first_name + ' ' + (tech.last_name || '')).trim())"
                                            :disabled="!selectedTechs.includes((tech.first_name + ' ' + (tech.last_name || '')).trim()) && selectedTechs.length >= 3" />

                                        <!-- Tech Info Profile -->
                                        <div class="ml-3 flex-1 flex flex-col justify-center">
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-semibold text-blue-700 dark:text-blue-300" x-text="getInitials(tech.first_name, tech.last_name)"></span>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900 dark:text-white" x-text="(tech.first_name + ' ' + (tech.last_name || '')).trim()"></div>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <span class="text-xs text-gray-500 dark:text-slate-400" x-text="tech.role_title || 'Technician'"></span>

                                                        <!-- Status Badges -->
                                                        <span x-show="(tech.availability_status || '').toLowerCase() === 'available'" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800/50">
                                                            Available
                                                        </span>
                                                        <span x-show="(tech.availability_status || '').toLowerCase() === 'busy'" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800/50">
                                                            Busy
                                                        </span>
                                                        <span x-show="(tech.availability_status || '').toLowerCase() === 'off-duty'" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800 dark:bg-gray-700/50 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                                            Off-Duty
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <!-- Fallback empty state -->
                            <div x-show="filteredTechnicians.length === 0" class="py-4 text-center text-sm text-gray-500 dark:text-slate-400 italic">
                                No technicians match your search filters.
                            </div>

                            <!-- Native Form Submission explicit syncing -->
                            <template x-for="t in selectedTechs">
                                <input type="hidden" name="technicians[]" :value="t">
                            </template>

                            @error('technicians')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Service Types (connected to Service Prices) -->
                        <div class="md:col-span-2 mt-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Service Types <span class="text-red-500">*</span></label>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">Each checked service type adds its configured price to the labor cost.</p>
                            <div class="flex flex-wrap gap-4">
                                @foreach($servicePrices as $sp)
                                <label class="inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="service_types[]" value="{{ $sp->service_name }}"
                                        class="rounded border-gray-300 dark:border-slate-500 text-blue-600 dark:text-blue-400 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        {{ collect(old('service_types'))->contains($sp->service_name) ? 'checked' : '' }}
                                        @change="toggleServiceType('{{ $sp->service_name }}')">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-slate-200">{{ $sp->service_name }}</span>
                                    <span class="ml-1 text-xs text-green-700 font-medium">(+₱{{ number_format($sp->service_price, 2) }})</span>
                                </label>
                                @endforeach
                                @if($servicePrices->isEmpty())
                                <p class="text-sm text-gray-400 italic">No service prices configured yet. <a href="{{ route('prices.create') }}" class="text-blue-600 dark:text-blue-400 underline">Add service prices</a>.</p>
                                @endif
                            </div>
                            @error('service_types')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Problem Description -->
                        <div class="md:col-span-2">
                            <label for="problem_desc" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Problem Description<span class="text-red-500">*</span></label>
                            <textarea id="problem_desc" name="problem_desc" rows="3" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('problem_desc') }}</textarea>
                            @error('problem_desc')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Findings -->
                        <div class="md:col-span-2">
                            <label for="findings" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Findings <span class="text-red-500">*</span></label>
                            <textarea id="findings" name="findings" rows="3" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('findings') }}</textarea>
                            @error('findings')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remarks -->
                        <div class="md:col-span-2">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Remarks <span class="text-red-500">*</span></label>
                            <textarea id="remarks" name="remarks" rows="2" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('remarks') }}</textarea>
                            @error('remarks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Attachments Upload -->
                        <div class="md:col-span-2" x-data="{ 
                            files: [],
                            init() {
                                this.$watch('files', () => {
                                    if(this.files.length === 0) {
                                        document.getElementById('attachments').value = '';
                                    }
                                });
                            },
                            removeFile(index) {
                                this.files.splice(index, 1);
                                
                                // Reconstruct the FileList using DataTransfer
                                const dt = new DataTransfer();
                                const input = document.getElementById('attachments');
                                const { files } = input;
                                
                                for (let i = 0; i < files.length; i++) {
                                    if (i !== index) {
                                        dt.items.add(files[i]);
                                    }
                                }
                                
                                input.files = dt.files;
                            }
                        }">
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Attachments <span class="text-gray-400 text-xs font-normal">Optional (Max 5 files)</span></label>

                            <div>
                                <label for="attachments" class="inline-flex items-center px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm font-medium text-blue-700 hover:bg-blue-100 cursor-pointer transition-colors cursor-pointer">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Choose Files
                                </label>
                                <input type="file" name="attachments[]" id="attachments" multiple accept="image/*,.pdf,.doc,.docx" class="hidden"
                                    @change="
                                        let selected = Array.from($event.target.files);
                                        if (selected.length > 5) {
                                            alert('Maximum of 5 files allowed.');
                                            $event.target.value = '';
                                            files = [];
                                        } else {
                                            files = selected.map(f => f.name);
                                        }
                                    ">
                            </div>

                            <div x-show="files.length > 0" class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                <template x-for="(file, index) in files" :key="index">
                                    <div class="flex items-center justify-between text-sm text-gray-600 bg-gray-50 p-2 rounded border border-gray-100 dark:bg-slate-700 dark:border-slate-600 dark:text-slate-300 shadow-sm">
                                        <div class="flex items-center truncate">
                                            <svg class="flex-shrink-0 w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                            <span x-text="file" class="truncate"></span>
                                        </div>
                                        <button type="button" @click="removeFile(index)" class="ml-2 text-gray-400 hover:text-red-500 focus:outline-none transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            @error('attachments.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Dynamic Used Parts -->
                        <div class="md:col-span-2 bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg border border-gray-200 dark:border-slate-600">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Parts Used (Inventory)</h3>

                            <div class="flex items-end gap-3 mb-4">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Select Part</label>
                                    <select x-model="selectedPartId"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">-- Choose Part --</option>
                                        <template x-for="part in parts" :key="part.id">
                                            <option :value="part.id"
                                                x-text="part.part_no + ' - ' + part.name + ' (₱' + part.price + ') - Stock: ' + part.quantity_stock">
                                            </option>
                                        </template>
                                    </select>
                                </div>
                                <div class="w-24">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-200">Qty</label>
                                    <input type="number" x-model="partQuantity" min="1"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <button type="button" @click="addPart"
                                    class="mb-px px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                                    Add Part
                                </button>
                            </div>

                            <!-- Parts Table -->
                            <div x-show="selectedParts.length > 0"
                                class="mt-4 border border-gray-200 dark:border-slate-600 rounded-md overflow-hidden bg-white dark:bg-slate-800">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Part No.
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-400">
                                                Description</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-400">Price
                                            </th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-slate-400">Qty</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-400">Subtotal
                                            </th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-slate-400">Not Working
                                            </th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-slate-400">Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <template x-for="(part, index) in selectedParts" :key="part.id">
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white" x-text="part.part_no"></td>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white" x-text="part.name"></td>
                                                <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white"
                                                    x-text="'₱' + part.price.toFixed(2)"></td>
                                                <td class="px-4 py-2 text-sm text-center text-gray-900 dark:text-white">
                                                    <input type="number" x-model.number="part.quantity" min="1"
                                                        class="w-16 p-1 text-center text-sm border-gray-300 dark:border-slate-500 rounded"
                                                        @change="$dispatch('input')">
                                                </td>
                                                <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white"
                                                    x-text="'₱' + (part.is_not_working ? '0.00' : (part.price * part.quantity).toFixed(2))"></td>
                                                <td class="px-4 py-2 text-sm text-center text-gray-900 dark:text-white">
                                                    <input type="checkbox" x-model="part.is_not_working" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                </td>
                                                <td class="px-4 py-2 text-sm text-center">
                                                    <button type="button" @click="removePart(part.id)"
                                                        class="text-red-500 hover:text-red-700">
                                                        <svg class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>

                                                <!-- Hidden inputs to submit array -->
                                                <td class="hidden">
                                                    <input type="hidden" :name="'parts['+index+'][id]'"
                                                        :value="part.id">
                                                    <input type="hidden" :name="'parts['+index+'][quantity]'"
                                                        :value="part.quantity">
                                                    <input type="hidden" :name="'parts['+index+'][price]'"
                                                        :value="part.price">
                                                    <input type="hidden" :name="'parts['+index+'][is_not_working]'"
                                                        :value="part.is_not_working ? '1' : '0'">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-slate-700/50 font-semibold">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">Parts
                                                Total:</td>
                                            <td class="px-4 py-3 text-right text-sm text-blue-700 dark:blue-600"
                                                x-text="'₱' + totalPartsCost.toFixed(2)"></td>
                                            <td colspan="2" class="px-4 py-3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Optional manual text just in case they need to write something else that is not in inventory -->
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="used_parts" class="block text-xs font-medium text-gray-700 dark:text-slate-200">Additional Notes / Miscellaneous Not In Inventory</label>
                                    <textarea id="used_parts" name="used_parts" rows="2"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Any screws, tapes, manual items used...">{{ old('used_parts') }}</textarea>
                                </div>
                                <div>
                                    <label for="miscellaneous_cost" class="block text-xs font-medium text-gray-700 dark:text-slate-200">Miscellaneous Cost</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">₱</span>
                                        </div>
                                        <input type="number" name="miscellaneous_cost" id="miscellaneous_cost" step="0.01" min="0" x-model.number="miscCost"
                                            class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 sm:text-sm border-gray-300 dark:border-slate-500 rounded-lg" placeholder="0.00">
                                    </div>
                                    @error('miscellaneous_cost')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4 p-3 bg-blue-50 dark:bg-slate-700/50 rounded-lg border border-blue-100 dark:border-slate-600 flex justify-between items-center text-sm font-medium text-gray-900 dark:text-white mb-1">
                                <span>Total Labor Material Cost (Parts + Misc):</span>
                                <span class="text-lg text-blue-700 dark:text-blue-400 font-bold" x-text="'₱' + (totalPartsCost + (Number(miscCost) || 0)).toFixed(2)"></span>
                            </div>
                        </div>

                        <!-- Labor Cost (auto-computed from service types) -->
                        <div>
                            <label for="labor_cost" class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                                Labor Cost <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-slate-400 sm:text-sm">₱</span>
                                </div>
                                <input type="number" name="labor_cost" id="labor_cost" step="0.01" min="0" required
                                    :value="computedLabor"
                                    x-bind:value="computedLabor"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-lg bg-gray-50 dark:bg-slate-700/50"
                                    placeholder="0.00" readonly>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Calculated from selected service types.</p>
                            @error('labor_cost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Status</label>
                            <select id="status" name="status"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-slate-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg">
                                <option value="Pending" {{ old('status') == 'Pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="In Progress" {{ old('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="Waiting for Parts" {{ old('status') == 'Waiting for Parts' ? 'selected' : '' }}>Waiting for Parts</option>
                                <option value="Under Repair" {{ old('status') == 'Under Repair' ? 'selected' : '' }}>Under
                                    Repair</option>
                                <option value="Completed" {{ old('status') == 'Completed' ? 'selected' : '' }}>Completed
                                </option>
                                <option value="Cancelled" {{ old('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled
                                </option>
                            </select>
                            @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-100 dark:border-slate-700">
                        <a href="{{ route('services.index') }}"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            Create Service Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>