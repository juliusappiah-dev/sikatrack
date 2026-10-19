<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');

try {
    // 1. Account Balances Breakdown
    $accStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN account_type = 'momo' THEN balance ELSE 0 END), 0) AS total_momo,
            COALESCE(SUM(CASE WHEN account_type != 'momo' THEN balance ELSE 0 END), 0) AS total_other,
            COALESCE(SUM(balance), 0) AS net_worth
        FROM accounts 
        WHERE user_id = ?
    ");
    $accStmt->execute([$user_id]);
    $balances = $accStmt->fetch();

    // 2. Current Month Income vs Expense
    $flowStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS month_income,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount + e_levy_fee ELSE 0 END), 0) AS month_expense
        FROM transactions 
        WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
    ");
    $flowStmt->execute([$user_id, $current_month]);
    $cashflow = $flowStmt->fetch();

    // 3. Category Spending Breakdown for Chart.js
    $chartStmt = $pdo->prepare("
        SELECT c.name AS category_name, SUM(t.amount + t.e_levy_fee) AS total_amount
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.type = 'expense' AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
        GROUP BY t.category_id, c.name
        ORDER BY total_amount DESC
    ");
    $chartStmt->execute([$user_id, $current_month]);
    $chartData = $chartStmt->fetchAll();

    $labels = [];
    $data = [];
    foreach ($chartData as $row) {
        $labels[] = $row['category_name'];
        $data[] = (float)$row['total_amount'];
    }

    echo json_encode([
        'status' => 'success',
        'metrics' => [
            'total_momo' => (float)$balances['total_momo'],
            'total_other' => (float)$balances['total_other'],
            'net_worth' => (float)$balances['net_worth'],
            'month_income' => (float)$cashflow['month_income'],
            'month_expense' => (float)$cashflow['month_expense']
        ],
        'chart' => [
            'labels' => $labels,
            'datasets' => $data
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>