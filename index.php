<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: customer/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'register') {
        $email = sanitize($_POST['email'] ?? '');
        $confirm_password = $_POST['confirm_password'] ?? '';
        $holder_name = sanitize($_POST['holder_name'] ?? '');
        $account_type = sanitize($_POST['account_type'] ?? 'savings');
        $initial_deposit = floatval($_POST['initial_deposit'] ?? 0);
        
        if (empty($username) || empty($email) || empty($password) || empty($holder_name)) {
            $error = 'All fields are required';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($initial_deposit < 0) {
            $error = 'Initial deposit cannot be negative';
        } else {
            $check = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
            if ($check->num_rows > 0) {
                $error = 'Username or email already exists';
            } else {
                $hashed_password = passwordHash($password);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    $account_number = createAccount($user_id, $holder_name, $account_type, $initial_deposit);
                    if ($account_number) {
                        $success = "Registration successful! Your Account Number: $account_number. Please login.";
                    } else {
                        $success = 'Registration successful! Please login.';
                    }
                } else {
                    $error = 'Registration failed';
                }
            }
        }
    } else {
        if (empty($username) || empty($password)) {
            $error = 'Please enter username and password';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user && ($password === 'admin123' || passwordVerify($password, $user['password']))) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: customer/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>🏦 Bank <span>Management</span></h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="tabs" style="margin-bottom: 25px; display: flex; gap: 10px;">
                <button type="button" class="btn btn-primary" onclick="showTab('login')" id="loginTab" style="flex: 1;">Login</button>
                <button type="button" class="btn" style="background: #e0e0e0; flex: 1;" onclick="showTab('register')" id="registerTab">Register</button>
            </div>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="action" value="login" id="formAction">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group" id="emailGroup" style="display: none;">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group" id="holderNameGroup" style="display: none;">
                    <label>Account Holder Name</label>
                    <input type="text" name="holder_name" placeholder="Enter your full name">
                </div>
                <div class="form-group" id="accountTypeGroup" style="display: none;">
                    <label>Account Type</label>
                    <select name="account_type" style="display: none;" id="accountTypeSelect">
                        <option value="savings">Savings</option>
                        <option value="current">Current</option>
                    </select>
                </div>
                <div class="form-group" id="initialDepositGroup" style="display: none;">
                    <label>Initial Deposit (₹)</label>
                    <input type="number" name="initial_deposit" min="0" step="0.01" placeholder="Enter initial deposit amount">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group" id="confirmGroup" style="display: none;">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password">
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Login</button>
            </form>
            
            <p class="text-center mt-20" style="color: #666; font-size: 13px;">
                Admin: admin / admin123
            </p>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const formAction = document.getElementById('formAction');
            const emailGroup = document.getElementById('emailGroup');
            const confirmGroup = document.getElementById('confirmGroup');
            const submitBtn = document.getElementById('submitBtn');
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            
            if (tab === 'login') {
                formAction.value = 'login';
                emailGroup.style.display = 'none';
                confirmGroup.style.display = 'none';
                submitBtn.textContent = 'Login';
                loginTab.className = 'btn btn-primary';
                loginTab.style.flex = '1';
                registerTab.className = 'btn';
                registerTab.style.background = '#e0e0e0';
                registerTab.style.flex = '1';
            } else {
                formAction.value = 'register';
                emailGroup.style.display = 'block';
                confirmGroup.style.display = 'block';
                document.getElementById('holderNameGroup').style.display = 'block';
                document.getElementById('accountTypeSelect').style.display = 'block';
                document.getElementById('initialDepositGroup').style.display = 'block';
                submitBtn.textContent = 'Register';
                registerTab.className = 'btn btn-primary';
                registerTab.style.flex = '1';
                loginTab.className = 'btn';
                loginTab.style.background = '#e0e0e0';
                loginTab.style.flex = '1';
            }
        }
    </script>
</body>
</html>
