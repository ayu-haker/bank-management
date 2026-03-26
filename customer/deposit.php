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
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description'] ?? '');
    
    if ($amount <= 0) {
        $error = 'Please enter a valid amount';
    } elseif (deposit($account['account_id'], $amount, $description)) {
        $success = "Successfully deposited $amount";
    } else {
        $error = 'Deposit failed';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit - Bank Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>🏦 My Bank</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="transactions.php">Transactions</a>
        <a href="deposit.php" class="active">Deposit</a>
        <a href="withdraw.php">Withdraw</a>
        <a href="transfer.php">Transfer</a>
        <a href="../logout.php">Logout</a>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Deposit Money</h1>
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
            <h2>Deposit Form</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Amount ($)</label>
                    <input type="number" name="amount" step="0.01" min="1" required placeholder="Enter amount">
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <input type="text" name="description" placeholder="e.g., Cash deposit">
                </div>
                <button type="submit" class="btn btn-success btn-block">Deposit</button>
            </form>
        </div>
    </div>
</body>
</html>
