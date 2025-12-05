<?php
define('IN_ADMIN', true);
$admin_title_key = 'oauth_settings';
require_once 'common.php';

// 관리자 권한 확인
if (!isAdmin()) {
    die('<div class="admin-card"><p>' . $lang['admin_only'] . '</p></div>');
}

$db = getDB();
$success = '';
$error = '';

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['provider'])) {
    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = $lang['csrf_token_invalid'];
    } else {
        $provider = $_POST['provider'] ?? '';
        $client_id = trim($_POST['client_id'] ?? '');
        $client_secret = trim($_POST['client_secret'] ?? '');
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        
        if (in_array($provider, ['google', 'line', 'apple'])) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO mb1_oauth_config (provider, client_id, client_secret, enabled) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    client_id = VALUES(client_id),
                    client_secret = VALUES(client_secret),
                    enabled = VALUES(enabled)
                ");
                $stmt->execute([$provider, $client_id, $client_secret, $enabled]);
                $success = $lang['settings_saved'];
            } catch (Exception $e) {
                $error = $lang['error_occurred'] . ': ' . $e->getMessage();
            }
        }
    }
}

// 현재 설정 가져오기
$oauth_configs = [];
$stmt = $db->query("SELECT * FROM mb1_oauth_config");
while ($row = $stmt->fetch()) {
    $oauth_configs[$row['provider']] = $row;
}
?>

<style>
.oauth-card {
    transition: transform 0.2s;
}
.oauth-card:hover {
    transform: translateY(-2px);
}
.oauth-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}
.oauth-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--secondary-color);
}
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
}
.status-configured {
    background: var(--success-color, #10b981);
    color: white;
}
.status-not-configured {
    background: var(--bg-tertiary);
    color: var(--text-muted);
}
.info-box {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    color: var(--text-color);
}
.info-box a {
    color: var(--primary-color);
    text-decoration: underline;
}
</style>

<?php if ($error): ?>
<div style="background: var(--danger-color); color: white; padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem;">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div style="background: var(--success-color); color: white; padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem;">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="info-box">
    <h3 style="margin-top: 0; color: #d97706;">📘 <?php echo $lang['oauth_setup_guide']; ?></h3>
    <ul style="margin: 0.5rem 0 1rem 1.5rem;">
        <li><strong>Google:</strong> <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> - OAuth 2.0 클라이언트 ID 생성</li>
        <li><strong>LINE:</strong> <a href="https://developers.line.biz/console/" target="_blank">LINE Developers Console</a> - 채널 생성 및 설정</li>
        <li><strong>Apple:</strong> <a href="https://developer.apple.com/account/" target="_blank">Apple Developer</a> - Sign in with Apple 설정</li>
    </ul>
    <p style="margin-bottom: 0;">
        <strong>🔗 <?php echo $lang['oauth_callback_url']; ?>:</strong> 
        <code style="background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px;"><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/oauth_callback.php'; ?></code>
    </p>
</div>

<div style="display: grid; gap: 2rem;">

<!-- Google OAuth 설정 -->
<?php 
$google_config = $oauth_configs['google'] ?? [];
$is_configured = !empty($google_config['client_id']) && !empty($google_config['client_secret']) && $google_config['enabled'];
?>
<div class="admin-card oauth-card">
    <div class="oauth-header">
        <div class="oauth-title">
            <img src="https://www.google.com/favicon.ico" width="24" height="24" alt="Google">
            Google OAuth
        </div>
        <span class="status-badge <?php echo $is_configured ? 'status-configured' : 'status-not-configured'; ?>">
            <?php echo $is_configured ? '✓ ' . $lang['oauth_configured'] : '⚠ ' . $lang['oauth_not_configured']; ?>
        </span>
    </div>
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="provider" value="google">
        
        <div style="display: grid; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['oauth_client_id']; ?></label>
                <input type="text" name="client_id" value="<?php echo htmlspecialchars($oauth_configs['google']['client_id'] ?? ''); ?>" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-color);">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['oauth_client_secret']; ?></label>
                <input type="text" name="client_secret" value="<?php echo htmlspecialchars($oauth_configs['google']['client_secret'] ?? ''); ?>" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-color);">
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                <input type="checkbox" name="enabled" <?php echo ($oauth_configs['google']['enabled'] ?? 0) ? 'checked' : ''; ?> style="width: 1.2rem; height: 1.2rem;">
                <?php echo $lang['oauth_enabled']; ?>
            </label>
            <button type="submit" class="btn-primary" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius); font-weight: 600; cursor: pointer;">
                💾 <?php echo $lang['save']; ?>
            </button>
        </div>
    </form>
