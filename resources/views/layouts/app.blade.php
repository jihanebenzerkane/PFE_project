<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFE Admin — @yield('title', 'Tableau de Bord')</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #0F172A;
            --accent: #3B82F6;
            --accent2: #6366F1;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --bg: #F1F5F9;
            --text: #334155;
            --text-light: #64748B;
            --card-bg: #FFFFFF;
            --border: #E2E8F0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-header {
            padding: 22px 20px;
            font-size: 1.2rem;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            background: rgba(0, 0, 0, 0.25);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header .logo-badge {
            width: 36px;
            height: 36px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .sidebar-nav {
            padding: 14px 0;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-label {
            margin: 18px 20px 6px;
            font-size: 0.67rem;
            color: #475569;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .nav-item {
            padding: 11px 20px;
            color: #94A3B8;
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #E2E8F0;
        }

        .nav-item.active {
            background: rgba(59, 130, 246, 0.15);
            color: white;
            border-left-color: var(--accent);
        }

        .nav-item .icon {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.75rem;
            color: #475569;
        }

        /* ── MAIN ── */
        .main {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            height: 62px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--primary);
        }

        .topbar-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .content {
            padding: 28px;
            flex: 1;
        }

        /* ── ALERTS ── */
        .alert {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .alert-info {
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }

        /* ── BUTTONS ── */
        .btn {
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.85rem;
            transition: all 0.15s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: #2563EB;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .btn-outline {
            background: white;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            border-color: #94A3B8;
        }

        .btn-outline-red {
            background: white;
            color: var(--danger);
            border: 1.5px solid var(--danger);
        }

        .btn-outline-red:hover {
            background: #FEF2F2;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* ── CARDS ── */
        .card {
            background: var(--card-bg);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            padding: 24px;
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
        }
    </style>
    @stack('styles')
</head>

<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-badge">🎓</div>
            PFE.Admin
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Vue d'ensemble</div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="icon">📊</span> Tableau de bord
            </a>

            <div class="nav-label">Flux principal</div>
            <a href="{{ route('import.form') }}" class="nav-item {{ request()->routeIs('import.*') ? 'active' : '' }}">
                <span class="icon">📥</span> Importation Excel
            </a>
            <a href="{{ route('affectation.index') }}"
                class="nav-item {{ request()->routeIs('affectation.*') ? 'active' : '' }}">
                <span class="icon">👥</span> Affectation Encadrants
            </a>
            <a href="{{ route('planning.results') }}"
                class="nav-item {{ request()->routeIs('planning.*') ? 'active' : '' }}">
                <span class="icon">📅</span> Planning Soutenances
            </a>
            <a href="{{ route('verification.index') }}"
                class="nav-item {{ request()->routeIs('verification.*') ? 'active' : '' }}">
                <span class="icon">🛡️</span> Contrôle de Conformité
            </a>
            <a href="{{ route('pv.index') }}" class="nav-item {{ request()->routeIs('pv.*') ? 'active' : '' }}">
                <span class="icon">📝</span> Génération des PVs
            </a>


        </nav>
        <div class="sidebar-footer">Année Universitaire 2024/2025</div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">@yield('title', 'Tableau de bord')</div>
            <div class="topbar-actions">@yield('topbar-actions')</div>
        </div>
        <div class="content">
            @if (session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">❌ {{ session('error') }}</div>
            @endif
            @if (session('info'))
                <div class="alert alert-info">ℹ️ {{ session('info') }}</div>
            @endif
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>

</html>
