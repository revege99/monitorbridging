<div class="flex items-center gap-2">
    <div class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs text-slate-600">{{ $pageDateLabel }}</div>
    @if(auth()->user()?->isSuperadmin())
        <form method="POST" action="{{ route('clinics.select') }}">@csrf
            <select name="clinic_id" onchange="this.form.submit()" class="max-w-56 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-cyan-400">
                @forelse($availableClinics ?? [] as $clinic)<option value="{{ $clinic->id }}" @selected(($activeClinic?->id ?? null) === $clinic->id)>{{ $clinic->name }}</option>@empty<option>Belum ada klinik</option>@endforelse
            </select>
        </form>
    @else
        <div class="rounded-xl border border-cyan-100 bg-cyan-50 px-4 py-2 text-xs font-semibold text-cyan-700">{{ $activeClinic?->name ?? 'Klinik belum ditetapkan' }}</div>
    @endif
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 hover:bg-slate-50">Keluar</button></form>
</div>
