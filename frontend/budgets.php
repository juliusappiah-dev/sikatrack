<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
check_auth();

$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');
$error = '';
$success = '';

// Handle Budget Creation / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $month_year = $_POST['month_year'] ?? $current_month;

    if (!$category_id || !$amount || $amount <= 0) {
        $error = "Please provide a valid category and budget amount.";
    } else {
        // Check if budget already exists for this category and month
        $checkStmt = $pdo->prepare("SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month_year = ?");
        $checkStmt->execute([$user_id, $category_id, $month_year]);
        
        if ($checkStmt->rowCount() > 0) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE budgets SET amount = ? WHERE user_id = ? AND category_id = ? AND month_year = ?");
            $success = $stmt->execute([$amount, $user_id, $category_id, $month_year]) ? "Budget updated successfully!" : "Failed to update budget.";
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO budgets (user_id, category_id, amount, month_year) VALUES (?, ?, ?, ?)");
            $success = $stmt->execute([$user_id, $category_id, $amount, $month_year]) ? "Budget set successfully!" : "Failed to set budget.";
        }
    }
}

// Fetch expense categories for dropdown
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE (user_id IS NULL OR user_id = ?) AND type = 'expense' ORDER BY name");
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll();

// Fetch budgets for current month along with actual spend calculation
$budgetStmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name, c.icon AS category_icon,
           COALESCE((
               SELECT SUM(t.amount) 
               FROM transactions t 
               WHERE t.user_id = ? AND t.category_id = b.category_id 
                 AND DATE_FORMAT(t.transaction_date, '%Y-%m') = b.month_year
           ), 0) AS total_spent
    FROM budgets b
    JOIN categories c ON b.category_id = c.id
    WHERE b.user_id = ? AND b.month_year = ?
    ORDER BY c.name
");
$budgetStmt->execute([$user_id, $user_id, $current_month]);
$budgets = $budgetStmt->fetchAll();

$page_title = "Monthly Budgets · SikaTrack";
include 'includes/header.php';
?>

<!-- ================= PAGE HEADER ================= -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-extrabold mb-1" style="letter-spacing: -0.035em;">Monthly Budgets</h4>
        <p class="text-muted mb-0" style="font-size: 0.92rem;">
            Set spending limits on categories to keep your expenses in check · <strong><?= date('F Y') ?></strong>
        </p>
    </div>
    <span class="pill-soft" style="background:#e8f7ef; color:var(--brand); font-size:0.75rem; font-weight:700; padding:0.5rem 1rem; border-radius:999px;">
        <i class="bi bi-calendar3 me-1"></i> <?= date('F Y') ?>
    </span>
</div>

