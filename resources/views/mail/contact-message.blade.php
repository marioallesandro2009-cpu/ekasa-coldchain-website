<x-mail::message>
# {{ __('New inquiry from the website') }}

<x-mail::table>
| | |
|:--|:--|
| **{{ __('Name') }}** | {{ str_replace('|', '¦', $lead->name) }} |
| **Email** | {{ $lead->email }} |
| **{{ __('Phone') }}** | {{ str_replace('|', '¦', (string) $lead->phone) ?: '-' }} |
| **{{ __('Company') }}** | {{ str_replace('|', '¦', (string) $lead->company) ?: '-' }} |
| **{{ __('Country') }}** | {{ str_replace('|', '¦', (string) $lead->country) ?: '-' }} |
| **{{ __('Service of interest') }}** | {{ str_replace('|', '¦', (string) $lead->service_title) ?: '-' }} |
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

{{ __('The same data is attached as an Excel file.') }}

@if ($sheetUrl)
<x-mail::button :url="$sheetUrl">
{{ __('Open Google Sheet') }}
</x-mail::button>
@endif
</x-mail::message>
