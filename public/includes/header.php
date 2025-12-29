<header class="main-header">
    <div class="header-left">
        <a href="dashboard.php" class="logo"><?php echo APP_NAME; ?></a>
    </div>
    <div class="header-right">
        <span class="user-name">Welcome, <?php echo escape($user['name']); ?></span>
        <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
    </div>
</header>
