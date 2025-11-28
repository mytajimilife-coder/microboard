<?php
define('IN_ADMIN', true);
require_once 'common.php';

$db = getDB();
$action = $_GET['action'] ?? '';
$bo_table = $_GET['bo_table'] ?? '';

// CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($action === 'delete' && $bo_table)) {
    if (!isset($_REQUEST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_REQUEST['csrf_token'])) {
        die($lang['csrf_token_invalid']);
    }
}

// 게시판 생성/수정
if ($_POST) {
  $bo_table = $_POST['bo_table'];
  $data = [
    'bo_table' => $bo_table,
    'bo_subject' => $_POST['bo_subject'],
    'bo_admin' => $_POST['bo_admin'],
    'bo_list_count' => (int)$_POST['bo_list_count'],
    'bo_use_comment' => isset($_POST['bo_use_comment']) ? 1 : 0,
    'bo_skin' => $_POST['bo_skin'] ?? 'default'
  ];
  
  $sql = "REPLACE INTO g5_board_config SET 
    bo_table = :bo_table, 
    bo_subject = :bo_subject, 
    bo_admin = :bo_admin, 
    bo_list_count = :bo_list_count, 
    bo_use_comment = :bo_use_comment,
    bo_skin = :bo_skin";
  
  $stmt = $db->prepare($sql);
  $stmt->execute($data);
  header('Location: board.php');
  exit;
}

// 게시판 삭제
if ($action === 'delete' && $bo_table) {
  $stmt = $db->prepare("DELETE FROM g5_board_config WHERE bo_table = ?");
  $stmt->execute([$bo_table]);
  header('Location: board.php');
  exit;
}

$board = [];
if ($bo_table) {
  $stmt = $db->prepare("SELECT * FROM g5_board_config WHERE bo_table = ?");
  $stmt->execute([$bo_table]);
  $board = $stmt->fetch();
}

$boards = $db->query("SELECT * FROM g5_board_config ORDER BY bo_table")->fetchAll();
?>
<h2><?php echo $lang['board_manager']; ?></h2>
<a href="board.php?action=create" class="btn"><?php echo $lang['create_board']; ?></a>

<h3><?php echo $lang['board_list_title']; ?></h3>
<table>
  <tr>
    <th><?php echo $lang['table_name']; ?></th>
    <th><?php echo $lang['board_name']; ?></th>
    <th><?php echo $lang['manager']; ?></th>
    <th><?php echo $lang['list_count']; ?></th>
    <th><?php echo $lang['use_comment']; ?></th>
    <th><?php echo $lang['skin']; ?></th>
    <th><?php echo $lang['function']; ?></th>
  </tr>
  <?php foreach ($boards as $b): ?>
  <tr>
    <td><?php echo $b['bo_table']; ?></td>
    <td><?php echo $b['bo_subject']; ?></td>
    <td><?php echo $b['bo_admin']; ?></td>
    <td><?php echo $b['bo_list_count']; ?></td>
    <td><?php echo $b['bo_use_comment'] ? 'Y' : 'N'; ?></td>
    <td><?php echo $b['bo_skin']; ?></td>
    <td>
      <a href="board.php?bo_table=<?php echo $b['bo_table']; ?>" class="btn"><?php echo $lang['edit']; ?></a>
      <a href="board.php?action=delete&bo_table=<?php echo $b['bo_table']; ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" 
         class="btn" onclick="return confirm('<?php echo $lang['delete_confirm']; ?>')"><?php echo $lang['delete']; ?></a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<?php if ($action === 'create' || $bo_table): ?>
<h3><?php echo $lang['board']; ?> <?php echo $bo_table ? $lang['edit'] : $lang['create']; ?></h3>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
  <div>
    <label><?php echo $lang['table_name_eng']; ?>:</label>
    <input type="text" name="bo_table" value="<?php echo $board['bo_table'] ?? ''; ?>" required>
  </div>
  <div>
    <label><?php echo $lang['board_name']; ?>:</label>
    <input type="text" name="bo_subject" value="<?php echo $board['bo_subject'] ?? ''; ?>" required>
  </div>
  <div>
    <label><?php echo $lang['manager']; ?>:</label>
    <input type="text" name="bo_admin" value="<?php echo $board['bo_admin'] ?? 'admin'; ?>">
  </div>
  <div>
    <label><?php echo $lang['list_count']; ?>:</label>
    <input type="number" name="bo_list_count" value="<?php echo $board['bo_list_count'] ?? 15; ?>">
  </div>
  <div>
    <label>
      <input type="checkbox" name="bo_use_comment" <?php echo ($board['bo_use_comment'] ?? 0) ? 'checked' : ''; ?>>
      <?php echo $lang['use_comment_label']; ?>
    </label>
  </div>
  <div>
    <label><?php echo $lang['skin']; ?>:</label>
    <select name="bo_skin">
      <option value="default" <?php echo ($board['bo_skin'] ?? 'default') === 'default' ? 'selected' : ''; ?>><?php echo $lang['default_skin']; ?></option>
      <option value="modern" <?php echo ($board['bo_skin'] ?? 'default') === 'modern' ? 'selected' : ''; ?>><?php echo $lang['modern_skin']; ?></option>
    </select>
  </div>
  <button type="submit" class="btn"><?php echo $lang['save']; ?></button>
</form>
<?php endif; ?>
</body>
</html>
