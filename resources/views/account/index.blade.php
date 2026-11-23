<x-account-shell :title="__('Welcome, :name', ['name' => explode(' ', $user->name)[0]])">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ([['account.favorites', 'Saved', $favorites, 'heart'], ['account.inquiries', 'Inquiries', $inquiries, 'mail'], ['account.visits', 'Site visits', $visits, 'calendar'], ['account.searches', 'Saved searches', $searches, 'search'], ['account.submissions', 'Submissions', $submissions, 'key']] as [$r, $l, $n, $ic])
        <a href="{{ route($r) }}" class="rounded-card border border-warm-100 bg-white p-5 lift"><x-ic :name="$ic" class="h-6 w-6 text-brand-600" /><p class="mt-3 font-display text-3xl font-semibold text-navy-900">{{ $n }}</p><p class="text-sm text-warm-700">{{ __($l) }}</p></a>@endforeach</div>
    <p class="mt-8 flex flex-wrap gap-3"><x-ui.button :href="route('properties.index')">{{ __('Browse properties') }}</x-ui.button><x-ui.button variant="secondary" :href="route('forms.submit')">{{ __('Submit a property') }}</x-ui.button>
        @if ($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))<x-ui.button variant="ghost" href="/admin">{{ __('Admin panel') }}</x-ui.button>@endif</p>
</x-account-shell>
