<?php
// Login Page

require_once 'config.php';
require_once 'auth.php';

$error = '';

if (isLoggedIn()) {
    if (mustChangePassword()) {
        header('Location: change-password.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($email, $password)) {
        if (mustChangePassword()) {
            header('Location: change-password.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Helpdesk System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        required 
                        autofocus
                        placeholder="admin@kdesk.local"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="login-footer">
                <p class="text-muted">Default credentials: admin@kdesk.local / admin123</p>
            </div>
        </div>
    </div>
</body>
</html>
