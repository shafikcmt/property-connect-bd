@php
    $desc = $post->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($post->tr('excerpt') ?: $post->body), 155);
    $crumbs = [['Home', route('home')], ['Blog', route('blog.index')], [$post->tr('title'), null]];
    $schema = [array_filter(['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $post->tr('title'), 'description' => $desc, 'datePublished' => $post->published_at?->toIso8601String(), 'dateModified' => $post->updated_at->toIso8601String(),
        'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null, 'image' => $post->coverMedia?->url(1600), 'mainEntityOfPage' => route('blog.show', $post->slug)])];
@endphp
<x-layouts.app :title="$post->seo_title ?: $post->tr('title')" :description="$desc" :image="$post->coverMedia?->url(1600)" :schema="$schema" :breadcrumbs="$crumbs" og-type="article">
    <x-breadcrumbs :items="$crumbs" />
    <article class="container-x max-w-3xl py-8">
        @if ($post->category)<a href="{{ route('blog.category', $post->category->slug) }}" class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ $post->category->name }}</a>@endif
        <h1 class="mt-2 font-display text-3xl font-semibold leading-tight sm:text-5xl">{{ $post->tr('title') }}</h1>
        <p class="mt-4 text-sm text-warm-500">@if ($post->author){{ $post->author->name }} · @endif{{ $post->published_at?->translatedFormat('F j, Y') }} · {{ $post->readingMinutes() }} {{ __('min read') }}</p>
        @if ($post->coverMedia)<div class="zoom-wrap mt-8 aspect-[16/9] overflow-hidden rounded-card"><x-picture :media="$post->coverMedia" :alt="$post->tr('title')" sizes="768px" :eager="true" /></div>@endif
        <div class="prose-pc mt-8 text-lg">{!! $post->bodyHtml() !!}</div>
        <p class="mt-10 rounded-card bg-warm-50 p-5 text-sm text-warm-700">{{ __('This article is general information, not legal or financial advice.') }}</p>
    </article>
    @if ($related->isNotEmpty())<section class="bg-warm-50 py-14"><div class="container-x"><x-section-head title="More to read" /><div class="grid gap-6 md:grid-cols-3">@foreach ($related as $i => $p)<x-blog-card :post="$p" :i="$i" />@endforeach</div></div></section>@endif
</x-layouts.app>
