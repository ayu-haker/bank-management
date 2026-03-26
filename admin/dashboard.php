<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$total_balance = getTotalBalance();
$total_accounts = getTotalAccounts();
$total_customers = getTotalUsers();
$accounts = getAllAccounts();
$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bank Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>🏦 Admin Panel</h2>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="accounts.php">Accounts</a>
        <a href="users.php">Users</a>
        <a href="../logout.php">Logout</a>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <p>Welcome, <?= $_SESSION['username'] ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Balance</h3>
                <div class="value">₹<?= number_format($total_balance, 2) ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Accounts</h3>
                <div class="value"><?= $total_accounts ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="value"><?= $total_customers ?></div>
            </div>
        </div>
        
        <div class="table-container">
            <h2 class="mb-20">Recent Accounts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Account #</th>
                        <th>Holder Name</th>
                        <th>Type</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($accounts, 0, 10) as $acc): ?>
                    <tr>
                        <td><?= $acc['account_number'] ?></td>
                        <td><?= $acc['account_holder_name'] ?></td>
                        <td><?= ucfirst($acc['account_type']) ?></td>
                        <td>₹<?= number_format($acc['balance'], 2) ?></td>
                        <td><span class="badge badge-<?= $acc['status'] === 'active' ? 'success' : 'danger' ?>"><?= $acc['status'] ?></span></td>
                        <td><?= date('M d, Y', strtotime($acc['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($accounts)): ?>
                    <tr><td colspan="6" class="text-center">No accounts yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
