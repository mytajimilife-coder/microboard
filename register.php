<?php
require_once 'config.php';

// 이미 로그인한 사용자는 리디렉션
if (isLoggedIn()) {
  header('Location: list.php');
  exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF 토큰 검증
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $error = $lang['csrf_token_invalid'];
  } else {
    // 입력값 검증 및 이스케이프
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // 입력값 길이 및 형식 검증
    if (strlen($username) > 20 || strlen($username) < 3) {
      $error = $lang['invalid_username'];
    } elseif (strlen($password) < 6) {
      $error = $lang['invalid_password'];
    } elseif ($password !== $password_confirm) {
      $error = $lang['password_mismatch'];
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
      $error = $lang['invalid_format'];
    } else {
      // 중복 체크
      if (isUsernameExists($username)) {
        $error = $lang['username_exists'];
      } else {
        // 회원가입 처리
        if (registerUser($username, $password)) {
          $success = $lang['register_success'];
        } else {
          $error = $lang['register_failed'];
        }
      }
    }
  }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = $lang['register'];
require_once 'inc/header.php';
?>
<style>
.register-page-wrapper {
  min-height: calc(100vh - 70px - 100px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.register-card {
  width: 100%;
  max-width: 480px;
  background: var(--bg-color);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.register-header {
  padding: 2.5rem 2rem 2rem;
  text-align: center;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white !important;
}

.register-header h2 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: white !important;
}

.register-header p {
  margin: 0.5rem 0 0;
  opacity: 1;
  font-size: 0.95rem;
  color: white !important;
}

.register-body {
  padding: 2rem;
}

.register-form {
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
  border-color: #10b981;
  background: var(--bg-color);
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.form-group small {
  display: block;
  margin-top: 0.375rem;
  color: var(--text-light);
  font-size: 0.8rem;
}

.register-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  margin-top: 0.5rem;
}

.register-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.register-btn:active {
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

.success-message {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  padding: 1rem;
  border-radius: var(--radius);
  border: 1px solid rgba(16, 185, 129, 0.2);
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
  border-color: #10b981;
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

.register-footer {
  padding: 1.5rem 2rem;
  background: var(--bg-secondary);
  text-align: center;
  border-top: 1px solid var(--border-color);
}

.register-footer p {
  margin: 0;
  color: var(--text-light);
  font-size: 0.9rem;
}

.register-footer a {
  color: #10b981;
  text-decoration: none;
  font-weight: 600;
  transition: var(--transition);
}

.register-footer a:hover {
  color: #059669;
  text-decoration: underline;
}

.password-strength {
  margin-top: 0.5rem;
  height: 4px;
  background: var(--border-color);
  border-radius: 2px;
  overflow: hidden;
  display: none;
}

.password-strength.active {
  display: block;
}

.password-strength-bar {
  height: 100%;
  transition: var(--transition);
  border-radius: 2px;
}

.password-strength-bar.weak {
  width: 33%;
  background: var(--danger-color);
}

.password-strength-bar.medium {
  width: 66%;
  background: var(--accent-color);
}

.password-strength-bar.strong {
  width: 100%;
  background: #10b981;
}

@media (max-width: 480px) {
  .register-card {
    border-radius: var(--radius-lg);
  }
  
  .register-header {
    padding: 2rem 1.5rem 1.5rem;
  }
  
  .register-header h2 {
    font-size: 1.75rem;
  }
  
  .register-body {
    padding: 1.5rem;
  }
}
</style>

<div class="register-page-wrapper">
  <div class="register-card">
    <div class="register-header">
      <h2>✨ <?php echo $lang['register']; ?></h2>
      <p><?php echo $lang['landing_description'] ?? 'Join MicroBoard Community'; ?></p>
    </div>
    
    <div class="register-body">
      <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="success-message">
          <?php echo htmlspecialchars($success); ?>
          <br><br>
          <a href="login.php" style="color: var(--success-color); font-weight: 600;"><?php echo $lang['login']; ?> →</a>
        </div>
      <?php else: ?>
      
      <form method="post" class="register-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        
        <div class="form-group">
          <label for="username"><?php echo $lang['username']; ?></label>
          <input type="text" id="username" name="username" placeholder="<?php echo $lang['username']; ?>" maxlength="20" required autofocus>
          <small><?php echo $lang['username_help']; ?></small>
        </div>

        <div class="form-group">
          <label for="nickname"><?php echo $lang['nickname'] ?? 'Nickname'; ?></label>
          <input type="text" id="nickname" name="nickname" placeholder="<?php echo $lang['nickname'] ?? 'Nickname'; ?>" maxlength="30" required>
          <small><?php echo $lang['nickname_help'] ?? 'Your display name (3-30 characters)'; ?></small>
        </div>

        <div class="form-group">
          <label for="email"><?php echo $lang['email'] ?? 'Email'; ?></label>
          <input type="email" id="email" name="email" placeholder="<?php echo $lang['email'] ?? 'Email'; ?>" maxlength="100" required>
          <small><?php echo $lang['email_help'] ?? 'Your email address for notifications'; ?></small>
        </div>

        <div class="form-group">
          <label for="password"><?php echo $lang['password']; ?></label>
          <input type="password" id="password" name="password" placeholder="<?php echo $lang['password']; ?>" maxlength="255" required>
          <small><?php echo $lang['password_help']; ?></small>
          <div class="password-strength" id="password-strength">
            <div class="password-strength-bar" id="password-strength-bar"></div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="password_confirm"><?php echo $lang['password_confirm']; ?></label>
          <input type="password" id="password_confirm" name="password_confirm" placeholder="<?php echo $lang['password_confirm']; ?>" maxlength="255" required>
        </div>
        
        <button type="submit" class="register-btn"><?php echo $lang['register']; ?></button>
      </form>
      
      <?php
      // OAuth 소셜 로그인 버튼
      require_once 'inc/oauth.php';
      $enabled_providers = getEnabledOAuthProviders();
      if (!empty($enabled_providers)):
      ?>
      <div class="divider">
        <span><?php echo $lang['oauth_register_with'] ?? '소셜 계정으로 가입'; ?></span>
      </div>
      
      <div class="oauth-buttons">
        <?php foreach ($enabled_providers as $provider): 
          $login_url = getOAuthLoginUrl($provider);
          if ($login_url):
        ?>
          <?php if ($provider === 'google'): ?>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="oauth-btn google">
              <img src="https://www.google.com/favicon.ico" width="20" height="20" alt="Google">
              <span>Google<?php echo $lang['oauth_register_suffix'] ?? '로 가입'; ?></span>
            </a>
          <?php elseif ($provider === 'line'): ?>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="oauth-btn line">
              <span style="font-weight: bold;">LINE</span>
              <span><?php echo $lang['oauth_register_suffix'] ?? '로 가입'; ?></span>
            </a>
          <?php elseif ($provider === 'apple'): ?>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="oauth-btn apple">
              <img src="https://www.apple.com/favicon.ico" width="20" height="20" alt="Apple">
              <span>Apple<?php echo $lang['oauth_register_suffix'] ?? '로 가입'; ?></span>
            </a>
          <?php endif; ?>
        <?php 
          endif;
        endforeach; 
        ?>
      </div>
      <?php endif; ?>
      
      <?php endif; ?>
    </div>
    
    <div class="register-footer">
      <p><?php echo $lang['already_member']; ?> <a href="login.php"><?php echo $lang['login']; ?></a></p>
    </div>
  </div>
</div>

<script>
// 비밀번호 강도 체크
document.getElementById('password').addEventListener('input', function() {
  const password = this.value;
  const strengthBar = document.getElementById('password-strength-bar');
  const strengthContainer = document.getElementById('password-strength');
  
  if (password.length === 0) {
    strengthContainer.classList.remove('active');
    return;
  }
  
  strengthContainer.classList.add('active');
  
  let strength = 0;
  
  // 길이 체크
  if (password.length >= 6) strength++;
  if (password.length >= 10) strength++;
  
  // 숫자 포함
  if (/\d/.test(password)) strength++;
  
  // 대소문자 포함
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
  
  // 특수문자 포함
  if (/[^a-zA-Z0-9]/.test(password)) strength++;
  
  strengthBar.className = 'password-strength-bar';
  
  if (strength <= 2) {
    strengthBar.classList.add('weak');
  } else if (strength <= 4) {
    strengthBar.classList.add('medium');
  } else {
    strengthBar.classList.add('strong');
  }
});

// 비밀번호 확인 일치 체크
document.getElementById('password_confirm').addEventListener('input', function() {
  const password = document.getElementById('password').value;
  const confirm = this.value;
  
  if (confirm.length > 0) {
    if (password === confirm) {
      this.style.borderColor = '#10b981';
    } else {
      this.style.borderColor = '#ef4444';
    }
  } else {
    this.style.borderColor = '';
  }
});
</script>

<?php require_once 'inc/footer.php'; ?>
