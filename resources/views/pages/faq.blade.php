@php $schema = $faqs->isEmpty() ? [] : [['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqs->flatten()->map(fn ($f) => ['@type' => 'Question', 'name' => $f->tr('question', true), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f->tr('answer', true))]])->all()]]; @endphp
<x-layouts.app title="Frequently asked questions" description="Answers to common questions about buying, selling, renting and site visits." :schema="$schema" :breadcrumbs="[['Home', route('home')], ['FAQ', null]]">
    <x-page-hero eyebrow="FAQ" title="Frequently asked questions" sub="Quick answers about our listings, verification, site visits and your data." />
    <div class="container-x max-w-3xl py-14">
        @forelse ($faqs as $cat => $list)<section class="mt-10 first:mt-0"><h2 class="font-display text-2xl font-semibold">{{ __($cat) }}</h2><div class="mt-4 divide-y divide-warm-100 rounded-card border border-warm-100 bg-white">
            @foreach ($list as $f)<details class="group p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-navy-900">{{ $f->tr('question', true) }}<x-ic name="chevron" class="h-5 w-5 shrink-0 transition group-open:rotate-180" /></summary><div class="prose-pc mt-3 whitespace-pre-line">{{ $f->tr('answer', true) }}</div></details>@endforeach</div></section>
        @empty<p class="rounded-card bg-warm-50 p-8 text-center text-warm-700">{{ __('FAQs are coming soon.') }}</p>@endforelse
        <p class="mt-12 text-center text-warm-700">{{ __('Still have questions?') }} <a class="font-semibold text-brand-700 underline" href="{{ route('contact') }}">{{ __('Contact us') }}</a></p>
    </div>
</x-layouts.app>
