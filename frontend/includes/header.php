<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($page_title ?? 'SikaTrack · Expense Tracker GHS', ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    >

    <!-- Fonts -->
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
            --surface: #f6faf7;
        }
        * { -webkit-font-smoothing: antialiased; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--surface);
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5 { letter-spacing: -0.03em; }
        .fw-extrabold { font-weight: 800; }

        /* --- Navbar --- */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: saturate(180%) blur(14px);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
            border-bottom: 1px solid rgba(10, 92, 54, 0.07);
            z-index: 1030;
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
        }
        .navbar-brand-custom {
            font-weight: 800;
            font-size: 1.25rem;
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
            flex-shrink: 0;
        }

        /* --- Nav links --- */
        .navbar-custom .nav-link {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--ink) !important;
            opacity: 0.72;
            padding: 0.55rem 0.9rem;
            border-radius: 10px;
            transition: opacity 0.15s, color 0.15s, background 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .navbar-custom .nav-link:hover {
            opacity: 1;
            color: var(--brand) !important;
            background: #f0f6f2;
        }
        .navbar-custom .nav-link.active {
            opacity: 1;
            color: var(--brand) !important;
            background: #e8f3ec;
            font-weight: 700;
        }
        .navbar-custom .nav-link i {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* --- User chip --- */
        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.35rem 0.85rem 0.35rem 0.35rem;
            background: #f4f8f6;
            border: 1px solid #e6ede9;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ink);
            transition: background 0.15s, border-color 0.15s;
        }
        .user-chip:hover {
            background: #eef5f1;
            border-color: #d7e6dd;
        }
        .user-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: linear-gradient(140deg, var(--brand), var(--brand-light));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 0;
            flex-shrink: 0;
        }
        .user-chip .user-name {
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* --- Logout button --- */
        .btn-logout {
            border: 1.5px solid #e2e8e4;
            background: #ffffff;
            color: #4d5d54;
            border-radius: 11px;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.45rem 0.95rem;
            transition: all 0.18s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-logout:hover {
            border-color: #fecbc6;
            background: #fff5f4;
            color: #b3261e;
        }

        /* --- Navbar toggler (mobile) --- */
        .navbar-custom .navbar-toggler {
            border: 1.5px solid #e2e8e4;
            border-radius: 10px;
            padding: 0.45rem 0.6rem;
            transition: background 0.15s;
        }
        .navbar-custom .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(10, 92, 54, 0.12);
        }
        .navbar-custom .navbar-toggler:hover {
            background: #f0f6f2;
        }

        /* --- Mobile dropdown panel --- */
        @media (max-width: 991.98px) {
            .navbar-custom .navbar-collapse {
                background: #ffffff;
                margin-top: 0.75rem;
                padding: 1rem;
                border-radius: 14px;
                border: 1px solid #eef2ef;
                box-shadow: 0 20px 40px -20px rgba(11, 27, 19, 0.20);
            }
            .navbar-custom .navbar-nav {
                gap: 0.15rem;
            }
            .navbar-custom .nav-link {
                padding: 0.7rem 0.9rem;
                font-size: 0.92rem;
            }
            .navbar-custom .navbar-nav.me-auto {
                margin-bottom: 0.85rem;
            }
            .user-chip {
                width: 100%;
                padding: 0.5rem 0.9rem;
            }
            .user-chip .user-name { max-width: none; }
            .btn-logout { width: 100%; justify-content: center; padding: 0.6rem 0.95rem; }
        }

        /* --- Content wrapper --- */
        .content-wrapper {
            padding-top: 1.5rem;
            padding-bottom: 3rem;
        }

        /* --- Generic content helpers (available to child pages) --- */
        .card {
            border: 1px solid #ecf1ee;
            border-radius: 18px;
        }
        .card-header {
            border-bottom: 1px solid #eef2ef;
            background: transparent;
            font-weight: 700;
        }
        .btn-primary {
            background: var(--brand);
            border-color: var(--brand);
        }
        .btn-primary:hover, .btn-primary:focus {
            background: var(--brand-light);
            border-color: var(--brand-light);
        }
    </style>
</head>

<body>

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">

            <!-- Brand -->
            <a class="navbar-brand-custom" href="dashboard.php">
                <span class="brand-dot"><i class="bi bi-wallet2"></i></span>
                SikaTrack<span style="color: var(--brand);">.</span>
            </a>

            <!-- Mobile Menu Toggle -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ms-lg-4 mb-2 mb-lg-0 gap-lg-1">

                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="bi bi-arrow-left-right"></i>
                            Transactions
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="budgets.php">
                            <i class="bi bi-pie-chart"></i>
                            Budgets
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="savings.php">
                            <i class="bi bi-piggy-bank"></i>
                            Savings
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="accounts.php">
                            <i class="bi bi-wallet2"></i>
                            Accounts &amp; MoMo
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-tags"></i>
                            Categories
                        </a>
                    </li>

                </ul>

                <!-- User Information and Logout -->
                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2">

                    <?php
                        $__fullname = $_SESSION['fullname'] ?? 'User';
                        $__initial = strtoupper(mb_substr(trim($__fullname), 0, 1, 'UTF-8'));
                        if ($__initial === '') { $__initial = 'U'; }
                    ?>

                    <span class="user-chip" title="<?= htmlspecialchars($__fullname, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="user-avatar"><?= htmlspecialchars($__initial, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="user-name">
                            Hi, <?= htmlspecialchars($__fullname, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </span>

                    <a href="logout.php" class="btn btn-logout">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>

                </div>
            </div>
        </div>
    </nav>

    <!-- ================= MAIN CONTENT WRAPPER ================= -->
    <div class="container content-wrapper mb-5">