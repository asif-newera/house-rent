<?php
/**
 * Admin Contact Messages Page
 * View and manage contact form submissions
 */

require_once '../config/config.php';
require_once 'admin_auth.php';

$pageTitle = 'Contact Messages - Admin Panel';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['message_id']) && isset($_POST['status'])) {
        $messageId = intval($_POST['message_id']);
        $status = $_POST['status'];
        
        if (in_array($status, ['new', 'read', 'replied'])) {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            $stmt->execute([$status, $messageId]);
            $_SESSION['success_message'] = 'Message status updated successfully.';
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['message_id'])) {
        $messageId = intval($_POST['message_id']);
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $_SESSION['success_message'] = 'Message deleted successfully.';
    }
    
    header('Location: messages.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter by status
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$whereClause = '';
if ($statusFilter !== 'all' && in_array($statusFilter, ['new', 'read', 'replied'])) {
    $whereClause = "WHERE status = '$statusFilter'";
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM contact_messages $whereClause";
$totalMessages = $pdo->query($countQuery)->fetch()['total'];
$totalPages = ceil($totalMessages / $perPage);

// Get messages
$query = "SELECT * FROM contact_messages $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$messages = $pdo->query($query)->fetchAll();

// Get counts for each status
$newCount = $pdo->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'")->fetch()['count'];
$readCount = $pdo->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'read'")->fetch()['count'];
$repliedCount = $pdo->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'replied'")->fetch()['count'];

require_once 'includes/header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-envelope"></i> Contact Messages</h1>
        <div class="header-actions">
            <span class="badge badge-info">Total: <?php echo $totalMessages; ?></span>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Status Filter Tabs -->
    <div class="filter-tabs">
        <a href="?status=all" class="tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            All (<?php echo $totalMessages; ?>)
        </a>
        <a href="?status=new" class="tab <?php echo $statusFilter === 'new' ? 'active' : ''; ?>">
            <i class="fas fa-circle" style="color: #ff6b6b;"></i> New (<?php echo $newCount; ?>)
        </a>
        <a href="?status=read" class="tab <?php echo $statusFilter === 'read' ? 'active' : ''; ?>">
            <i class="fas fa-circle" style="color: #ffd93d;"></i> Read (<?php echo $readCount; ?>)
        </a>
        <a href="?status=replied" class="tab <?php echo $statusFilter === 'replied' ? 'active' : ''; ?>">
            <i class="fas fa-circle" style="color: #6bcf7f;"></i> Replied (<?php echo $repliedCount; ?>)
        </a>
    </div>

    <!-- Messages List -->
    <div class="messages-container">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No messages found</h3>
                <p>There are no contact messages to display.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message-card <?php echo $msg['status']; ?>">
                    <div class="message-header">
                        <div class="message-info">
                            <h3><?php echo htmlspecialchars($msg['name']); ?></h3>
                            <span class="message-email">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?>
                            </span>
                            <?php if (!empty($msg['phone'])): ?>
                                <span class="message-phone">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($msg['phone']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="message-meta">
                            <span class="status-badge status-<?php echo $msg['status']; ?>">
                                <?php echo ucfirst($msg['status']); ?>
                            </span>
                            <span class="message-date">
                                <i class="fas fa-clock"></i> 
                                <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($msg['subject'])): ?>
                        <div class="message-subject">
                            <strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="message-body">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                    
                    <div class="message-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                            <select name="status" onchange="this.form.submit()" class="status-select">
                                <option value="new" <?php echo $msg['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="read" <?php echo $msg['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                <option value="replied" <?php echo $msg['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                            </select>
                        </form>
                        
                        <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-reply"></i> Reply
                        </a>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="page-link active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>" class="page-link"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>" class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 0;
}

.filter-tabs .tab {
    padding: 12px 24px;
    text-decoration: none;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    font-weight: 500;
}

.filter-tabs .tab:hover {
    color: #4A6BFF;
    background: #f8f9fc;
}

.filter-tabs .tab.active {
    color: #4A6BFF;
    border-bottom-color: #4A6BFF;
}

.messages-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.message-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    border-left: 4px solid #e0e0e0;
    transition: all 0.3s ease;
}

.message-card:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.message-card.new {
    border-left-color: #ff6b6b;
    background: #fff9f9;
}

.message-card.read {
    border-left-color: #ffd93d;
}

.message-card.replied {
    border-left-color: #6bcf7f;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 15px;
}

.message-info h3 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 1.2rem;
}

.message-email,
.message-phone {
    display: inline-block;
    margin-right: 15px;
    color: #666;
    font-size: 0.9rem;
}

.message-email i,
.message-phone i {
    color: #4A6BFF;
    margin-right: 5px;
}

.message-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge.status-new {
    background: #ffe0e0;
    color: #ff6b6b;
}

.status-badge.status-read {
    background: #fff8dd;
    color: #d4a800;
}

.status-badge.status-replied {
    background: #e0f7e4;
    color: #2d8f3f;
}

.message-date {
    color: #999;
    font-size: 0.85rem;
}

.message-subject {
    padding: 12px;
    background: #f8f9fc;
    border-radius: 6px;
    margin-bottom: 15px;
    color: #555;
}

.message-body {
    padding: 15px 0;
    color: #444;
    line-height: 1.6;
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
}

.message-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.status-select {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.status-select:hover {
    border-color: #4A6BFF;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #999;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #ddd;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.page-link {
    padding: 10px 15px;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    text-decoration: none;
    color: #666;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: #4A6BFF;
    color: white;
    border-color: #4A6BFF;
}

.page-link.active {
    background: #4A6BFF;
    color: white;
    border-color: #4A6BFF;
}

@media (max-width: 768px) {
    .message-header {
        flex-direction: column;
    }
    
    .message-meta {
        align-items: flex-start;
    }
    
    .message-actions {
        flex-wrap: wrap;
    }
    
    .filter-tabs {
        overflow-x: auto;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