<!-- ================= SUMMARY STRIP ================= -->
<?php
    $totalBudget = 0;
    $totalSpent = 0;
    foreach ($budgets as $b) {
        $totalBudget += (float)$b['amount'];
        $totalSpent  += (float)$b['total_spent'];
    }
    $overallPercent = $totalBudget > 0 ? min(round(($totalSpent / $totalBudget) * 100), 100) : 0;
    $overallBar = $overallPercent >= 90 ? 'bg-danger' : ($overallPercent >= 75 ? 'bg-warning' : 'bg-success');
    $remaining = max($totalBudget - $totalSpent, 0);
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="budget-summary-tile">
            <div class="tile-icon tile-brand"><i class="bi bi-pie-chart"></i></div>
            <div>
                <div class="tile-label">Total Budget</div>
                <div class="tile-value font-monospace">GH₵ <?= number_format($totalBudget, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="budget-summary-tile">
            <div class="tile-icon tile-danger"><i class="bi bi-arrow-up-right-circle"></i></div>
            <div>
                <div class="tile-label">Total Spent</div>
                <div class="tile-value font-monospace" style="color:#d9342a;">GH₵ <?= number_format($totalSpent, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="budget-summary-tile">
            <div class="tile-icon tile-success"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="tile-label">Remaining</div>
                <div class="tile-value font-monospace" style="color:#199a5e;">GH₵ <?= number_format($remaining, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="budget-summary-tile">
            <div class="tile-icon tile-accent"><i class="bi bi-speedometer2"></i></div>
            <div>
                <div class="tile-label">Used</div>
                <div class="tile-value font-monospace"><?= $overallPercent ?>%</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ================= BUDGETS LIST ================= -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="letter-spacing:-0.03em;">Active Budgets</h5>
                        <p class="text-muted mb-0" style="font-size: 0.84rem;">
                            <?= count($budgets) ?> <?= count($budgets) === 1 ? 'category' : 'categories' ?> tracked this month
                        </p>
                    </div>
                    <span class="pill-soft" style="background:#f4f8f6; color:#4d5d54; font-size:0.72rem; font-weight:700; padding:0.35rem 0.75rem; border-radius:999px;">
                        <i class="bi bi-list-check me-1"></i> Overview
                    </span>
                </div>

                <?php if (empty($budgets)): ?>
                    <div class="text-center py-5">
                        <div class="empty-icon mx-auto">
                            <i class="bi bi-pie-chart"></i>
                        </div>
                        <h6 class="fw-bold mt-3 mb-1">No budgets yet</h6>
                        <p class="text-muted mb-0" style="font-size:0.88rem;">
                            Set your first category budget using the form to start tracking.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle budget-table mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Budget</th>
                                    <th>Spent</th>
                                    <th style="width: 30%;">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($budgets as $b):
                                    $percent = $b['amount'] > 0 ? min(round(($b['total_spent'] / $b['amount']) * 100), 100) : 0;
                                    $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 75 ? 'bg-warning' : 'bg-success');
                                    $remainingCat = max((float)$b['amount'] - (float)$b['total_spent'], 0);
                                ?>
                                <tr>
                                    <td>
                                        <div class="budget-cat-cell">
                                            <span class="budget-cat-icon">
                                                <i class="bi <?= htmlspecialchars($b['category_icon']) ?>"></i>
                                            </span>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($b['category_name']) ?></div>
                                                <div class="budget-cat-meta">
                                                    GH₵ <?= number_format($remainingCat, 2) ?> remaining
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="budget-amount font-monospace">GH₵ <?= number_format($b['amount'], 2) ?></div>
                                    </td>
                                    <td>
                                        <div class="budget-spent font-monospace">GH₵ <?= number_format($b['total_spent'], 2) ?></div>
                                    </td>
                                    <td>
                                        <div class="budget-progress-wrap">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="budget-percent <?= $percent >= 90 ? 'pct-danger' : ($percent >= 75 ? 'pct-warning' : 'pct-success') ?>">
                                                    <?= $percent ?>%
                                                </span>
                                                <span class="budget-percent-label">
                                                    <?= $percent >= 100 ? 'At limit' : ($percent >= 90 ? 'Almost' : ($percent >= 75 ? 'Watch' : 'On track')) ?>
                                                </span>
                                            </div>
                                            <div class="progress budget-bar">
                                                <div class="progress-bar <?= $barColor ?>" style="width: <?= $percent ?>%;"></div>
                                            </div>
                                        </div>
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

    <!-- ================= SET / UPDATE FORM ================= -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="form-section-icon"><i class="bi bi-plus-lg"></i></span>
                    <div>
                        <h5 class="fw-bold mb-0" style="letter-spacing:-0.02em;">Set / Update Budget</h5>
                        <p class="text-muted mb-0" style="font-size:0.82rem;">Define a spending limit</p>
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
                    <input type="hidden" name="month_year" value="<?= $current_month ?>">

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Expense Category</label>
                        <select name="category_id" class="form-select form-control-custom" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label form-label-custom">Monthly Limit</label>
                        <div class="input-group-custom">
                            <span class="input-prefix">GH₵</span>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-custom" placeholder="0.00" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold"
                            style="background:var(--brand); border-color:var(--brand);">
                        <i class="bi bi-check2 me-1"></i> Save Budget
                    </button>

                    <p class="text-muted text-center mt-3 mb-0" style="font-size:0.78rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Setting the same category twice updates the existing limit.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= BUDGETS PAGE STYLES ================= -->
<style>
    /* Summary tiles */
    .budget-summary-tile {
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
    .budget-summary-tile:hover {
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
    .tile-danger  { background: #fdeceb; color: #d9342a; }
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

    /* Budget table */
    .budget-table thead th {
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
    .budget-table tbody td {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f3f7f4;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .budget-table tbody tr:last-child td { border-bottom: none; }
    .budget-table tbody tr { transition: background 0.15s; }
    .budget-table tbody tr:hover { background: #fafdfb; }

    .budget-cat-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .budget-cat-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        background: #f4f8f6;
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .budget-cat-meta {
        font-size: 0.74rem;
        color: var(--muted);
        font-weight: 600;
        margin-top: 0.1rem;
    }

    .budget-amount {
        font-weight: 700;
        color: var(--ink);
        font-size: 0.9rem;
        letter-spacing: -0.02em;
    }
    .budget-spent {
        font-weight: 700;
        color: #d9342a;
        font-size: 0.9rem;
        letter-spacing: -0.02em;
    }

    .budget-progress-wrap { min-width: 140px; }
    .budget-percent {
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .pct-success { color: #199a5e; }
    .pct-warning { color: #b58800; }
    .pct-danger  { color: #d9342a; }
    .budget-percent-label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #9aa8b8;
    }
    .budget-bar {
        height: 8px;
        border-radius: 99px;
        background: #eef3f0;
        overflow: hidden;
    }
    .budget-bar .progress-bar {
        border-radius: 99px;
        transition: width 0.4s ease;
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
        .budget-table thead { display: none; }
        .budget-table tbody td {
            display: block;
            padding: 0.4rem 0;
            border-bottom: none;
            text-align: left !important;
        }
        .budget-table tbody tr {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid #f3f7f4;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            background: #ffffff;
        }
        .budget-table tbody tr:last-child { margin-bottom: 0; }
        .budget-progress-wrap { min-width: 100%; margin-top: 0.4rem; }
        .budget-table tbody td:last-child { margin-top: 0.25rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>