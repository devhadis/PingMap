<aside class="tools-sidebar pm-navigation" aria-label="Navegação principal">
    <style>
        .tools-shell {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            min-height: 100vh;
        }

        .tools-sidebar {
            background: #111827;
            border-right: 1px solid #293548;
            padding: 28px 16px;
            min-width: 0;
        }

        .tools-content {
            width: 100%;
            max-width: 1250px;
            padding: 42px;
            min-width: 0;
        }

        .pm-nav-brand {
            display: block;
            color: #38bdf8;
            font-size: 25px;
            font-weight: 800;
            text-decoration: none;
            margin: 0 10px 35px;
        }

        .pm-nav-group {
            color: #94a3b8;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin: 26px 12px 10px;
        }

        .pm-nav-link {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 9px;
            margin: 4px 0;
        }

        .pm-nav-link:hover {
            background: #1e293b;
        }

        .pm-nav-link.active {
            color: #7dd3fc;
            background: #17334e;
            font-weight: bold;
        }

        .pm-nav-link.disabled {
            color: #64748b;
            cursor: not-allowed;
        }

        @media (max-width: 850px) {
            .tools-shell {
                grid-template-columns: 1fr;
            }

            .tools-sidebar {
                border-right: 0;
                border-bottom: 1px solid #293548;
                padding: 16px;
            }

            .pm-nav-brand {
                margin-bottom: 12px;
            }

            .tools-content {
                padding: 22px 14px;
            }
        }
    </style>

    <a class="pm-nav-brand" href="{{ route('dashboard') }}">
        ◈ PingMap
    </a>

    <div class="pm-nav-group">VISÃO GERAL</div>

    <a
        class="pm-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
        href="{{ route('dashboard') }}"
        @if(request()->routeIs('dashboard')) aria-current="page" @endif
    >
        ◫ Dashboard / Ping
    </a>

    <div class="pm-nav-group">CONECTIVIDADE</div>

    <a
        class="pm-nav-link {{ request()->routeIs('ferramentas.dns') ? 'active' : '' }}"
        href="{{ route('ferramentas.dns') }}"
        @if(request()->routeIs('ferramentas.dns')) aria-current="page" @endif
    >
        ◉ DNS Lookup
    </a>

    <div class="pm-nav-group">EM DESENVOLVIMENTO</div>

    <span class="pm-nav-link disabled">TCP / Port Check</span>
    <span class="pm-nav-link disabled">MTR / Traceroute</span>
    <span class="pm-nav-link disabled">BGP Lookup</span>
    <span class="pm-nav-link disabled">HTTP / TLS</span>
    <span class="pm-nav-link disabled">Monitoramento</span>
</aside>
