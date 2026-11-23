@props(['post', 'i' => 0, 'tag' => 'h3'])
<article class="reveal group relative overflow-hidden rounded-card border border-warm-100 bg-white lift" style="--i: {{ $i }}">
    <div class="zoom-wrap aspect-[16/10]"><x-picture :media="$post->coverMedia" :alt="$post->title" /></div>
    <div class="p-6">
        @if ($post->category)<p class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ $post->category->name }}</p>@endif
        <{{ $tag }} class="mt-2 font-display text-xl font-semibold leading-snug"><a href="{{ route('blog.show', $post->slug) }}" class="after:absolute after:inset-0 after:content-['']">{{ $post->title }}</a></{{ $tag }}>
        <p class="mt-2 line-clamp-2 text-sm text-warm-700">{{ $post->excerpt }}</p>
        <p class="mt-4 text-xs text-warm-500">{{ $post->published_at?->format('M j, Y') }} · {{ $post->readingMinutes() }} {{ __('min read') }}</p>
    </div>
</article>
