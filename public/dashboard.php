<?php
// Dashboard - Tickets List

require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

$user = getCurrentUser();
$isAdmin = $user['role'] === 'admin';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$assignedFilter = $_GET['assigned'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if (!$isAdmin) {
    $where[] = "t.assigned_to_id = ?";
    $params[] = $user['id'];
}

if ($statusFilter) {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $where[] = "t.priority = ?";
    $params[] = $priorityFilter;
}

if ($assignedFilter === 'unassigned') {
    $where[] = "t.assigned_to_id IS NULL";
} elseif ($assignedFilter === 'me' && $isAdmin) {
    $where[] = "t.assigned_to_id = ?";
    $params[] = $user['id'];
}

if ($search) {
    $where[] = "(t.subject LIKE ? OR t.requester_name LIKE ? OR t.requester_email LIKE ? OR t.ticket_number LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT t.*, 
               u.name as assigned_to_name,
               d.name as department_name,
               (SELECT COUNT(*) FROM messages WHERE ticket_id = t.id) as message_count
        FROM tickets t
        LEFT JOIN users u ON t.assigned_to_id = u.id
        LEFT JOIN departments d ON t.department_id = d.id
        $whereClause
        ORDER BY t.updated_at DESC
        LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset";

$tickets = db()->fetchAll($sql, $params);

$totalSql = "SELECT COUNT(*) as total FROM tickets t $whereClause";
$totalResult = db()->fetchOne($totalSql, $params);
$totalTickets = $totalResult['total'];
$totalPages = ceil($totalTickets / ITEMS_PER_PAGE);

$stats = db()->fetchOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'solved' THEN 1 ELSE 0 END) as solved_count,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count
    FROM tickets
    " . (!$isAdmin ? "WHERE assigned_to_id = {$user['id']}" : "")
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'bulk_update_status' && $isAdmin) {
        $ticketIds = $_POST['ticket_ids'] ?? [];
        $newStatus = $_POST['status'] ?? '';
        
        if ($ticketIds && in_array($newStatus, ['new', 'open', 'pending', 'solved', 'closed'])) {
            $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
            db()->execute(
                "UPDATE tickets SET status = ? WHERE id IN ($placeholders)",
                array_merge([$newStatus], $ticketIds)
            );
            echo json_encode(['success' => true]);
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1><?php echo $isAdmin ? 'All Tickets' : 'My Tickets'; ?></h1>
                    <?php if ($isAdmin): ?>
                        <a href="ticket-create.php" class="btn btn-primary">New Ticket</a>
                    <?php endif; ?>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Tickets</div>
                    </div>
                    <div class="stat-card stat-new">
                        <div class="stat-value"><?php echo $stats['new_count']; ?></div>
                        <div class="stat-label">New</div>
                    </div>
                    <div class="stat-card stat-open">
                        <div class="stat-value"><?php echo $stats['open_count']; ?></div>
                        <div class="stat-label">Open</div>
                    </div>
                    <div class="stat-card stat-pending">
                        <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card stat-solved">
                        <div class="stat-value"><?php echo $stats['solved_count']; ?></div>
                        <div class="stat-label">Solved</div>
                    </div>
                </div>

                <div class="filter-bar">
                    <form method="GET" action="" class="filter-form">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search tickets..." 
                            value="<?php echo escape($search); ?>"
                            class="form-control search-input"
                        >
                        
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="solved" <?php echo $statusFilter === 'solved' ? 'selected' : ''; ?>>Solved</option>
                            <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>

                        <select name="priority" class="form-control">
                            <option value="">All Priority</option>
                            <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="normal" <?php echo $priorityFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                        </select>

                        <?php if ($isAdmin): ?>
                        <select name="assigned" class="form-control">
                            <option value="">All Assignments</option>
                            <option value="me" <?php echo $assignedFilter === 'me' ? 'selected' : ''; ?>>Assigned to Me</option>
                            <option value="unassigned" <?php echo $assignedFilter === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                        </select>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-secondary">Filter</button>
                        <a href="dashboard.php" class="btn btn-outline">Clear</a>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="80">#</th>
                                <th>Subject</th>
                                <th>Requester</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Updated</th>
                                <th width="60"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No tickets found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="ticket-number">
                                            #<?php echo $ticket['ticket_number']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="ticket-subject">
                                            <?php echo escape($ticket['subject']); ?>
                                        </a>
                                        <span class="message-count"><?php echo $ticket['message_count']; ?> messages</span>
                                    </td>
                                    <td>
                                        <div class="requester-info">
                                            <div class="requester-name"><?php echo escape($ticket['requester_name']); ?></div>
                                            <div class="requester-email"><?php echo escape($ticket['requester_email']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($ticket['status']); ?>">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getPriorityBadgeClass($ticket['priority']); ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ticket['assigned_to_name']): ?>
                                            <span class="assigned-user"><?php echo escape($ticket['assigned_to_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="time-ago"><?php echo timeAgo($ticket['updated_at']); ?></span>
                                    </td>
                                    <td>
                                        <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>" class="btn btn-sm btn-outline">Previous</a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>" class="btn btn-sm btn-outline">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
