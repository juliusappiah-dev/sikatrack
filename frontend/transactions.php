<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
check_auth();

$user_id = $_SESSION['user_id'];

// Fetch user accounts for dropdown
$accStmt = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ?");
$accStmt->execute([$user_id]);
$accounts = $accStmt->fetchAll();

// Fetch categories for dropdown
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id = ? ORDER BY type, name");
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll();

// Fetch recent transactions with account and category names
$txnStmt = $pdo->prepare("
    SELECT t.*, a.account_name, c.name AS category_name, c.icon AS category_icon 
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.transaction_date DESC, t.id DESC
    LIMIT 50
");
$txnStmt->execute([$user_id]);
$transactions = $txnStmt->fetchAll();

$page_title = "Transactions · SikaTrack";
include 'includes/header.php';
?>

<!-- ================= PAGE HEADER ================= -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-extrabold mb-1" style="letter-spacing: -0.035em;">Transactions Ledger</h4>
        <p class="text-muted mb-0" style="font-size: 0.92rem;">
            Log and review your income, expenses, and mobile money activity.
        </p>
    </div>
    <button type="button" class="btn btn-primary rounded-3 fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#transactionModal"
            style="background:var(--brand); border-color:var(--brand);">
        <i class="bi bi-plus-lg me-1"></i> Add Transaction
    </button>
</div>

<!-- ================= TRANSACTIONS TABLE ================= -->
<div class="card border-0 shadow-sm" style="border-radius: 20px;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-1" style="letter-spacing:-0.03em;">Recent Activity</h5>
                <p class="text-muted mb-0" style="font-size: 0.84rem;">
                    Showing your last <?= count($transactions) ?> transactions
                </p>
            </div>
            <span class="pill-soft" style="background:#e8f7ef; color:var(--brand); font-size:0.72rem; font-weight:700; padding:0.35rem 0.75rem; border-radius:999px;">
                <i class="bi bi-list-ul"></i> Ledger
            </span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle txn-table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Account / MoMo</th>
                        <th>Description</th>
                        <th class="text-end">Amount (GH₵)</th>
                    </tr>
                </thead>
                <tbody id="transactionTableBody">
                    <?php if(empty($transactions)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="text-center py-5">
                                <div class="empty-icon mx-auto">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <h6 class="fw-bold mt-3 mb-1">No transactions yet</h6>
                                <p class="text-muted mb-3" style="font-size:0.88rem;">
                                    Click "Add Transaction" to record your first entry.
                                </p>
                                <button type="button" class="btn btn-primary rounded-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#transactionModal"
                                        style="background:var(--brand); border-color:var(--brand);">
                                    <i class="bi bi-plus-lg me-1"></i> Add Transaction
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($transactions as $txn): ?>
                        <tr>
                            <td>
                                <span class="txn-date"><?= htmlspecialchars($txn['transaction_date']) ?></span>
                            </td>
                            <td>
                                <div class="txn-category-cell">
                                    <span class="txn-cat-icon">
                                        <i class="bi <?= htmlspecialchars($txn['category_icon']) ?>"></i>
                                    </span>
                                    <span class="fw-semibold"><?= htmlspecialchars($txn['category_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="account-badge"><?= htmlspecialchars($txn['account_name']) ?></span>
                            </td>
                            <td>
                                <span class="txn-desc"><?= htmlspecialchars($txn['description'] ?: '—') ?></span>
                            </td>
                            <td class="text-end">
                                <div class="txn-amount <?= $txn['type'] == 'income' ? 'txn-income' : 'txn-expense' ?> font-monospace">
                                    <?= $txn['type'] == 'income' ? '+' : '−' ?> GH₵ <?= number_format($txn['amount'], 2) ?>
                                </div>
                                <?php if($txn['e_levy_fee'] > 0): ?>
                                    <div class="txn-fee">Fee: GH₵ <?= number_format($txn['e_levy_fee'], 2) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= ADD TRANSACTION MODAL ================= -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 modal-custom">
            <div class="modal-header modal-header-custom">
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-icon"><i class="bi bi-plus-lg"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="transactionModalLabel" style="letter-spacing:-0.02em;">New Transaction</h5>
                        <p class="text-muted small mb-0" style="font-size:0.8rem;">Record an income or expense entry</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transactionForm">
                <div class="modal-body">
                    <div id="modalAlert"></div>

                    <!-- Type toggle -->
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Transaction Type</label>
                        <select name="type" id="txnType" class="form-select form-control-custom" required>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>

                    <!-- Account -->
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Account / MoMo Wallet</label>
                        <select name="account_id" class="form-select form-control-custom" required>
                            <?php foreach($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?> (GH₵ <?= number_format($acc['balance'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Category</label>
                        <select name="category_id" class="form-select form-control-custom" required>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Amount (GH₵)</label>
                        <div class="input-group-custom">
                            <span class="input-prefix">GH₵</span>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-custom" placeholder="0.00" required>
                        </div>
                    </div>

                    <!-- e-Levy fee -->
                    <div class="mb-3">
                        <label class="form-label form-label-custom">
                            MoMo / Transfer Fee
                            <span class="optional-tag">Optional e-levy</span>
                        </label>
                        <div class="input-group-custom">
                            <span class="input-prefix">GH₵</span>
                            <input type="number" step="0.01" name="e_levy_fee" class="form-control form-control-custom" value="0.00" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Description / Note</label>
                        <input type="text" name="description" class="form-control form-control-custom" placeholder="e.g. Lunch at Melcom, Trotro fare">
                    </div>

                    <!-- Date -->
                    <div class="mb-1">
                        <label class="form-label form-label-custom">Date</label>
                        <input type="date" name="transaction_date" class="form-control form-control-custom" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold" data-bs-dismiss="modal"
                            style="border-color:#e2e8e4; color:#4d5d54;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 fw-semibold px-4" id="saveTxnBtn"
                            style="background:var(--brand); border-color:var(--brand);">
                        <i class="bi bi-check2 me-1"></i> Save Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= TRANSACTIONS PAGE STYLES ================= -->
<style>
    /* Table */
    .txn-table thead th {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #6b7d74;
        border-bottom: 1px solid #eef2ef;
        background: transparent;
        padding: 0.85rem 1rem;
        white-space: nowrap;
    }
    .txn-table tbody td {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid #f3f7f4;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .txn-table tbody tr:last-child td { border-bottom: none; }
    .txn-table tbody tr { transition: background 0.15s; }
    .txn-table tbody tr:hover { background: #fafdfb; }

    .txn-date {
        color: var(--muted);
        font-size: 0.83rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .txn-category-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
    }
    .txn-cat-icon {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: #f4f8f6;
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .account-badge {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.3rem 0.65rem;
        background: #f4f8f6;
        border: 1px solid #e6ede9;
        border-radius: 999px;
        color: #4d5d54;
        white-space: nowrap;
    }

    .txn-desc {
        color: var(--muted);
        font-size: 0.85rem;
        display: inline-block;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .txn-amount {
        font-weight: 800;
        font-size: 0.92rem;
        letter-spacing: -0.02em;
        white-space: nowrap;
    }
    .txn-income { color: #199a5e; }
    .txn-expense { color: #d9342a; }
    .txn-fee {
        font-size: 0.72rem;
        color: var(--muted);
        font-weight: 500;
        margin-top: 0.15rem;
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

    /* Input with prefix */
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
    .input-group-custom .form-control-custom:focus {
        border-color: var(--brand);
    }

    @media (max-width: 767.98px) {
        .txn-table thead { display: none; }
        .txn-table tbody td {
            display: block;
            padding: 0.5rem 0;
            border-bottom: none;
            text-align: left !important;
        }
        .txn-table tbody tr {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid #f3f7f4;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            background: #ffffff;
        }
        .txn-table tbody tr:hover { background: #fafdfb; }
        .txn-table tbody td::before {
            content: attr(data-label);
            display: inline-block;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #9aa8b8;
            margin-right: 0.5rem;
        }
        .txn-desc { max-width: 100%; }
        .modal-footer-custom .btn { flex: 1; }
        .modal-header-custom { padding: 1rem 1.15rem; }
        .modal-body { padding: 1.15rem; }
    }
</style>

<?php 
$extra_js = '
<script>
$(document).ready(function() {
    $("#transactionForm").on("submit", function(e) {
        e.preventDefault();
        $("#saveTxnBtn").prop("disabled", true).html(\'<span class="spinner-border spinner-border-sm me-2"></span>Saving...\');

        $.ajax({
            url: "api/save_transaction.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                $("#saveTxnBtn").prop("disabled", false).html(\'<i class="bi bi-check2 me-1"></i> Save Transaction\');
                if(response.status === "success") {
                    location.reload(); // Reload to refresh table balances and items
                } else {
                    $("#modalAlert").html(\'<div class="alert alert-danger py-2 rounded-3">\' + response.message + \'</div>\');
                }
            },
            error: function() {
                $("#saveTxnBtn").prop("disabled", false).html(\'<i class="bi bi-check2 me-1"></i> Save Transaction\');
                $("#modalAlert").html(\'<div class="alert alert-danger py-2 rounded-3">Server communication error.</div>\');
            }
        });
    });
});
</script>
';
include 'includes/footer.php'; 
?>