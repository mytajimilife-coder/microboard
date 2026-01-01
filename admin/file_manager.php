<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$message = '';
$error = '';

// 파일 관리 테이블 생성
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `mb1_file_manager` (
        `file_id` int(11) NOT NULL AUTO_INCREMENT,
        `file_path` varchar(500) NOT NULL,
        `file_size` bigint(20) NOT NULL,
        `file_type` varchar(100) NOT NULL,
        `upload_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `mb_id` varchar(50) DEFAULT NULL,
        `ref_table` varchar(100) DEFAULT NULL,
        `ref_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`file_id`),
        KEY `mb_id` (`mb_id`),
        KEY `upload_date` (`upload_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // 테이블이 이미 존재하면 무시
}

// 파일 스캔 및 DB 동기화
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'scan_files') {
        try {
            $file_dir = __DIR__ . '/../data/file';
            if (is_dir($file_dir)) {
                $files = glob($file_dir . '/*');
                $scanned = 0;
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $filename = basename($file);
                        $filesize = filesize($file);
                        $filetype = mime_content_type($file);
                        
                        // DB에 없으면 추가
                        $stmt = $db->prepare("INSERT IGNORE INTO mb1_file_manager (file_path, file_size, file_type) VALUES (?, ?, ?)");
                        $stmt->execute([$filename, $filesize, $filetype]);
                        $scanned++;
                    }
                }
                
                $message = sprintf($lang['files_scanned'] ?? "%d개의 파일이 스캔되었습니다.", $scanned);
            }
        } catch (Exception $e) {
            $error = $lang['scan_failed'] ?? "스캔 실패: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_file' && isset($_POST['file_id'])) {
        try {
            $file_id = intval($_POST['file_id']);
            
            // 파일 정보 조회
            $stmt = $db->prepare("SELECT file_path FROM mb1_file_manager WHERE file_id = ?");
            $stmt->execute([$file_id]);
            $file = $stmt->fetch();
            
            if ($file) {
                $filepath = __DIR__ . '/../data/file/' . $file['file_path'];
                
                // 실제 파일 삭제
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                // DB에서 삭제
                $stmt = $db->prepare("DELETE FROM mb1_file_manager WHERE file_id = ?");
                $stmt->execute([$file_id]);
                
                $message = $lang['file_deleted'] ?? "파일이 삭제되었습니다.";
            }
        } catch (Exception $e) {
            $error = $lang['delete_failed'] ?? "삭제 실패: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'cleanup_orphaned') {
        try {
            // DB에는 있지만 실제 파일이 없는 레코드 삭제
            $stmt = $db->query("SELECT file_id, file_path FROM mb1_file_manager");
            $files = $stmt->fetchAll();
            $cleaned = 0;
            
            foreach ($files as $file) {
                $filepath = __DIR__ . '/../data/file/' . $file['file_path'];
                if (!file_exists($filepath)) {
                    $stmt = $db->prepare("DELETE FROM mb1_file_manager WHERE file_id = ?");
                    $stmt->execute([$file['file_id']]);
                    $cleaned++;
                }
            }
            
            $message = sprintf($lang['orphaned_cleaned'] ?? "%d개의 고아 레코드가 정리되었습니다.", $cleaned);
        } catch (Exception $e) {
            $error = $lang['cleanup_failed'] ?? "정리 실패: " . $e->getMessage();
        }
    }
}

// 파일 통계
$total_files = 0;
$total_size = 0;
$file_types = [];

try {
    $stmt = $db->query("SELECT COUNT(*) as count, SUM(file_size) as total_size FROM mb1_file_manager");
    $stats = $stmt->fetch();
    $total_files = $stats['count'] ?? 0;
    $total_size = $stats['total_size'] ?? 0;
    
    // 파일 타입별 통계
    $stmt = $db->query("SELECT file_type, COUNT(*) as count, SUM(file_size) as size FROM mb1_file_manager GROUP BY file_type ORDER BY count DESC LIMIT 10");
    $file_types = $stmt->fetchAll();
} catch (Exception $e) {
    // 오류 무시
}

// 페이지네이션
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 파일 목록
try {
    $stmt = $db->query("SELECT COUNT(*) FROM mb1_file_manager");
    $total_count = $stmt->fetchColumn();
    $total_pages = ceil($total_count / $per_page);
    
    $stmt = $db->prepare("SELECT * FROM mb1_file_manager ORDER BY upload_date DESC LIMIT $per_page OFFSET $offset");
    $stmt->execute();
    $files = $stmt->fetchAll();
} catch (Exception $e) {
    $files = [];
    $total_pages = 0;
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

include 'common.php';
?>

<div class="admin-content">
    <h2>📁 <?php echo $lang['file_manager'] ?? '파일 관리'; ?></h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-label"><?php echo $lang['total_files'] ?? '전체 파일'; ?></div>
                <div class="stat-value"><?php echo number_format($total_files); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💾</div>
            <div class="stat-info">
                <div class="stat-label"><?php echo $lang['total_size'] ?? '전체 용량'; ?></div>
                <div class="stat-value"><?php echo formatFileSize($total_size); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <div class="stat-label"><?php echo $lang['avg_file_size'] ?? '평균 파일 크기'; ?></div>
                <div class="stat-value"><?php echo $total_files > 0 ? formatFileSize($total_size / $total_files) : '0 B'; ?></div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3>🔧 <?php echo $lang['file_operations'] ?? '파일 작업'; ?></h3>
        <div class="button-group">
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="scan_files">
                <button type="submit" class="btn btn-primary"><?php echo $lang['scan_files'] ?? '파일 스캔'; ?></button>
            </form>
            
            <form method="post" style="display: inline;" onsubmit="return confirm('고아 레코드를 정리하시겠습니까?');">
                <input type="hidden" name="action" value="cleanup_orphaned">
                <button type="submit" class="btn btn-warning"><?php echo $lang['cleanup_orphaned'] ?? '고아 레코드 정리'; ?></button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($file_types)): ?>
    <div class="card">
        <h3>📊 <?php echo $lang['file_type_stats'] ?? '파일 타입별 통계'; ?></h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo $lang['file_type'] ?? '파일 타입'; ?></th>
                    <th><?php echo $lang['count'] ?? '개수'; ?></th>
                    <th><?php echo $lang['total_size'] ?? '전체 용량'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($file_types as $type): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($type['file_type']); ?></td>
                        <td><?php echo number_format($type['count']); ?></td>
                        <td><?php echo formatFileSize($type['size']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h3>📂 <?php echo $lang['file_list'] ?? '파일 목록'; ?></h3>
        <?php if (empty($files)): ?>
            <p><?php echo $lang['no_files'] ?? '파일이 없습니다.'; ?></p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo $lang['filename'] ?? '파일명'; ?></th>
                        <th><?php echo $lang['type'] ?? '타입'; ?></th>
                        <th><?php echo $lang['size'] ?? '크기'; ?></th>
                        <th><?php echo $lang['upload_date'] ?? '업로드 일시'; ?></th>
                        <th><?php echo $lang['actions'] ?? '작업'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?php echo $file['file_id']; ?></td>
                            <td><?php echo htmlspecialchars($file['file_path']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($file['file_type']); ?></span></td>
                            <td><?php echo formatFileSize($file['file_size']); ?></td>
                            <td><?php echo $file['upload_date']; ?></td>
                            <td>
                                <a href="../data/file/<?php echo urlencode($file['file_path']); ?>" target="_blank" class="btn btn-sm">보기</a>
                                <form method="post" style="display: inline;" onsubmit="return confirm('정말로 삭제하시겠습니까?');">
                                    <input type="hidden" name="action" value="delete_file">
                                    <input type="hidden" name="file_id" value="<?php echo $file['file_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">삭제</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2.5rem;
}

.stat-info {
    flex: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

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

.button-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
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

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
}

.btn-danger {
    background: #dc2626;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn-warning {
    background: #f59e0b;
}

.btn-warning:hover {
    background: #d97706;
}
</style>
