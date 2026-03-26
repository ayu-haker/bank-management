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

if (!$account) {
    header('Location: dashboard.php');
    exit;
}

$transactions = getTransactions($account['account_id'], 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Bank Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>🏦 My Bank</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="transactions.php" class="active">Transactions</a>
        <a href="deposit.php">Deposit</a>
        <a href="withdraw.php">Withdraw</a>
        <a href="transfer.php">Transfer</a>
        <a href="../logout.php">Logout</a>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Transaction History</h1>
            <a href="dashboard.php" class="back-link">← Back</a>
        </div>
        
        <div class="table-container">
            <?php if (!empty($transactions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $running_balance = $account['balance'];
                        foreach ($transactions as $trans): 
                            $is_credit = in_array($trans['transaction_type'], ['deposit', 'transfer_in']);
                            if (!$is_credit) {
                                $running_balance += $trans['amount'];
                            } else {
                                $running_balance -= $trans['amount'];
                            }
                        ?>
                            <tr>
                                <td><?= date('M d, Y H:i', strtotime($trans['transaction_date'])) ?></td>
                                <td><span class="badge badge-<?= $is_credit ? 'success' : 'danger' ?>"><?= ucfirst(str_replace('_', ' ', $trans['transaction_type'])) ?></span></td>
                                <td><?= $trans['description'] ?: '-' ?></td>
                                <td class="<?= $is_credit ? 'credit' : 'debit' ?>" style="color: <?= $is_credit ? '#28a745' : '#dc3545' ?>; font-weight: bold;">
                                    <?= $is_credit ? '+' : '-' ?>₹<?= number_format($trans['amount'], 2) ?>
                                </td>
                                <td>₹<?= number_format($running_balance, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center">No transactions yet</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
