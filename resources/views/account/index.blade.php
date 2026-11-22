<x-layouts.app :title="__('My account')" robots="noindex,nofollow">
    <div class="mx-auto max-w-3xl px-4 py-14">
        <h1 class="font-display text-3xl font-semibold">{{ __('Welcome, :name', ['name' => $user->name]) }}</h1>
        <div class="mt-6"><x-ui.flash /></div>
        <p class="mt-4 text-warm-700">{{ __('Favorites, saved searches and your inquiries will appear here.') }}</p>
        @if ($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
            <p class="mt-6"><x-ui.button variant="secondary" href="/admin">{{ __('Go to admin panel') }}</x-ui.button></p>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="mt-8">@csrf<x-ui.button variant="ghost" type="submit">{{ __('Log out') }}</x-ui.button></form>
    </div>
</x-layouts.app>
