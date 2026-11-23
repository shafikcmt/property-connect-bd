<x-layouts.app title="Your favorites" robots="noindex,follow" :breadcrumbs="[['Home', route('home')], ['Favorites', null]]">
    <x-page-hero eyebrow="Saved" title="Your favorites" sub="Properties and projects you have saved." />
    <div class="container-x py-12">
        @guest<p class="mb-8 rounded-card border border-warm-100 bg-warm-50 p-4 text-sm text-warm-700">{{ __('Favorites are saved in this browser. ') }}<a class="font-semibold text-brand-700 underline" href="{{ route('login') }}">{{ __('Log in') }}</a> {{ __('or') }} <a class="font-semibold text-brand-700 underline" href="{{ route('register') }}">{{ __('create an account') }}</a> {{ __('to keep them on all your devices.') }}</p>@endguest
        @if ($properties->isEmpty() && $projects->isEmpty())
            <div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-12 text-center"><x-ic name="heart" class="mx-auto h-10 w-10 text-warm-500" /><h2 class="mt-4 font-display text-2xl font-semibold">{{ __('Nothing saved yet') }}</h2><p class="mt-2 text-warm-700">{{ __('Tap the heart on any property or project to save it here.') }}</p><p class="mt-6"><x-ui.button :href="route('properties.index')">{{ __('Browse properties') }}</x-ui.button></p></div>
        @endif
        @if ($properties->isNotEmpty())<h2 class="font-display text-2xl font-semibold">{{ __('Properties') }}</h2><div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($properties as $i => $p)<x-property-card :property="$p" :i="$i % 3" tag="h3" />@endforeach</div>@endif
        @if ($projects->isNotEmpty())<h2 class="mt-12 font-display text-2xl font-semibold">{{ __('Projects') }}</h2><div class="mt-5 grid gap-6 md:grid-cols-2 xl:grid-cols-3">@foreach ($projects as $i => $p)<x-project-card :project="$p" :i="$i % 3" />@endforeach</div>@endif
    </div>
</x-layouts.app>
