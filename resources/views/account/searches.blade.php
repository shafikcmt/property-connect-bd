<x-account-shell :title="__('Saved searches')">
    @forelse ($searches as $s)<div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-card border border-warm-100 bg-white p-5"><div><p class="font-semibold text-navy-900">{{ $s->name }}</p><p class="text-sm text-warm-500">{{ $s->created_at->format('M j, Y') }}</p></div>
        <div class="flex items-center gap-3"><a class="text-sm font-semibold text-brand-700" href="{{ route('properties.index', $s->filters) }}">{{ __('Run search') }}</a>
            <form method="POST" action="{{ route('account.searches.destroy', $s->id) }}">@csrf @method('DELETE')<button class="text-sm text-danger hover:underline">{{ __('Delete') }}</button></form></div></div>
    @empty <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-10 text-center text-warm-700">{{ __('Use “Save this search” on the properties page while logged in.') }}<p class="mt-4"><x-ui.button :href="route('properties.index')">{{ __('Search properties') }}</x-ui.button></p></div>
    @endforelse
</x-account-shell>
