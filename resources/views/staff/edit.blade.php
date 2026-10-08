<x-app-layout>
    <div class="ui-page">
        <!-- Header -->
        <div class="ui-page-header">
            <h2 class="ui-page-title">Edit User</h2>
            <a href="{{ route('staff.index') }}"
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
                <form action="{{ route('staff.update', $staff) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-slate-200">First
                                    Name</label>
                                <input type="text" name="first_name" id="first_name"
                                    value="{{ old('first_name', $staff->first_name) }}" required
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Last Name</label>
                                <input type="text" name="last_name" id="last_name"
                                    value="{{ old('last_name', $staff->last_name) }}" required
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Username</label>
                            <input type="text" name="username" id="username"
                                value="{{ old('username', $staff->username) }}" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Email Address</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $staff->email) }}"
                                required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Update Password (Optional) -->
                        @if (auth()->id() === $staff->id)
                            <div class="bg-gray-50 dark:bg-slate-700/50 border border-gray-200 dark:border-slate-700 rounded-lg p-4 mb-4">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-slate-200 mb-4">Reset Password (Optional)</h3>
                                <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">Leave these fields blank if you do not want to change
                                    the user's current password.</p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-slate-200">New
                                            Password</label>
                                        <input type="password" name="password" id="password"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            placeholder="••••••••">
                                        @error('password')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Confirm New Password -->
                                    <div>
                                        <label for="password_confirmation"
                                            class="block text-sm font-medium text-gray-700 dark:text-slate-200">Confirm New Password</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-slate-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            placeholder="••••••••">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Password Management</h3>
                                <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-1">For security reasons, administrators cannot change other users' passwords directly. If a user has forgotten their password, they must reset it themselves using the password reset process.</p>
                            </div>
                        @endif

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-slate-200">Role</label>
                            <select id="role" name="role" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-slate-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg">
                                <option value="Technician" {{ old('role', $staff->role) == 'Technician' ? 'selected' : '' }}>Technician</option>
                                <option value="Administrator" {{ old('role', $staff->role) == 'Administrator' ? 'selected' : '' }}>Administrator</option>
                                <option value="Secretary" {{ old('role', $staff->role) == 'Secretary' ? 'selected' : '' }}>Secretary
                                </option>
                                <option value="Cashier" {{ old('role', $staff->role) == 'Cashier' ? 'selected' : '' }}>
                                    Cashier
                                </option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        </div>

                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-100 dark:border-slate-700">
                        <a href="{{ route('staff.index') }}"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-500 rounded-lg text-sm font-medium text-gray-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:bg-slate-700/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Update Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>