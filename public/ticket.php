<?php
// Ticket Detail Page

require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

$user = getCurrentUser();
$isAdmin = $user['role'] === 'admin';

$ticketId = $_GET['id'] ?? 0;
$ticket = getTicketWithMessages($ticketId);

if (!$ticket) {
    header('Location: dashboard.php');
    exit;
}

if (!$isAdmin && $ticket['assigned_to_id'] != $user['id']) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reply') {
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            $error = 'Message cannot be empty';
        } else {
            db()->execute(
                "INSERT INTO messages (ticket_id, type, from_address, to_address, body_text, is_internal, created_by_id) 
                 VALUES (?, 'outbound', ?, ?, ?, 0, ?)",
                [$ticketId, $user['email'], $ticket['requester_email'], $message, $user['id']]
            );
            
            db()->execute(
                "UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?",
                [$ticketId]
            );
            
            $success = 'Reply sent successfully';
            $ticket = getTicketWithMessages($ticketId);
        }
    } elseif ($action === 'note') {
        $note = trim($_POST['note'] ?? '');
        
        if (empty($note)) {
            $error = 'Note cannot be empty';
        } else {
            db()->execute(
                "INSERT INTO messages (ticket_id, type, from_address, body_text, is_internal, created_by_id) 
                 VALUES (?, 'note', ?, ?, 1, ?)",
                [$ticketId, $user['email'], $note, $user['id']]
            );
            
            db()->execute(
                "UPDATE tickets SET updated_at = NOW() WHERE id = ?",
                [$ticketId]
            );
            
            $success = 'Note added successfully';
            $ticket = getTicketWithMessages($ticketId);
        }
    } elseif ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        
        if (in_array($newStatus, ['new', 'open', 'pending', 'solved', 'closed'])) {
            db()->execute(
                "UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?",
                [$newStatus, $ticketId]
            );
            
            $success = 'Status updated successfully';
            $ticket = getTicketWithMessages($ticketId);
        }
    } elseif ($action === 'update_priority') {
        $newPriority = $_POST['priority'] ?? '';
        
        if (in_array($newPriority, ['low', 'normal', 'high'])) {
            db()->execute(
                "UPDATE tickets SET priority = ?, updated_at = NOW() WHERE id = ?",
                [$newPriority, $ticketId]
            );
            
            $success = 'Priority updated successfully';
            $ticket = getTicketWithMessages($ticketId);
        }
    } elseif ($action === 'assign' && $isAdmin) {
        $assignToId = $_POST['assign_to_id'] ?? '';
        
        if ($assignToId === '') {
            db()->execute(
                "UPDATE tickets SET assigned_to_id = NULL, updated_at = NOW() WHERE id = ?",
                [$ticketId]
            );
        } else {
            db()->execute(
                "UPDATE tickets SET assigned_to_id = ?, updated_at = NOW() WHERE id = ?",
                [$assignToId, $ticketId]
            );
        }
        
        $success = 'Assignment updated successfully';
        $ticket = getTicketWithMessages($ticketId);
    } elseif ($action === 'assign_department' && $isAdmin) {
        $departmentId = $_POST['department_id'] ?? '';
        
        if ($departmentId === '') {
            db()->execute(
                "UPDATE tickets SET department_id = NULL, updated_at = NOW() WHERE id = ?",
                [$ticketId]
            );
        } else {
            db()->execute(
                "UPDATE tickets SET department_id = ?, updated_at = NOW() WHERE id = ?",
                [$departmentId, $ticketId]
            );
        }
        
        $success = 'Department updated successfully';
        $ticket = getTicketWithMessages($ticketId);
    }
}

