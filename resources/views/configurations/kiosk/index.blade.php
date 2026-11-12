<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfigurasi Anjungan</title>@fonts
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="setup-page min-h-screen bg-[#f4f7fb] text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="relative bg-[#071325] text-white">
        <div class="relative flex h-full flex-col px-[5px] py-5">
            <h1 class="px-2 text-xl font-semibold">Bridging Monitor</h1>
            <nav class="mt-6 flex-1 overflow-y-auto">
                <a href="{{ url('/') }}" class="flex rounded-xl px-2 py-2.5 text-sm text-slate-300">Dashboard</a>
                @include('partials.setup-sidebar-complete')
                @include('partials.setup-clinic-menu')
            </nav>
        </div>
    </aside>
    <main class="min-w-0">
        <header class="app-page-header"><div class="flex items-center justify-between px-4 py-2.5">
            <div><h2 class="font-semibold">Konfigurasi Anjungan</h2><p class="text-xs text-slate-500">Konfigurasi &gt; Anjungan</p></div>
            @include('partials.clinic-selector')
        </div></header>
        <div class="p-4">
            @if(session('success'))<div role="status" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
            @if($errors->any())<div role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
            <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold">Format Nomor Rekam Medis</h3>
                <p class="mt-1 text-sm text-slate-500">Atur nomor RM untuk pasien baru yang dibuat melalui anjungan. Nomor pasien yang sudah ada tidak diubah.</p>
                <form method="POST" action="{{ route('configurations.kiosk.update') }}" id="kiosk-format-form" class="mt-6">
                    @csrf @method('PUT')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm text-slate-600">Jumlah digit angka
                            <input id="rm-digits" name="rm_digits" type="number" min="1" max="12" required value="{{ old('rm_digits', $configuration->rm_digits) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5">
                        </label>
                        <label class="text-sm text-slate-600">Gunakan pemisah
                            <select id="rm-use-separator" name="rm_use_separator" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5">
                                <option value="1" @selected((string) old('rm_use_separator', (int) $configuration->rm_use_separator) === '1')>Ya</option>
                                <option value="0" @selected((string) old('rm_use_separator', (int) $configuration->rm_use_separator) === '0')>Tidak</option>
                            </select>
                        </label>
                        <label class="text-sm text-slate-600">Karakter pemisah
                            <select id="rm-separator" name="rm_separator" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5">
                                @foreach(['-' => 'Strip (-)', '.' => 'Titik (.)', '/' => 'Garis miring (/)'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('rm_separator', $configuration->rm_separator) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm text-slate-600">Pengelompokan digit
                            <input id="rm-groups" name="rm_groups" value="{{ old('rm_groups', $configuration->rm_groups) }}" placeholder="2-2-2" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5">
                            <span class="mt-1 block text-xs text-slate-500">Contoh: 2-2-2 untuk 6 digit, atau 3-3.</span>
                        </label>
                    </div>
                    <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-semibold text-blue-700">PRATINJAU NOMOR PERTAMA</p>
                        <p id="rm-preview" class="mt-1 font-mono text-2xl font-semibold text-blue-900" aria-live="polite">{{ $preview }}</p>
                        <p class="mt-2 text-xs text-slate-600">Nomor berikutnya melanjutkan pencatat set_no_rkm_medis di SIMRS. Pencatat hanya diperbarui setelah pasien baru berhasil disimpan. Nomor yang sudah digunakan akan dilewati. Pratinjau ini bukan nomor yang dipesan.</p>
                        <p class="mt-1 text-xs text-slate-600">Tidak ada prefix tetap: untuk 8 digit, 00099999 dilanjutkan menjadi 00100000.</p>
                    </div>
                    <button type="submit" class="mt-5 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Simpan Pengaturan</button>
                </form>
            </section>
        </div>
    </main>
</div>
<script src="{{ asset('js/kiosk-configuration.js') }}"></script>
<script src="{{ asset('js/sidebar-state.js') }}"></script>
</body>
</html>
