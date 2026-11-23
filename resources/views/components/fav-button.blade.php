@props(['kind' => 'property', 'id', 'active' => false, 'label' => 'Save to favorites'])
<button type="button" x-data="favorite('{{ $kind }}', {{ $id }}, {{ $active ? 'true' : 'false' }})" @click.prevent.stop="toggle()" :aria-pressed="on.toString()" aria-label="{{ __($label) }}"
        {{ $attributes->merge(['class' => 'relative z-10 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-navy-900 shadow-sm transition hover:scale-110 hover:text-danger']) }}>
    <svg class="h-5 w-5 transition" :class="[on ? 'fill-danger stroke-danger' : 'fill-none stroke-current', pop ? 'pop' : '']" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0112 7.6a4.3 4.3 0 017.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg>
</button>
