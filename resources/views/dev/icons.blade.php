{{-- Preview sheet of the Ekasa icon stock (local only, routes/web.php). --}}
@php
    $manifest = require resource_path('icons/ekasa/manifest.php');
    $svg = fn (string $key): string => (string) file_get_contents(resource_path("icons/ekasa/{$key}.svg"));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pustaka ikon Ekasa (pratinjau)</title>
    <style>
        body { margin: 0; padding: 24px; font: 13px/1.4 system-ui, sans-serif; color: #2d3748; background: #f5f7fa; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        h2 { margin: 28px 0 10px; font-size: 14px; text-transform: uppercase; letter-spacing: .08em; color: #647084; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 12px; }
        .card { display: grid; grid-template-columns: 1fr 1fr; border-radius: 10px; overflow: hidden; border: 1px solid #d0d5de; }
        .tile { display: flex; align-items: center; justify-content: space-around; padding: 14px 8px; }
        .light { background: #fff; color: #0f6b46; }
        .dark { background: #16231d; color: #fff; }
        .name { grid-column: 1 / -1; padding: 8px 10px; background: #fff; border-top: 1px solid #d0d5de; }
        .name code { color: #647084; }
        .review { color: #b45309; font-weight: 600; }
        .s16 svg { width: 16px; height: 16px; display: block; }
        .s24 svg { width: 24px; height: 24px; display: block; }
        .s48 svg { width: 48px; height: 48px; display: block; }
    </style>
</head>
<body>
    <h1>Pustaka ikon Ekasa</h1>
    <p>{{ count($manifest['icons']) }} ikon orisinal · 16, 24, 48 px · latar putih dan gelap · <span class="review">menunggu persetujuan</span>: {{ count($manifest['review'] ?? []) }} ikon batch 2</p>
    @foreach ($manifest['categories'] as $category => $categoryLabel)
        @php($keys = array_keys(array_filter($manifest['icons'], fn (array $icon): bool => $icon[1] === $category)))
        @if ($keys)
            <h2>{{ $categoryLabel['id'] }} / {{ $categoryLabel['en'] }}</h2>
            <div class="grid">
                @foreach ($keys as $key)
                    <div class="card" data-icon="{{ $key }}">
                        @foreach (['light', 'dark'] as $tone)
                            <div class="tile {{ $tone }}">
                                <span class="s16">{!! $svg($key) !!}</span>
                                <span class="s24">{!! $svg($key) !!}</span>
                                <span class="s48">{!! $svg($key) !!}</span>
                            </div>
                        @endforeach
                        <div class="name">@if (in_array($key, $manifest['review'] ?? [], true))<span class="review">menunggu persetujuan</span><br>@endif<strong>{{ $manifest['icons'][$key][0][1] }}</strong> · {{ $manifest['icons'][$key][0][0] }}<br><code>{{ $key }}</code></div>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
</body>
</html>
