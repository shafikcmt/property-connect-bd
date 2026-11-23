@props(['title', 'sub', 'eyebrow' => 'Request'])
<x-layouts.app :title="$title" :description="$sub" :breadcrumbs="[['Home', route('home')], [$title, null]]">
    <x-page-hero :eyebrow="$eyebrow" :title="$title" :sub="$sub" />
    <div class="container-x max-w-3xl py-12">{{ $slot }}</div>
</x-layouts.app>
