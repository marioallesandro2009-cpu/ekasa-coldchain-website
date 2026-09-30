<x-mail::message>
# {{ __('New inquiry from the website') }}

<x-mail::table>
| | |
|:--|:--|
| **{{ __('Name') }}** | {{ $lead->name }} |
| **Email** | {{ $lead->email }} |
| **{{ __('Phone') }}** | {{ $lead->phone ?: '-' }} |
| **{{ __('Company') }}** | {{ $lead->company ?: '-' }} |
| **{{ __('Service of interest') }}** | {{ $lead->service_title ?: '-' }} |
| **{{ __('Received') }}** | {{ $lead->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB |
| **{{ __('Language') }}** | {{ $localeName }} |
</x-mail::table>

**{{ __('Message') }}**

<x-mail::panel>
{{ $lead->message }}
</x-mail::panel>

<x-mail::button :url="'mailto:'.$lead->email">
{{ __('Reply to :name', ['name' => $lead->name]) }}
</x-mail::button>

{{ __('You can also press Reply on this email: it goes straight to the sender.') }}
</x-mail::message>
