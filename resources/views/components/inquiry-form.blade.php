@props(['property' => null, 'project' => null, 'developer' => null, 'title' => 'Request information', 'cta' => 'Send inquiry', 'compact' => false])
@php $name = $property?->title ?? $project?->name ?? $developer?->name; @endphp
<section id="inquiry" class="rounded-card border border-warm-100 bg-white p-6 shadow-sm" aria-labelledby="inq-title">
    <h2 id="inq-title" class="font-display text-xl font-semibold">{{ __($title) }}</h2>
    @if ($name)<p class="mt-1 text-sm text-warm-700">{{ __('About') }}: <span class="font-medium text-navy-900">{{ $name }}</span></p>@endif
    @if (session('form') === 'inquiry')
        <div class="mt-4 rounded-input border border-brand-600 bg-brand-600/5 p-4 text-sm text-navy-900" role="status"><x-ic name="check" class="mr-1 inline h-5 w-5 text-brand-600" />{{ session('status') }}</div>
    @else
        <form method="POST" action="{{ route('inquiry.store') }}" class="mt-4" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @if ($property)<input type="hidden" name="property_id" value="{{ $property->id }}">@endif
            @if ($project)<input type="hidden" name="project_id" value="{{ $project->id }}">@endif
            @if ($developer)<input type="hidden" name="developer_id" value="{{ $developer->id }}">@endif
            <x-forms.contact-fields prefix="inq-" message-placeholder="{{ $name ? __('I am interested in :n. Please share more details.', ['n' => $name]) : '' }}" />
            <x-ui.button type="submit" class="mt-5 w-full" ::disabled="busy"><span x-show="!busy">{{ __($cta) }}</span><span x-show="busy" x-cloak>{{ __('Sending…') }}</span></x-ui.button>
        </form>
    @endif
</section>
