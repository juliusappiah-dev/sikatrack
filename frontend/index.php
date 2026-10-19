<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Manager & Budget Planner | SikaTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0a5c36;
            --brand-light: #0f7a47;
            --brand-dark: #06452a;
            --accent: #f5b700;
            --ink: #0b1b13;
            --muted: #5b6b62;
        }
        * { -webkit-font-smoothing: antialiased; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: #ffffff;
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5 { letter-spacing: -0.03em; }
        .fw-extrabold { font-weight: 800; }

        /* --- Navbar --- */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: saturate(180%) blur(14px);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
            border-bottom: 1px solid rgba(10, 92, 54, 0.06);
            z-index: 1030;
        }
        .navbar-brand-custom {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--ink);
            letter-spacing: -0.04em;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
        }
        .navbar-brand-custom:hover { color: var(--ink); }
        .brand-dot {
            width: 34px; height: 34px;
            border-radius: 11px;
            background: linear-gradient(140deg, var(--brand), var(--brand-light));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 8px 14px -6px rgba(10, 92, 54, 0.45);
        }
        .nav-link-custom {
            font-weight: 600;
            font-size: 0.93rem;
            color: var(--ink) !important;
            opacity: 0.75;
            transition: opacity 0.15s, color 0.15s;
        }
        .nav-link-custom:hover { opacity: 1; color: var(--brand) !important; }

        .btn-brand {
            background: var(--brand);
            border: none;
            color: #fff;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 12px 20px -10px rgba(10, 92, 54, 0.55);
        }
        .btn-brand:hover {
            background: var(--brand-light);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 18px 26px -12px rgba(10, 92, 54, 0.65);
        }
        .btn-ghost {
            border: 1.5px solid #dfe7e2;
            background: transparent;
            color: var(--ink);
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-ghost:hover {
            background: #f4f8f6;
            border-color: var(--brand);
            color: var(--brand);
        }
        .btn-accent {
            background: var(--accent);
            border: none;
            color: var(--ink);
            border-radius: 12px;
            font-weight: 800;
            transition: all 0.2s;
            box-shadow: 0 14px 24px -10px rgba(245, 183, 0, 0.6);
        }
        .btn-accent:hover {
            background: #ffc82e;
            color: var(--ink);
            transform: translateY(-1px);
            box-shadow: 0 20px 30px -12px rgba(245, 183, 0, 0.7);
        }

        /* --- Hero --- */
        .hero {
            position: relative;
            padding: 6.5rem 0 8.5rem;
            background:
                radial-gradient(1200px 600px at 88% -10%, rgba(245, 183, 0, 0.16), transparent 60%),
                radial-gradient(900px 500px at 0% 110%, rgba(10, 92, 54, 0.10), transparent 60%),
                linear-gradient(180deg, #ffffff 0%, #f6faf7 100%);
            overflow: hidden;
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(10, 92, 54, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(10, 92, 54, 0.035) 1px, transparent 1px);
            background-size: 46px 46px;
            -webkit-mask-image: radial-gradient(circle at 50% 20%, #000 0%, transparent 72%);
            mask-image: radial-gradient(circle at 50% 20%, #000 0%, transparent 72%);
            pointer-events: none;
        }
        .hero .container { position: relative; z-index: 2; }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #fff;
            border: 1px solid #e4ede8;
            color: var(--brand);
            font-weight: 700;
            font-size: 0.78rem;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            box-shadow: 0 6px 18px -10px rgba(10, 92, 54, 0.4);
        }
        .hero h1 {
            font-size: clamp(2.15rem, 4.6vw, 3.6rem);
            line-height: 1.06;
            font-weight: 800;
        }
        .hero h1 .highlight {
            position: relative;
            color: var(--brand);
            white-space: nowrap;
        }
        .hero h1 .highlight::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0.06em;
            height: 0.32em;
            background: rgba(245, 183, 0, 0.42);
            border-radius: 4px;
            z-index: -1;
        }
        .hero .lead {
            font-size: 1.1rem;
            color: var(--muted);
            max-width: 620px;
            line-height: 1.65;
        }

        /* --- Floating preview card --- */
        .preview-wrap { position: relative; z-index: 2; }
        .preview-card {
            background: #ffffff;
            border-radius: 22px;
            border: 1px solid #eaf0ec;
            box-shadow: 0 40px 80px -30px rgba(11, 27, 19, 0.28), 0 8px 22px -12px rgba(11, 27, 19, 0.12);
            padding: 1.5rem;
        }
        .preview-chip {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .balance-amount {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: var(--ink);
            line-height: 1;
        }
        .mini-bar {
            height: 8px;
            border-radius: 99px;
            background: #eef3f0;
            overflow: hidden;
        }
        .mini-bar > span {
            display: block;
            height: 100%;
            border-radius: 99px;
        }
        .pill-soft {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.3rem 0.7rem;
            border-radius: 999px;
        }

        /* --- Stats strip --- */
        .stats-strip {
            position: relative;
            z-index: 3;
            margin-top: -3.6rem;
        }
        .stat-tile {
            background: #ffffff;
            border: 1px solid #ecf1ee;
            border-radius: 18px;
            padding: 1.35rem 1rem;
            text-align: center;
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 40px -22px rgba(11, 27, 19, 0.25);
        }
        .stat-tile .stat-num {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }
        .stat-tile .stat-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--muted);
            margin-top: 0.2rem;
        }

        /* --- Section headings --- */
        .section-kicker {
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--brand);
        }
        .section-title {
            font-size: clamp(1.7rem, 3.2vw, 2.5rem);
            font-weight: 800;
            line-height: 1.15;
        }
        .section-sub {
            color: var(--muted);
            max-width: 620px;
        }

        /* --- Feature cards --- */
        .feature-card {
            background: #ffffff;
            border: 1px solid #ecf1ee;
            border-radius: 20px;
            padding: 1.7rem;
            height: 100%;
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            border-color: #d7e6dd;
            box-shadow: 0 30px 50px -26px rgba(11, 27, 19, 0.28);
        }
        .feature-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 1.1rem;
        }
        .feature-card h5 {
            font-weight: 800;
            font-size: 1.05rem;
            margin-bottom: 0.55rem;
        }
        .feature-card p {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.6;
            margin: 0;
        }

        /* --- Deep dive panels --- */
        .panel {
            border-radius: 26px;
            padding: 2.4rem;
            border: 1px solid #ecf1ee;
            background: #ffffff;
        }
        .panel-momo { background: linear-gradient(150deg, #fffdf4 0%, #ffffff 55%); }
        .panel-susu { background: linear-gradient(150deg, #f3faf6 0%, #ffffff 55%); }
        .check-item {
            display: flex;
            gap: 0.7rem;
            align-items: flex-start;
            font-size: 0.92rem;
            color: var(--ink);
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        .check-item i { color: var(--brand); font-size: 1rem; margin-top: 0.12rem; }

        /* --- CTA band --- */
        .cta-band {
            border-radius: 28px;
            background:
                radial-gradient(900px 380px at 100% 0%, rgba(245, 183, 0, 0.18), transparent 60%),
                linear-gradient(135deg, var(--brand-dark) 0%, var(--brand) 100%);
            color: #fff;
            padding: 3.6rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .cta-band::after {
            content: "";
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            border: 60px solid rgba(255,255,255,0.05);
            right: -140px; bottom: -200px;
            pointer-events: none;
        }
        .cta-band > * { position: relative; z-index: 2; }

        /* --- Footer --- */
        .site-footer {
            background: #0b1b13;
            color: #b7c4bc;
            padding: 3.5rem 0 2rem;
            font-size: 0.9rem;
        }
        .site-footer h6 {
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        .site-footer a {
            color: #b7c4bc;
            text-decoration: none;
            transition: color 0.2s;
        }
        .site-footer a:hover { color: var(--accent); }
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            color: #ffffff;
            font-weight: 800;
            font-size: 1.2rem;
            margin-bottom: 0.85rem;
            letter-spacing: -0.03em;
        }
        .footer-brand .brand-dot {
            box-shadow: 0 8px 14px -6px rgba(0, 0, 0, 0.5);
        }
        .footer-list { list-style: none; padding: 0; margin: 0; }
        .footer-list li { margin-bottom: 0.6rem; }
        .social-btn {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.06);
            color: #ffffff;
            transition: background 0.2s, transform 0.2s;
        }
        .social-btn:hover {
            background: var(--brand);
            color: #fff;
            transform: translateY(-2px);
        }
        .footer-divider {
            border-color: rgba(255,255,255,0.08);
            margin: 2.5rem 0 1.5rem;
        }
        .footer-bottom {
            font-size: 0.82rem;
            color: #8a9a92;
        }

        @media (max-width: 991.98px) {
            .hero { padding: 4.2rem 0 6.5rem; }
            .preview-wrap { margin-top: 2.5rem; }
            .navbar-collapse {
                background: #ffffff;
                margin-top: 0.75rem;
                padding: 1rem;
                border-radius: 14px;
                border: 1px solid #eef2ef;
                box-shadow: 0 20px 40px -20px rgba(11, 27, 19, 0.2);
            }
        }
        @media (max-width: 575.98px) {
            .hero h1 { font-size: 2.05rem; }
            .stat-tile .stat-num { font-size: 1.3rem; }
            .panel { padding: 1.6rem; }
            .cta-band { padding: 2.4rem 1.4rem; }
        }
    </style>
</head>
<body>

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-expand-lg navbar-custom py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand-custom" href="index.php">
                <span class="brand-dot"><i class="bi bi-wallet2"></i></span>
                SikaTrack<span style="color:var(--brand);">.</span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav" aria-controls="landingNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="landingNav">
                <ul class="navbar-nav mx-auto mb-3 mb-lg-0 gap-lg-4">
                    <li class="nav-item"><a class="nav-link nav-link-custom" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link nav-link-custom" href="#momo">Mobile Money</a></li>
                    <li class="nav-item"><a class="nav-link nav-link-custom" href="#susu">Savings &amp; Susu</a></li>
                </ul>
                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                    <a href="login.php" class="btn btn-ghost px-4 py-2">Log In</a>
                    <a href="register.php" class="btn btn-brand px-4 py-2">
                        Get Started Free <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ================= HERO ================= -->
    <header class="hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 text-center text-lg-start">
                    <span class="hero-badge mb-3">
                        <i class="bi bi-geo-alt-fill"></i> Built for Ghana · MoMo · GHS · Susu
                    </span>
                    <h1 class="mb-3">
                        Own your money.<br>
                        <span class="highlight">Grow it smarter</span><br>
                        the Ghanaian way.
                    </h1>
                    <p class="lead mb-4 mx-auto mx-lg-0">
                        Track MTN MoMo, Telecel Cash, bank accounts and physical cash in one clean dashboard. Set budgets, hit your Susu goals, and see exactly where every cedi goes.
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-2 gap-sm-3">
                        <a href="register.php" class="btn btn-accent btn-lg px-4 py-3">
                            Start Tracking Free <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                        <a href="login.php" class="btn btn-ghost btn-lg px-4 py-3">
                            I already have an account
                        </a>
                    </div>
                    <p class="small text-muted mt-3 mb-0">
                        <i class="bi bi-shield-check me-1" style="color:var(--brand);"></i>
                        No credit card. Free forever. Your data stays private.
                    </p>
                </div>

                <!-- Floating preview card (pure decoration) -->
                <div class="col-lg-6 preview-wrap">
                    <div class="preview-card mx-auto" style="max-width: 440px;">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="preview-chip">Total balance</div>
                                <div class="balance-amount mt-1">GHS 8,420<span style="font-size:1.1rem; opacity:.6;">.50</span></div>
                            </div>
                            <span class="pill-soft" style="background:#e8f7ef; color:var(--brand);">
                                <i class="bi bi-arrow-up-right"></i> +12.4%
                            </span>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div style="background:#f6faf7; border-radius:12px; padding:.7rem .85rem;">
                                    <div class="preview-chip" style="font-size:.62rem;">MoMo</div>
                                    <div class="fw-bold" style="font-size:.98rem;">GHS 4,120.00</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="background:#fdfaf0; border-radius:12px; padding:.7rem .85rem;">
                                    <div class="preview-chip" style="font-size:.62rem;">Cash</div>
                                    <div class="fw-bold" style="font-size:.98rem;">GHS 1,300.50</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold" style="font-size:.82rem;">Food &amp; Groceries</span>
                            <span class="text-muted" style="font-size:.78rem;">GHS 620 / 800</span>
                        </div>
                        <div class="mini-bar mb-3"><span style="width:77%; background:linear-gradient(90deg,var(--brand),#37b06f);"></span></div>

                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold" style="font-size:.82rem;">Transport (Trotro)</span>
                            <span class="text-muted" style="font-size:.78rem;">GHS 210 / 300</span>
                        </div>
                        <div class="mini-bar mb-3"><span style="width:70%; background:linear-gradient(90deg,#e0a800,#f5b700);"></span></div>

                        <div class="d-flex align-items-center gap-2 pt-2 border-top" style="border-color:#eef2ef !important;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:#e8f7ef;color:var(--brand);">
                                <i class="bi bi-piggy-bank"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:.82rem;">Susu goal · Rent</div>
                                <div class="text-muted" style="font-size:.74rem;">GHS 1,800 of 3,000 saved</div>
                            </div>
                            <div class="ms-auto fw-bold" style="color:var(--brand); font-size:.82rem;">60%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ================= STATS STRIP ================= -->
    <div class="container stats-strip">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-tile">
                    <div class="stat-num" style="color:var(--brand);">100%</div>
                    <div class="stat-label">Ghana-friendly (GHS)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-tile">
                    <div class="stat-num" style="color:#0f7a47;">MoMo</div>
                    <div class="stat-label">MTN · Telecel · AT</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-tile">
                    <div class="stat-num" style="color:#b58800;">Budgets</div>
                    <div class="stat-label">Category limits</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-tile">
                    <div class="stat-num" style="color:#c0392b;">Susu</div>
                    <div class="stat-label">Savings progress</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= FEATURES ================= -->
    <section id="features" class="py-5 my-4 my-lg-5">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-kicker mb-2">Features</div>
                <h2 class="section-title mb-3">Everything you need to master your finances</h2>
                <p class="section-sub mx-auto">No complicated setup. Built around the way money actually moves in Ghana — from MoMo transfers to daily cash.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:#e8f7ef; color:var(--brand);"><i class="bi bi-phone"></i></div>
                        <h5>MoMo &amp; multi-wallet sync</h5>
                        <p>Keep physical cash in hand alongside your digital wallets — MTN Mobile Money, Telecel Cash, and bank accounts, all in one place.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:#fff6dd; color:#b58800;"><i class="bi bi-pie-chart"></i></div>
                        <h5>Category spending limits</h5>
                        <p>Set monthly budgets for food, trotro fare, utilities (ECG/Water), airtime, and tithes. Get clear visual alerts before you overspend.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:#e8f7ef; color:#0f7a47;"><i class="bi bi-piggy-bank"></i></div>
                        <h5>Savings &amp; Susu goals</h5>
                        <p>Plan for emergency funds, rent advances, or group susu contributions with clean progress bars and goal tracking.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:#fdeceb; color:#c0392b;"><i class="bi bi-receipt"></i></div>
                        <h5>Transaction fees &amp; e-Levy</h5>
                        <p>Record transfer fees and e-levy deductions alongside your payments, so your reported balance matches your real wallet balance.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:#eaf1ff; color:#2563eb;"><i class="bi bi-bar-chart-line"></i></div>
                        <h5>Visual monthly reports</h5>
                        <p>Understand where your money goes every month with dynamic, interactive charts that make spending patterns obvious.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:#eef2ef; color:#0b1b13;"><i class="bi bi-shield-check"></i></div>
                        <h5>Secure &amp; private</h5>
                        <p>Your financial data is protected with BCRYPT password hashing and prepared SQL statements. Privacy is built in.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= DEEP DIVE: MOMO ================= -->
    <section id="momo" class="pb-4 pb-lg-5">
        <div class="container">
            <div class="panel panel-momo">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-lg-6">
                        <div class="section-kicker mb-2">Mobile Money</div>
                        <h3 class="section-title mb-3" style="font-size: clamp(1.5rem, 2.6vw, 2.05rem);">Every MoMo cedi, accounted for</h3>
                        <p class="text-muted mb-4" style="font-size:.95rem; line-height:1.7;">
                            Ghana runs on mobile money. SikaTrack treats your MoMo wallet as a first-class account — not an afterthought.
                        </p>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Separate balances for MTN MoMo, Telecel Cash and AT Money</span></div>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Track transfer fees and e-levy as part of each transaction</span></div>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Reconcile your app balance against your real wallet at a glance</span></div>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Tag transactions by category for instant monthly insight</span></div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-12">
                                <div style="background:#fff; border:1px solid #f0ead6; border-radius:16px; padding:1.1rem 1.25rem;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:#fff6dd;color:#b58800;"><i class="bi bi-phone"></i></span>
                                            <div>
                                                <div class="fw-bold" style="font-size:.9rem;">MoMo Wallet</div>
                                                <div class="text-muted" style="font-size:.76rem;">MTN Mobile Money</div>
                                            </div>
                                        </div>
                                        <div class="fw-bold">GHS 4,120.00</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div style="background:#fff; border:1px solid #eef2ef; border-radius:16px; padding:1.1rem 1.25rem;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:#e8f7ef;color:var(--brand);"><i class="bi bi-bank"></i></span>
                                            <div>
                                                <div class="fw-bold" style="font-size:.9rem;">Bank Account</div>
                                                <div class="text-muted" style="font-size:.76rem;">Ghana Commercial Bank</div>
                                            </div>
                                        </div>
                                        <div class="fw-bold">GHS 3,000.00</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div style="background:#fff; border:1px solid #eef2ef; border-radius:16px; padding:1.1rem 1.25rem;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:#eef2ef;color:#0b1b13;"><i class="bi bi-cash-stack"></i></span>
                                            <div>
                                                <div class="fw-bold" style="font-size:.9rem;">Cash in Hand</div>
                                                <div class="text-muted" style="font-size:.76rem;">Physical wallet</div>
                                            </div>
                                        </div>
                                        <div class="fw-bold">GHS 1,300.50</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= DEEP DIVE: SUSU ================= -->
    <section id="susu" class="pb-5">
        <div class="container">
            <div class="panel panel-susu">
                <div class="row align-items-center g-4 g-lg-5 flex-lg-row-reverse">
                    <div class="col-lg-6">
                        <div class="section-kicker mb-2">Savings &amp; Susu</div>
                        <h3 class="section-title mb-3" style="font-size: clamp(1.5rem, 2.6vw, 2.05rem);">Turn good intentions into real savings</h3>
                        <p class="text-muted mb-4" style="font-size:.95rem; line-height:1.7;">
                            Whether you save solo for rent or contribute to a group susu, seeing progress is what keeps you consistent. SikaTrack makes it visible.
                        </p>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Create unlimited savings goals with target dates</span></div>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Track group susu contributions and your personal share</span></div>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Watch progress bars fill up as you get closer to each goal</span></div>
                        <div class="check-item"><i class="bi bi-check2-circle"></i><span>Stay motivated with a clear picture of what you're saving for</span></div>
                    </div>
                    <div class="col-lg-6">
                        <div style="background:#fff; border:1px solid #e6f0ea; border-radius:20px; padding:1.6rem;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <div class="preview-chip">Susu goal</div>
                                    <div class="fw-bold" style="font-size:1.1rem;">Rent advance 2025</div>
                                </div>
                                <span class="pill-soft" style="background:#e8f7ef; color:var(--brand);">On track</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-end my-3">
                                <div class="fw-extrabold" style="font-size:1.6rem; color:var(--brand);">GHS 1,800</div>
                                <div class="text-muted" style="font-size:.82rem;">of GHS 3,000</div>
                            </div>
                            <div class="mini-bar mb-3" style="height:10px;">
                                <span style="width:60%; background:linear-gradient(90deg,var(--brand),#37b06f);"></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size:.78rem;">
                                <span>60% complete</span>
                                <span>GHS 1,200 to go</span>
                            </div>
                            <hr style="border-color:#eef2ef; margin:1.3rem 0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#fff6dd;color:#b58800;"><i class="bi bi-people"></i></span>
                                    <div>
                                        <div class="fw-bold" style="font-size:.86rem;">Group Susu · Osu</div>
                                        <div class="text-muted" style="font-size:.74rem;">This month's contribution</div>
                                    </div>
                                </div>
                                <div class="fw-bold" style="font-size:.9rem;">GHS 200.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= CTA BAND ================= -->
    <section class="pb-5">
        <div class="container">
            <div class="cta-band text-center">
                <div class="section-kicker mb-2" style="color:var(--accent);">Ready when you are</div>
                <h2 class="fw-extrabold mb-3" style="font-size: clamp(1.8rem, 3.4vw, 2.6rem);">Take charge of your GHS expenses today</h2>
                <p class="mb-4 mx-auto" style="max-width: 560px; color: rgba(255,255,255,0.75); font-size: 1rem;">
                    Create your free account in under a minute and start tracking your MoMo, bank and cash flow right away.
                </p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 gap-sm-3">
                    <a href="register.php" class="btn btn-accent btn-lg px-4 py-3">
                        Create Free Account <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-3" style="border-radius:12px; font-weight:700;">
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <span class="brand-dot"><i class="bi bi-wallet2"></i></span>
                        SikaTrack
                    </div>
                    <p style="max-width: 320px; line-height: 1.7; color:#9fb0a7; font-size:.88rem;">
                        The modern expense tracker built for Ghana. Track MoMo, cash, and bank accounts, set budgets, and reach your Susu goals — all in one place.
                    </p>
                    <div class="d-flex gap-2 mt-3">
                        <a href="#" class="social-btn" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-btn" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-btn" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h6>Product</h6>
                    <ul class="footer-list">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#momo">Mobile Money</a></li>
                        <li><a href="#susu">Savings &amp; Susu</a></li>
                        <li><a href="register.php">Get Started</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h6>Company</h6>
                    <ul class="footer-list">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Careers</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h6>Support</h6>
                    <ul class="footer-list">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="login.php">Log In</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h6>Contact</h6>
                    <ul class="footer-list">
                        <li><i class="bi bi-envelope me-1"></i> hello@sikatrack.app</li>
                        <li><i class="bi bi-geo-alt me-1"></i> Accra, Ghana</li>
                        <li><i class="bi bi-clock me-1"></i> Mon–Fri, 9am–5pm</li>
                    </ul>
                </div>
            </div>

            <hr class="footer-divider">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 footer-bottom">
                <div>
                    © <?= date('Y') ?> SikaTrack LLC. All rights reserved.
                </div>
                <div>
                    Built with <i class="bi bi-heart-fill" style="color:#e74c3c;"></i> by NextGen Digital
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS bundle — REQUIRED for the hamburger menu to work -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>