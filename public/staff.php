<?php
// Staff Management Page (Admin Only)

require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $departmentId = $_POST['department_id'] ?? null;
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            $existing = db()->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                $error = 'Email already exists';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                db()->execute(
                    "INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)",
                    [$name, $email, $hashedPassword, $role, $departmentId ?: null]
                );
                $success = 'Staff member created successfully';
            }
        }
    } elseif ($action === 'update') {
        $staffId = $_POST['staff_id'] ?? 0;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'staff';
        $departmentId = $_POST['department_id'] ?? null;
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required';
        } else {
            db()->execute(
                "UPDATE users SET name = ?, email = ?, role = ?, department_id = ? WHERE id = ?",
                [$name, $email, $role, $departmentId ?: null, $staffId]
            );
            $success = 'Staff member updated successfully';
        }
    } elseif ($action === 'toggle_status') {
        $staffId = $_POST['staff_id'] ?? 0;
        db()->execute(
            "UPDATE users SET is_active = NOT is_active WHERE id = ?",
            [$staffId]
        );
        $success = 'Status updated successfully';
    } elseif ($action === 'delete') {
        $staffId = $_POST['staff_id'] ?? 0;
        if ($staffId != $user['id']) {
            db()->execute("DELETE FROM users WHERE id = ?", [$staffId]);
            $success = 'Staff member deleted successfully';
        } else {
            $error = 'Cannot delete your own account';
        }
    }
}

$staffMembers = db()->fetchAll("
    SELECT u.*, d.name as department_name,
           (SELECT COUNT(*) FROM tickets WHERE assigned_to_id = u.id) as ticket_count
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY u.created_at DESC
");

$departments = db()->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1>Staff Management</h1>
                    <button class="btn btn-primary" onclick="showModal('createModal')">Add Staff Member</button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo escape($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo escape($success); ?></div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Tickets</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffMembers as $staff): ?>
                            <tr>
                                <td><?php echo escape($staff['name']); ?></td>
                                <td><?php echo escape($staff['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $staff['role'] === 'admin' ? 'badge-high' : 'badge-normal'; ?>">
                                        <?php echo ucfirst($staff['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $staff['department_name'] ? escape($staff['department_name']) : '<span class="text-muted">None</span>'; ?></td>
                                <td><?php echo $staff['ticket_count']; ?></td>
                                <td>
                                    <?php if ($staff['is_active']): ?>
                                        <span class="badge badge-solved">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-closed">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline" onclick="editStaff(<?php echo htmlspecialchars(json_encode($staff)); ?>)">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status?')">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline">
                                            <?php echo $staff['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <?php if ($staff['id'] != $user['id']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this staff member?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Staff Member</h2>
                <button class="modal-close" onclick="hideModal('createModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control">
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id" class="form-control">
                            <option value="">No Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo escape($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Staff Member</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Staff Member</h2>
                <button class="modal-close" onclick="hideModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_staff_id" name="staff_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" class="form-control">
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_department_id">Department</label>
                        <select id="edit_department_id" name="department_id" class="form-control">
                            <option value="">No Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo escape($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Staff Member</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editStaff(staff) {
            document.getElementById('edit_staff_id').value = staff.id;
            document.getElementById('edit_name').value = staff.name;
            document.getElementById('edit_email').value = staff.email;
            document.getElementById('edit_role').value = staff.role;
            document.getElementById('edit_department_id').value = staff.department_id || '';
            showModal('editModal');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
