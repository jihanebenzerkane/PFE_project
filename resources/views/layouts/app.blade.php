<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0F172A;
            --accent: #3B82F6;
            --bg: #F8FAFC;
            --text: #334155;
            --text-light: #64748B;
            --fati: #EF4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }
        .sidebar-header {
            padding: 22px 20px;
            font-size: 1.3rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-family: 'Outfit', sans-serif;
            background: rgba(0,0,0,0.2);
        }
        .sidebar-nav { padding: 20px 0; flex-grow: 1; }
        .nav-section-title {
            margin: 20px 24px 8px;
            font-size: 0.7rem;
            color: #64748B;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .nav-item {
            padding: 12px 24px;
            color: #94A3B8;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.05);
            color: white;
            border-right: 4px solid var(--accent);
        }
        .main {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: white;
            height: 65px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .navbar-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary);
        }
        .content { padding: 30px; }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-red { background: var(--fati); color: white; }
        .btn-red:hover { background: #DC2626; }
        .btn-outline-red {
            background: white;
            color: var(--fati);
            border: 1px solid var(--fati);
        }
    </style>
    @stack('styles')
</head>
<body>

    {{-- SIDEBAR --}}
    <div class="sidebar">
        <div class="sidebar-header">🎓 PFE.Admin</div>
        <div class="sidebar-nav">
            <div class="nav-section-title">Configuration</div>
            <a href="#" class="nav-item">📥 Importation Excel</a>

            <div class="nav-section-title">Processus Principal</div>
            <a href="#" class="nav-item">📅 Planning Résultats</a>
            <a href="#" class="nav-item">📦 Dossiers PVs</a>

            <div class="nav-section-title">Gestion</div>
            <a href="{{ route('salles.index') }}"
               class="nav-item {{ request()->routeIs('salles.*') ? 'active' : '' }}">
               🏛️ Salles
            </a>
            <a href="{{ route('jurys.index') }}"
               class="nav-item {{ request()->routeIs('jurys.*') ? 'active' : '' }}">
               ⚖️ Jurys
            </a>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="main">
        <div class="navbar">
            <div class="navbar-title">@yield('title')</div>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('export.supervision') }}" class="btn btn-outline-red">
                    📄 PDF Supervision
                </a>
                <a href="{{ route('export.planning') }}" class="btn btn-red">
                    📄 PDF Planning
                </a>
            </div>
        </div>
        <div class="content">
            @yield('content')
        </div>
    </div>

</body>
</html>