<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$mb_id = $_SESSION['user'];

// 알림 테이블 생성
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `mb1_notifications` (
        `noti_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(50) NOT NULL,
        `noti_type` varchar(50) NOT NULL,
        `noti_content` text NOT NULL,
        `noti_link` varchar(500) DEFAULT NULL,
        `is_read` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`noti_id`),
        KEY `mb_id` (`mb_id`),
        KEY `is_read` (`is_read`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // 테이블이 이미 존재하면 무시
}

// 알림 읽음 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_read' && isset($_POST['noti_id'])) {
        $noti_id = intval($_POST['noti_id']);
        $stmt = $db->prepare("UPDATE mb1_notifications SET is_read = 1 WHERE noti_id = ? AND mb_id = ?");
        $stmt->execute([$noti_id, $mb_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } elseif ($_POST['action'] === 'mark_all_read') {
        $stmt = $db->prepare("UPDATE mb1_notifications SET is_read = 1 WHERE mb_id = ?");
        $stmt->execute([$mb_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } elseif ($_POST['action'] === 'delete' && isset($_POST['noti_id'])) {
        $noti_id = intval($_POST['noti_id']);
        $stmt = $db->prepare("DELETE FROM mb1_notifications WHERE noti_id = ? AND mb_id = ?");
        $stmt->execute([$noti_id, $mb_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// 알림 목록 조회
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filter = $_GET['filter'] ?? 'all';
$where = 'mb_id = ?';
$params = [$mb_id];

if ($filter === 'unread') {
    $where .= ' AND is_read = 0';
}

// 전체 개수
$stmt = $db->prepare("SELECT COUNT(*) FROM mb1_notifications WHERE $where");
$stmt->execute($params);
$total_count = $stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// 알림 목록
$stmt = $db->prepare("SELECT * FROM mb1_notifications WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// 읽지 않은 알림 개수
$stmt = $db->prepare("SELECT COUNT(*) FROM mb1_notifications WHERE mb_id = ? AND is_read = 0");
$stmt->execute([$mb_id]);
$unread_count = $stmt->fetchColumn();

include 'inc/header.php';
?>

<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
    <div class="notifications-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="margin: 0;">🔔 <?php echo $lang['notifications'] ?? '알림'; ?></h1>
        <div class="notification-controls" style="display: flex; gap: 1rem;">
            <a href="?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                <?php echo $lang['all'] ?? '전체'; ?> (<?php echo $total_count; ?>)
            </a>
            <a href="?filter=unread" class="btn btn-sm <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-secondary'; ?>">
                <?php echo $lang['unread'] ?? '읽지 않음'; ?> (<?php echo $unread_count; ?>)
            </a>
            <?php if ($unread_count > 0): ?>
                <button onclick="markAllRead()" class="btn btn-sm btn-success">
                    <?php echo $lang['mark_all_read'] ?? '모두 읽음'; ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (empty($notifications)): ?>
        <div class="empty-state" style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: var(--radius-lg);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
            <p><?php echo $lang['no_notifications'] ?? '알림이 없습니다.'; ?></p>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $noti): ?>
                <div class="notification-item <?php echo $noti['is_read'] ? 'read' : 'unread'; ?>" data-id="<?php echo $noti['noti_id']; ?>">
                    <div class="notification-content">
                        <div class="notification-type">
                            <?php
                            $icons = [
                                'comment' => '💬',
                                'like' => '❤️',
                                'mention' => '📢',
                                'system' => '⚙️',
                                'report' => '🚨'
                            ];
                            echo $icons[$noti['noti_type']] ?? '📌';
                            ?>
                        </div>
                        <div class="notification-body">
                            <p><?php echo htmlspecialchars($noti['noti_content']); ?></p>
                            <div class="notification-meta">
                                <span class="notification-time"><?php echo $noti['created_at']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <?php if ($noti['noti_link']): ?>
                            <a href="<?php echo htmlspecialchars($noti['noti_link']); ?>" class="btn btn-sm btn-primary" onclick="markAsRead(<?php echo $noti['noti_id']; ?>)">
                                <?php echo $lang['view'] ?? '보기'; ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!$noti['is_read']): ?>
                            <button onclick="markAsRead(<?php echo $noti['noti_id']; ?>)" class="btn btn-sm btn-secondary">
                                <?php echo $lang['mark_read'] ?? '읽음'; ?>
                            </button>
                        <?php endif; ?>
                        <button onclick="deleteNotification(<?php echo $noti['noti_id']; ?>)" class="btn btn-sm btn-danger">
                            <?php echo $lang['delete'] ?? '삭제'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination" style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 2rem;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php
                    $query_params = $_GET;
                    $query_params['page'] = $i;
                    $query_string = http_build_query($query_params);
                    ?>
                    <a href="?<?php echo $query_string; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s;
}

.notification-item.unread {
    background: #eff6ff;
    border-left: 4px solid var(--primary-color);
}

.notification-item:hover {
    box-shadow: var(--shadow-md);
}

.notification-content {
    display: flex;
    gap: 1rem;
    flex: 1;
}

.notification-type {
    font-size: 2rem;
}

.notification-body {
    flex: 1;
}

.notification-body p {
    margin: 0 0 0.5rem 0;
    color: var(--text-color);
}

.notification-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
}

.btn-secondary {
    background: #6b7280;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-success {
    background: #10b981;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: #dc2626;
}

.btn-danger:hover {
    background: #b91c1c;
}

@media (max-width: 768px) {
    .notification-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notification-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
function markAsRead(notiId) {
    fetch('notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=mark_read&noti_id=' + notiId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`.notification-item[data-id="${notiId}"]`);
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                location.reload();
            }
        }
    });
}

function markAllRead() {
    if (!confirm('<?php echo $lang['confirm_mark_all_read'] ?? '모든 알림을 읽음으로 표시하시겠습니까?'; ?>')) {
        return;
    }
    
    fetch('notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=mark_all_read'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function deleteNotification(notiId) {
    if (!confirm('<?php echo $lang['confirm_delete'] ?? '정말로 삭제하시겠습니까?'; ?>')) {
        return;
    }
    
    fetch('notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&noti_id=' + notiId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php include 'inc/footer.php'; ?>
