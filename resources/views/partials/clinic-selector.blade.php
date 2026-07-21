<div class="flex items-center gap-2">
    <div class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs text-slate-600">{{ $pageDateLabel }}</div>
    @unless($hideClinicSelector ?? false)
        @if(auth()->user()?->isSuperadmin())
            <form method="POST" action="{{ route('clinics.select') }}" data-preserve-sidebar>@csrf
                <select name="clinic_id" onchange="this.form.requestSubmit()" class="max-w-56 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-cyan-400">
                    @forelse($availableClinics ?? [] as $clinic)<option value="{{ $clinic->id }}" @selected(($activeClinic?->id ?? null) === $clinic->id)>{{ $clinic->name }}</option>@empty<option>Belum ada klinik</option>@endforelse
                </select>
            </form>
        @else
            <div class="rounded-xl border border-cyan-100 bg-cyan-50 px-4 py-2 text-xs font-semibold text-cyan-700">{{ $activeClinic?->name ?? 'Klinik belum ditetapkan' }}</div>
        @endif
    @endunless

    <div class="relative" data-account-menu>
        <button type="button" data-account-toggle aria-expanded="false" class="flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 text-left text-slate-700 shadow-sm transition hover:bg-slate-50">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <span class="hidden leading-tight xl:block"><strong class="block max-w-28 truncate text-xs">{{ auth()->user()->name }}</strong><small class="block text-[10px] capitalize text-slate-400">{{ auth()->user()->role }}</small></span>
            <svg class="h-3.5 w-3.5 text-slate-400 transition" data-account-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div data-account-panel class="absolute right-0 z-50 mt-2 hidden w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_20px_55px_rgba(15,23,42,0.18)]">
            <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3"><p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p><p class="mt-0.5 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p></div>
            <div class="p-2">
                <button type="button" data-password-open class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-700"><span>🔑</span><span>Ubah Password</span></button>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-rose-600 transition hover:bg-rose-50"><span>↪</span><span>Keluar</span></button></form>
            </div>
        </div>
    </div>
</div>

<dialog data-password-dialog class="m-auto w-[min(92vw,430px)] rounded-3xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/50">
    <form method="POST" action="{{ route('account.password.update') }}" data-password-form data-no-loading class="p-6">@csrf
        <div class="flex items-start justify-between"><div><h3 class="text-lg font-semibold text-slate-900">Ubah Password</h3><p class="mt-1 text-xs text-slate-500">Masukkan password lama dan password baru Anda.</p></div><button type="button" data-password-close class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100">✕</button></div>
        <div data-password-error class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs text-rose-700"></div>
        <div class="mt-5 space-y-3">
            <label class="block text-xs font-medium text-slate-600">Password Lama<input name="current_password" type="password" required autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-400"></label>
            <label class="block text-xs font-medium text-slate-600">Password Baru<input name="password" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-400"></label>
            <label class="block text-xs font-medium text-slate-600">Konfirmasi Password Baru<input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-400"></label>
        </div>
        <div class="mt-5 flex justify-end gap-2"><button type="button" data-password-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600">Batal</button><button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-blue-700">Simpan Password</button></div>
    </form>
</dialog>

@once
    <script src="{{ asset('js/app-loading.js') }}" defer></script>
    <script src="{{ asset('js/content-navigation.js') }}" defer></script>
    <script src="{{ asset('js/clinic-switcher.js') }}" defer></script>
    <script src="{{ asset('js/account-menu.js') }}" defer></script>
@endonce
