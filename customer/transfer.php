<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$account = getAccountByUserId($user_id);
$error = '';
$success = '';

if (!$account) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_account = sanitize($_POST['to_account']);
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description'] ?? '');
    
    if ($amount <= 0) {
        $error = 'Please enter a valid amount';
    } elseif (empty($to_account)) {
        $error = 'Please enter recipient account number';
    } elseif ($account['balance'] < $amount) {
        $error = 'Insufficient balance';
    } else {
        $result = transfer($account['account_id'], $to_account, $amount, $description);
        if ($result['success']) {
            $success = "Successfully transferred $amount to $to_account";
            $account = getAccountByUserId($user_id);
        } else {
            $error = $result['message'] ?? 'Transfer failed';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer - Bank Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>🏦 My Bank</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="transactions.php">Transactions</a>
        <a href="deposit.php">Deposit</a>
        <a href="withdraw.php">Withdraw</a>
        <a href="transfer.php" class="active">Transfer</a>
        <a href="../logout.php">Logout</a>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Transfer Money</h1>
            <a href="dashboard.php" class="back-link">← Back</a>
        </div>
        
        <div class="balance-display" style="margin-bottom: 30px;">
            <h3>Current Balance</h3>
            <div class="amount">₹<?= number_format($account['balance'], 2) ?></div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Transfer Form</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Recipient Account Number</label>
                    <input type="text" name="to_account" required placeholder="e.g., ACC000123">
                </div>
                <div class="form-group">
                    <label>Amount ($)</label>
                    <input type="number" name="amount" step="0.01" min="1" required placeholder="Enter amount">
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <input type="text" name="description" placeholder="e.g., Payment">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Transfer</button>
            </form>
        </div>
    </div>
</body>
</html>
