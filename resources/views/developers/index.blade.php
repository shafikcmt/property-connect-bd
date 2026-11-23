<x-layouts.app title="Real estate developers" description="Browse partner developers, their verified status and current projects." :breadcrumbs="[['Home', route('home')], ['Developers', null]]">
    <section class="bg-navy-900 text-white"><div class="container-x py-10 sm:py-14"><h1 class="font-display text-3xl font-semibold !text-white sm:text-4xl">{{ __('Developers') }}</h1><p class="mt-2 max-w-2xl text-warm-100/80">{{ __('Partner developers and their projects. A Verified badge means our team recorded how and when we checked the company.') }}</p></div></section>
    <div class="container-x py-10">
        @if ($developers->isEmpty())<div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-12 text-center"><x-ic name="handshake" class="mx-auto h-10 w-10 text-warm-500" /><h2 class="mt-4 font-display text-2xl font-semibold">{{ __('Developers will appear here soon') }}</h2><p class="mt-2 text-warm-700">{{ __('Are you a developer? Partner with us to showcase your projects.') }}</p><p class="mt-6"><x-ui.button :href="route('contact')">{{ __('Partner with us') }}</x-ui.button></p></div>
        @else<div class="grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4">@foreach ($developers as $i => $d)<x-developer-card :developer="$d" :i="$i % 4" tag="h2" />@endforeach</div><div class="mt-10">{{ $developers->links() }}</div>@endif
    </div>
</x-layouts.app>
