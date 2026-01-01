<?php
require_once 'config.php';

if (isLoggedIn()) {
  header('Location: list.php');
  exit;
}

// 탈퇴 완료 메시지
if (isset($_GET['withdrawn'])) {
  $error = $lang['withdraw_success'];
}

$error = $error ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF 토큰 검증
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $error = $lang['csrf_token_invalid'];
  } else {
    // 2FA 코드 검증 단계
    if (isset($_POST['2fa_code'])) {
      $username = $_SESSION['2fa_username'] ?? '';
      $code = trim($_POST['2fa_code'] ?? '');

      if (empty($username) || empty($code)) {
        $error = $lang['2fa_code_required'] ?? '2FA code is required.';
      } else {
        // 2FA 코드 검증
        $result = verifyTwoFactorCode($username, $code);

        if ($result['success']) {
          $_SESSION['user'] = $username;
          $_SESSION['login_time'] = time();
          // 2FA 세션 정보 제거
          unset($_SESSION['2fa_username']);
          unset($_SESSION['2fa_required']);
          // CSRF 토큰 재생성
          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
          header('Location: list.php');
          exit;
        } else {
          $error = $lang['invalid_2fa_code'] ?? 'Invalid 2FA code.';
        }
      }
    }
    // 기본 로그인 처리
    else {
      // 입력값 검증 및 이스케이프
      $username = trim($_POST['username'] ?? '');
      $password = $_POST['password'] ?? '';

      // 입력값 길이 제한
      if (strlen($username) > 50 || strlen($password) > 255) {
        $error = $lang['input_too_long'];
      } elseif (empty($username) || empty($password)) {
        $error = $lang['login_input_required'];
      } else {
        // 무차별 대입 공격(Brute Force) 방어
        $config = get_config();
        $limit = intval($config['cf_login_attempts_limit'] ?: 5);
        $lockout_min = intval($config['cf_login_lockout_time'] ?: 10);
        $user_ip = $_SERVER['REMOTE_ADDR'];
        
        $db = getDB();
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM mb1_login_log 
                                    WHERE lo_ip = ? AND lo_success = 0 
                                    AND lo_datetime > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt_check->execute([$user_ip, $lockout_min]);
        $failed_count = $stmt_check->fetchColumn();
        
        if ($failed_count >= $limit) {
          $error = sprintf($lang['login_locked'] ?? '로그인 시도가 너무 많습니다. %d분 후에 다시 시도해주세요.', $lockout_min);
        } else {
          // 차단 및 탈퇴 확인을 포함한 인증
          $result = verifyUserWithBlock($username, $password);

        if ($result['success']) {
          // 로그인 기록 (성공)
          $db = getDB();
          $stmt_log = $db->prepare("INSERT INTO mb1_login_log (mb_id, lo_ip, lo_ua, lo_success) VALUES (?, ?, ?, 1)");
          $stmt_log->execute([$username, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

          // 2FA가 활성화되어 있는지 확인
          if (isTwoFactorEnabled($username)) {
            // 2FA 코드 입력 페이지로 리다이렉트
            $_SESSION['2fa_username'] = $username;
            $_SESSION['2fa_required'] = true;
            header('Location: login.php?2fa=1');
            exit;
          } else {
            $_SESSION['user'] = $username;
            $_SESSION['login_time'] = time();
            // CSRF 토큰 재생성
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: list.php');
            exit;
          }
        } else {
          // 로그인 기록 (실패)
          $db = getDB();
          $stmt_log = $db->prepare("INSERT INTO mb1_login_log (mb_id, lo_ip, lo_ua, lo_success) VALUES (?, ?, ?, 0)");
          $stmt_log->execute([$username, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

          // 에러 메시지 처리
          if ($result['message'] === 'account_blocked') {
            $error = $lang['account_blocked'] . '<br><small>' . htmlspecialchars($result['reason'] ?? '') . '</small>';
          } elseif ($result['message'] === 'account_withdrawn') {
            $error = $lang['account_withdrawn'];
          } else {
            $error = $lang['login_failed'];
          }
        }
      }
    }
  }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = $lang['login'];
require_once 'inc/header.php';
?>
<style>
.login-page-wrapper {
  min-height: calc(100vh - 70px - 100px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: var(--bg-color);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.login-header {
  padding: 2.5rem 2rem 2rem;
  text-align: center;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: white !important;
}

.login-header h2 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: white !important;
}

.login-header p {
  margin: 0.5rem 0 0;
  opacity: 1;
  font-size: 0.95rem;
  color: white !important;
}

.login-body {
  padding: 2rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.9rem;
}

.form-group input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 1rem;
  transition: var(--transition);
  background: var(--bg-secondary);
  color: var(--text-color);
}

.form-group input::placeholder {
  color: var(--text-muted);
  opacity: 0.7;
}

.form-group input:focus {
  border-color: var(--primary-color);
  background: var(--bg-color);
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.login-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: white;
  border: none;
  border-radius: var(--radius);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  margin-top: 0.5rem;
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.login-btn:active {
  transform: translateY(0);
}

.error-message {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger-color);
  padding: 1rem;
  border-radius: var(--radius);
  border: 1px solid rgba(239, 68, 68, 0.2);
  font-size: 0.9rem;
  margin-bottom: 1rem;
  text-align: center;
}

.divider {
  display: flex;
  align-items: center;
  text-align: center;
  margin: 1.5rem 0;
  color: var(--text-light);
  font-size: 0.875rem;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid var(--border-color);
}

.divider span {
  padding: 0 1rem;
}

.oauth-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.oauth-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: var(--radius);
  text-decoration: none;
  font-weight: 500;
  transition: var(--transition);
  background: var(--bg-color);
}

.oauth-btn:hover {
  border-color: var(--primary-color);
  background: var(--bg-secondary);
  transform: translateY(-1px);
}

.oauth-btn.google {
  color: var(--text-color);
}

.oauth-btn.line {
  background: #00B900;
  border-color: #00B900;
  color: white;
}

.oauth-btn.line:hover {
  background: #00a000;
  border-color: #00a000;
}

.oauth-btn.apple {
  background: #000;
  border-color: #000;
  color: white;
}

.oauth-btn.apple:hover {
  background: #333;
  border-color: #333;
}

.login-footer {
  padding: 1.5rem 2rem;
  background: var(--bg-secondary);
  text-align: center;
  border-top: 1px solid var(--border-color);
}

.login-footer p {
  margin: 0;
  color: var(--text-light);
  font-size: 0.9rem;
}

.login-footer a {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
  transition: var(--transition);
}

.login-footer a:hover {
  color: var(--primary-dark);
  text-decoration: underline;
}

@media (max-width: 480px) {
  .login-card {
    border-radius: var(--radius-lg);
  }
  
  .login-header {
    padding: 2rem 1.5rem 1.5rem;
  }
  
  .login-header h2 {
    font-size: 1.75rem;
  }
  
  .login-body {
    padding: 1.5rem;
  }
}
</style>

<div class="login-page-wrapper">
  <div class="login-card">
    <div class="login-header">
      <h2>🔐 <?php echo $lang['login']; ?></h2>
      <p><?php echo $lang['welcome_to_microboard'] ?? 'Welcome to MicroBoard'; ?></p>
    </div>
    
    <div class="login-body">
      <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['2fa']) && ($_SESSION['2fa_required'] ?? false)): ?>
        <!-- 2FA 코드 입력 폼 -->
        <form method="post" class="login-form">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <input type="hidden" name="2fa_code" value="1">

          <div class="form-group">
            <label for="2fa_code"><?php echo $lang['2fa_code'] ?? 'Two-Factor Authentication Code'; ?></label>
            <input type="text" id="2fa_code" name="2fa_code" placeholder="<?php echo $lang['enter_6_digit_code'] ?? 'Enter 6-digit code'; ?>" maxlength="6" required autofocus>
            <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
              <?php echo $lang['2fa_code_help'] ?? 'Please enter the 6-digit code from your authenticator app.'; ?>
            </small>
          </div>

          <button type="submit" class="login-btn"><?php echo $lang['verify_code'] ?? 'Verify Code'; ?></button>
        </form>

        <div style="margin-top: 1.5rem; text-align: center;">
          <a href="login.php" style="color: var(--text-light); font-size: 0.9rem; text-decoration: underline;">
            <?php echo $lang['cancel_2fa'] ?? 'Cancel and go back to login'; ?>
          </a>
        </div>
      <?php else: ?>
        <!-- 기본 로그인 폼 -->
        <form method="post" class="login-form">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

          <div class="form-group">
            <label for="username"><?php echo $lang['username']; ?></label>
            <input type="text" id="username" name="username" placeholder="<?php echo $lang['username']; ?>" maxlength="50" required autofocus>
          </div>

          <div class="form-group">
            <label for="password"><?php echo $lang['password']; ?></label>
            <input type="password" id="password" name="password" placeholder="<?php echo $lang['password']; ?>" maxlength="255" required>
          </div>

          <button type="submit" class="login-btn"><?php echo $lang['login']; ?></button>
        </form>
      <?php endif; ?>
      
      <?php
      // OAuth 소셜 로그인 버튼
      require_once 'inc/oauth.php';
      $enabled_providers = getEnabledOAuthProviders();
      if (!empty($enabled_providers)):
      ?>
      <div class="divider">
        <span><?php echo $lang['oauth_login_with'] ?? '소셜 계정으로 로그인'; ?></span>
      </div>
      
      <div class="oauth-buttons">
        <?php foreach ($enabled_providers as $provider): 
          $login_url = getOAuthLoginUrl($provider);
          if ($login_url):
        ?>
          <?php if ($provider === 'google'): ?>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="oauth-btn google">
              <img src="https://www.google.com/favicon.ico" width="20" height="20" alt="Google">
              <span>Google<?php echo $lang['oauth_login_suffix'] ?? '로 로그인'; ?></span>
            </a>
          <?php elseif ($provider === 'line'): ?>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="oauth-btn line">
              <span style="font-weight: bold;">LINE</span>
              <span><?php echo $lang['oauth_login_suffix'] ?? '로 로그인'; ?></span>
            </a>
          <?php elseif ($provider === 'apple'): ?>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="oauth-btn apple">
              <img src="https://www.apple.com/favicon.ico" width="20" height="20" alt="Apple">
              <span>Apple<?php echo $lang['oauth_login_suffix'] ?? '로 로그인'; ?></span>
            </a>
          <?php endif; ?>
        <?php 
          endif;
        endforeach; 
        ?>
      </div>
      <?php endif; ?>
    </div>
    
    <div class="login-footer">
      <p><?php echo $lang['first_visit']; ?> <a href="register.php"><?php echo $lang['register']; ?></a></p>
      <p style="margin-top: 0.5rem;">
        <a href="password_reset.php" style="font-size: 0.85rem;">
          🔑 <?php echo $lang['forgot_password'] ?? '비밀번호를 잊으셨나요?'; ?>
        </a>
      </p>
    </div>
  </div>
</div>

<?php require_once 'inc/footer.php'; ?>
