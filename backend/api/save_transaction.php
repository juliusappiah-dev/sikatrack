<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized session. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$account_id = filter_input(INPUT_POST, 'account_id', FILTER_SANITIZE_NUMBER_INT);
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
$type = $_POST['type'] ?? '';
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$e_levy = filter_input(INPUT_POST, 'e_levy_fee', FILTER_VALIDATE_FLOAT) ?: 0.00;
$description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
$date = $_POST['transaction_date'] ?? date('Y-m-d');

if (!$account_id || !$category_id || !in_array($type, ['income', 'expense']) || !$amount || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide valid transaction details.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insert transaction record
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, amount, e_levy_fee, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $account_id, $category_id, $type, $amount, $e_levy, $description, $date]);

    // 2. Adjust account balance accordingly
    $total_deduction = $amount + $e_levy;
    if ($type === 'income') {
        $updateStmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        $updateStmt->execute([$amount, $account_id, $user_id]);
    } else {
        $updateStmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        $updateStmt->execute([$total_deduction, $account_id, $user_id]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Transaction logged successfully!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>