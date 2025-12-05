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
    // 입력값 검증 및 이스케이프
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 입력값 길이 제한
    if (strlen($username) > 50 || strlen($password) > 255) {
      $error = $lang['input_too_long'];
    } elseif (empty($username) || empty($password)) {
      $error = $lang['login_input_required'];
    } else {
      // 차단 및 탈퇴 확인을 포함한 인증
      $result = verifyUserWithBlock($username, $password);
      
      if ($result['success']) {
        $_SESSION['user'] = $username;
        $_SESSION['login_time'] = time();
        // CSRF 토큰 재생성
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: list.php');
        exit;
      } else {
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

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = $lang['login'];
require_once 'inc/header.php';
?>
<div class="login-container">
  <div class="lang-selector" style="position: absolute; top: 20px; right: 20px; display: flex; gap: 8px;">
    <?php
    $lang_code = $_SESSION['lang'] ?? 'ko';
    $langs = ['ko' => '🇰🇷', 'en' => '🇺🇸', 'ja' => '🇯🇵', 'zh' => '🇨🇳'];
    foreach ($langs as $code => $flag) {
        $params = $_GET;
        $params['lang'] = $code;
        $url = '?' . http_build_query($params);
        $opacity = ($lang_code === $code) ? '1' : '0.4';
        echo "<a href=\"{$url}\" style=\"text-decoration: none; opacity: {$opacity}; margin-left: 10px; font-size: 1.5em; filter: grayscale(" . ($lang_code === $code ? '0' : '1') . ");\">{$flag}</a>";
    }
    ?>
  </div>
  <h2><?php echo $lang['login']; ?></h2>
  <?php if ($error): ?>
    <p class="error"><?php echo $error; ?></p>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="text" name="username" placeholder="<?php echo $lang['username']; ?>" maxlength="50" required>
    <input type="password" name="password" placeholder="<?php echo $lang['password']; ?>" maxlength="255" required>
    <button type="submit"><?php echo $lang['login']; ?></button>
  </form>
  <p><?php echo $lang['test']; ?>: admin / admin</p>
  
  <?php
  // OAuth 소셜 로그인 버튼
  require_once 'inc/oauth.php';
  $enabled_providers = getEnabledOAuthProviders();
  if (!empty($enabled_providers)):
  ?>
  <div style="margin-top: 30px; padding: 20px; border-top: 1px solid #ddd;">
    <p style="text-align: center; color: #666; margin-bottom: 15px;"><?php echo $lang['oauth_login_with'] ?? '소셜 계정으로 로그인'; ?></p>
    <div style="display: flex; flex-direction: column; gap: 10px;">
      <?php foreach ($enabled_providers as $provider): 
        $login_url = getOAuthLoginUrl($provider);
        if ($login_url):
      ?>
        <?php if ($provider === 'google'): ?>
          <a href="<?php echo htmlspecialchars($login_url); ?>" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: #fff; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.2s;">
            <img src="https://www.google.com/favicon.ico" width="20" height="20" alt="Google">
            <span>Google<?php echo $lang['oauth_login_suffix'] ?? '로 로그인'; ?></span>
          </a>
        <?php elseif ($provider === 'line'): ?>
          <a href="<?php echo htmlspecialchars($login_url); ?>" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: #00B900; border: 1px solid #00B900; border-radius: 4px; text-decoration: none; color: white; font-weight: 500; transition: all 0.2s;">
            <span style="font-weight: bold;">LINE</span>
            <span><?php echo $lang['oauth_login_suffix'] ?? '로 로그인'; ?></span>
          </a>
        <?php elseif ($provider === 'apple'): ?>
          <a href="<?php echo htmlspecialchars($login_url); ?>" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: #000; border: 1px solid #000; border-radius: 4px; text-decoration: none; color: white; font-weight: 500; transition: all 0.2s;">
            <img src="https://www.apple.com/favicon.ico" width="20" height="20" alt="Apple">
            <span>Apple<?php echo $lang['oauth_login_suffix'] ?? '로 로그인'; ?></span>
          </a>
        <?php endif; ?>
      <?php 
        endif;
      endforeach; 
      ?>
    </div>
  </div>
  <?php endif; ?>
  
  <div style="margin-top: 30px; padding: 15px; border-top: 1px solid #ddd; text-align: center;">
    <p><?php echo $lang['first_visit']; ?> <a href="register.php" style="color: #28a745; text-decoration: none; font-weight: bold;"><?php echo $lang['register']; ?></a></p>
  </div>
</div>
<?php require_once 'inc/footer.php'; ?>
