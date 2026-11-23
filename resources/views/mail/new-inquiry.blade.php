<x-mail::message>
# {{ __('New inquiry received') }}

{{ __('A new :type request has arrived on the website. Open the inbox to view the details and respond.', ['type' => $lead->request_type->label()]) }}

<x-mail::button :url="$url">{{ __('Open inquiry') }}</x-mail::button>
</x-mail::message>
