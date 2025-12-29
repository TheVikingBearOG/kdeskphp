<?php
// Change Password Page

require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

$error = '';
$success = '';

if (!mustChangePassword()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $user = getCurrentUser();
    $dbUser = db()->fetchOne("SELECT password FROM users WHERE id = ?", [$user['id']]);

    if (!password_verify($currentPassword, $dbUser['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        changePassword($user['id'], $newPassword);
        $_SESSION['must_change_password'] = false;
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Change Password</h1>
                <p>You must change your password before continuing</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-control" 
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-control" 
                        required
                        minlength="6"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        required
                        minlength="6"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>
