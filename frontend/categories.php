<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
check_auth();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Add Custom Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $icon = trim($_POST['icon']) ?: 'bi-tag';

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, icon) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $name, $type, $icon])) {
            $success = "Custom category added successfully!";
        } else {
            $error = "Failed to add category.";
        }
    }
}

// Fetch system defaults (user_id IS NULL) and user's custom categories
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id = ? ORDER BY type, name");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();

$page_title = "Manage Categories · SikaTrack";
include 'includes/header.php';
?>

<!-- ================= PAGE HEADER ================= -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-extrabold mb-1" style="letter-spacing: -0.035em;">Transaction Categories</h4>
        <p class="text-muted mb-0" style="font-size: 0.92rem;">
            Standard defaults plus your personal custom categories.
        </p>
    </div>
    <a href="#addCategoryForm" class="btn btn-primary rounded-3 fw-semibold px-3 py-2 d-none d-md-inline-flex"
       style="background:var(--brand); border-color:var(--brand);">
        <i class="bi bi-plus-lg me-1"></i> Add Category
    </a>
</div>

<?php
    // Compute summary stats from existing data
    $systemCount = 0;
    $customCount = 0;
    $incomeCount = 0;
    $expenseCount = 0;
    foreach ($categories as $cat) {
        if ($cat['user_id'] === NULL) $systemCount++; else $customCount++;
        if (strtolower($cat['type']) === 'income') $incomeCount++; else $expenseCount++;
    }
?>

