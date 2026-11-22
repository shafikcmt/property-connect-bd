@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])
@php $id = 'f-'.$name; @endphp
<div>
    <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-warm-900">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           @required($required)
           @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
           {{ $attributes->merge(['class' => 'block h-12 w-full rounded-input border border-warm-300 bg-white px-3 text-base text-warm-900 placeholder:text-warm-500 focus:border-navy-900']) }}>
    @if ($hint && ! $errors->has($name))<p id="{{ $id }}-hint" class="mt-1 text-sm text-warm-700">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $id }}-error" class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>@enderror
</div>
