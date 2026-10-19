<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
check_auth();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle New Savings Goal Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_goal') {
    $goal_name = trim($_POST['goal_name']);
    $target_amount = filter_input(INPUT_POST, 'target_amount', FILTER_VALIDATE_FLOAT);
    $target_date = $_POST['target_date'] ?: null;

    if (empty($goal_name) || !$target_amount || $target_amount <= 0) {
        $error = "Please provide a valid goal name and target amount.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, target_date) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$user_id, $goal_name, $target_amount, $target_date]) ? "Savings goal created successfully!" : "Failed to create goal.";
    }
}

// Handle Deposit into Savings Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit_savings') {
    $goal_id = filter_input(INPUT_POST, 'goal_id', FILTER_SANITIZE_NUMBER_INT);
    $deposit_amount = filter_input(INPUT_POST, 'deposit_amount', FILTER_VALIDATE_FLOAT);

    if (!$goal_id || !$deposit_amount || $deposit_amount <= 0) {
        $error = "Invalid deposit amount.";
    } else {
        $stmt = $pdo->prepare("UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$deposit_amount, $goal_id, $user_id]) ? "Successfully added funds to savings goal!" : "Failed to update savings.";
    }
}

// Fetch user savings goals
$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll();

$page_title = "Savings & Susu Goals · SikaTrack";
include 'includes/header.php';
?>

<!-- ================= PAGE HEADER ================= -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-extrabold mb-1" style="letter-spacing: -0.035em;">Savings &amp; Susu Targets</h4>
        <p class="text-muted mb-0" style="font-size: 0.92rem;">
            Build financial security by tracking your personal savings goals.
        </p>
    </div>
    <button type="button" class="btn btn-primary rounded-3 fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#goalModal"
            style="background:var(--brand); border-color:var(--brand);">
        <i class="bi bi-plus-lg me-1"></i> New Savings Goal
    </button>
</div>

<?php
    // Compute overall stats for the summary strip
    $totalSaved = 0;
    $totalTarget = 0;
    $completedGoals = 0;
    foreach ($goals as $g) {
        $totalSaved  += (float)$g['current_amount'];
        $totalTarget += (float)$g['target_amount'];
        if ((float)$g['target_amount'] > 0 && (float)$g['current_amount'] >= (float)$g['target_amount']) {
            $completedGoals++;
        }
    }
    $overallPercent = $totalTarget > 0 ? min(round(($totalSaved / $totalTarget) * 100), 100) : 0;
?>

