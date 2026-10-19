<?php


require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
check_auth();

$user_id = $_SESSION['user_id'];

// Fetch recent 5 transactions
$recentStmt = $pdo->prepare("
    SELECT t.*, a.account_name, c.name AS category_name, c.icon AS category_icon 
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.transaction_date DESC, t.id DESC
    LIMIT 5
");
$recentStmt->execute([$user_id]);
$recentTxns = $recentStmt->fetchAll();

// Fetch accounts list
$accStmt = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY balance DESC");
$accStmt->execute([$user_id]);
$userAccounts = $accStmt->fetchAll();

$page_title = "Dashboard · SikaTrack";
include 'includes/header.php';
?>

<!-- ================= PAGE HEADER ================= -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-extrabold mb-1" style="letter-spacing: -0.035em;">Welcome back 👋</h4>
        <p class="text-muted mb-0" style="font-size: 0.92rem;">
            Here's your money at a glance for <strong><?= date('F Y') ?></strong>.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="transactions.php" class="btn btn-outline-secondary rounded-3 fw-semibold" style="border-color:#e2e8e4; color:#4d5d54;">
            <i class="bi bi-arrow-left-right me-1"></i> View Transactions
        </a>
        <a href="transactions.php?action=add" class="btn btn-primary rounded-3 fw-semibold" style="background:var(--brand); border-color:var(--brand);">
            <i class="bi bi-plus-lg me-1"></i> Add Transaction
        </a>
    </div>
</div>

<!-- ================= FINANCIAL SUMMARY WIDGETS ================= -->
<div class="row g-3 mb-4">
    <!-- Net Worth -->
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-brand">
            <div class="stat-card-top">
                <span class="stat-card-label">Total Net Worth</span>
                <span class="stat-card-icon"><i class="bi bi-wallet2"></i></span>
            </div>
            <h3 class="stat-card-value font-monospace" id="widgetNetWorth">GH₵ 0.00</h3>
            <div class="stat-card-foot">
                <i class="bi bi-graph-up-arrow"></i> All accounts combined
            </div>
        </div>
    </div>

    <!-- MoMo Balance -->
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-accent">
            <div class="stat-card-top">
                <span class="stat-card-label">Total MoMo Balance</span>
                <span class="stat-card-icon"><i class="bi bi-phone"></i></span>
            </div>
            <h3 class="stat-card-value font-monospace" id="widgetMoMo">GH₵ 0.00</h3>
            <div class="stat-card-foot">
                <i class="bi bi-shield-check"></i> MTN · Telecel · AT
            </div>
        </div>
    </div>

    <!-- Monthly Income -->
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-success">
            <div class="stat-card-top">
                <span class="stat-card-label">Income · <?= date('M') ?></span>
                <span class="stat-card-icon"><i class="bi bi-arrow-down-left-circle"></i></span>
            </div>
            <h3 class="stat-card-value font-monospace" id="widgetIncome">GH₵ 0.00</h3>
            <div class="stat-card-foot">
                <i class="bi bi-calendar-check"></i> This month
            </div>
        </div>
    </div>

    <!-- Monthly Expenses -->
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-danger">
            <div class="stat-card-top">
                <span class="stat-card-label">Expenses · <?= date('M') ?></span>
                <span class="stat-card-icon"><i class="bi bi-arrow-up-right-circle"></i></span>
            </div>
            <h3 class="stat-card-value font-monospace" id="widgetExpense">GH₵ 0.00</h3>
            <div class="stat-card-foot">
                <i class="bi bi-calendar-check"></i> This month
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ================= LEFT COLUMN ================= -->
    <div class="col-lg-7">

        <!-- Expense Distribution Chart -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="letter-spacing:-0.03em;">Expense Distribution</h5>
                        <p class="text-muted mb-0" style="font-size: 0.84rem;"><?= date('F Y') ?> · spending by category</p>
                    </div>
                    <span class="pill-soft" style="background:#e8f7ef; color:var(--brand); font-size:0.72rem; font-weight:700; padding:0.35rem 0.75rem; border-radius:999px;">
                        <i class="bi bi-pie-chart"></i> Breakdown
                    </span>
                </div>
                <div style="max-height: 340px; position: relative;" class="d-flex justify-content-center">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Wallets & Accounts -->
        <div class="card border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="letter-spacing:-0.03em;">Wallets &amp; Accounts</h5>
                        <p class="text-muted mb-0" style="font-size: 0.84rem;">Your current balances across accounts</p>
                    </div>
                    <a href="accounts.php" class="btn btn-sm rounded-3 fw-semibold" style="background:#f0f6f2; color:var(--brand); border:1px solid #e6ede9;">
                        Manage <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <?php if (empty($userAccounts)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-wallet2" style="font-size:2rem; opacity:0.5;"></i>
                        <p class="mb-0 mt-2" style="font-size:0.9rem;">No accounts yet. Add one to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach($userAccounts as $acc): ?>
                        <div class="col-md-6">
                            <div class="account-tile">
                                <div class="account-icon">
                                    <?php
                                        $typeIcon = 'bi-wallet2';
                                        if (strtolower($acc['account_type']) === 'momo') $typeIcon = 'bi-phone';
                                        elseif (strtolower($acc['account_type']) === 'cash') $typeIcon = 'bi-cash-stack';
                                        elseif (strtolower($acc['account_type']) === 'bank') $typeIcon = 'bi-bank';
                                    ?>
                                    <i class="bi <?= $typeIcon ?>"></i>
                                </div>
                                <div class="account-info">
                                    <small class="account-label"><?= htmlspecialchars($acc['account_name']) ?></small>
                                    <span class="account-balance font-monospace">GH₵ <?= number_format($acc['balance'], 2) ?></span>
                                </div>
                                <span class="account-badge"><?= strtoupper(htmlspecialchars($acc['account_type'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================= RIGHT COLUMN: RECENT ACTIVITY ================= -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="letter-spacing:-0.03em;">Recent Transactions</h5>
                        <p class="text-muted mb-0" style="font-size: 0.84rem;">Your last 5 activities</p>
                    </div>
                    <a href="transactions.php" class="btn btn-sm rounded-3 fw-semibold" style="background:#f0f6f2; color:var(--brand); border:1px solid #e6ede9;">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <?php if (empty($recentTxns)): ?>
                    <div class="text-center py-5">
                        <div class="empty-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h6 class="fw-bold mt-3 mb-1">No activity yet</h6>
                        <p class="text-muted mb-0" style="font-size:0.88rem;">Add your first transaction to see it here.</p>
                    </div>
                <?php else: ?>
                    <div class="txn-list">
                        <?php foreach($recentTxns as $t): ?>
                        <div class="txn-item">
                            <div class="txn-icon">
                                <i class="bi <?= htmlspecialchars($t['category_icon']) ?>"></i>
                            </div>
                            <div class="txn-info">
                                <div class="txn-category"><?= htmlspecialchars($t['category_name']) ?></div>
                                <div class="txn-meta">
                                    <?= htmlspecialchars($t['account_name']) ?>
                                    <span class="dot-sep">·</span>
                                    <?= htmlspecialchars($t['transaction_date']) ?>
                                </div>
                            </div>
                            <div class="txn-amount <?= $t['type'] == 'income' ? 'txn-income' : 'txn-expense' ?> font-monospace">
                                <?= $t['type'] == 'income' ? '+' : '−' ?> GH₵ <?= number_format($t['amount'], 2) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= DASHBOARD-SPECIFIC STYLES ================= -->
<style>
    /* Stat cards */
    .stat-card {
        border-radius: 18px;
        padding: 1.25rem 1.25rem 1.1rem;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid transparent;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 22px 36px -22px rgba(11, 27, 19, 0.28);
    }
    .stat-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    .stat-card-label {
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        opacity: 0.85;
    }
    .stat-card-icon {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        background: rgba(255,255,255,0.20);
        backdrop-filter: blur(4px);
    }
    .stat-card-value {
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin: 0 0 0.65rem;
        line-height: 1.1;
    }
    .stat-card-foot {
        font-size: 0.76rem;
        font-weight: 600;
        opacity: 0.85;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    /* Card color variants */
    .stat-brand {
        background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 100%);
        color: #fff;
        box-shadow: 0 18px 28px -18px rgba(10, 92, 54, 0.55);
    }
    .stat-accent {
        background: linear-gradient(135deg, #d99b00 0%, var(--accent) 100%);
        color: #1a1505;
        box-shadow: 0 18px 28px -18px rgba(245, 183, 0, 0.55);
    }
    .stat-accent .stat-card-icon { background: rgba(0,0,0,0.10); }
    .stat-success {
        background: linear-gradient(135deg, #0c6b3f 0%, #199a5e 100%);
        color: #fff;
        box-shadow: 0 18px 28px -18px rgba(25, 154, 94, 0.55);
    }
    .stat-danger {
        background: linear-gradient(135deg, #a62219 0%, #d9342a 100%);
        color: #fff;
        box-shadow: 0 18px 28px -18px rgba(217, 52, 42, 0.55);
    }

    /* Account tiles */
    .account-tile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        background: #ffffff;
        border: 1px solid #ecf1ee;
        border-radius: 14px;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        height: 100%;
    }
    .account-tile:hover {
        border-color: #d7e6dd;
        box-shadow: 0 12px 20px -14px rgba(11, 27, 19, 0.20);
        transform: translateY(-2px);
    }
    .account-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: #e8f3ec;
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .account-info {
        flex: 1;
        min-width: 0;
    }
    .account-label {
        display: block;
        color: var(--muted);
        font-size: 0.74rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .account-balance {
        display: block;
        font-weight: 800;
        font-size: 0.96rem;
        color: var(--ink);
        letter-spacing: -0.02em;
    }
    .account-badge {
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        padding: 0.28rem 0.55rem;
        background: #f4f8f6;
        border: 1px solid #e6ede9;
        border-radius: 999px;
        color: #4d5d54;
        flex-shrink: 0;
    }

    /* Transactions list */
    .txn-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .txn-item {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.75rem 0.5rem;
        border-radius: 12px;
        transition: background 0.15s ease;
    }
    .txn-item:hover {
        background: #f7fbf8;
    }
    .txn-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        background: #f4f8f6;
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .txn-info {
        flex: 1;
        min-width: 0;
    }
    .txn-category {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .txn-meta {
        font-size: 0.76rem;
        color: var(--muted);
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dot-sep { opacity: 0.6; margin: 0 0.15rem; }
    .txn-amount {
        font-weight: 800;
        font-size: 0.9rem;
        letter-spacing: -0.02em;
        flex-shrink: 0;
    }
    .txn-income { color: #199a5e; }
    .txn-expense { color: #d9342a; }

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

    @media (max-width: 575.98px) {
        .stat-card-value { font-size: 1.15rem; }
        .stat-card { padding: 1rem 1rem 0.9rem; }
        .stat-card-label { font-size: 0.68rem; }
        .stat-card-foot { font-size: 0.7rem; }
        .account-badge { display: none; }
    }
</style>

<?php 
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Fetch Dashboard Data
    $.ajax({
        url: "api/fetch_dashboard.php",
        type: "GET",
        dataType: "json",
        success: function(res) {
            if(res.status === "success") {
                const m = res.metrics;
                $("#widgetNetWorth").text("GH₵ " + m.net_worth.toFixed(2));
                $("#widgetMoMo").text("GH₵ " + m.total_momo.toFixed(2));
                $("#widgetIncome").text("GH₵ " + m.month_income.toFixed(2));
                $("#widgetExpense").text("GH₵ " + m.month_expense.toFixed(2));

                // Render Chart.js
                const ctx = document.getElementById("expenseChart").getContext("2d");
                if (res.chart.labels.length === 0) {
                    ctx.font = "16px Plus Jakarta Sans, sans-serif";
                    ctx.fillStyle = "#8a9a92";
                    ctx.textAlign = "center";
                    ctx.fillText("No expense data for this month.", ctx.canvas.width / 2, 100);
                } else {
                    new Chart(ctx, {
                        type: "doughnut",
                        data: {
                            labels: res.chart.labels,
                            datasets: [{
                                data: res.chart.datasets,
                                backgroundColor: [
                                    "#0a5c36", "#d9342a", "#f5b700", "#199a5e",
                                    "#0dcaf0", "#6c757d", "#2563eb", "#fd7e14"
                                ],
                                borderWidth: 3,
                                borderColor: "#ffffff"
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: "62%",
                            plugins: {
                                legend: {
                                    position: "bottom",
                                    labels: {
                                        font: { family: "Plus Jakarta Sans, sans-serif", size: 12 },
                                        padding: 14,
                                        usePointStyle: true,
                                        pointStyle: "circle"
                                    }
                                },
                                tooltip: {
                                    backgroundColor: "#0b1b13",
                                    padding: 10,
                                    cornerRadius: 8,
                                    titleFont: { family: "Plus Jakarta Sans, sans-serif" },
                                    bodyFont: { family: "Plus Jakarta Sans, sans-serif" },
                                    callbacks: {
                                        label: function(context) {
                                            return " " + context.label + ": GH₵ " + parseFloat(context.parsed).toFixed(2);
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    });
});
</script>
';
include 'includes/footer.php'; 
?>