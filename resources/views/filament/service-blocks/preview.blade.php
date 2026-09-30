{{-- Admin preview of one service detail block (Builder): heading, status and a short summary.
     Inline styles: the admin theme only ships Filament's own classes. --}}
@php
    $en = \App\Support\Locales::default();
    $title = trim((string) ($data['title'][$en] ?? ''));
    $eyebrow = trim((string) ($data['eyebrow'][$en] ?? ''));
    $hidden = (bool) ($data['hidden'] ?? false);
    $reviewed = (bool) ($data['id_reviewed'] ?? false);
    $summary = \App\Support\ServiceBlocks::summary($type, $data);
    $badge = 'display:inline-block;padding:1px 8px;border-radius:9999px;font-size:11px;font-weight:600;';
@endphp
<div style="padding:14px 16px;{{ $hidden ? 'opacity:.55;' : '' }}">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:12px;">
        <span style="{{ $badge }}background:#DFF2EE;color:#006C5E;">{{ \App\Support\ServiceBlocks::label($type) }}</span>
        @if ($hidden)
            <span style="{{ $badge }}background:#EEF0F3;color:#475467;">Disembunyikan</span>
        @endif
        @unless ($reviewed)
            <span style="{{ $badge }}background:#FEF3C7;color:#92400E;">ID belum dicek: /id tampil EN</span>
        @endunless
    </div>
    @if ($eyebrow !== '')
        <p style="margin-top:8px;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#006C5E;font-weight:700;">{{ $eyebrow }}</p>
    @endif
    <p style="margin-top:4px;font-size:15px;font-weight:700;">{{ $title !== '' ? $title : ($type === 'hero_layanan' ? 'Judul = nama layanan' : 'Tanpa judul') }}</p>
    @if ($summary !== '')
        <p style="margin-top:2px;font-size:13px;opacity:.75;">{{ $summary }}</p>
    @endif
</div>
