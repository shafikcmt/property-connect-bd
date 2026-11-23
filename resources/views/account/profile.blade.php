<x-account-shell :title="__('Profile')">
    <form method="POST" action="{{ route('account.profile.update') }}" class="max-w-lg space-y-5 rounded-card border border-warm-100 bg-white p-6">@csrf @method('PUT')
        <div><label class="label" for="pf-name">{{ __('Full name') }}</label><input id="pf-name" class="input" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="pf-email">{{ __('Email') }}</label><input id="pf-email" class="input bg-warm-50" value="{{ $user->email }}" disabled aria-describedby="pf-email-h"><p id="pf-email-h" class="mt-1 text-sm text-warm-500">{{ __('Contact us to change your email address.') }}</p></div>
        <div><label class="label" for="pf-phone">{{ __('Phone') }}</label><input id="pf-phone" class="input" type="tel" name="phone" value="{{ old('phone', $user->phone) }}">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
        <x-ui.button type="submit">{{ __('Save changes') }}</x-ui.button></form>
</x-account-shell>
