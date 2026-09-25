# {!! $settings['companyName'] !!}

> {!! $settings['seo']['description'] !!}

{!! trim(($settings['companyDescription'] ?? '').' '.($settings['heroSubtext'] ?? '')) !!}

## Services

@foreach ($services as $service)
- [{!! $service['title'] !!}]({!! $baseUrl !!}{!! $service['url'] ?? '/#'.$service['anchor'] !!}){!! $service['description'] ? ': '.$service['description'] : '' !!}
@endforeach

## Languages

@foreach ($alternates as $locale => $url)
- {!! \App\Support\Locales::nativeName($locale) !!}: {!! $url !!}
@endforeach

## Contact

- Website: {!! $baseUrl !!}/
@if ($settings['email'])
- Email: {!! $settings['email'] !!}
@endif
@if ($settings['phone'])
- Phone: {!! $settings['phone'] !!}
@endif
@if ($settings['whatsappUrl'])
- WhatsApp: {!! $settings['whatsappUrl'] !!}
@endif
@foreach ($settings['sameAs'] as $profile)
- {!! $profile !!}
@endforeach

## Links

- [Sitemap]({!! $baseUrl !!}/sitemap.xml)
