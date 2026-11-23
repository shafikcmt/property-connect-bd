<x-layouts.app title="Cookie Policy" robots="index,follow" :breadcrumbs="[['Home', route('home')], ['Cookie Policy', null]]">
    <x-page-hero title="Cookie Policy" sub="Last updated: {{ now()->format('F Y') }}" />
    <div class="container-x max-w-3xl py-12">
        @unless (app()->environment('production'))<p class="mb-8 rounded-input border border-gold-500 bg-gold-500/10 p-4 text-sm text-warm-900"><strong>Draft for legal review.</strong> This text is a plain-language starting point, not legal advice. It must be reviewed by a qualified advisor before launch (see docs/21, R-03). This notice is hidden in production.</p>@endunless
        <div class="prose-pc">
<p>Cookies are small files stored on your device.</p>
<h2>Cookies we use</h2><ul><li><strong>Essential:</strong> session and security (CSRF) cookies required for the site and forms to work, and to remember favorites and compare lists.</li><li><strong>Attribution:</strong> we remember campaign parameters (such as UTM) for your session so inquiries can be attributed to the right source.</li></ul>
<h2>Analytics and marketing</h2><p>If we enable analytics or advertising tools (such as Google Analytics or Meta Pixel), they will load only after you give consent, and this policy will list them.</p>
<h2>Your choices</h2><p>You can clear or block cookies in your browser settings; some features may then stop working.</p>
        </div>
    </div>
</x-layouts.app>
