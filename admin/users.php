<?php
define('IN_ADMIN', true);
require_once 'common.php';

// 관리자 권한 확인
if (!isAdmin()) {
  die('<p>' . $lang['admin_only'] . '</p>');
}

$error = '';
$success = '';

// 회원 탈퇴 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
  // CSRF 토큰 검증
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $error = 'CSRF 토큰이 유효하지 않습니다.';
  } else {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
      $error = $lang['no_user_id'];
    } else {
      // 회원 탈퇴 처리
      if (deleteUser($username)) {
        $success = sprintf($lang['user_deleted_success'], $username);
      } else {
        $error = sprintf($lang['user_delete_fail'], $username);
      }
    }
  }
}

// 모든 회원 조회
$users = getAllUsers();
$total_users = count($users);
?>
<h1><?php echo $lang['user_management']; ?></h1>

<?php if ($error): ?>
  <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
    <?php echo htmlspecialchars($error); ?>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
    <?php echo htmlspecialchars($success); ?>
  </div>
<?php endif; ?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
  <div>
      <strong><?php echo $lang['all_users']; ?>: <?php echo $total_users; ?></strong>
  </div>
  <div>
    <a href="index.php" class="btn" style="background: #6c757d; text-decoration: none; color: white; padding: 8px 16px; border-radius: 4px;">← <?php echo $lang['admin_home']; ?></a>
  </div>
</div>

<?php if ($users): ?>
  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
      <thead>
        <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
          <th style="padding: 12px 15px; text-align: left;"><?php echo $lang['user_id']; ?></th>
          <th style="padding: 12px 15px; text-align: left;"><?php echo $lang['join_date']; ?></th>
          <th style="padding: 12px 15px; text-align: center; width: 150px;"><?php echo $lang['action']; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr style="border-bottom: 1px solid #dee2e6; transition: background-color 0.2s ease;">
            <td style="padding: 12px 15px;">
              <strong><?php echo htmlspecialchars($user['mb_id']); ?></strong>
              <?php if ($user['mb_id'] === 'admin'): ?>
                <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;"><?php echo $lang['admin_role']; ?></span>
              <?php endif; ?>
            </td>
            <td style="padding: 12px 15px; color: #666;">
              <?php echo htmlspecialchars($user['mb_datetime'] ?? 'N/A'); ?>
            </td>
            <td style="padding: 12px 15px; text-align: center;">
              <?php if ($user['mb_id'] === 'admin'): ?>
                <span style="color: #666; font-style: italic;"><?php echo $lang['admin_protected']; ?></span>
              <?php else: ?>
                <form method="post" style="display: inline;" onsubmit="return confirm('<?php echo $lang['delete_user_confirm']; ?>');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="action" value="delete_user">
                  <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['mb_id']); ?>">
                <button type="submit" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;"><?php echo $lang['delete_user']; ?></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div style="text-align: center; padding: 40px; color: #888; background: #f8f9fa; border-radius: 8px; margin-top: 20px;">
    <p><?php echo $lang['no_users']; ?></p>
  </div>
<?php endif; ?>

<style>
  .btn:hover {
    opacity: 0.8;
  }
  
  table tbody tr:hover {
    background-color: #f1f3f4;
  }
  
  form {
    margin: 0;
  }
  
  button[type="submit"] {
    transition: background-color 0.2s ease;
  }
  
  button[type="submit"]:hover {
    background-color: #c82333;
  }
</style>
