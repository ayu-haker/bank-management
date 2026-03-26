<?php
require_once __DIR__ . '/../config/database.php';

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateAccountNumber() {
    return 'ACC' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function getAccountByUserId($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getAccountByNumber($account_number) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE account_number = ?");
    $stmt->bind_param("s", $account_number);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function createAccount($user_id, $holder_name, $account_type, $initial_balance = 0) {
    global $conn;
    $account_number = generateAccountNumber();
    
    do {
        $account_number = generateAccountNumber();
        $check = $conn->query("SELECT account_id FROM accounts WHERE account_number = '$account_number'");
    } while ($check->num_rows > 0);
    
    $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, account_holder_name, account_type, balance) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssd", $user_id, $account_number, $holder_name, $account_type, $initial_balance);
    
    return $stmt->execute() ? $account_number : false;
}

function deposit($account_id, $amount, $description = '') {
    global $conn;
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
        $stmt->bind_param("di", $amount, $account_id);
        $stmt->execute();
        
        $trans = $conn->prepare("INSERT INTO transactions (account_id, transaction_type, amount, description) VALUES (?, 'deposit', ?, ?)");
        $trans->bind_param("ids", $account_id, $amount, $description);
        $trans->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function withdraw($account_id, $amount, $description = '') {
    global $conn;
    $account = getAccountById($account_id);
    
    if ($account['balance'] < $amount) {
        return ['success' => false, 'message' => 'Insufficient balance'];
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?");
        $stmt->bind_param("di", $amount, $account_id);
        $stmt->execute();
        
        $trans = $conn->prepare("INSERT INTO transactions (account_id, transaction_type, amount, description) VALUES (?, 'withdrawal', ?, ?)");
        $trans->bind_param("ids", $account_id, $amount, $description);
        $trans->execute();
        
        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Transaction failed'];
    }
}

function transfer($from_account_id, $to_account_number, $amount, $description = '') {
    global $conn;
    
    $to_account = getAccountByNumber($to_account_number);
    if (!$to_account) {
        return ['success' => false, 'message' => 'Recipient account not found'];
    }
    
    if ($to_account['account_id'] == $from_account_id) {
        return ['success' => false, 'message' => 'Cannot transfer to same account'];
    }
    
    $from_account = getAccountById($from_account_id);
    if ($from_account['balance'] < $amount) {
        return ['success' => false, 'message' => 'Insufficient balance'];
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?");
        $stmt->bind_param("di", $amount, $from_account_id);
        $stmt->execute();
        
        $stmt2 = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
        $stmt2->bind_param("di", $amount, $to_account['account_id']);
        $stmt2->execute();
        
        $trans1 = $conn->prepare("INSERT INTO transactions (account_id, transaction_type, amount, receiver_account_id, description) VALUES (?, 'transfer_out', ?, ?, ?)");
        $trans1->bind_param("idds", $from_account_id, $amount, $to_account['account_id'], $description);
        $trans1->execute();
        
        $trans2 = $conn->prepare("INSERT INTO transactions (account_id, transaction_type, amount, receiver_account_id, description) VALUES (?, 'transfer_in', ?, ?, ?)");
        $trans2->bind_param("idds", $to_account['account_id'], $amount, $from_account_id, $description);
        $trans2->execute();
        
        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Transfer failed'];
    }
}

function getAccountById($account_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE account_id = ?");
    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getTransactions($account_id, $limit = 50) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE account_id = ? ORDER BY transaction_date DESC LIMIT ?");
    $stmt->bind_param("ii", $account_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllAccounts() {
    global $conn;
    return $conn->query("SELECT accounts.*, users.username, users.email FROM accounts JOIN users ON accounts.user_id = users.id ORDER BY accounts.created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

function getAllUsers() {
    global $conn;
    return $conn->query("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

function getTotalBalance() {
    global $conn;
    $result = $conn->query("SELECT SUM(balance) as total FROM accounts");
    return $result->fetch_assoc()['total'] ?? 0;
}

function getTotalAccounts() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM accounts");
    return $result->fetch_assoc()['count'] ?? 0;
}

function getTotalUsers() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    return $result->fetch_assoc()['count'] ?? 0;
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function passwordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function passwordVerify($password, $hash) {
    return password_verify($password, $hash);
}
?>
