{{-- Floating WhatsApp button, bottom-right on every page. Number comes from the
     admin (Pengaturan Situs > Nomor WhatsApp); without a number nothing renders. --}}
@props(['settings'])
@if ($settings['whatsappUrl'])
    <a
        href="{{ $settings['whatsappUrl'] }}?text={{ rawurlencode(__('Hi, I would like to ask about your cold chain services.')) }}"
        target="_blank"
        rel="noopener"
        class="fixed right-4 bottom-4 z-40 grid size-14 place-items-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/25 ring-1 ring-black/5 transition duration-200 hover:scale-105 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#25D366] sm:right-6 sm:bottom-6 sm:size-16"
        aria-label="{{ $settings['texts']['whatsapp_button'] }}"
    >
        <svg viewBox="0 0 24 24" fill="currentColor" class="size-7 sm:size-8" aria-hidden="true"><path d="M17.5 14.4c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.66.15-.2.3-.76.96-.93 1.16-.17.2-.34.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.64-2.06-.17-.3-.02-.46.13-.6.13-.13.3-.34.44-.51.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.66-1.6-.9-2.18-.24-.57-.48-.5-.66-.5h-.56c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.05 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.2-3.58.94.96-3.5-.22-.36a9.44 9.44 0 0 1-1.45-5.04c0-5.22 4.25-9.47 9.48-9.47 2.53 0 4.9.99 6.69 2.78a9.4 9.4 0 0 1 2.77 6.7c0 5.23-4.25 9.47-9.47 9.47zM20.1 3.9A11.3 11.3 0 0 0 12.05.5C5.83.5.77 5.56.77 11.78c0 1.99.52 3.93 1.51 5.65L.68 23.5l6.2-1.63a11.27 11.27 0 0 0 5.17 1.31h.01c6.22 0 11.28-5.06 11.28-11.28 0-3.01-1.17-5.85-3.24-7.99z" fill-rule="evenodd"/></svg>
    </a>
@endif
