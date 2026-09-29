{{--
    Fleet showcase truck info: eyebrow, split title, description. Used twice per
    truck (katalog-armada.blade.php) — once in normal flow above the photo on
    mobile (plain text on the section's dark background, no photo behind it to
    scrim against), once overlaid top-left on the photo at lg+ (inside
    .fleet-scrim there). Same markup both times; only the CTA that follows it
    differs in position between the two breakpoints.

    Title split: the name ("FE 71 Long") reads as two stacked lines — the model
    code as the large visual anchor, the trailing variant word ("Long") small
    and tracked-out beneath it — rather than one run-on string. Split on the
    LAST space so it degrades sanely for any future name shape. The full name
    stays in a sr-only span since the two visual lines are aria-hidden: a
    screen reader should hear "FE 71 Long" once, not "FE 71" then, separately,
    a decorative "Long".

    Eyebrow at text-sm, not text-xs: a touch more legible at the size that
    reads first on a truck's card. (A real glyph-rendering bug was also found
    near this text — a rounded overflow clip further up the tree shaving the
    first letter off small capitals — but that was fixed at its source, the
    outer carousel wrapper in katalog-armada.blade.php, not by this size.)

    All of this text is full-opacity white, not a muted tint: it sits over a
    photo, not a flat color, and the desktop overlay's scrim fades by design
    (see fleet-scrim in app.css) — a muted white that measured fine right at
    the top-left corner dropped under the required 4.5:1 a few lines down,
    where the scrim is already fading and a light truck cab can show through.
    Measured against the actual worst-case panel: even text-white/85 wasn't
    enough at every position/breakpoint these labels sit in.

    The reefer/dry colour cue moved from the eyebrow's own text colour to a
    small dot beside it, for the same reason: colouring the word itself
    (text-highlight, a mid-tone green) measured 3.7–3.9:1 over a light truck
    cab, under the 4.5:1 small text needs — a dot is a decorative graphic, not
    text, so it only needs to be visible, not AA-text-contrast-legible, and
    the type is already stated in words either way.
--}}
@props(['item'])
@php
    $nameMain = \Illuminate\Support\Str::beforeLast($item['name'], ' ');
    $nameVariant = \Illuminate\Support\Str::afterLast($item['name'], ' ');
@endphp
<p class="flex items-center gap-2 text-sm font-semibold tracking-[0.2em] text-white uppercase">
    <span @class([
        'size-1.5 rounded-full',
        'bg-highlight' => $item['type'] === 'reefer',
        'bg-white/60' => $item['type'] !== 'reefer',
    ]) aria-hidden="true"></span>
    {{ $item['typeLabel'] }}
</p>
<h3 class="mt-2 leading-[0.95] text-white">
    <span class="sr-only">{{ $item['name'] }}</span>
    <span aria-hidden="true" class="block text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">{{ $nameMain }}</span>
    <span aria-hidden="true" class="mt-1 block text-sm font-semibold tracking-[0.15em] text-white uppercase sm:text-base">{{ $nameVariant }}</span>
</h3>
@if ($item['description'])
    <p class="mt-3 line-clamp-2 max-w-md text-sm font-medium text-white sm:text-base">{{ $item['description'] }}</p>
@endif
