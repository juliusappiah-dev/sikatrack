<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($fullname) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $pdo->beginTransaction();

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$fullname, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();

                // Create default Ghana payment accounts for the new user
                $default_accounts = [
                    ['MTN MoMo', 'momo'],
                    ['Telecel Cash', 'momo'],
                    ['Physical Cash', 'cash']
                ];

                $accStmt = $pdo->prepare("INSERT INTO accounts (user_id, account_name, account_type, balance) VALUES (?, ?, ?, 0.00)");
                foreach ($default_accounts as $acc) {
                    $accStmt->execute([$user_id, $acc[0], $acc[1]]);
                }

                $pdo->commit();
                $success = "Registration successful! You can now log in.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | SikaTrack</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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

        /* --- Auth layout --- */
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
            background:
                radial-gradient(1100px 550px at 100% 0%, rgba(245, 183, 0, 0.13), transparent 55%),
                radial-gradient(900px 500px at 0% 100%, rgba(10, 92, 54, 0.10), transparent 55%),
                linear-gradient(180deg, #ffffff 0%, #f6faf7 100%);
            position: relative;
            overflow: hidden;
        }
        .auth-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(10, 92, 54, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(10, 92, 54, 0.035) 1px, transparent 1px);
            background-size: 46px 46px;
            -webkit-mask-image: radial-gradient(circle at 50% 40%, #000 0%, transparent 70%);
            mask-image: radial-gradient(circle at 50% 40%, #000 0%, transparent 70%);
            pointer-events: none;
        }
        .auth-shell {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1040px;
        }
        .auth-card {
            background: #ffffff;
            border: 1px solid #eaf0ec;
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 40px 80px -34px rgba(11, 27, 19, 0.30), 0 10px 26px -14px rgba(11, 27, 19, 0.12);
        }

        /* --- Left branding panel --- */
        .auth-aside {
            background:
                radial-gradient(700px 320px at 100% 0%, rgba(245, 183, 0, 0.20), transparent 60%),
                linear-gradient(155deg, var(--brand-dark) 0%, var(--brand) 100%);
            color: #ffffff;
            padding: 2.75rem 2.5rem;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .auth-aside::after {
            content: "";
            position: absolute;
            width: 380px; height: 380px;
            border-radius: 50%;
            border: 55px solid rgba(255,255,255,0.05);
            right: -150px; bottom: -170px;
            pointer-events: none;
        }
        .auth-aside > * { position: relative; z-index: 2; }
        .aside-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.16);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.74rem;
            padding: 0.45rem 0.9rem;
            border-radius: 999px;
            width: fit-content;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(6px);
        }
        .auth-aside h2 {
            font-size: 1.85rem;
            line-height: 1.15;
            font-weight: 800;
            margin-bottom: 0.85rem;
        }
        .auth-aside p {
            color: rgba(255,255,255,0.78);
            font-size: 0.92rem;
            line-height: 1.65;
            margin-bottom: 1.75rem;
        }
        .aside-check {
            display: flex;
            gap: 0.65rem;
            align-items: flex-start;
            font-size: 0.86rem;
            font-weight: 500;
            color: rgba(255,255,255,0.92);
            margin-bottom: 0.7rem;
        }
        .aside-check i {
            color: var(--accent);
            font-size: 0.95rem;
            margin-top: 0.15rem;
            flex-shrink: 0;
        }
        .aside-wallets {
            margin-top: auto;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            padding: 1.1rem 1.2rem;
            backdrop-filter: blur(6px);
        }
        .aside-wallets .label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.65);
            margin-bottom: 0.75rem;
        }
        .wallet-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.45rem 0;
            font-size: 0.86rem;
            font-weight: 600;
            border-bottom: 1px dashed rgba(255,255,255,0.10);
        }
        .wallet-row:last-child { border-bottom: none; padding-bottom: 0; }
        .wallet-row .name { display: flex; align-items: center; gap: 0.55rem; }
        .wallet-row .dot {
            width: 22px; height: 22px;
            border-radius: 7px;
            background: rgba(255,255,255,0.14);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: var(--accent);
        }
        .wallet-row .amt { color: rgba(255,255,255,0.85); font-weight: 700; }

        /* --- Form side --- */
        .auth-form-side { padding: 2.75rem 2.5rem; }
        .form-icon-badge {
            width: 54px; height: 54px;
            border-radius: 16px;
            background: linear-gradient(140deg, var(--brand), var(--brand-light));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.4rem;
            box-shadow: 0 12px 22px -10px rgba(10, 92, 54, 0.55);
            margin-bottom: 1.1rem;
        }
        .auth-form-side h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.4rem;
            letter-spacing: -0.035em;
        }
        .auth-form-side .sub {
            color: var(--muted);
            font-size: 0.93rem;
            margin-bottom: 1.75rem;
        }

        .form-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #4d5d54;
            margin-bottom: 0.5rem;
        }
        .input-icon-group { position: relative; }
        .input-icon-group > i.field-icon {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa8b8;
            font-size: 1.05rem;
            pointer-events: none;
            z-index: 2;
        }
        .input-icon-group .form-control {
            padding-left: 2.9rem;
            padding-right: 2.9rem;
        }
        .form-control {
            border-radius: 13px;
            padding: 0.85rem 1.15rem;
            border: 1.5px solid #e2e8e4;
            background: #fafcfa;
            font-size: 0.94rem;
            font-weight: 500;
            color: var(--ink);
            transition: all 0.18s ease;
        }
        .form-control::placeholder { color: #a4b0aa; font-weight: 400; }
        .form-control:focus {
            border-color: var(--brand);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(10, 92, 54, 0.10);
            outline: none;
        }
        .toggle-pass {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #9aa8b8;
            padding: 0.4rem 0.55rem;
            font-size: 1rem;
            border-radius: 8px;
            transition: color 0.15s, background 0.15s;
            z-index: 2;
            cursor: pointer;
        }
        .toggle-pass:hover { color: var(--brand); background: #eef5f1; }
        .toggle-pass:focus-visible { outline: 2px solid var(--brand); outline-offset: 1px; }

        /* Password strength meter */
        .pw-meter {
            display: flex;
            gap: 4px;
            margin-top: 0.6rem;
        }
        .pw-meter span {
            flex: 1;
            height: 5px;
            border-radius: 99px;
            background: #e6ede9;
            transition: background 0.25s;
        }
        .pw-meter span.active-weak { background: #e74c3c; }
        .pw-meter span.active-fair { background: #f5b700; }
        .pw-meter span.active-strong { background: var(--brand); }
        .pw-hint {
            font-size: 0.76rem;
            color: #9aa8b8;
            margin-top: 0.4rem;
            font-weight: 500;
        }

        .btn-submit {
            background: var(--brand);
            border: none;
            color: #ffffff;
            border-radius: 13px;
            font-weight: 700;
            font-size: 0.98rem;
            padding: 0.9rem 1.5rem;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 14px 24px -10px rgba(10, 92, 54, 0.55);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-submit:hover {
            background: var(--brand-light);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 20px 30px -12px rgba(10, 92, 54, 0.65);
        }
        .btn-submit:active { transform: translateY(1px); }
        .btn-submit:focus-visible {
            outline: 3px solid rgba(245, 183, 0, 0.6);
            outline-offset: 2px;
        }

        /* --- Alerts --- */
        .alert-login-error, .alert-login-success {
            border-radius: 13px;
            padding: 0.85rem 1.05rem;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin-bottom: 1.4rem;
        }
        .alert-login-error {
            background: #fff5f4;
            border: 1px solid #fecbc6;
            color: #b3261e;
        }
        .alert-login-success {
            background: #effaf3;
            border: 1px solid #b9ecd0;
            color: #0e6b3e;
        }
        .alert-login-error i, .alert-login-success i {
            font-size: 1rem;
            margin-top: 0.1rem;
            flex-shrink: 0;
        }
        .alert-login-success a {
            color: #0e6b3e;
            font-weight: 800;
            text-decoration: underline;
            margin-left: 0.3rem;
        }

        .divider-text {
            display: flex;
            align-items: center;
            color: #9aa8b8;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 1.5rem 0;
        }
        .divider-text::before,
        .divider-text::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e6ede9;
            margin: 0 0.85rem;
        }
        .login-link-box {
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
            margin-top: 1.4rem;
        }
        .login-link-box a {
            color: var(--brand);
            font-weight: 800;
            text-decoration: none;
        }
        .login-link-box a:hover { color: var(--brand-dark); text-decoration: underline; }

        /* --- Footer --- */
        .site-footer {
            background: #0b1b13;
            color: #b7c4bc;
            padding: 2.5rem 0 1.5rem;
            font-size: 0.88rem;
            margin-top: auto;
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
            font-size: 1.1rem;
            letter-spacing: -0.03em;
            margin-bottom: 0.6rem;
        }
        .footer-divider {
            border-color: rgba(255,255,255,0.08);
            margin: 1.75rem 0 1.25rem;
        }
        .footer-bottom { font-size: 0.8rem; color: #8a9a92; }
        .footer-links-inline {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .footer-links-inline a { font-size: 0.82rem; }

        /* --- Responsive --- */
        @media (max-width: 991.98px) {
            .auth-aside { padding: 2rem 1.75rem; }
            .auth-aside h2 { font-size: 1.55rem; }
            .auth-aside p { margin-bottom: 1.25rem; }
            .aside-wallets { margin-top: 1.5rem; }
            .auth-form-side { padding: 2rem 1.6rem; }
        }
        @media (max-width: 575.98px) {
            .auth-wrapper { padding: 1.25rem 0.9rem; }
            .auth-card { border-radius: 20px; }
            .auth-aside { padding: 1.6rem 1.35rem; }
            .auth-aside h2 { font-size: 1.35rem; }
            .auth-aside p { font-size: 0.86rem; }
            .auth-form-side { padding: 1.75rem 1.35rem; }
            .auth-form-side h1 { font-size: 1.5rem; }
            .form-icon-badge { width: 48px; height: 48px; font-size: 1.25rem; }
            .aside-check { font-size: 0.8rem; }
        }
    </style>
</head>
<body>

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-expand-lg navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand-custom" href="index.php">
                <span class="brand-dot"><i class="bi bi-wallet2"></i></span>
                SikaTrack<span style="color:var(--brand);">.</span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-ghost px-3 py-2 d-none d-sm-inline-flex align-items-center">
                    <i class="bi bi-arrow-left me-1"></i> Back to home
                </a>
                <a href="login.php" class="btn btn-brand px-4 py-2">
                    Log in
                </a>
            </div>
        </div>
    </nav>

    <!-- ================= AUTH SECTION ================= -->
    <main class="auth-wrapper">
        <div class="auth-shell">
            <div class="auth-card">
                <div class="row g-0">

                    <!-- ---------- Left: branding panel ---------- -->
                    <div class="col-lg-5 d-none d-lg-flex">
                        <div class="auth-aside w-100">
                            <span class="aside-badge">
                                <i class="bi bi-stars"></i> Free forever · No card needed
                            </span>
                            <h2>Start owning your money in under a minute.</h2>
                            <p>
                                Join Ghanaians who track every cedi — from MTN MoMo to pocket cash — with one clean dashboard.
                            </p>

                            <div class="aside-check">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Set up your MoMo, bank &amp; cash wallets instantly</span>
                            </div>
                            <div class="aside-check">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Create budgets, savings and Susu goals on day one</span>
                            </div>
                            <div class="aside-check">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Bank-grade password hashing keeps your data safe</span>
                            </div>

                            <div class="aside-wallets">
                                <div class="label">Your accounts get created automatically</div>
                                <div class="wallet-row">
                                    <div class="name">
                                        <span class="dot"><i class="bi bi-phone"></i></span>
                                        MTN MoMo
                                    </div>
                                    <div class="amt">GHS 0.00</div>
                                </div>
                                <div class="wallet-row">
                                    <div class="name">
                                        <span class="dot"><i class="bi bi-phone"></i></span>
                                        Telecel Cash
                                    </div>
                                    <div class="amt">GHS 0.00</div>
                                </div>
                                <div class="wallet-row">
                                    <div class="name">
                                        <span class="dot"><i class="bi bi-cash-stack"></i></span>
                                        Physical Cash
                                    </div>
                                    <div class="amt">GHS 0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ---------- Right: registration form ---------- -->
                    <div class="col-lg-7">
                        <div class="auth-form-side">
                            <div class="form-icon-badge">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <h1>Create your free account</h1>
                            <p class="sub">A few details and your dashboard is ready to go.</p>

                            <!-- PHP error/success output — unchanged logic -->
                            <?php if (!empty($error)): ?>
                                <div class="alert-login-error" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span><?= htmlspecialchars($error) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($success)): ?>
                                <div class="alert-login-success" role="alert">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        <?= htmlspecialchars($success) ?>
                                        <a href="login.php">Log in now <i class="bi bi-arrow-right"></i></a>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Registration form — same method, same field names -->
                            <form method="POST" action="" novalidate>
                                <div class="mb-3">
                                    <label for="fullnameInput" class="form-label">Full name</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-person field-icon"></i>
                                        <input
                                            type="text"
                                            name="fullname"
                                            id="fullnameInput"
                                            class="form-control"
                                            placeholder="e.g. Ama Mensah"
                                            autocomplete="name"
                                            required
                                            value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>"
                                        >
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="emailInput" class="form-label">Email address</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-envelope field-icon"></i>
                                        <input
                                            type="email"
                                            name="email"
                                            id="emailInput"
                                            class="form-control"
                                            placeholder="you@example.com"
                                            autocomplete="email"
                                            required
                                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                        >
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label for="passwordInput" class="form-label">Password</label>
                                    <div class="input-icon-group">
                                        <i class="bi bi-lock field-icon"></i>
                                        <input
                                            type="password"
                                            name="password"
                                            id="passwordInput"
                                            class="form-control"
                                            placeholder="Create a strong password"
                                            autocomplete="new-password"
                                            required
                                        >
                                        <button
                                            type="button"
                                            class="toggle-pass"
                                            id="togglePassword"
                                            aria-label="Show password"
                                            aria-pressed="false"
                                        >
                                            <i class="bi bi-eye" id="eyeIcon"></i>
                                        </button>
                                    </div>
                                    <div class="pw-meter" id="pwMeter" aria-hidden="true">
                                        <span></span><span></span><span></span><span></span>
                                    </div>
                                    <div class="pw-hint" id="pwHint">Use at least 8 characters for a strong password.</div>
                                </div>

                                <button type="submit" class="btn-submit mt-3">
                                    <i class="bi bi-person-plus"></i> Create my account
                                </button>
                            </form>

                            <div class="divider-text">Already registered?</div>

                            <div class="login-link-box">
                                Already have an account? <a href="login.php">Log in</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Back link on small screens -->
            <div class="text-center mt-3 d-sm-none">
                <a href="index.php" class="btn btn-ghost btn-sm px-3 py-2">
                    <i class="bi bi-arrow-left me-1"></i> Back to home
                </a>
            </div>
        </div>
    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="site-footer">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="footer-brand">
                    <span class="brand-dot"><i class="bi bi-wallet2"></i></span>
                    SikaTrack
                </div>
                <div class="footer-links-inline">
                    <a href="index.php">Home</a>
                    <a href="index.php#features">Features</a>
                    <a href="index.php#momo">Mobile Money</a>
                    <a href="index.php#susu">Savings &amp; Susu</a>
                    <a href="login.php">Log In</a>
                </div>
            </div>

            <hr class="footer-divider">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 footer-bottom">
                <div>
                    © <?= date('Y') ?> SikaTrack LLC. All rights reserved.
                </div>
                <div>
                    <i class="bi bi-shield-check me-1" style="color:var(--accent);"></i>
                    Passwords hashed with BCRYPT · Data never shared.
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS bundle (for navbar / future tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Pure UI: password visibility toggle + strength meter (does not affect PHP logic) -->
    <script>
        (function () {
            const btn = document.getElementById('togglePassword');
            const input = document.getElementById('passwordInput');
            const eye = document.getElementById('eyeIcon');
            const meter = document.getElementById('pwMeter');
            const hint = document.getElementById('pwHint');

            if (btn && input && eye) {
                btn.addEventListener('click', function () {
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    eye.classList.toggle('bi-eye', !isPassword);
                    eye.classList.toggle('bi-eye-slash', isPassword);
                    btn.setAttribute('aria-pressed', String(isPassword));
                    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                });
            }

            if (input && meter && hint) {
                const bars = meter.querySelectorAll('span');

                function scorePassword(pw) {
                    if (!pw) return 0;
                    let score = 0;
                    if (pw.length >= 8) score++;
                    if (pw.length >= 12) score++;
                    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
                    if (/\d/.test(pw)) score++;
                    if (/[^A-Za-z0-9]/.test(pw)) score++;
                    return Math.min(score, 4);
                }

                input.addEventListener('input', function () {
                    const score = scorePassword(input.value);
                    bars.forEach((bar, i) => {
                        bar.classList.remove('active-weak', 'active-fair', 'active-strong');
                        if (i < score) {
                            if (score <= 1) bar.classList.add('active-weak');
                            else if (score === 2) bar.classList.add('active-fair');
                            else bar.classList.add('active-strong');
                        }
                    });

                    if (!input.value) {
                        hint.textContent = 'Use at least 8 characters for a strong password.';
                    } else if (score <= 1) {
                        hint.textContent = 'Weak — try more length or mix upper/lowercase & numbers.';
                    } else if (score === 2) {
                        hint.textContent = 'Fair — adding a symbol would make it stronger.';
                    } else if (score === 3) {
                        hint.textContent = 'Good password. You can go stronger with a symbol.';
                    } else {
                        hint.textContent = 'Excellent — strong and secure.';
                    }
                });
            }
        })();
    </script>
</body>
</html>