<?php if (!empty($goals)): ?>
<!-- ================= SUMMARY STRIP ================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="savings-summary-tile">
            <div class="tile-icon tile-brand"><i class="bi bi-piggy-bank"></i></div>
            <div>
                <div class="tile-label">Total Saved</div>
                <div class="tile-value font-monospace">GH₵ <?= number_format($totalSaved, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="savings-summary-tile">
            <div class="tile-icon tile-accent"><i class="bi bi-bullseye"></i></div>
            <div>
                <div class="tile-label">Total Target</div>
                <div class="tile-value font-monospace">GH₵ <?= number_format($totalTarget, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="savings-summary-tile">
            <div class="tile-icon tile-success"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="tile-label">Completed</div>
                <div class="tile-value font-monospace"><?= $completedGoals ?> / <?= count($goals) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="savings-summary-tile">
            <div class="tile-icon tile-danger"><i class="bi bi-speedometer2"></i></div>
            <div>
                <div class="tile-label">Overall Progress</div>
                <div class="tile-value font-monospace"><?= $overallPercent ?>%</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ================= GOALS GRID ================= -->
<div class="row g-4">
    <?php if(empty($goals)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 20px;">
                <div class="card-body p-5 text-center">
                    <div class="empty-icon mx-auto">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                    <h5 class="fw-bold mt-3 mb-2" style="letter-spacing:-0.03em;">No Savings Goals Yet</h5>
                    <p class="text-muted mb-4 mx-auto" style="font-size:0.92rem; max-width: 420px;">
                        Create a target for your emergency fund, rent, or susu contributions and watch your progress grow.
                    </p>
                    <button type="button" class="btn btn-primary rounded-3 fw-semibold px-4 py-2" data-bs-toggle="modal" data-bs-target="#goalModal"
                            style="background:var(--brand); border-color:var(--brand);">
                        <i class="bi bi-plus-lg me-1"></i> Create First Goal
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($goals as $g):
            $progress = $g['target_amount'] > 0 ? min(round(($g['current_amount'] / $g['target_amount']) * 100), 100) : 0;
            $isComplete = $progress >= 100;
            $remaining = max((float)$g['target_amount'] - (float)$g['current_amount'], 0);

            // Progress bar color variant
            if ($isComplete)          $barClass = 'bar-complete';
            elseif ($progress >= 75)  $barClass = 'bar-success';
            elseif ($progress >= 40)  $barClass = 'bar-warning';
            else                      $barClass = 'bar-danger';

            // Status pill
            if ($isComplete)         { $statusText = 'Completed'; $statusClass = 'status-success'; }
            elseif ($progress >= 75) { $statusText = 'Almost there'; $statusClass = 'status-success'; }
            elseif ($progress >= 40) { $statusText = 'On track'; $statusClass = 'status-warning'; }
            else                     { $statusText = 'Just started'; $statusClass = 'status-neutral'; }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="goal-card h-100">
                <div class="goal-header">
                    <div class="goal-icon-wrap <?= $isComplete ? 'goal-icon-complete' : '' ?>">
                        <i class="bi <?= $isComplete ? 'bi-trophy' : 'bi-piggy-bank' ?>"></i>
                    </div>
                    <span class="goal-status <?= $statusClass ?>"><?= $statusText ?></span>
                </div>

                <h5 class="goal-title"><?= htmlspecialchars($g['goal_name']) ?></h5>

                <?php if($g['target_date']): ?>
                    <div class="goal-date">
                        <i class="bi bi-calendar3"></i>
                        Target: <?= htmlspecialchars($g['target_date']) ?>
                    </div>
                <?php else: ?>
                    <div class="goal-date">
                        <i class="bi bi-infinity"></i>
                        No target date
                    </div>
                <?php endif; ?>

                <div class="goal-amounts">
                    <div class="goal-amount-primary font-monospace">
                        GH₵ <?= number_format($g['current_amount'], 2) ?>
                    </div>
                    <div class="goal-amount-target font-monospace">
                        of GH₵ <?= number_format($g['target_amount'], 2) ?>
                    </div>
                </div>

                <div class="goal-progress-wrap">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="goal-percent"><?= $progress ?>%</span>
                        <span class="goal-remaining">
                            <?= $isComplete ? 'Goal reached 🎉' : 'GH₵ ' . number_format($remaining, 2) . ' to go' ?>
                        </span>
                    </div>
                    <div class="goal-bar">
                        <div class="goal-bar-fill <?= $barClass ?>" style="width: <?= $progress ?>%;"></div>
                    </div>
                </div>

                <button type="button"
                        class="btn btn-add-funds w-100 mt-3"
                        data-bs-toggle="modal"
                        data-bs-target="#depositModal<?= $g['id'] ?>">
                    <i class="bi bi-wallet2 me-1"></i> Add Funds
                </button>
            </div>
        </div>

        <!-- Deposit Modal for each goal -->
        <div class="modal fade" id="depositModal<?= $g['id'] ?>" tabindex="-1" aria-labelledby="depositModalLabel<?= $g['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 modal-custom">
                    <div class="modal-header modal-header-custom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="modal-icon"><i class="bi bi-wallet2"></i></span>
                            <div>
                                <h5 class="modal-title fw-bold mb-0" id="depositModalLabel<?= $g['id'] ?>" style="letter-spacing:-0.02em; font-size:1.05rem;">
                                    Add Funds
                                </h5>
                                <p class="text-muted small mb-0" style="font-size:0.8rem;">
                                    Deposit into <strong><?= htmlspecialchars($g['goal_name']) ?></strong>
                                </p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="deposit_savings">
                            <input type="hidden" name="goal_id" value="<?= $g['id'] ?>">

                            <div class="goal-mini-summary mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="mini-label">Current saved</span>
                                    <span class="mini-value font-monospace">GH₵ <?= number_format($g['current_amount'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="mini-label">Target</span>
                                    <span class="mini-value font-monospace">GH₵ <?= number_format($g['target_amount'], 2) ?></span>
                                </div>
                            </div>

                            <label class="form-label form-label-custom">Deposit Amount</label>
                            <div class="input-group-custom">
                                <span class="input-prefix">GH₵</span>
                                <input type="number" step="0.01" name="deposit_amount" class="form-control form-control-custom" placeholder="0.00" required autofocus>
                            </div>
                        </div>
                        <div class="modal-footer modal-footer-custom">
                            <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold" data-bs-dismiss="modal"
                                    style="border-color:#e2e8e4; color:#4d5d54;">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary rounded-3 fw-semibold px-4"
                                    style="background:var(--brand); border-color:var(--brand);">
                                <i class="bi bi-check2 me-1"></i> Deposit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ================= NEW GOAL MODAL ================= -->
<div class="modal fade" id="goalModal" tabindex="-1" aria-labelledby="goalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 modal-custom">
            <div class="modal-header modal-header-custom">
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-icon"><i class="bi bi-plus-lg"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="goalModalLabel" style="letter-spacing:-0.02em;">Create Savings Goal</h5>
                        <p class="text-muted small mb-0" style="font-size:0.8rem;">Define what you're saving towards</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_goal">

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Goal Name</label>
                        <input type="text" name="goal_name" class="form-control form-control-custom"
                               placeholder="e.g. Emergency Fund, Rent Advance, Susu" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Target Amount</label>
                        <div class="input-group-custom">
                            <span class="input-prefix">GH₵</span>
                            <input type="number" step="0.01" name="target_amount" class="form-control form-control-custom"
                                   placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label form-label-custom">
                            Target Date
                            <span class="optional-tag">Optional</span>
                        </label>
                        <input type="date" name="target_date" class="form-control form-control-custom">
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold" data-bs-dismiss="modal"
                            style="border-color:#e2e8e4; color:#4d5d54;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 fw-semibold px-4"
                            style="background:var(--brand); border-color:var(--brand);">
                        <i class="bi bi-check2 me-1"></i> Create Goal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= SAVINGS PAGE STYLES ================= -->
<style>
    /* Summary tiles */
    .savings-summary-tile {
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
    .savings-summary-tile:hover {
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

    /* Goal cards */
    .goal-card {
        background: #ffffff;
        border: 1px solid #ecf1ee;
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        box-shadow: 0 4px 12px -8px rgba(11, 27, 19, 0.08);
    }
    .goal-card:hover {
        transform: translateY(-4px);
        border-color: #d7e6dd;
        box-shadow: 0 26px 40px -24px rgba(11, 27, 19, 0.25);
    }
    .goal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    .goal-icon-wrap {
        width: 44px; height: 44px;
        border-radius: 13px;
        background: linear-gradient(140deg, #e8f3ec, #d7eadd);
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .goal-icon-complete {
        background: linear-gradient(140deg, #fff6dd, #ffe8a3);
        color: #b58800;
    }
    .goal-status {
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .status-success { background: #e6f7ee; color: #0e6b3e; }
    .status-warning { background: #fff6dd; color: #8a6a00; }
    .status-neutral { background: #f4f8f6; color: #4d5d54; }

    .goal-title {
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: -0.025em;
        margin-bottom: 0.4rem;
        color: var(--ink);
        word-break: break-word;
    }
    .goal-date {
        font-size: 0.78rem;
        color: var(--muted);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 1rem;
    }
    .goal-date i { opacity: 0.75; }

    .goal-amounts {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        margin-bottom: 1rem;
    }
    .goal-amount-primary {
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--brand);
        line-height: 1.1;
    }
    .goal-amount-target {
        font-size: 0.82rem;
        color: var(--muted);
        font-weight: 600;
    }

    .goal-progress-wrap { margin-top: auto; }
    .goal-percent {
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--ink);
        letter-spacing: -0.02em;
    }
    .goal-remaining {
        font-size: 0.74rem;
        font-weight: 700;
        color: var(--muted);
    }
    .goal-bar {
        height: 8px;
        border-radius: 99px;
        background: #eef3f0;
        overflow: hidden;
        margin-top: 0.4rem;
    }
    .goal-bar-fill {
        height: 100%;
        border-radius: 99px;
        transition: width 0.5s ease;
    }
    .bar-complete { background: linear-gradient(90deg, #199a5e, #37b06f); }
    .bar-success  { background: linear-gradient(90deg, #199a5e, #37b06f); }
    .bar-warning  { background: linear-gradient(90deg, #d99b00, #f5b700); }
    .bar-danger   { background: linear-gradient(90deg, #a62219, #d9342a); }

    .btn-add-funds {
        background: #f0f6f2;
        border: 1px solid #e6ede9;
        color: var(--brand);
        border-radius: 11px;
        font-weight: 700;
        font-size: 0.88rem;
        padding: 0.55rem 1rem;
        transition: all 0.18s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-add-funds:hover {
        background: var(--brand);
        border-color: var(--brand);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 12px 20px -12px rgba(10, 92, 54, 0.55);
    }

    /* Modal */
    .modal-custom {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 40px 80px -34px rgba(11, 27, 19, 0.30), 0 10px 26px -14px rgba(11, 27, 19, 0.12);
    }
    .modal-header-custom {
        border-bottom: 1px solid #eef2ef;
        padding: 1.25rem 1.5rem;
    }
    .modal-footer-custom {
        border-top: 1px solid #eef2ef;
        padding: 1.1rem 1.5rem;
        background: #fafdfb;
    }
    .modal-icon {
        width: 40px; height: 40px;
        border-radius: 12px;
        background: linear-gradient(140deg, var(--brand), var(--brand-light));
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        box-shadow: 0 8px 16px -8px rgba(10, 92, 54, 0.55);
        flex-shrink: 0;
    }
    .modal-body { padding: 1.5rem; }

    .goal-mini-summary {
        background: #f7fbf8;
        border: 1px solid #e6f0ea;
        border-radius: 12px;
        padding: 0.85rem 1rem;
    }
    .mini-label {
        font-size: 0.78rem;
        color: var(--muted);
        font-weight: 600;
    }
    .mini-value {
        font-size: 0.88rem;
        color: var(--ink);
        font-weight: 800;
    }

    /* Form controls */
    .form-label-custom {
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #4d5d54;
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .optional-tag {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #9aa8b8;
        background: #f4f8f6;
        padding: 0.15rem 0.45rem;
        border-radius: 6px;
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
        width: 68px; height: 68px;
        border-radius: 20px;
        background: #f4f8f6;
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }

    @media (max-width: 575.98px) {
        .modal-footer-custom .btn { flex: 1; }
        .modal-header-custom { padding: 1rem 1.15rem; }
        .modal-body { padding: 1.15rem; }
        .goal-card { padding: 1.25rem; }
        .goal-amount-primary { font-size: 1.35rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>