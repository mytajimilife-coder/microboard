<?php
define('IN_ADMIN', true);
$admin_title_key = 'board_management';
require_once 'common.php';

$db = getDB();
$action = $_GET['action'] ?? '';
$bo_table = $_GET['bo_table'] ?? '';

// CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($action === 'delete' && $bo_table)) {
    if (!isset($_REQUEST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_REQUEST['csrf_token'])) {
        die('<div class="admin-card"><p>' . $lang['csrf_token_invalid'] . '</p></div>');
    }
}

// 게시판 생성/수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save') {
  $bo_table = $_POST['bo_table'] ?? '';
  
  // 테이블명 검증 (영문, 숫자, 언더스코어만 허용)
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $bo_table)) {
    die('<div class="admin-card"><p>' . ($lang['invalid_table_name'] ?? '잘못된 테이블명입니다.') . '</p></div>');
  }
  
  // 플러그인 목록 처리
  $bo_plugins = isset($_POST['bo_plugins']) && is_array($_POST['bo_plugins']) ? implode(',', $_POST['bo_plugins']) : '';
  
  $data = [
    'bo_table' => $bo_table,
    'bo_subject' => $_POST['bo_subject'],
    'bo_admin' => $_POST['bo_admin'],
    'bo_list_count' => (int)$_POST['bo_list_count'],
    'bo_use_comment' => isset($_POST['bo_use_comment']) ? 1 : 0,
    'bo_skin' => $_POST['bo_skin'] ?? 'default',
    'bo_plugins' => $bo_plugins
  ];
  
  // 기존 게시판인지 확인
  $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_board_config WHERE bo_table = ?");
  $stmt->execute([$bo_table]);
  $is_existing = $stmt->fetchColumn() > 0;
  
  // 게시판 설정 저장
  $sql = "REPLACE INTO mb1_board_config SET 
    bo_table = :bo_table, 
    bo_subject = :bo_subject, 
    bo_admin = :bo_admin, 
    bo_list_count = :bo_list_count, 
    bo_use_comment = :bo_use_comment,
    bo_skin = :bo_skin,
    bo_plugins = :bo_plugins";
  
  $stmt = $db->prepare($sql);
  $stmt->execute($data);
  
  // 새 게시판인 경우 테이블 생성 (기존 로직 유지)
  if (!$is_existing) {
    try {
      $write_table = "mb1_write_" . $bo_table;
      $db->exec("CREATE TABLE IF NOT EXISTS `{$write_table}` (
          `wr_id` int(11) NOT NULL AUTO_INCREMENT,
          `wr_subject` varchar(255) NOT NULL,
          `wr_content` longtext NOT NULL,
          `wr_name` varchar(50) NOT NULL,
          `wr_datetime` datetime NOT NULL,
          `wr_hit` int(11) NOT NULL DEFAULT 0,
          `wr_comment` int(11) NOT NULL DEFAULT 0,
          PRIMARY KEY (`wr_id`),
          KEY `wr_name` (`wr_name`),
          KEY `wr_datetime` (`wr_datetime`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      
      $comment_table = "mb1_comment_" . $bo_table;
      $db->exec("CREATE TABLE IF NOT EXISTS `{$comment_table}` (
          `co_id` int(11) NOT NULL AUTO_INCREMENT,
          `wr_id` int(11) NOT NULL,
          `co_content` text NOT NULL,
          `co_name` varchar(50) NOT NULL,
          `co_datetime` datetime NOT NULL,
          PRIMARY KEY (`co_id`),
          KEY `wr_id` (`wr_id`),
          KEY `co_name` (`co_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      
      $file_table = "mb1_board_file_" . $bo_table;
      $db->exec("CREATE TABLE IF NOT EXISTS `{$file_table}` (
          `bf_no` int(11) NOT NULL AUTO_INCREMENT,
          `wr_id` int(11) NOT NULL,
          `bf_source` varchar(255) NOT NULL,
          `bf_file` varchar(255) NOT NULL,
          `bf_download` int(11) NOT NULL DEFAULT 0,
          `bf_content` text,
          `bf_filesize` int(11) NOT NULL DEFAULT 0,
          `bf_datetime` datetime NOT NULL,
          PRIMARY KEY (`bf_no`),
          KEY `wr_id` (`wr_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      
    } catch (PDOException $e) {
      die('<div class="admin-card"><p>' . ($lang['table_creation_failed'] ?? '테이블 생성 실패') . ': ' . $e->getMessage() . '</p></div>');
    }
  }
  
  echo "<script>location.href='board.php';</script>";
  exit;
}

// 게시판 삭제
if ($action === 'delete' && $bo_table) {
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $bo_table)) {
    die('<div class="admin-card"><p>' . ($lang['invalid_table_name'] ?? '잘못된 테이블명입니다.') . '</p></div>');
  }
  
  try {
    $stmt = $db->prepare("DELETE FROM mb1_board_config WHERE bo_table = ?");
    $stmt->execute([$bo_table]);
    
    $write_table = "mb1_write_" . $bo_table;
    $comment_table = "mb1_comment_" . $bo_table;
    $file_table = "mb1_board_file_" . $bo_table;
    
    $db->exec("DROP TABLE IF EXISTS `{$write_table}`");
    $db->exec("DROP TABLE IF EXISTS `{$comment_table}`");
    $db->exec("DROP TABLE IF EXISTS `{$file_table}`");
    
  } catch (PDOException $e) {
    die('<div class="admin-card"><p>' . ($lang['table_deletion_failed'] ?? '테이블 삭제 실패') . ': ' . $e->getMessage() . '</p></div>');
  }
  
  echo "<script>location.href='board.php';</script>";
  exit;
}

$board = [];
if ($bo_table) {
  $stmt = $db->prepare("SELECT * FROM mb1_board_config WHERE bo_table = ?");
  $stmt->execute([$bo_table]);
  $board = $stmt->fetch();
}

$boards = $db->query("SELECT * FROM mb1_board_config ORDER BY bo_table")->fetchAll();

// 플러그인 목록 가져오기
$plugin_dir = '../plugin';
$available_plugins = [];
if (is_dir($plugin_dir)) {
    $dirs = glob($plugin_dir . '/*', GLOB_ONLYDIR);
    if ($dirs) {
        foreach ($dirs as $dir) {
            $available_plugins[] = basename($dir);
        }
    }
}
?>

<style>
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-color);
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  background: var(--bg-secondary);
  color: var(--text-color);
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-control:focus {
  border-color: var(--primary-color);
  outline: none;
}