</div>

<!-- LINE OAuth 설정 -->
<?php 
$line_config = $oauth_configs['line'] ?? [];
$is_configured = !empty($line_config['client_id']) && !empty($line_config['client_secret']) && $line_config['enabled'];
?>
<div class="admin-card oauth-card">
    <div class="oauth-header">
        <div class="oauth-title">
            <span style="color: #00B900; font-weight: 800;">LINE</span> OAuth
        </div>
        <span class="status-badge <?php echo $is_configured ? 'status-configured' : 'status-not-configured'; ?>">
            <?php echo $is_configured ? '✓ ' . $lang['oauth_configured'] : '⚠ ' . $lang['oauth_not_configured']; ?>
        </span>
    </div>
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="provider" value="line">
        
        <div style="display: grid; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['oauth_client_id']; ?> (Channel ID)</label>
                <input type="text" name="client_id" value="<?php echo htmlspecialchars($oauth_configs['line']['client_id'] ?? ''); ?>" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-color);">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['oauth_client_secret']; ?> (Channel Secret)</label>
                <input type="text" name="client_secret" value="<?php echo htmlspecialchars($oauth_configs['line']['client_secret'] ?? ''); ?>" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-color);">
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                <input type="checkbox" name="enabled" <?php echo ($oauth_configs['line']['enabled'] ?? 0) ? 'checked' : ''; ?> style="width: 1.2rem; height: 1.2rem;">
                <?php echo $lang['oauth_enabled']; ?>
            </label>
            <button type="submit" class="btn-primary" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius); font-weight: 600; cursor: pointer;">
                💾 <?php echo $lang['save']; ?>
            </button>
        </div>
    </form>
</div>

<!-- Apple OAuth 설정 -->
<?php 
$apple_config = $oauth_configs['apple'] ?? [];
$is_configured = !empty($apple_config['client_id']) && !empty($apple_config['client_secret']) && $apple_config['enabled'];
?>
<div class="admin-card oauth-card">
    <div class="oauth-header">
        <div class="oauth-title">
            <img src="https://www.apple.com/favicon.ico" width="24" height="24" alt="Apple">
            Apple OAuth
        </div>
        <span class="status-badge <?php echo $is_configured ? 'status-configured' : 'status-not-configured'; ?>">
            <?php echo $is_configured ? '✓ ' . $lang['oauth_configured'] : '⚠ ' . $lang['oauth_not_configured']; ?>
        </span>
    </div>
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="provider" value="apple">
        
        <div style="display: grid; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['oauth_client_id']; ?> (Service ID)</label>
                <input type="text" name="client_id" value="<?php echo htmlspecialchars($oauth_configs['apple']['client_id'] ?? ''); ?>" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-color);">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php echo $lang['oauth_client_secret']; ?> (Team ID)</label>
                <input type="text" name="client_secret" value="<?php echo htmlspecialchars($oauth_configs['apple']['client_secret'] ?? ''); ?>" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-color);">
                <small style="color: var(--text-light); display: block; margin-top: 0.25rem;"><?php echo $lang['apple_key_help']; ?></small>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                <input type="checkbox" name="enabled" <?php echo ($oauth_configs['apple']['enabled'] ?? 0) ? 'checked' : ''; ?> style="width: 1.2rem; height: 1.2rem;">
                <?php echo $lang['oauth_enabled']; ?>
            </label>
            <button type="submit" class="btn-primary" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius); font-weight: 600; cursor: pointer;">
                💾 <?php echo $lang['save']; ?>
            </button>
        </div>
    </form>
</div>

</div>

</main>
</div>
</body>
</html>
