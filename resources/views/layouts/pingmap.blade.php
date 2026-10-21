<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PingMap')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #1e293b;
            padding: 32px;
            border-radius: 16px;
            border: 1px solid #334155;
        }
        h1 { color: #38bdf8; margin-bottom: 8px; }
        p { color: #cbd5e1; }
        label { display: block; margin: 16px 0 6px; }
        input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #64748b;
            background: #0f172a;
            color: white;
        }
        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: 0;
            border-radius: 8px;
            background: #0284c7;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover { background: #0369a1; }
        a { color: #7dd3fc; }
        .error { color: #fca5a5; font-size: 14px; }
        .footer { text-align: center; margin-top: 20px; }

        body.dashboard-page {
            align-items: flex-start;
        }
        body.dashboard-page .card {
            max-width: 1200px;
            padding: 28px;
        }
        @media (max-width: 640px) {
            body.dashboard-page {
                padding: 10px;
            }
            body.dashboard-page .card {
                padding: 16px;
            }
        }
    </style>
    @if(trim($__env->yieldContent('body_class')) === 'tools-page')
        <link rel="stylesheet" href="{{ asset('css/pingmap-tools.css') }}">
    @endif
    @stack('styles')
</head>
<body class="@yield('body_class')">
    <main class="card">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
