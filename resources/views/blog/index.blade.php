<x-layouts.app :title="$category ? $category->name.' articles' : 'Blog & guides'" description="Buying guides, selling tips, investment insights and location guides for the Bangladesh property market." :breadcrumbs="[['Home', route('home')], ['Blog', null]]">
    <x-page-hero eyebrow="Blog" :title="$category ? $category->name : 'Guides & insights'" sub="Practical advice on buying, selling, renting and investing in property." />
    <div class="container-x py-12">
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Categories') }}"><a href="{{ route('blog.index') }}" class="chip !px-4 !py-2 text-sm {{ ! $category ? 'bg-navy-900 text-white' : 'bg-warm-100 text-warm-700' }}">{{ __('All') }}</a>@foreach ($categories as $c)<a href="{{ route('blog.category', $c->slug) }}" class="chip !px-4 !py-2 text-sm {{ $category?->id === $c->id ? 'bg-navy-900 text-white' : 'bg-warm-100 text-warm-700 hover:bg-warm-300/60' }}">{{ $c->name }}</a>@endforeach</nav>
        @if ($posts->isEmpty())<div class="mt-10 rounded-card border border-dashed border-warm-300 bg-warm-50 p-12 text-center"><x-ic name="doc" class="mx-auto h-10 w-10 text-warm-500" /><h2 class="mt-4 font-display text-2xl font-semibold">{{ __('Articles are on the way') }}</h2><p class="mt-2 text-warm-700">{{ __('Check back soon for guides and market insights.') }}</p></div>
        @else<div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">@foreach ($posts as $i => $p)<x-blog-card :post="$p" :i="$i % 3" />@endforeach</div><div class="mt-10">{{ $posts->links() }}</div>@endif
    </div>
</x-layouts.app>
