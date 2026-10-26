@props(['id', 'title', 'subtitle' => 'Kelola akses integrasi PCare klinik Anda.'])
<dialog id="{{ $id }}" class="pcare-modal">
    <header><div class="pcare-modal-title"><span class="pcare-emblem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><circle cx="13" cy="10" r="2"/><path d="m11.5 11.5-4 4"/></svg></span><div><h3>{{ $title }}</h3><p>{{ $subtitle }}</p></div></div><button type="button" data-modal-close aria-label="Tutup form">&#215;</button></header>
    <div class="pcare-modal-body">{{ $slot }}</div>
</dialog>
