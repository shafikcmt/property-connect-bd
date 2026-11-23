@php $s = app(\App\Services\SettingsService::class); $wa = \App\Support\Phone::whatsapp($s->get('contact.whatsapp')); @endphp
<x-layouts.app title="Contact us" description="Get in touch with Property Connect BD for buying, selling, renting or developer partnerships." :breadcrumbs="[['Home', route('home')], ['Contact', null]]"
    :schema="[['@context' => 'https://schema.org', '@type' => 'ContactPage', 'name' => 'Contact', 'url' => route('contact')]]">
    <x-page-hero eyebrow="Contact" title="We would love to hear from you" sub="Tell us what you need and we will get back to you." />
    <div class="container-x grid gap-10 py-14 lg:grid-cols-[1fr_1.2fr]">
        <div class="space-y-5">
            @foreach ([['phone', 'Phone', $s->get('contact.phone'), $s->get('contact.phone') ? 'tel:'.preg_replace('/[^+\d]/', '', $s->get('contact.phone')) : null], ['whatsapp', 'WhatsApp', $wa ? '+'.$wa : null, $wa ? "https://wa.me/$wa" : null], ['mail', 'Email', $s->get('contact.email'), $s->get('contact.email') ? 'mailto:'.$s->get('contact.email') : null], ['pin', 'Office', $s->get('contact.address'), null]] as [$ic, $l, $v, $href])
                @if ($v)<div class="flex gap-4 rounded-card border border-warm-100 bg-white p-5"><span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-600/10 text-brand-700"><x-ic :name="$ic" /></span><div><p class="text-xs uppercase tracking-wide text-warm-500">{{ __($l) }}</p>@if ($href)<a class="font-semibold text-navy-900 hover:underline" href="{{ $href }}" @if ($ic === 'whatsapp') target="_blank" rel="noopener" @endif>{{ $v }}</a>@else<p class="font-semibold text-navy-900">{{ $v }}</p>@endif</div></div>@endif
            @endforeach
            @unless ($s->get('contact.phone') || $s->get('contact.email') || $wa || $s->get('contact.address'))<p class="rounded-card bg-warm-50 p-5 text-warm-700">{{ __('Use the form and our team will respond as soon as possible.') }}</p>@endunless
            <p class="text-sm text-warm-700">{{ __('Are you a developer? Mention your company and projects and we will contact you about a partnership.') }}</p>
        </div>
        <x-inquiry-form title="Send us a message" cta="Send message" />
    </div>
</x-layouts.app>
