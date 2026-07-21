<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Login - Bridging Monitor</title>@fonts<link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body class="min-h-screen bg-[#071325] text-slate-900">
<div class="grid min-h-screen place-items-center bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,.22),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,.2),_transparent_35%)] p-5">
    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white p-8 shadow-2xl">
        <div class="mb-7"><div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-600">✚</div><h1 class="text-2xl font-semibold">Bridging Monitor</h1><p class="mt-1 text-sm text-slate-500">Masuk untuk mengakses monitoring klinik.</p></div>
        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">@csrf
            <label class="block text-sm font-medium">Username atau Email<input name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" class="mt-1.5 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-cyan-400"></label>
            <label class="block text-sm font-medium">Password<input name="password" type="password" required class="mt-1.5 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-cyan-400"></label>
            @error('login')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button class="w-full rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700">Masuk</button>
        </form>
    </div>
</div></body></html>
