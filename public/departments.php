<?php
// Department Management Page (Admin Only)

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
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Department name is required';
        } else {
            db()->execute(
                "INSERT INTO departments (name, description) VALUES (?, ?)",
                [$name, $description]
            );
            $success = 'Department created successfully';
        }
    } elseif ($action === 'update') {
        $deptId = $_POST['dept_id'] ?? 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Department name is required';
        } else {
            db()->execute(
                "UPDATE departments SET name = ?, description = ? WHERE id = ?",
                [$name, $description, $deptId]
            );
            $success = 'Department updated successfully';
        }
    } elseif ($action === 'toggle_status') {
        $deptId = $_POST['dept_id'] ?? 0;
        db()->execute(
            "UPDATE departments SET is_active = NOT is_active WHERE id = ?",
            [$deptId]
        );
        $success = 'Status updated successfully';
    } elseif ($action === 'delete') {
        $deptId = $_POST['dept_id'] ?? 0;
        db()->execute("DELETE FROM departments WHERE id = ?", [$deptId]);
        $success = 'Department deleted successfully';
    }
}

$departments = db()->fetchAll("
    SELECT d.*,
           (SELECT COUNT(*) FROM users WHERE department_id = d.id) as staff_count,
           (SELECT COUNT(*) FROM tickets WHERE department_id = d.id) as ticket_count
    FROM departments d
    ORDER BY d.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1>Department Management</h1>
                    <button class="btn btn-primary" onclick="showModal('createModal')">Add Department</button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo escape($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo escape($success); ?></div>
                <?php endif; ?>

                <div class="departments-grid">
                    <?php foreach ($departments as $dept): ?>
                    <div class="department-card">
                        <div class="department-header">
                            <h3><?php echo escape($dept['name']); ?></h3>
                            <?php if ($dept['is_active']): ?>
                                <span class="badge badge-solved">Active</span>
                            <?php else: ?>
                                <span class="badge badge-closed">Inactive</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($dept['description']): ?>
                        <p class="department-description"><?php echo escape($dept['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="department-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $dept['staff_count']; ?></span>
                                <span class="stat-label">Staff Members</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $dept['ticket_count']; ?></span>
                                <span class="stat-label">Tickets</span>
                            </div>
                        </div>
                        
                        <div class="department-actions">
                            <button class="btn btn-sm btn-outline" onclick="editDepartment(<?php echo htmlspecialchars(json_encode($dept)); ?>)">Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status?')">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <?php echo $dept['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this department? Staff and tickets will be unassigned.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Department</h2>
                <button class="modal-close" onclick="hideModal('createModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Department Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Department</h2>
                <button class="modal-close" onclick="hideModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_dept_id" name="dept_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Department Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
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

        function editDepartment(dept) {
            document.getElementById('edit_dept_id').value = dept.id;
            document.getElementById('edit_name').value = dept.name;
            document.getElementById('edit_description').value = dept.description || '';
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
