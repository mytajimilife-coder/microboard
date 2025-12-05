<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// 회원 탈퇴 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF 토큰 검증
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $error = $lang['csrf_token_invalid'];
  } else {
    $password = $_POST['password'] ?? '';
    $username = $_SESSION['user'];
    
    if (empty($password)) {
      $error = $lang['withdraw_password_confirm'];
    } else {
      // 회원 탈퇴 처리
      if (withdrawMember($username, $password)) {
        // 세션 종료
        session_unset();
        session_destroy();
        
        // 로그인 페이지로 리다이렉트
        header('Location: ../login.php?withdrawn=1');
        exit;
      } else {
        $error = $lang['withdraw_failed'];
      }
    }
  }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 헤더 포함
$page_title = $lang['withdraw_account'];
require_once '../inc/header.php';
?>

<style>
.withdraw-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-color);
}

.warning-box {
  background: rgba(220, 38, 38, 0.1);
  border: 1px solid rgba(220, 38, 38, 0.3);
  border-radius: var(--radius);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.warning-box h3 {
  color: var(--danger-color);
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.warning-box p {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--danger-color);
}

.warning-box ul {
  color: var(--text-color);
  margin: 10px 0;
  padding-left: 1.5rem;
}

.warning-box li {
  margin-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-color);
}

.form-group input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 1rem;
  background: var(--bg-secondary);
  color: var(--text-color);
  transition: var(--transition);
}

.form-group input:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  outline: none;
}

.btn-group {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.btn {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
  text-align: center;
  transition: var(--transition);
}

.btn-danger {
  background: var(--danger-color);
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
  transform: translateY(-2px);
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text-color);
  border: 1px solid var(--border-color);
}

.btn-secondary:hover {
  background: var(--bg-tertiary);
}

.error-message {
  color: white;
  background: var(--danger-color);
  padding: 1rem;
  border-radius: var(--radius);
  margin-bottom: 1.5rem;
  text-align: center;
}
</style>

<div class="content-wrapper">
    <div class="withdraw-container">
        <h2 style="margin-top: 0; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);"><?php echo $lang['withdraw_account']; ?></h2>
        
        <?php if ($error): ?>
          <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="warning-box">
          <h3>⚠️ <?php echo $lang['withdraw_confirm_title']; ?></h3>
          <p><?php echo $lang['withdraw_confirm_message']; ?></p>
          <ul>
            <li>작성한 모든 게시글과 댓글이 삭제됩니다.</li>
            <li>포인트 및 활동 내역이 모두 삭제됩니다.</li>
            <li>탈퇴 후 같은 아이디로 재가입할 수 없습니다.</li>
            <li>탈퇴 처리 후에는 복구가 불가능합니다.</li>
          </ul>
        </div>
        
        <form method="post" onsubmit="return confirm('<?php echo $lang['withdraw_confirm_title']; ?>\n\n<?php echo $lang['withdraw_confirm_message']; ?>');">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          
          <div class="form-group">
            <label for="password"><?php echo $lang['withdraw_password_confirm']; ?></label>
            <input type="password" name="password" id="password" placeholder="<?php echo $lang['password']; ?>" required>
          </div>
          
          <div class="btn-group">
            <a href="mypage.php" class="btn btn-secondary"><?php echo $lang['cancel']; ?></a>
            <button type="submit" class="btn btn-danger">🗑️ <?php echo $lang['withdraw_account']; ?></button>
          </div>
        </form>
    </div>
</div>

<?php require_once '../inc/footer.php'; ?>
