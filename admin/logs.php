<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$message = '';
$error = '';

// 로그 테이블 생성
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `mb1_admin_log` (
        `log_id` int(11) NOT NULL AUTO_INCREMENT,
        `mb_id` varchar(50) NOT NULL,
        `log_action` varchar(100) NOT NULL,
        `log_detail` text,
        `log_ip` varchar(50) NOT NULL,
        `log_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`log_id`),
        KEY `mb_id` (`mb_id`),
        KEY `log_datetime` (`log_datetime`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // 테이블이 이미 존재하면 무시
}

// 로그 기록 함수
function log_admin_action($action, $detail = '') {
    $db = getDB();
    $mb_id = $_SESSION['user'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $db->prepare("INSERT INTO mb1_admin_log (mb_id, log_action, log_detail, log_ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$mb_id, $action, $detail, $ip]);
    } catch (Exception $e) {
        // 로그 실패 시 무시 (서비스에 영향 없도록)
    }
}

// 로그 삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_logs') {
        $days = intval($_POST['days'] ?? 30);
        try {
            $stmt = $db->prepare("DELETE FROM mb1_admin_log WHERE log_datetime < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$days]);
            $message = sprintf($lang['logs_cleared'] ?? "%d일 이전 로그가 삭제되었습니다.", $days);
            log_admin_action('로그 삭제', "{$days}일 이전 로그 삭제");
        } catch (Exception $e) {
            $error = $lang['log_delete_failed'] ?? "로그 삭제 실패: " . $e->getMessage();
        }
    }
}

// 페이지네이션
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 필터
$filter_action = $_GET['filter_action'] ?? '';
$filter_user = $_GET['filter_user'] ?? '';

// 로그 조회
$where = '1=1';
$params = [];

if ($filter_action) {
    $where .= ' AND log_action LIKE ?';
    $params[] = "%$filter_action%";
}

if ($filter_user) {
    $where .= ' AND mb_id = ?';
    $params[] = $filter_user;
}

try {
    // 전체 개수
    $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_admin_log WHERE $where");
    $stmt->execute($params);
    $total_logs = $stmt->fetchColumn();
    $total_pages = ceil($total_logs / $per_page);
    
    // 로그 목록
    $stmt = $db->prepare("SELECT * FROM mb1_admin_log WHERE $where ORDER BY log_datetime DESC LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // 관리자 목록 (필터용)
    $admins = $db->query("SELECT DISTINCT mb_id FROM mb1_admin_log ORDER BY mb_id")->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $logs = [];
    $admins = [];
    $total_logs = 0;
    $total_pages = 0;
}

include 'common.php';
?>

<div class="admin-content">
    <h2>📋 <?php echo $lang['admin_logs'] ?? '관리자 활동 로그'; ?></h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="filter-section">
            <form method="get" class="filter-form">
                <div class="filter-group">
                    <label><?php echo $lang['filter_by_user'] ?? '사용자 필터'; ?>:</label>
                    <select name="filter_user">
                        <option value=""><?php echo $lang['all_users'] ?? '전체'; ?></option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo htmlspecialchars($admin); ?>" <?php echo $filter_user === $admin ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><?php echo $lang['filter_by_action'] ?? '작업 필터'; ?>:</label>
                    <input type="text" name="filter_action" value="<?php echo htmlspecialchars($filter_action); ?>" placeholder="<?php echo $lang['search_action'] ?? '작업 검색'; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo $lang['apply_filter'] ?? '필터 적용'; ?></button>
                <a href="logs.php" class="btn btn-secondary"><?php echo $lang['reset_filter'] ?? '초기화'; ?></a>
            </form>
        </div>
        
        <div class="log-stats">
            <span><?php echo $lang['total_logs'] ?? '전체 로그'; ?>: <strong><?php echo number_format($total_logs); ?></strong></span>
        </div>
        
        <?php if (empty($logs)): ?>
            <p><?php echo $lang['no_logs'] ?? '로그가 없습니다.'; ?></p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo $lang['user'] ?? '사용자'; ?></th>
                        <th><?php echo $lang['action'] ?? '작업'; ?></th>
                        <th><?php echo $lang['detail'] ?? '상세'; ?></th>
                        <th>IP</th>
                        <th><?php echo $lang['datetime'] ?? '일시'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['log_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($log['mb_id']); ?></strong></td>
                            <td><span class="badge"><?php echo htmlspecialchars($log['log_action']); ?></span></td>
                            <td><?php echo htmlspecialchars(substr($log['log_detail'], 0, 100)); ?><?php echo strlen($log['log_detail']) > 100 ? '...' : ''; ?></td>
                            <td><?php echo htmlspecialchars($log['log_ip']); ?></td>
                            <td><?php echo $log['log_datetime']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php
                        $query_params = $_GET;
                        $query_params['page'] = $i;
                        $query_string = http_build_query($query_params);
                        ?>
                        <a href="?<?php echo $query_string; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="card" style="margin-top: 2rem;">
        <h3>🗑️ <?php echo $lang['log_management'] ?? '로그 관리'; ?></h3>
        <form method="post" onsubmit="return confirm('정말로 삭제하시겠습니까?');">
            <input type="hidden" name="action" value="clear_logs">
            <div class="form-group">
                <label><?php echo $lang['delete_logs_older_than'] ?? '다음 기간보다 오래된 로그 삭제'; ?>:</label>
                <select name="days">
                    <option value="7">7일</option>
                    <option value="30" selected>30일</option>
                    <option value="90">90일</option>
                    <option value="180">180일</option>
                    <option value="365">1년</option>
                </select>
                <button type="submit" class="btn btn-danger"><?php echo $lang['delete_old_logs'] ?? '오래된 로그 삭제'; ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.card h3 {
    margin-top: 0;
    color: #1f2937;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-success {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.filter-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.filter-form {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    font-size: 0.875rem;
    color: #374151;
}

.filter-group select,
.filter-group input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    min-width: 150px;
}

.log-stats {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 6px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.admin-table th,
.admin-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.admin-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.admin-table tr:hover {
    background: #f9fafb;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

.pagination {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.5rem;
    justify-content: center;
}

.pagination a {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
}

.pagination a:hover {
    background: #f3f4f6;
}

.pagination a.active {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.btn-secondary {
    background: #6b7280;
}

.btn-secondary:hover {
    background: #4b5563;
}

.form-group {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.form-group select {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}
</style>
