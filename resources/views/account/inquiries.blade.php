<x-account-shell :title="__('My inquiries')">
    @forelse ($leads as $l)<div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-card border border-warm-100 bg-white p-5"><div><p class="font-semibold text-navy-900">{{ $l->request_type->label() }}@if ($l->property) — <a class="underline" href="{{ route('properties.show', $l->property->slug) }}">{{ $l->property->title }}</a>@elseif ($l->project) — <a class="underline" href="{{ route('projects.show', $l->project->slug) }}">{{ $l->project->name }}</a>@endif</p><p class="text-sm text-warm-500">{{ $l->created_at->translatedFormat('M j, Y') }}</p></div><span class="chip bg-navy-50 text-navy-900">{{ $l->status->label() }}</span></div>
    @empty <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-10 text-center text-warm-700">{{ __('You have not sent any inquiries yet.') }}<p class="mt-4"><x-ui.button :href="route('properties.index')">{{ __('Browse properties') }}</x-ui.button></p></div>
    @endforelse
    {{ $leads->links() }}
</x-account-shell>
