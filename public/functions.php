<?php
// Common Helper Functions

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getStatusBadgeClass($status) {
    $classes = [
        'new' => 'badge-new',
        'open' => 'badge-open',
        'pending' => 'badge-pending',
        'solved' => 'badge-solved',
        'closed' => 'badge-closed'
    ];
    return $classes[$status] ?? 'badge-default';
}

function getPriorityBadgeClass($priority) {
    $classes = [
        'low' => 'badge-low',
        'normal' => 'badge-normal',
        'high' => 'badge-high'
    ];
    return $classes[$priority] ?? 'badge-default';
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function formatDate($datetime) {
    return date('M d, Y', strtotime($datetime));
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return formatDateTime($datetime);
}

function generateTicketNumber() {
    $lastTicket = db()->fetchOne("SELECT MAX(ticket_number) as max_number FROM tickets");
    return ($lastTicket['max_number'] ?? 1000) + 1;
}

function getTicketWithMessages($ticketId) {
    $ticket = db()->fetchOne(
        "SELECT t.*, 
                u.name as assigned_to_name,
                d.name as department_name
         FROM tickets t
         LEFT JOIN users u ON t.assigned_to_id = u.id
         LEFT JOIN departments d ON t.department_id = d.id
         WHERE t.id = ?",
        [$ticketId]
    );

    if ($ticket) {
        $ticket['messages'] = db()->fetchAll(
            "SELECT m.*, u.name as created_by_name
             FROM messages m
             LEFT JOIN users u ON m.created_by_id = u.id
             WHERE m.ticket_id = ?
             ORDER BY m.created_at ASC",
            [$ticketId]
        );

        $ticket['tags'] = db()->fetchAll(
            "SELECT t.* FROM tags t
             JOIN ticket_tags tt ON t.id = tt.tag_id
             WHERE tt.ticket_id = ?",
            [$ticketId]
        );
    }

    return $ticket;
}
