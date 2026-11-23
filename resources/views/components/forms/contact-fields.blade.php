@props(['prefix' => '', 'message' => true, 'messageLabel' => 'Message', 'messagePlaceholder' => ''])
@php $user = auth()->user(); @endphp
<input type="hidden" name="_t" value="{{ now()->timestamp }}">
<input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="grid gap-4 sm:grid-cols-2">
    <div><label class="label" for="{{ $prefix }}name">{{ __('Full name') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <input class="input" id="{{ $prefix }}name" name="name" value="{{ old('name', $user?->name) }}" required maxlength="100" autocomplete="name" @error('name') aria-invalid="true" @enderror>
        @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
    <div><label class="label" for="{{ $prefix }}phone">{{ __('Phone / WhatsApp') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <input class="input" id="{{ $prefix }}phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', $user?->phone) }}" required placeholder="01XXXXXXXXX" autocomplete="tel" @error('phone') aria-invalid="true" @enderror>
        @error('phone')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
</div>
<div class="mt-4"><label class="label" for="{{ $prefix }}email">{{ __('Email (optional)') }}</label>
    <input class="input" id="{{ $prefix }}email" name="email" type="email" value="{{ old('email', $user?->email) }}" autocomplete="email">
    @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
@if ($message)
<div class="mt-4"><label class="label" for="{{ $prefix }}message">{{ __($messageLabel) }}</label>
    <textarea class="textarea" id="{{ $prefix }}message" name="message" rows="4" maxlength="2000" placeholder="{{ $messagePlaceholder }}">{{ old('message') }}</textarea>
    @error('message')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
@endif
<div class="mt-4 space-y-2">
    <label class="flex items-start gap-2.5 text-sm text-warm-700"><input type="checkbox" name="consent" value="1" class="mt-1 h-4 w-4 accent-[#0e8f6e]" @checked(old('consent')) required>
        <span>{{ __('I agree to be contacted about this request and accept the') }} <a href="{{ route('legal', 'privacy-policy') }}" class="underline" target="_blank">{{ __('Privacy Policy') }}</a>.</span></label>
    @error('consent')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    <label class="flex items-start gap-2.5 text-xs text-warm-500"><input type="checkbox" name="marketing_consent" value="1" class="mt-0.5 h-4 w-4 accent-[#0e8f6e]"> <span>{{ __('Also send me relevant property updates (optional).') }}</span></label>
</div>
