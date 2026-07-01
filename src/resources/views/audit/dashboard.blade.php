<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Dashboard - Document Tracking System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <link href="https://api.fontshare.com/css?f[]=satoshi@400,500,600,700,900&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: #10B981;
            --accent-soft: rgba(16, 185, 129, 0.08);
            --canvas: #F9FAFB;
            --surface: #FFFFFF;
            --ink: #18181B;
            --steel: #71717A;
            --muted: #94A3B8;
            --whisper: rgba(226, 232, 240, 0.5);
            --shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
            --radius-card: 2.5rem;
            --radius-badge: 0.375rem;
            --font-display: 'Satoshi', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--canvas);
            font-family: var(--font-display);
            color: var(--ink);
        }

        .audit-header {
            margin-bottom: 2rem;
        }

        .audit-header h1 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }

        .audit-header p {
            font-size: 0.9375rem;
            color: var(--steel);
            max-width: 65ch;
            margin-bottom: 0;
        }

        .hero-card {
            background: var(--surface);
            border: 1px solid var(--whisper);
            border-radius: 2.5rem;
            box-shadow: var(--shadow);
            padding: 3rem 3rem;
            margin-bottom: 2rem;
        }

        .hero-card h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.1;
            color: var(--ink);
            margin-bottom: 1rem;
        }

        .hero-card .hero-sub {
            font-size: 1rem;
            color: var(--steel);
            max-width: 55ch;
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .hero-card .hero-visual {
            width: 5rem;
            height: 5rem;
            border-radius: 1.5rem;
            background: var(--accent-soft);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }

        .quick-card {
            background: var(--surface);
            border: 1px solid var(--whisper);
            border-radius: 2rem;
            box-shadow: var(--shadow);
            padding: 1.75rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            display: block;
            color: var(--ink);
        }

        .quick-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 48px -18px rgba(0, 0, 0, 0.08);
            color: var(--ink);
        }

        .quick-card .quick-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .quick-card .quick-icon.emerald {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .quick-card .quick-icon.blue {
            background: rgba(59, 130, 246, 0.1);
            color: #2563EB;
        }

        .quick-card .quick-icon.amber {
            background: rgba(245, 158, 11, 0.1);
            color: #D97706;
        }

        .quick-card h6 {
            font-weight: 600;
            font-size: 0.9375rem;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .quick-card p {
            font-size: 0.8125rem;
            color: var(--steel);
            margin-bottom: 0;
        }

        .btn-accent {
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 2rem;
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 0.9375rem;
            letter-spacing: -0.01em;
            transition: background 0.15s ease, transform 0.15s ease;
        }

        .btn-accent:hover {
            background: #059669;
            color: #fff;
            transform: scale(0.98);
        }

        .btn-accent i {
            margin-right: 0.5rem;
        }

        @media (max-width: 767.98px) {
            .hero-card {
                padding: 2rem 1.5rem;
            }

            .hero-card .hero-visual {
                margin-top: 1.5rem;
            }
        }
    </style>
</head>
<body>
    @include('partials.sidebar-nav')

    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0">Audit Portal</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <button class="btn btn-link position-relative">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">0</span>
                    </button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <h6 class="profile-name mb-1">{{ auth()->user()->name }}</h6>
                            <p class="profile-department small text-muted mb-1">{{ auth()->user()->department->name ?? 'No Department Assigned' }}</p>
                            <span class="profile-role-badge badge bg-primary">{{ auth()->user()->role->name ?? 'Standard User' }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="audit-header">
                <h1>Audit Dashboard</h1>
                <p>Cross-departmental visibility into the entire document lifecycle. All metrics are read-only and computed in real time.</p>
            </div>

            <div class="hero-card">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2>Welcome to UCDTS</h2>
                        <p class="hero-sub">
                            The University Document Tracking System provides complete visibility into document routing,
                            receipt confirmation, and archival across all departments. Use the directory below to search,
                            filter, and inspect any document in the system.
                        </p>
                        <a href="{{ route('audit.documents') }}" class="btn btn-accent">
                            <i class="bi bi-file-earmark-text"></i> Browse Documents
                        </a>
                    </div>
                    <div class="col-lg-4 text-center text-lg-end">
                        <div class="hero-visual d-inline-flex">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('audit.documents') }}" class="quick-card">
                        <div class="quick-icon emerald">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h6>Documents Directory</h6>
                        <p>Search and inspect all registered documents across the system.</p>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('audit.documents') }}?status=pending_transfer" class="quick-card">
                        <div class="quick-icon amber">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h6>Pending Transfers</h6>
                        <p>View documents awaiting transfer between departments.</p>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('audit.documents') }}?status=in_transit" class="quick-card">
                        <div class="quick-icon blue">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <h6>In Transit</h6>
                        <p>Monitor documents currently in transit between locations.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.auth-context')
</body>
</html>
