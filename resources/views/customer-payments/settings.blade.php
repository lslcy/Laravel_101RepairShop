<x-app-layout>
    <div class="ui-page max-w-3xl">
        <div class="ui-page-header">
            <div>
                <h1 class="ui-page-title">GCash QR settings</h1>
                <p class="ui-page-subtitle">Choose the QR image customers see when paying through the app.</p>
            </div>
            <a href="{{ route('customer-payments.index') }}" class="inline-flex min-h-[48px] items-center justify-center rounded-lg border border-gray-300 px-4 py-3 text-sm font-medium hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-600 dark:hover:bg-slate-700">Back to payments</a>
        </div>
        @include('customer-payments.validation-errors')
        @if(!$paymentReviewAvailable || !$storageConfigured)
            <div role="status" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                {{ !$paymentReviewAvailable ? 'Customer payment settings have not been set up yet. Contact the system administrator to finish setup.' : 'GCash image storage is not configured. Contact the system administrator before uploading a QR image.' }}
            </div>
        @endif
        <section class="ui-card p-5 sm:p-6" aria-labelledby="qr-settings-heading">
            <h2 id="qr-settings-heading" class="text-lg font-semibold">Payment destination</h2>
            <form method="POST" action="{{ route('customer-payments.settings.update') }}" enctype="multipart/form-data" class="mt-5 space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PUT')
                <div>
                    <label for="gcash-recipient" class="block text-sm font-medium">Recipient name <span aria-hidden="true">*</span></label>
                    <input id="gcash-recipient" name="gcash_recipient_name" required maxlength="150" value="{{ old('gcash_recipient_name', $settings?->gcash_recipient_name ?? $defaultRecipient) }}" aria-describedby="recipient-help"
                        class="mt-2 block min-h-[48px] w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600">
                    <p id="recipient-help" class="mt-2 text-sm text-gray-500 dark:text-slate-400">Use the name associated with the shop's GCash account.</p>
                </div>
                @if($settings?->gcash_qr_image_url)
                    <div>
                        <h3 class="text-sm font-medium">Current QR code</h3>
                        <div class="mt-3 max-w-sm rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-600"><img src="{{ $settings->gcash_qr_image_url }}" alt="Current GCash QR code for {{ $settings->gcash_recipient_name }}" class="aspect-square w-full object-contain"></div>
                    </div>
                @endif
                <div>
                    <label for="gcash-qr-image" class="block text-sm font-medium">{{ $settings ? 'Replace QR image' : 'GCash QR image' }} @if(!$settings)<span aria-hidden="true">*</span>@endif</label>
                    <input id="gcash-qr-image" name="qr_image" type="file" accept="image/jpeg,image/png,image/webp" @required(!$settings) aria-describedby="qr-image-help"
                        class="mt-2 block min-h-[48px] w-full rounded-lg border border-gray-300 bg-white p-2 text-sm file:mr-4 file:min-h-[40px] file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:font-medium file:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:file:bg-slate-700 dark:file:text-blue-300">
                    <p id="qr-image-help" class="mt-2 text-sm text-gray-500 dark:text-slate-400">Upload the actual GCash QR image. JPG, PNG, or WebP, up to 5 MB. {{ $settings ? 'Leave this empty to keep the current image.' : '' }}</p>
                </div>
                <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-slate-700/50 dark:text-slate-200">Scan and check the QR code in GCash before saving. Customers' submitted receipts remain private.</p>
                <button type="submit" @disabled(!$paymentReviewAvailable || !$storageConfigured) :disabled="submitting || {{ $paymentReviewAvailable && $storageConfigured ? 'false' : 'true' }}" class="inline-flex min-h-[48px] w-full items-center justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:focus-visible:ring-offset-slate-900">
                    <span x-text="submitting ? 'Saving GCash settings...' : 'Save GCash settings'">Save GCash settings</span>
                </button>
            </form>
        </section>
    </div>
</x-app-layout>