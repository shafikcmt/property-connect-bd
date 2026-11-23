<x-layouts.app title="Terms and Conditions" robots="index,follow" :breadcrumbs="[['Home', route('home')], ['Terms and Conditions', null]]">
    <x-page-hero title="Terms and Conditions" sub="Last updated: {{ now()->translatedFormat('F Y') }}" />
    <div class="container-x max-w-3xl py-12">
        @unless (app()->environment('production'))<p class="mb-8 rounded-input border border-gold-500 bg-gold-500/10 p-4 text-sm text-warm-900"><strong>Draft for legal review.</strong> This text is a plain-language starting point, not legal advice. It must be reviewed by a qualified advisor before launch (see docs/21, R-03). This notice is hidden in production.</p>@endunless
        <div class="prose-pc">
<p>By using this website you agree to these terms.</p>
<h2>Using the website</h2><p>The site is provided for lawful personal and business use related to property search and marketing. Do not misuse the site, attempt to disrupt it, scrape it at scale or submit false information.</p>
<h2>Listings and information</h2><p>Listings, prices, availability and specifications are provided by owners and developers and may change. We work to keep information accurate and show a "Verified" badge only where our team has recorded a verification, but you should confirm important details (including legal status, approvals and payment terms) before making any decision.</p>
<h2>Our role</h2><p>We are an independent marketing and facilitation platform. Unless stated otherwise, we are not a party to any sale or rental agreement between you and an owner or developer.</p>
<h2>Submitted content</h2><p>If you submit a property, you confirm you are authorised to list it and that the information and photos are accurate and yours to use. We may review, edit, decline or remove submissions.</p>
<h2>Limitation of liability</h2><p>To the extent permitted by law, we are not liable for losses arising from reliance on listing information or from dealings with third parties.</p>
<h2>Changes</h2><p>We may update these terms; continued use means you accept the updated terms.</p>
        </div>
    </div>
</x-layouts.app>
