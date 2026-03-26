<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_account'])) {
        $user_id = intval($_POST['user_id']);
        $holder_name = sanitize($_POST['holder_name']);
        $account_type = sanitize($_POST['account_type']);
        $initial_balance = floatval($_POST['initial_balance'] ?? 0);
        
        $account_number = createAccount($user_id, $holder_name, $account_type, $initial_balance);
        if ($account_number) {
            $success = "Account created successfully! Account Number: $account_number";
        } else {
            $error = "Failed to create account";
        }
    }
    
    if (isset($_POST['delete_account'])) {
        $account_id = intval($_POST['account_id']);
        $conn->query("DELETE FROM accounts WHERE account_id = $account_id");
        $success = "Account deleted successfully";
    }
    
    if (isset($_POST['add_balance'])) {
        $account_id = intval($_POST['account_id']);
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            deposit($account_id, $amount, 'Admin added balance');
            $success = "Balance added successfully!";
        }
    }
}

$accounts = getAllAccounts();
$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>🏦 Admin Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="accounts.php" class="active">Accounts</a>
        <a href="users.php">Users</a>
        <a href="../logout.php">Logout</a>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Accounts</h1>
            <a href="../index.php" class="back-link">← Back to Login</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <div class="card mb-20">
            <h2>Create New Account</h2>
            <form method="POST">
                <input type="hidden" name="create_account" value="1">
                <div class="form-group">
                    <label>Select User</label>
                    <select name="user_id" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= $user['username'] ?> (<?= $user['email'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Account Holder Name</label>
                    <input type="text" name="holder_name" required>
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="account_type" required>
                        <option value="savings">Savings</option>
                        <option value="current">Current</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Initial Balance (₹)</label>
                    <input type="number" name="initial_balance" min="0" step="0.01" value="0">
                </div>
                <button type="submit" class="btn btn-success">Create Account</button>
            </form>
        </div>
        
        <div class="table-container">
            <h2 class="mb-20">All Accounts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Account #</th>
                        <th>Holder Name</th>
                        <th>Type</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $acc): ?>
                    <tr>
                        <td><?= $acc['account_number'] ?></td>
                        <td><?= $acc['account_holder_name'] ?></td>
                        <td><?= ucfirst($acc['account_type']) ?></td>
                        <td>₹<?= number_format($acc['balance'], 2) ?></td>
                        <td><span class="badge badge-<?= $acc['status'] === 'active' ? 'success' : 'danger' ?>"><?= $acc['status'] ?></span></td>
                        <td><?= date('M d, Y', strtotime($acc['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline-flex; gap: 5px;">
                                <input type="number" name="amount" placeholder="₹" min="1" style="width: 80px; padding: 5px;">
                                <input type="hidden" name="add_balance" value="1">
                                <input type="hidden" name="account_id" value="<?= $acc['account_id'] ?>">
                                <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">Add</button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this account?');">
                                <input type="hidden" name="delete_account" value="1">
                                <input type="hidden" name="account_id" value="<?= $acc['account_id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($accounts)): ?>
                    <tr><td colspan="7" class="text-center">No accounts yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
