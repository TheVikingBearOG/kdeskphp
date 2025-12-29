<aside class="sidebar">
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        
        <?php if ($isAdmin): ?>
        <a href="staff.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span>
            <span class="nav-label">Staff</span>
        </a>
        
        <a href="departments.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'departments.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏢</span>
            <span class="nav-label">Departments</span>
        </a>
        
        <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
            <span class="nav-icon">⚙️</span>
            <span class="nav-label">Settings</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-role-badge">
            <?php echo ucfirst($user['role']); ?>
        </div>
    </div>
</aside>
