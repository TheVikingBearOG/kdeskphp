<?php
// Authentication Functions

require_once 'db.php';

function login($email, $password) {
    $user = db()->fetchOne(
        "SELECT * FROM users WHERE email = ? AND is_active = 1",
        [$email]
    );

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['must_change_password'] = $user['must_change_password'];
        return true;
    }

    return false;
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return db()->fetchOne(
        "SELECT id, email, name, role, department_id, avatar_url, is_active FROM users WHERE id = ?",
        [$_SESSION['user_id']]
    );
}

function changePassword($userId, $newPassword) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->execute(
        "UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?",
        [$hashedPassword, $userId]
    );
}

function mustChangePassword() {
    return isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'];
}
