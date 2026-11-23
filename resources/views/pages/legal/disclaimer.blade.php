<x-layouts.app title="Disclaimer" robots="index,follow" :breadcrumbs="[['Home', route('home')], ['Disclaimer', null]]">
    <x-page-hero title="Disclaimer" sub="Last updated: {{ now()->translatedFormat('F Y') }}" />
    <div class="container-x max-w-3xl py-12">
        @unless (app()->environment('production'))<p class="mb-8 rounded-input border border-gold-500 bg-gold-500/10 p-4 text-sm text-warm-900"><strong>Draft for legal review.</strong> This text is a plain-language starting point, not legal advice. It must be reviewed by a qualified advisor before launch (see docs/21, R-03). This notice is hidden in production.</p>@endunless
        <div class="prose-pc">
<p>The information on this website is for general information only.</p>
<ul><li>Prices, availability, sizes, images (including artist impressions and renders) and specifications may differ from the final property and may change without notice.</li><li>Nothing on this website is legal, financial, tax or investment advice. Seek independent professional advice before buying, selling or investing.</li><li>A "Verified" badge reflects the checks recorded by our team at the date shown; it is not a guarantee of title, approvals or future performance.</li><li>We are not responsible for the content or practices of third-party websites we link to.</li></ul>
        </div>
    </div>
</x-layouts.app>
