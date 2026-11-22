@if (session('status'))
    <div role="status" class="mb-4 rounded-input border border-brand-600 bg-navy-50 px-4 py-3 text-sm text-navy-900">{{ session('status') }}</div>
@endif
