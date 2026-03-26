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
$transactions = $account ? getTransactions($account['account_id'], 10) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Bank Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>🏦 My Bank</h2>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="transactions.php">Transactions</a>
        <a href="deposit.php">Deposit</a>
        <a href="withdraw.php">Withdraw</a>
        <a href="transfer.php">Transfer</a>
        <a href="../logout.php">Logout</a>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>My Account</h1>
            <p>Welcome, <?= $_SESSION['username'] ?></p>
        </div>
        
        <?php if (!$account): ?>
            <div class="card">
                <h2>No Account Found</h2>
                <p>Please contact the bank to create your account.</p>
            </div>
        <?php else: ?>
            <div class="balance-display">
                <h3>Available Balance</h3>
                <div class="amount">₹<?= number_format($account['balance'], 2) ?></div>
                <p>Account: <?= $account['account_number'] ?> | <?= ucfirst($account['account_type']) ?></p>
            </div>
            
            <div class="transaction-grid">
                <div class="transaction-card deposit" onclick="location.href='deposit.php'">
                    <div style="font-size: 40px; margin-bottom: 15px;">💰</div>
                    <h3>Deposit</h3>
                    <p>Add money to account</p>
                </div>
                <div class="transaction-card withdraw" onclick="location.href='withdraw.php'">
                    <div style="font-size: 40px; margin-bottom: 15px;">💸</div>
                    <h3>Withdraw</h3>
                    <p>Withdraw money</p>
                </div>
                <div class="transaction-card transfer" onclick="location.href='transfer.php'">
                    <div style="font-size: 40px; margin-bottom: 15px;">🔄</div>
                    <h3>Transfer</h3>
                    <p>Send to others</p>
                </div>
            </div>
            
            <div class="table-container">
                <h2 class="mb-20">Recent Transactions</h2>
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $trans): ?>
                        <div class="history-item">
                            <div class="info">
                                <h4><?= ucfirst(str_replace('_', ' ', $trans['transaction_type'])) ?></h4>
                                <p><?= $trans['description'] ?: 'No description' ?> • <?= date('M d, Y H:i', strtotime($trans['transaction_date'])) ?></p>
                            </div>
                            <div class="amount <?= in_array($trans['transaction_type'], ['deposit', 'transfer_in']) ? 'credit' : 'debit' ?>">
                                <?= in_array($trans['transaction_type'], ['deposit', 'transfer_in']) ? '+' : '-' ?>₹<?= number_format($trans['amount'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">No transactions yet</p>
                <?php endif; ?>
                <div class="text-center mt-20">
                    <a href="transactions.php" class="btn btn-primary">View All Transactions</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
