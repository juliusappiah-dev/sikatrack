<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
check_auth();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Add Account Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_name = trim($_POST['account_name']);
    $account_type = $_POST['account_type'];
    $initial_balance = filter_input(INPUT_POST, 'balance', FILTER_VALIDATE_FLOAT) ?: 0.00;

    if (empty($account_name)) {
        $error = "Account name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_name, account_type, balance) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $account_name, $account_type, $initial_balance])) {
            $success = "Account added successfully!";
        } else {
            $error = "Failed to add account.";
        }
    }
}

// Fetch user accounts
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$accounts = $stmt->fetchAll();

$page_title = "Manage Accounts & MoMo · SikaTrack";
include 'includes/header.php';
?>

<!-- ================= PAGE HEADER ================= -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-extrabold mb-1" style="letter-spacing: -0.035em;">Accounts &amp; Wallets</h4>
        <p class="text-muted mb-0" style="font-size: 0.92rem;">
            Track balances across MTN MoMo, Telecel Cash, bank accounts, and physical cash.
        </p>
    </div>
    <a href="#addAccountForm" class="btn btn-primary rounded-3 fw-semibold px-3 py-2 d-none d-md-inline-flex"
       style="background:var(--brand); border-color:var(--brand);">
        <i class="bi bi-plus-lg me-1"></i> Add Account
    </a>
</div>

<?php
    // Compute summary stats from existing data
    $totalBalance = 0;
    $totalMoMo = 0;
    $totalCash = 0;
    $totalBank = 0;
    foreach ($accounts as $acc) {
        $bal = (float)$acc['balance'];
        $totalBalance += $bal;
        $type = strtolower($acc['account_type']);
        if ($type === 'momo')      $totalMoMo += $bal;
        elseif ($type === 'cash')  $totalCash += $bal;
        elseif ($type === 'bank')  $totalBank += $bal;
    }
?>

