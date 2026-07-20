@props(['id', 'title'])
<dialog id="{{ $id }}" class="m-auto w-[min(94vw,680px)] rounded-2xl bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50">
    <div class="flex items-center justify-between border-b px-5 py-4"><div><h3 class="font-semibold">{{ $title }}</h3><p class="mt-1 text-xs text-slate-500">Lengkapi data dengan benar lalu simpan.</p></div><button type="button" data-modal-close class="grid h-9 w-9 place-items-center rounded-xl text-xl text-slate-400 hover:bg-slate-100">×</button></div>
    <div class="max-h-[75vh] overflow-y-auto p-5">{{ $slot }}</div>
</dialog>
