<x-layouts.app title="Privacy Policy" robots="index,follow" :breadcrumbs="[['Home', route('home')], ['Privacy Policy', null]]">
    <x-page-hero title="Privacy Policy" sub="Last updated: {{ now()->translatedFormat('F Y') }}" />
    <div class="container-x max-w-3xl py-12">
        @unless (app()->environment('production'))<p class="mb-8 rounded-input border border-gold-500 bg-gold-500/10 p-4 text-sm text-warm-900"><strong>Draft for legal review.</strong> This text is a plain-language starting point, not legal advice. It must be reviewed by a qualified advisor before launch (see docs/21, R-03). This notice is hidden in production.</p>@endunless
        <div class="prose-pc">
<p>This policy explains what personal data {{ config('app.name') }} collects, why, and your choices.</p>
<h2>Information we collect</h2><ul><li><strong>Inquiry and request forms:</strong> your name, phone number, optional email, your message, and the property or project you asked about.</li><li><strong>Accounts:</strong> name, email, optional phone, and a securely hashed password.</li><li><strong>Property submissions:</strong> property details, photos and the owner contact details you provide.</li><li><strong>Technical data:</strong> a keyed hash of your IP address and browser type for abuse prevention, and limited campaign information (such as UTM parameters) to understand which sources bring inquiries.</li></ul>
<h2>How we use it</h2><p>To respond to your requests, arrange site visits, manage listings, keep the site secure and improve our service. We send marketing messages only if you opt in, and you can withdraw at any time.</p>
<h2>Sharing</h2><p>We may share your request details with the relevant developer or property owner so they can assist you. We do not sell your personal data. We use service providers (for example hosting and email) who process data on our behalf.</p>
<h2>Your contact details are never shown publicly</h2><p>Buyer and inquiry contact details are visible only to authorised staff.</p>
<h2>Retention and your rights</h2><p>We keep inquiry data only as long as needed for the purposes above. You can ask us to access, correct or delete your data by contacting us; we may anonymise records we must keep for business or legal reasons.</p>
<h2>Cookies</h2><p>See our <a href="{{ route('legal', 'cookie-policy') }}">Cookie Policy</a>.</p>
<h2>Contact</h2><p>Questions about privacy? Use our <a href="{{ route('contact') }}">contact page</a>.</p>
        </div>
    </div>
</x-layouts.app>