<!-- ================= SUMMARY STRIP ================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="acc-summary-tile">
            <div class="tile-icon tile-brand"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="tile-label">Total Balance</div>
                <div class="tile-value font-monospace">GH₵ <?= number_format($totalBalance, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="acc-summary-tile">
            <div class="tile-icon tile-accent"><i class="bi bi-phone"></i></div>
            <div>
                <div class="tile-label">MoMo Wallets</div>
                <div class="tile-value font-monospace">GH₵ <?= number_format($totalMoMo, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="acc-summary-tile">
            <div class="tile-icon tile-success"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="tile-label">Cash in Hand</div>
                <div class="tile-value font-monospace">GH₵ <?= number_format($totalCash, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="acc-summary-tile">
            <div class="tile-icon tile-danger"><i class="bi bi-bank"></i></div>
            <div>
                <div class="tile-label">Bank Accounts</div>
                <div class="tile-value font-monospace">GH₵ <?= number_format($totalBank, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ================= ACCOUNTS LIST ================= -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="letter-spacing:-0.03em;">Your Accounts</h5>
                        <p class="text-muted mb-0" style="font-size: 0.84rem;">
                            <?= count($accounts) ?> <?= count($accounts) === 1 ? 'account' : 'accounts' ?> linked
                        </p>
                    </div>
                    <span class="pill-soft" style="background:#e8f7ef; color:var(--brand); font-size:0.72rem; font-weight:700; padding:0.35rem 0.75rem; border-radius:999px;">
                        <i class="bi bi-list-ul"></i> Ledger
                    </span>
                </div>

                <?php if (empty($accounts)): ?>
                    <div class="text-center py-5">
                        <div class="empty-icon mx-auto">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h6 class="fw-bold mt-3 mb-1">No accounts yet</h6>
                        <p class="text-muted mb-3" style="font-size:0.88rem;">
                            Add your first MoMo, bank or cash wallet using the form.
                        </p>
                        <a href="#addAccountForm" class="btn btn-primary rounded-3 fw-semibold d-md-none"
                           style="background:var(--brand); border-color:var(--brand);">
                            <i class="bi bi-plus-lg me-1"></i> Add Account
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle acc-table mb-0">
                            <thead>
                                <tr>
                                    <th>Account Name</th>
                                    <th>Type</th>
                                    <th class="text-end">Balance (GH₵)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($accounts as $acc):
                                    $type = strtolower($acc['account_type']);
                                    if ($type === 'momo')       { $icon = 'bi-phone';       $iconClass = 'acc-icon-momo'; }
                                    elseif ($type === 'bank')   { $icon = 'bi-bank';        $iconClass = 'acc-icon-bank'; }
                                    else                        { $icon = 'bi-cash-stack';  $iconClass = 'acc-icon-cash'; }
                                ?>
                                <tr>
                                    <td>
                                        <div class="acc-name-cell">
                                            <span class="acc-icon <?= $iconClass ?>">
                                                <i class="bi <?= $icon ?>"></i>
                                            </span>
                                            <span class="fw-semibold"><?= htmlspecialchars($acc['account_name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="acc-type-badge <?= $iconClass ?>">
                                            <?= htmlspecialchars(strtoupper($acc['account_type'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="acc-balance font-monospace">GH₵ <?= number_format($acc['balance'], 2) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================= ADD ACCOUNT FORM ================= -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius: 20px;" id="addAccountForm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="form-section-icon"><i class="bi bi-plus-lg"></i></span>
                    <div>
                        <h5 class="fw-bold mb-0" style="letter-spacing:-0.02em;">Add New Account</h5>
                        <p class="text-muted mb-0" style="font-size:0.82rem;">Link a wallet or bank</p>
                    </div>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-danger py-2 rounded-3" style="font-size:0.88rem; font-weight:600;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="alert alert-success py-2 rounded-3" style="font-size:0.88rem; font-weight:600; background:#effaf3; border-color:#b9ecd0; color:#0e6b3e;">
                        <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Account / Wallet Name</label>
                        <input type="text" name="account_name" class="form-control form-control-custom"
                               placeholder="e.g. Zenith Bank, AT Money" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Account Type</label>
                        <select name="account_type" class="form-select form-control-custom" required>
                            <option value="momo">Mobile Money (MoMo)</option>
                            <option value="cash">Physical Cash</option>
                            <option value="bank">Bank Account</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label form-label-custom">Initial Balance</label>
                        <div class="input-group-custom">
                            <span class="input-prefix">GH₵</span>
                            <input type="number" step="0.01" name="balance" class="form-control form-control-custom"
                                   value="0.00" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold"
                            style="background:var(--brand); border-color:var(--brand);">
                        <i class="bi bi-check2 me-1"></i> Add Account
                    </button>

                    <p class="text-muted text-center mt-3 mb-0" style="font-size:0.78rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        You can track as many wallets as you need.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= ACCOUNTS PAGE STYLES ================= -->
<style>
    /* Summary tiles */
    .acc-summary-tile {
        background: #ffffff;
        border: 1px solid #ecf1ee;
        border-radius: 16px;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        height: 100%;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .acc-summary-tile:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 26px -18px rgba(11, 27, 19, 0.22);
    }
    .tile-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .tile-brand   { background: #e8f3ec; color: var(--brand); }
    .tile-danger  { background: #eaf1ff; color: #2563eb; }
    .tile-success { background: #e6f7ee; color: #199a5e; }
    .tile-accent  { background: #fff6dd; color: #b58800; }
    .tile-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 0.15rem;
    }
    .tile-value {
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--ink);
        line-height: 1.1;
    }

    /* Accounts table */
    .acc-table thead th {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #6b7d74;
        border-bottom: 1px solid #eef2ef;
        background: transparent;
        padding: 0.85rem 0.75rem;
        white-space: nowrap;
    }
    .acc-table tbody td {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f3f7f4;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .acc-table tbody tr:last-child td { border-bottom: none; }
    .acc-table tbody tr { transition: background 0.15s; }
    .acc-table tbody tr:hover { background: #fafdfb; }

    .acc-name-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .acc-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .acc-icon-momo  { background: #fff6dd; color: #b58800; }
    .acc-icon-bank  { background: #eaf1ff; color: #2563eb; }
    .acc-icon-cash  { background: #e6f7ee; color: #199a5e; }

    .acc-type-badge {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        padding: 0.32rem 0.7rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .acc-type-badge.acc-icon-momo { background: #fff6dd; color: #8a6a00; }
    .acc-type-badge.acc-icon-bank { background: #eaf1ff; color: #1d4ed8; }
    .acc-type-badge.acc-icon-cash { background: #e6f7ee; color: #0e6b3e; }

    .acc-balance {
        font-weight: 800;
        font-size: 0.96rem;
        letter-spacing: -0.02em;
        color: var(--ink);
    }

    /* Form */
    .form-section-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        background: linear-gradient(140deg, var(--brand), var(--brand-light));
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        box-shadow: 0 8px 14px -8px rgba(10, 92, 54, 0.55);
        flex-shrink: 0;
    }
    .form-label-custom {
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #4d5d54;
        margin-bottom: 0.5rem;
        display: block;
    }
    .form-control-custom {
        border-radius: 11px;
        padding: 0.7rem 0.95rem;
        border: 1.5px solid #e2e8e4;
        background: #fafcfa;
        font-size: 0.92rem;
        font-weight: 500;
        color: var(--ink);
        transition: all 0.18s ease;
    }
    .form-control-custom:focus {
        border-color: var(--brand);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(10, 92, 54, 0.10);
        outline: none;
    }
    .form-control-custom::placeholder { color: #a4b0aa; font-weight: 400; }

    .input-group-custom {
        position: relative;
        display: flex;
        align-items: stretch;
    }
    .input-prefix {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        display: inline-flex;
        align-items: center;
        padding: 0 0.85rem;
        background: #f0f6f2;
        border: 1.5px solid #e2e8e4;
        border-right: none;
        border-radius: 11px 0 0 11px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #4d5d54;
        z-index: 2;
    }
    .input-group-custom .form-control-custom {
        padding-left: 4.2rem;
        border-radius: 0 11px 11px 0;
    }

    /* Empty state */
    .empty-icon {
        width: 64px; height: 64px;
        border-radius: 18px;
        background: #f4f8f6;
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
    }

    @media (max-width: 767.98px) {
        .acc-table thead { display: none; }
        .acc-table tbody td {
            display: block;
            padding: 0.4rem 0;
            border-bottom: none;
            text-align: left !important;
        }
        .acc-table tbody tr {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid #f3f7f4;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            background: #ffffff;
        }
        .acc-table tbody tr:last-child { margin-bottom: 0; }
        .acc-balance { font-size: 1.05rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>