.plugin-section {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.plugin-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-right: 1.5rem;
  margin-bottom: 0.5rem;
  cursor: pointer;
}

.action-btn {
  padding: 0.5rem 1rem;
  border-radius: var(--radius);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  transition: background 0.2s;
}

.btn-edit {
  background: var(--bg-secondary);
  color: var(--text-color);
  border: 1px solid var(--border-color);
}

.btn-edit:hover {
  background: var(--bg-tertiary);
}

.btn-delete {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger-color);
  border: 1px solid transparent;
}

.btn-delete:hover {
  background: var(--danger-color);
  color: white;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-primary:hover {
  opacity: 0.9;
}
</style>

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0; color: var(--secondary-color);"><?php echo $lang['board_list_title']; ?></h3>
        <a href="board.php?action=create" class="action-btn" style="background: var(--primary-color); color: white;">
            ➕ <?php echo $lang['create_board']; ?>
        </a>
    </div>

    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th><?php echo $lang['table_name']; ?></th>
            <th><?php echo $lang['board_name']; ?></th>
            <th><?php echo $lang['manager']; ?></th>
            <th style="text-align: center;"><?php echo $lang['list_count']; ?></th>
            <th style="text-align: center;"><?php echo $lang['use_comment']; ?></th>
            <th><?php echo $lang['skin']; ?></th>
            <th style="min-width: 140px; text-align: center;"><?php echo $lang['function']; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($boards as $b): ?>
          <tr>
            <td style="font-weight: 600; color: var(--primary-color);">
                <a href="../list.php?bo_table=<?php echo $b['bo_table']; ?>" target="_blank" style="text-decoration: none; color: inherit;">
                    <?php echo htmlspecialchars($b['bo_table']); ?> ↗️
                </a>
            </td>
            <td><?php echo htmlspecialchars($b['bo_subject']); ?></td>
            <td><?php echo htmlspecialchars($b['bo_admin']); ?></td>
            <td style="text-align: center;"><?php echo $b['bo_list_count']; ?></td>
            <td style="text-align: center;">
                <?php if ($b['bo_use_comment']): ?>
                    <span style="color: var(--success-color, #10b981);">✔</span>
                <?php else: ?>
                    <span style="color: var(--text-light);">-</span>
                <?php endif; ?>
            </td>
            <td><span style="background: var(--bg-secondary); padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><?php echo htmlspecialchars($b['bo_skin']); ?></span></td>
            <td style="text-align: center;">
              <div style="display: flex; gap: 0.5rem; justify-content: center;">
                  <a href="board.php?bo_table=<?php echo $b['bo_table']; ?>" class="action-btn btn-edit">
                      ✏️ <?php echo $lang['edit']; ?>
                  </a>
                  <a href="board.php?action=delete&bo_table=<?php echo $b['bo_table']; ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" 
                     class="action-btn btn-delete" onclick="return confirm('<?php echo $lang['delete_confirm']; ?>')">
                      🗑️
                  </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
</div>

<?php if ($action === 'create' || $bo_table): ?>
<div class="admin-card">
    <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--secondary-color);">
        <?php echo $bo_table ? "✏️ {$lang['edit']}" : "➕ {$lang['create']}"; ?>
    </h3>
    
    <form method="post">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      
      <div class="form-grid">
          <div class="form-group">
            <label><?php echo $lang['table_name_eng']; ?></label>
            <input type="text" name="bo_table" class="form-control" value="<?php echo htmlspecialchars($board['bo_table'] ?? ''); ?>" required <?php echo $bo_table ? 'readonly' : ''; ?>>
            <?php if (!$bo_table): ?>
                <small style="color: var(--text-light);"><?php echo $lang['table_name_help']; ?></small>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label><?php echo $lang['board_name']; ?></label>
            <input type="text" name="bo_subject" class="form-control" value="<?php echo htmlspecialchars($board['bo_subject'] ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label><?php echo $lang['manager']; ?></label>
            <input type="text" name="bo_admin" class="form-control" value="<?php echo htmlspecialchars($board['bo_admin'] ?? 'admin'); ?>">
          </div>
          <div class="form-group">
            <label><?php echo $lang['list_count']; ?></label>
            <input type="number" name="bo_list_count" class="form-control" value="<?php echo htmlspecialchars($board['bo_list_count'] ?? 15); ?>">
          </div>
          <div class="form-group">
            <label><?php echo $lang['skin']; ?></label>
            <select name="bo_skin" class="form-control">
              <option value="default" <?php echo ($board['bo_skin'] ?? 'default') === 'default' ? 'selected' : ''; ?>><?php echo $lang['default_skin']; ?></option>
              <option value="modern" <?php echo ($board['bo_skin'] ?? 'default') === 'modern' ? 'selected' : ''; ?>><?php echo $lang['modern_skin']; ?></option>
            </select>
          </div>
          <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 0.75rem;">
            <label class="plugin-checkbox" style="margin-bottom: 0;">
              <input type="checkbox" name="bo_use_comment" <?php echo ($board['bo_use_comment'] ?? 0) ? 'checked' : ''; ?>>
              <?php echo $lang['use_comment_label']; ?>
            </label>
          </div>
      </div>
      
      <div class="plugin-section">
        <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: var(--secondary-color);">🧩 <?php echo $lang['plugin_settings']; ?> (Plugins)</label>
        <?php if (empty($available_plugins)): ?>
            <p style="color: var(--text-light);"><?php echo $lang['no_plugins_installed']; ?></p>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
            <?php 
            $active_plugins = isset($board['bo_plugins']) ? explode(',', $board['bo_plugins']) : [];
            foreach ($available_plugins as $plugin): 
            ?>
            <label class="plugin-checkbox">
                <input type="checkbox" name="bo_plugins[]" value="<?php echo htmlspecialchars($plugin); ?>" 
                       <?php echo in_array($plugin, $active_plugins) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($plugin); ?>
            </label>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
      </div>
      
      <div style="text-align: right;">
          <a href="board.php" class="action-btn" style="background: var(--bg-secondary); color: var(--text-color); border: 1px solid var(--border-color); margin-right: 0.5rem; padding: 0.75rem 1.5rem;"><?php echo $lang['cancel']; ?></a>
          <button type="submit" class="btn-primary"><?php echo $lang['save']; ?></button>
      </div>
    </form>
</div>
<?php endif; ?>

</main>
</div>
</body>
</html>
