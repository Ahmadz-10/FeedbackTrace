<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101b2e">
    <title>@yield('page-title', 'Dashboard') · FeedbackTrace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ft-app">
<div class="ft-app-shell">
    <div class="ft-sidebar-backdrop" data-sidebar-close></div>
    <aside class="ft-sidebar" id="ft-sidebar">
        <a href="{{ route('dashboard.overview') }}" class="ft-brand" aria-label="FeedbackTrace home">
            <span class="ft-brand-symbol">F<span>.</span></span>
            <span class="ft-brand-name"><strong>FeedbackTrace</strong><small>UNIVERSITY INSIGHTS</small></span>
        </a>

        <div class="ft-workspace-switcher">
            <span class="ft-workspace-avatar">NU</span>
            <span class="ft-workspace-copy"><strong>Northbridge University</strong><small>Institution workspace</small></span>
            <span class="ft-chevron">⌄</span>
        </div>

        <div class="ft-nav-section">
            <div class="ft-nav-heading">WORKSPACE</div>
            <a class="ft-nav-link {{ request()->routeIs('dashboard.overview') ? 'is-active' : '' }}" href="{{ route('dashboard.overview') }}">
                <span class="ft-nav-icon">▦</span><span>Dashboard</span>
            </a>
            <a class="ft-nav-link {{ request()->routeIs('dashboard.feedback') ? 'is-active' : '' }}" href="{{ route('dashboard.feedback') }}">
                <span class="ft-nav-icon">▤</span><span>Feedback</span><span class="ft-nav-badge">2.4k</span>
            </a>
        </div>

        <div class="ft-nav-section">
            <div class="ft-nav-heading">FEEDBACK INTELLIGENCE</div>
            <a class="ft-nav-link {{ request()->routeIs('dashboard.task-a') ? 'is-active' : '' }}" href="{{ route('dashboard.task-a') }}">
                <span class="ft-nav-icon">◉</span><span>Task A Analysis</span>
            </a>
        </div>

        <div class="ft-nav-section">
            <div class="ft-nav-heading">SERVICE QUALITY</div>
            <a class="ft-nav-link {{ request()->routeIs('dashboard.task-b') ? 'is-active' : '' }}" href="{{ route('dashboard.task-b') }}">
                <span class="ft-nav-icon">⌘</span><span>Task B Analysis</span><span class="ft-nav-new">NEW</span>
            </a>
            <a class="ft-nav-link {{ request()->routeIs('dashboard.documents') ? 'is-active' : '' }}" href="{{ route('dashboard.documents') }}">
                <span class="ft-nav-icon">▧</span><span>Documents</span>
            </a>
            <a class="ft-nav-link {{ request()->routeIs('dashboard.recommendations') ? 'is-active' : '' }}" href="{{ route('dashboard.recommendations') }}">
                <span class="ft-nav-icon">✳</span><span>Recommendations</span><span class="ft-nav-count">12</span>
            </a>
        </div>

        <div class="ft-sidebar-spacer"></div>
        <div class="ft-demo-card">
            <div class="ft-demo-card-head"><span class="ft-demo-indicator"></span><strong>Demo workspace</strong></div>
            <p>Sample data is displayed. No AI service or database connection is required.</p>
            <span class="ft-demo-label">UI PREVIEW MODE</span>
        </div>
        <div class="ft-sidebar-footer">
            <div class="ft-user-avatar">AD</div>
            <div class="ft-user-copy"><strong>Admin Demo</strong><small>University staff</small></div>
            <button class="ft-icon-button ft-user-menu" type="button" aria-label="User menu" data-toast="Demo profile menu">···</button>
        </div>
    </aside>

    <main class="ft-main">
        <header class="ft-topbar">
            <div class="ft-topbar-left">
                <button class="ft-icon-button ft-menu-toggle" type="button" aria-label="Open navigation" data-sidebar-toggle>☰</button>
                <div class="ft-breadcrumb"><span>FeedbackTrace</span><i>/</i><strong>@yield('breadcrumb', 'Dashboard')</strong></div>
            </div>
            <div class="ft-topbar-actions">
                <label class="ft-global-search">
                    <span>⌕</span><input type="search" placeholder="Search workspace..." aria-label="Search workspace" data-global-search>
                    <kbd>⌘ K</kbd>
                </label>
                <span class="ft-topbar-divider"></span>
                <div class="ft-demo-chip"><span></span> DEMO DATA</div>
                <button class="ft-icon-button ft-notification-button" type="button" aria-label="Notifications" data-toast="You are all caught up.">
                    ♧<span class="ft-notification-dot"></span>
                </button>
                <div class="ft-topbar-avatar">AD</div>
            </div>
        </header>

        <div class="ft-page-wrap">
            @yield('content')
            <footer class="ft-page-footer">
                <span>FeedbackTrace <i>·</i> University service intelligence</span>
                <span>Interactive UI preview <i>·</i> Sample data only</span>
            </footer>
        </div>
    </main>
</div>
<div class="ft-toast-region" aria-live="polite" aria-atomic="true" data-toast-region></div>
</body>
</html>
