<x-account-shell :title="__('Recently viewed')">
    @if ($properties->isEmpty())<div class="rounded-card border border-dashed border-warm-300 bg-warm-50 p-10 text-center text-warm-700">{{ __('Properties you view will appear here.') }}<p class="mt-4"><x-ui.button :href="route('properties.index')">{{ __('Browse properties') }}</x-ui.button></p></div>@else<div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">@foreach ($properties as $i => $p)<x-property-card :property="$p" :i="$i % 3" tag="h3" />@endforeach</div>@endif
</x-account-shell>
