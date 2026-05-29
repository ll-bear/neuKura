{{-- resources/views/layouts/terminal.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>webPlot</title>

    {{-- VT323：ターミナルフォント --}}
    <link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="bg-[#050a03] min-h-screen flex items-center justify-center p-5">

    {{-- CRTスキャンラインオーバーレイ --}}
    <div class="crt-overlay"></div>

    {{-- ヴィネット --}}
    <div class="crt-vignette"></div>

    @yield('content')

</body>
</html>