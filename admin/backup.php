<?php
require_once '../config.php';
requireAdmin();

$db = getDB();
$message = '';
$error = '';

// 백업 실행
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'backup') {
        try {
            $backup_dir = __DIR__ . '/../data/backup';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0777, true);
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backup_dir . '/' . $filename;
            
            // 모든 테이블 목록 가져오기
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $output = "-- MicroBoard Database Backup\n";
            $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                // 테이블 구조
                $result = $db->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch(PDO::FETCH_NUM);
                $output .= "DROP TABLE IF EXISTS `$table`;\n";
                $output .= $row[1] . ";\n\n";
                
                // 테이블 데이터
                $result = $db->query("SELECT * FROM `$table`");
                $num_fields = $result->columnCount();
                
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $output .= "INSERT INTO `$table` VALUES(";
                    for ($i = 0; $i < $num_fields; $i++) {
                        if ($row[$i] === null) {
                            $output .= 'NULL';
                        } else {
                            $output .= $db->quote($row[$i]);
                        }
                        if ($i < $num_fields - 1) {
                            $output .= ',';
                        }
                    }
                    $output .= ");\n";
                }
                $output .= "\n";
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            file_put_contents($filepath, $output);
            $message = $lang['backup_success'] ?? "백업이 성공적으로 생성되었습니다: $filename";
            
        } catch (Exception $e) {
            $error = $lang['backup_failed'] ?? "백업 실패: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'restore' && isset($_FILES['backup_file'])) {
        try {
            $file = $_FILES['backup_file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $sql = file_get_contents($file['tmp_name']);
                
                // 트랜잭션 시작
                $db->beginTransaction();
                
                // SQL 실행
                $db->exec($sql);
                
                $db->commit();
                $message = $lang['restore_success'] ?? "데이터베이스가 성공적으로 복원되었습니다.";
            } else {
                $error = $lang['file_upload_error'] ?? "파일 업로드 오류";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = $lang['restore_failed'] ?? "복원 실패: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['filename'])) {
        $filename = basename($_POST['filename']);
        $filepath = __DIR__ . '/../data/backup/' . $filename;
        if (file_exists($filepath) && unlink($filepath)) {
            $message = $lang['delete_success'] ?? "백업 파일이 삭제되었습니다.";
        } else {
            $error = $lang['delete_failed'] ?? "파일 삭제 실패";
        }
    }
}

// 백업 파일 목록
$backup_files = [];
$backup_dir = __DIR__ . '/../data/backup';
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backup_dir . '/' . $file;
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => filemtime($filepath)
            ];
        }
    }
    // 날짜순 정렬
    usort($backup_files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

include 'common.php';
?>

<div class="admin-content">
    <h2>💾 <?php echo $lang['backup_restore'] ?? '백업 및 복원'; ?></h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3>🔄 새 백업 생성</h3>
        <p><?php echo $lang['backup_description'] ?? '현재 데이터베이스의 전체 백업을 생성합니다.'; ?></p>
        <form method="post" style="margin-top: 1rem;">
            <input type="hidden" name="action" value="backup">
            <button type="submit" class="btn btn-primary">백업 생성</button>
        </form>
    </div>
    
    <div class="card" style="margin-top: 2rem;">
        <h3>📥 백업 복원</h3>
        <p><?php echo $lang['restore_description'] ?? '백업 파일에서 데이터베이스를 복원합니다. 주의: 현재 데이터가 모두 삭제됩니다!'; ?></p>
        <form method="post" enctype="multipart/form-data" style="margin-top: 1rem;" onsubmit="return confirm('정말로 복원하시겠습니까? 현재 데이터가 모두 삭제됩니다!');">
            <input type="hidden" name="action" value="restore">
            <input type="file" name="backup_file" accept=".sql" required>
            <button type="submit" class="btn btn-warning" style="margin-top: 0.5rem;">복원 실행</button>
        </form>
    </div>
    
    <div class="card" style="margin-top: 2rem;">
        <h3>📂 백업 파일 목록</h3>
        <?php if (empty($backup_files)): ?>
            <p><?php echo $lang['no_backups'] ?? '백업 파일이 없습니다.'; ?></p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?php echo $lang['filename'] ?? '파일명'; ?></th>
                        <th><?php echo $lang['size'] ?? '크기'; ?></th>
                        <th><?php echo $lang['date'] ?? '날짜'; ?></th>
                        <th><?php echo $lang['actions'] ?? '작업'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backup_files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['name']); ?></td>
                            <td><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                            <td><?php echo date('Y-m-d H:i:s', $file['date']); ?></td>
                            <td>
                                <a href="../data/backup/<?php echo urlencode($file['name']); ?>" download class="btn btn-sm">다운로드</a>
                                <form method="post" style="display: inline;" onsubmit="return confirm('정말로 삭제하시겠습니까?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['name']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">삭제</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
