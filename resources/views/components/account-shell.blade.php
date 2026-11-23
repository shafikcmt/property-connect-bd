@props(['title'])
@php $nav = [['account.index', 'Overview', 'home'], ['account.favorites', 'Saved properties', 'heart'], ['account.recent', 'Recently viewed', 'clock'], ['account.searches', 'Saved searches', 'search'], ['account.inquiries', 'My inquiries', 'mail'], ['account.visits', 'Site visits', 'calendar'], ['account.submissions', 'My submissions', 'key'], ['account.profile', 'Profile', 'user']]; @endphp
<x-layouts.app :title="$title" robots="noindex,nofollow">
    <div class="container-x grid gap-8 py-10 lg:grid-cols-[240px_1fr]">
        <aside><nav aria-label="{{ __('Account') }}" class="flex gap-1 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible lg:pb-0 no-scrollbar">
            @foreach ($nav as [$r, $l, $ic])<a href="{{ route($r) }}" class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-input px-3.5 py-2.5 text-sm font-medium {{ request()->routeIs($r) ? 'bg-navy-900 text-white' : 'text-warm-700 hover:bg-warm-100' }}" @if (request()->routeIs($r)) aria-current="page" @endif><x-ic :name="$ic" class="h-4 w-4" />{{ __($l) }}</a>@endforeach
            <form method="POST" action="{{ route('logout') }}" class="lg:mt-3">@csrf<button class="flex w-full items-center gap-2.5 whitespace-nowrap rounded-input px-3.5 py-2.5 text-sm font-medium text-warm-700 hover:bg-warm-100">{{ __('Log out') }}</button></form></nav></aside>
        <div><h1 class="font-display text-3xl font-semibold">{{ $title }}</h1><div class="mt-4"><x-ui.flash /></div><div class="mt-6">{{ $slot }}</div></div>
    </div>
</x-layouts.app>
