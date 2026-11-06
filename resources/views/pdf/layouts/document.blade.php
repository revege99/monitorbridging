<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen PDF')</title>
    <style>
        @page { margin: 10mm 12mm; }
        body { margin: 0; color: #111; font-family: Helvetica, Arial, sans-serif; font-size: 9pt; line-height: 1.2; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; overflow-wrap: break-word; }
        .keep-together { page-break-inside: avoid; }
        @yield('styles')
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