<!-- ================= SUMMARY STRIP ================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="cat-summary-tile">
            <div class="tile-icon tile-brand"><i class="bi bi-tags"></i></div>
            <div>
                <div class="tile-label">Total Categories</div>
                <div class="tile-value font-monospace"><?= count($categories) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="cat-summary-tile">
            <div class="tile-icon tile-success"><i class="bi bi-arrow-down-left-circle"></i></div>
            <div>
                <div class="tile-label">Income</div>
                <div class="tile-value font-monospace"><?= $incomeCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="cat-summary-tile">
            <div class="tile-icon tile-danger"><i class="bi bi-arrow-up-right-circle"></i></div>
            <div>
                <div class="tile-label">Expense</div>
                <div class="tile-value font-monospace"><?= $expenseCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="cat-summary-tile">
            <div class="tile-icon tile-accent"><i class="bi bi-stars"></i></div>
            <div>
                <div class="tile-label">Custom</div>
                <div class="tile-value font-monospace"><?= $customCount ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ================= CATEGORY LIST ================= -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="letter-spacing:-0.03em;">All Categories</h5>
                        <p class="text-muted mb-0" style="font-size: 0.84rem;">
                            <?= $systemCount ?> system default<?= $systemCount === 1 ? '' : 's' ?> · <?= $customCount ?> custom
                        </p>
                    </div>
                    <span class="pill-soft" style="background:#e8f7ef; color:var(--brand); font-size:0.72rem; font-weight:700; padding:0.35rem 0.75rem; border-radius:999px;">
                        <i class="bi bi-list-ul"></i> Library
                    </span>
                </div>

                <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <div class="empty-icon mx-auto">
                            <i class="bi bi-tags"></i>
                        </div>
                        <h6 class="fw-bold mt-3 mb-1">No categories found</h6>
                        <p class="text-muted mb-0" style="font-size:0.88rem;">
                            Add your first custom category to get started.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle cat-table mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Origin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories as $cat):
                                    $isIncome = strtolower($cat['type']) === 'income';
                                ?>
                                <tr>
                                    <td>
                                        <div class="cat-name-cell">
                                            <span class="cat-icon <?= $isIncome ? 'cat-icon-income' : 'cat-icon-expense' ?>">
                                                <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                                            </span>
                                            <span class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="cat-type-badge <?= $isIncome ? 'cat-type-income' : 'cat-type-expense' ?>">
                                            <?= htmlspecialchars(strtoupper($cat['type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cat['user_id'] === NULL): ?>
                                            <span class="origin-tag origin-system">
                                                <i class="bi bi-shield-check"></i> System
                                            </span>
                                        <?php else: ?>
                                            <span class="origin-tag origin-custom">
                                                <i class="bi bi-star-fill"></i> Custom
                                            </span>
                                        <?php endif; ?>
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

    <!-- ================= ADD CATEGORY FORM ================= -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius: 20px;" id="addCategoryForm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="form-section-icon"><i class="bi bi-plus-lg"></i></span>
                    <div>
                        <h5 class="fw-bold mb-0" style="letter-spacing:-0.02em;">Add Custom Category</h5>
                        <p class="text-muted mb-0" style="font-size:0.82rem;">Create a personal category</p>
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
                        <label class="form-label form-label-custom">Category Name</label>
                        <input type="text" name="name" class="form-control form-control-custom"
                               placeholder="e.g. Fuel, Church Tithe" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Type</label>
                        <select name="type" class="form-select form-control-custom" id="catTypeSelect" required>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label form-label-custom">
                            Bootstrap Icon Class
                            <span class="optional-tag">Optional</span>
                        </label>
                        <div class="icon-input-row">
                            <span class="icon-preview" id="iconPreview">
                                <i class="bi bi-tag"></i>
                            </span>
                            <input type="text" name="icon" id="iconInput" class="form-control form-control-custom"
                                   value="bi-tag" placeholder="e.g. bi-cart, bi-bus-front">
                        </div>
                        <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">
                            <i class="bi bi-lightbulb me-1"></i>
                            Browse icons at <span style="color:var(--brand); font-weight:700;">icons.getbootstrap.com</span>
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold"
                            style="background:var(--brand); border-color:var(--brand);">
                        <i class="bi bi-check2 me-1"></i> Add Category
                    </button>

                    <p class="text-muted text-center mt-3 mb-0" style="font-size:0.78rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Custom categories are private to your account.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= CATEGORIES PAGE STYLES ================= -->
<style>
    /* Summary tiles */
    .cat-summary-tile {
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
    .cat-summary-tile:hover {
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

    /* Category table */
    .cat-table thead th {
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
    .cat-table tbody td {
        padding: 0.9rem 0.75rem;
        border-bottom: 1px solid #f3f7f4;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .cat-table tbody tr:last-child td { border-bottom: none; }
    .cat-table tbody tr { transition: background 0.15s; }
    .cat-table tbody tr:hover { background: #fafdfb; }

    .cat-name-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .cat-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .cat-icon-income  { background: #e6f7ee; color: #199a5e; }
    .cat-icon-expense { background: #fdeceb; color: #d9342a; }

    .cat-type-badge {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        padding: 0.32rem 0.7rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .cat-type-income  { background: #e6f7ee; color: #0e6b3e; }
    .cat-type-expense { background: #fdeceb; color: #b3261e; }

    .origin-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.28rem 0.6rem;
        border-radius: 8px;
        white-space: nowrap;
    }
    .origin-tag i { font-size: 0.68rem; }
    .origin-system { background: #f4f8f6; color: #4d5d54; }
    .origin-custom { background: #fff6dd; color: #8a6a00; }

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

    /* Icon input with preview */
    .icon-input-row {
        display: flex;
        align-items: stretch;
        gap: 0.5rem;
    }
    .icon-preview {
        width: 44px;
        border-radius: 11px;
        background: #f0f6f2;
        border: 1.5px solid #e2e8e4;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: var(--brand);
        flex-shrink: 0;
        transition: all 0.18s ease;
    }
    .icon-input-row .form-control-custom { flex: 1; }

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
        .cat-table thead { display: none; }
        .cat-table tbody td {
            display: block;
            padding: 0.4rem 0;
            border-bottom: none;
            text-align: left !important;
        }
        .cat-table tbody tr {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid #f3f7f4;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            background: #ffffff;
        }
        .cat-table tbody tr:last-child { margin-bottom: 0; }
    }
</style>

<script>
    // Pure UI: live icon preview — does not affect PHP or form submission
    (function () {
        const input = document.getElementById('iconInput');
        const preview = document.getElementById('iconPreview');
        if (!input || !preview) return;

        function updatePreview() {
            let cls = input.value.trim() || 'bi-tag';
            // Allow user to type either "bi-cart" or "bi bi-cart"
            if (!cls.startsWith('bi-')) cls = 'bi-' + cls.replace(/^bi\s+/, '');
            // Strip any "bi bi-" duplicates
            cls = cls.replace(/^bi\s+/, '');
            preview.innerHTML = '<i class="bi ' + cls + '"></i>';
        }

        input.addEventListener('input', updatePreview);
        updatePreview();
    })();
</script>

<?php include 'includes/footer.php'; ?>