$staffMembers = $isAdmin ? db()->fetchAll("SELECT id, name, email FROM users WHERE is_active = 1 ORDER BY name") : [];
$departments = $isAdmin ? db()->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name") : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket['ticket_number']; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <div>
                        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
                        <h1>Ticket #<?php echo $ticket['ticket_number']; ?></h1>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo escape($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo escape($success); ?></div>
                <?php endif; ?>

                <div class="ticket-layout">
                    <div class="ticket-main">
                        <div class="ticket-header-card">
                            <h2 class="ticket-subject"><?php echo escape($ticket['subject']); ?></h2>
                            <div class="ticket-meta">
                                <div class="meta-item">
                                    <strong>From:</strong> 
                                    <?php echo escape($ticket['requester_name']); ?> 
                                    <span class="text-muted">&lt;<?php echo escape($ticket['requester_email']); ?>&gt;</span>
                                </div>
                                <div class="meta-item">
                                    <strong>Created:</strong> <?php echo formatDateTime($ticket['created_at']); ?>
                                </div>
                                <div class="meta-item">
                                    <strong>Updated:</strong> <?php echo formatDateTime($ticket['updated_at']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="messages-container">
                            <?php foreach ($ticket['messages'] as $message): ?>
                                <div class="message-card <?php echo $message['is_internal'] ? 'message-note' : ($message['type'] === 'inbound' ? 'message-inbound' : 'message-outbound'); ?>">
                                    <div class="message-header">
                                        <div class="message-from">
                                            <?php if ($message['is_internal']): ?>
                                                <span class="message-type-badge">Internal Note</span>
                                                <strong><?php echo escape($message['created_by_name'] ?? 'Unknown'); ?></strong>
                                            <?php elseif ($message['type'] === 'inbound'): ?>
                                                <span class="message-type-badge">Customer</span>
                                                <strong><?php echo escape($message['from_address']); ?></strong>
                                            <?php else: ?>
                                                <span class="message-type-badge">Reply</span>
                                                <strong><?php echo escape($message['created_by_name'] ?? $message['from_address']); ?></strong>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo formatDateTime($message['created_at']); ?>
                                        </div>
                                    </div>
                                    <div class="message-body">
                                        <?php echo nl2br(escape($message['body_text'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="reply-section">
                            <div class="reply-tabs">
                                <button class="tab-btn active" data-tab="reply">Reply to Customer</button>
                                <button class="tab-btn" data-tab="note">Add Internal Note</button>
                            </div>

                            <div class="tab-content active" id="reply-tab">
                                <form method="POST" action="" class="reply-form">
                                    <input type="hidden" name="action" value="reply">
                                    <div class="form-group">
                                        <label for="message">Your Reply</label>
                                        <textarea 
                                            id="message" 
                                            name="message" 
                                            rows="6" 
                                            class="form-control" 
                                            placeholder="Type your reply to the customer..."
                                            required
                                        ></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Send Reply</button>
                                </form>
                            </div>

                            <div class="tab-content" id="note-tab">
                                <form method="POST" action="" class="reply-form">
                                    <input type="hidden" name="action" value="note">
                                    <div class="form-group">
                                        <label for="note">Internal Note</label>
                                        <textarea 
                                            id="note" 
                                            name="note" 
                                            rows="6" 
                                            class="form-control" 
                                            placeholder="Add a note visible only to staff..."
                                            required
                                        ></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-secondary">Add Note</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-sidebar">
                        <div class="sidebar-card">
                            <h3>Ticket Details</h3>
                            
                            <div class="detail-group">
                                <label>Status</label>
                                <form method="POST" action="" class="inline-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <select name="status" class="form-control" onchange="this.form.submit()">
                                        <option value="new" <?php echo $ticket['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="solved" <?php echo $ticket['status'] === 'solved' ? 'selected' : ''; ?>>Solved</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </form>
                            </div>

                            <div class="detail-group">
                                <label>Priority</label>
                                <form method="POST" action="" class="inline-form">
                                    <input type="hidden" name="action" value="update_priority">
                                    <select name="priority" class="form-control" onchange="this.form.submit()">
                                        <option value="low" <?php echo $ticket['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="normal" <?php echo $ticket['priority'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="high" <?php echo $ticket['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                    </select>
                                </form>
                            </div>

                            <?php if ($isAdmin): ?>
                            <div class="detail-group">
                                <label>Assigned To</label>
                                <form method="POST" action="" class="inline-form">
                                    <input type="hidden" name="action" value="assign">
                                    <select name="assign_to_id" class="form-control" onchange="this.form.submit()">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($staffMembers as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" <?php echo $ticket['assigned_to_id'] == $staff['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($staff['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>

                            <div class="detail-group">
                                <label>Department</label>
                                <form method="POST" action="" class="inline-form">
                                    <input type="hidden" name="action" value="assign_department">
                                    <select name="department_id" class="form-control" onchange="this.form.submit()">
                                        <option value="">No Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo $ticket['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <?php endif; ?>

                            <div class="detail-group">
                                <label>Channel</label>
                                <div class="detail-value">
                                    <span class="badge badge-channel"><?php echo ucfirst($ticket['channel']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(targetTab + '-tab').classList.add('active');
            });
        });
    </script>
</body>
</html>
