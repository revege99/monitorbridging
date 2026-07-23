<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Login - Bridging Monitor</title>
    @fonts
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="min-h-screen bg-[#061426] font-sans text-slate-900 antialiased">
    <main class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-28 -top-32 h-96 w-96 rounded-full bg-cyan-400/15 blur-3xl"></div>
            <div class="absolute -bottom-40 right-0 h-[34rem] w-[34rem] rounded-full bg-blue-600/15 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.045]" style="background-image:linear-gradient(rgba(255,255,255,.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.6) 1px,transparent 1px);background-size:48px 48px"></div>
        </div>

        <div class="relative mx-auto grid min-h-screen w-full max-w-[1500px] lg:grid-cols-[1.08fr_.92fr]">
            <section class="hidden min-h-screen flex-col justify-between px-12 py-10 text-white lg:flex xl:px-20 xl:py-14">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-300/10 text-cyan-300 shadow-lg shadow-cyan-950/20">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12h4l2-5 4 10 2-5h6"></path>
                            <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-semibold tracking-tight">Bridging Monitor</p>
                        <p class="text-xs text-slate-400">Integrated Clinic Monitoring</p>
                    </div>
                </div>

                <div class="max-w-2xl py-10">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-cyan-300/15 bg-cyan-300/8 px-3 py-1.5 text-xs font-medium text-cyan-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_0_4px_rgba(52,211,153,.12)]"></span>
                        Sistem monitoring terintegrasi
                    </div>
                    <h1 class="max-w-xl text-4xl font-semibold leading-[1.13] tracking-[-0.035em] xl:text-5xl">
                        Satu akses untuk memantau seluruh proses
                        <span class="bg-gradient-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">bridging klinik.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-300">
                        Pantau antrean BPJS, integrasi SATUSEHAT, dan aktivitas pelayanan secara terpusat dengan informasi yang lebih jelas.
                    </p>

                    <div class="mt-10 grid max-w-xl grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/8 bg-white/[0.045] p-4 backdrop-blur-sm">
                            <svg class="mb-3 h-5 w-5 text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-7"/></svg>
                            <p class="text-sm font-semibold">Monitoring</p>
                            <p class="mt-1 text-xs text-slate-400">Data pelayanan</p>
                        </div>
                        <div class="rounded-2xl border border-white/8 bg-white/[0.045] p-4 backdrop-blur-sm">
                            <svg class="mb-3 h-5 w-5 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                            <p class="text-sm font-semibold">Integrasi</p>
                            <p class="mt-1 text-xs text-slate-400">BPJS & SATUSEHAT</p>
                        </div>
                        <div class="rounded-2xl border border-white/8 bg-white/[0.045] p-4 backdrop-blur-sm">
                            <svg class="mb-3 h-5 w-5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                            <p class="text-sm font-semibold">Aman</p>
                            <p class="mt-1 text-xs text-slate-400">Akses terkontrol</p>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-slate-500">&copy; {{ date('Y') }} Bridging Monitor. Sistem informasi klinik.</p>
            </section>

            <section class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-10 lg:bg-white/[0.025] lg:px-12">
                <div class="w-full max-w-[460px]">
                    <div class="mb-8 flex items-center gap-3 text-white lg:hidden">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-300/12 text-cyan-300">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12h4l2-5 4 10 2-5h6"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                        </div>
                        <div><p class="font-semibold">Bridging Monitor</p><p class="text-xs text-slate-400">Integrated Clinic Monitoring</p></div>
                    </div>

                    <div class="rounded-[30px] border border-white/70 bg-white p-6 shadow-[0_30px_90px_rgba(2,8,23,.3)] sm:p-9">
                        <div class="mb-8">
                            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white shadow-lg shadow-blue-500/20">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <path d="M19 8v6M22 11h-6"></path>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-600">Selamat datang</p>
                            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Masuk ke akun Anda</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan username atau email yang sudah terdaftar.</p>
                        </div>

                        @if($errors->any())
                            <div class="mb-5 flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg>
                                <div><p class="font-semibold">Login belum berhasil</p><p class="mt-0.5 text-xs text-rose-600">{{ $errors->first() }}</p></div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label for="login" class="mb-2 block text-sm font-semibold text-slate-700">Username atau Email</label>
                                <div class="group relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex w-12 items-center justify-center text-slate-400 group-focus-within:text-cyan-600">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                    </span>
                                    <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" placeholder="Masukkan username atau email" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50/70 pl-12 pr-4 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-500/10">
                                </div>
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                                <div class="group relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex w-12 items-center justify-center text-slate-400 group-focus-within:text-cyan-600">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                    </span>
                                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50/70 pl-12 pr-12 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-500/10">
                                    <button type="button" data-password-toggle class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-slate-700" aria-label="Tampilkan password" aria-pressed="false">
                                        <svg data-eye-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                        <svg data-eye-closed class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 6.2A11 11 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.1 2.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6a10 10 0 0 0 4.1-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                                    </button>
                                </div>
                            </div>

                            <label class="flex w-fit items-center gap-2.5 text-sm text-slate-600">
                                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                Ingat saya di perangkat ini
                            </label>

                            <button type="submit" class="group flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-600 to-blue-600 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-600/25 focus:outline-none focus:ring-4 focus:ring-blue-500/20 active:translate-y-0">
                                Masuk ke Dashboard
                                <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </button>
                        </form>

                        <div class="mt-7 flex items-center justify-center gap-2 border-t border-slate-100 pt-5 text-xs text-slate-400">
                            <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                            Akses aman dan terlindungi
                        </div>
                    </div>

                    <p class="mt-6 text-center text-xs text-slate-500 lg:hidden">&copy; {{ date('Y') }} Bridging Monitor</p>
                </div>
            </section>
        </div>
    </main>

    <script>
        (() => {
            const button = document.querySelector('[data-password-toggle]');
            const input = document.getElementById('password');
            const openIcon = button?.querySelector('[data-eye-open]');
            const closedIcon = button?.querySelector('[data-eye-closed]');
            if (!button || !input || !openIcon || !closedIcon) return;

            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', show ? 'true' : 'false');
                button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                openIcon.classList.toggle('hidden', show);
                closedIcon.classList.toggle('hidden', !show);
                input.focus();
            });
        })();
    </script>
</body>
</html>
