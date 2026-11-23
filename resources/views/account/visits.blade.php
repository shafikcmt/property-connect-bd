<x-account-shell :title="__('Site visits')">
    @forelse ($visits as $v)<div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-card border border-warm-100 bg-white p-5"><div><p class="font-semibold text-navy-900">{{ $v->property?->title ?? $v->project?->name }}</p><p class="text-sm text-warm-700">{{ __('Preferred') }}: {{ $v->preferred_date->format('D, M j, Y') }} · {{ ucfirst($v->preferred_window) }}@if ($v->scheduled_at) · <strong>{{ __('Confirmed for') }} {{ $v->scheduled_at->format('M j, g:i a') }}</strong>@endif</p></div><span class="chip bg-navy-50 text-navy-900">{{ ucwords(str_replace('_', ' ', $v->status)) }}</span></div>
    @empty <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-10 text-center text-warm-700">{{ __('You have not requested any site visits yet.') }}<p class="mt-4"><x-ui.button :href="route('properties.index')">{{ __('Browse properties') }}</x-ui.button></p></div>
    @endforelse
    {{ $visits->links() }}
</x-account-shell>
