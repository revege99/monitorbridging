@if(auth()->user()?->isSuperadmin())
    <section class="mt-1">
        <div class="rounded-2xl px-0">
            <button type="button" class="flex w-full cursor-pointer items-center gap-2 rounded-2xl px-0 py-1.5 text-left text-white" data-dropdown-toggle aria-expanded="false">
                <span class="flex h-11 w-11 items-center justify-center text-slate-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6l-.04.08H10l-.04-.08a1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1L3.92 14v-4L4 9.96a1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6l.04-.08h3.92L14 4a1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.08.38.29.73.6 1l.08.04v3.92L20 14a1.7 1.7 0 0 0-.6 1Z" />
                    </svg>
                </span>
                <span class="min-w-0 flex-1 text-sm font-semibold">Setup Klinik</span>
                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <div class="h-px w-10 bg-white/10"></div>
                    <svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6" /></svg>
                </div>
            </button>
            <div class="mt-1 hidden space-y-1 pl-[7px]" data-dropdown-panel>
                @foreach ([['clinics', 'Profil Klinik'], ['database', 'Konfigurasi DB'], ['users', 'Manajemen User']] as [$setupTab, $label])
                    <a href="{{ route('setup.index', ['tab' => $setupTab]) }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 text-sm {{ request('tab', 'clinics') === $setupTab && request()->routeIs('setup.*') ? 'bg-cyan-400/8 text-white ring-1 ring-cyan-400/10' : 'text-slate-300 hover:bg-white/5' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ request('tab', 'clinics') === $setupTab && request()->routeIs('setup.*') ? 'bg-cyan-300' : 'bg-slate-500' }}"></span>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
