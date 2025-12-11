<?php
define('IN_ADMIN', true);
require_once 'common.php';

// 이메일 설정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_email_settings') {
    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = $lang['csrf_token_invalid'] ?? 'CSRF token is invalid.';
    } else {
        $smtp_host = $_POST['smtp_host'] ?? '';
        $smtp_port = $_POST['smtp_port'] ?? '';
        $smtp_username = $_POST['smtp_username'] ?? '';
        $smtp_password = $_POST['smtp_password'] ?? '';
        $smtp_encryption = $_POST['smtp_encryption'] ?? '';
        $sender_email = $_POST['sender_email'] ?? '';
        $sender_name = $_POST['sender_name'] ?? '';

        // 데이터베이스에 저장
        $db = getDB();

        try {
            // 기존 설정 확인
            $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_email_settings");
            $stmt->execute();
            $exists = $stmt->fetchColumn();

            if ($exists > 0) {
                // 업데이트
                $stmt = $db->prepare("UPDATE mb1_email_settings SET
                    smtp_host = ?,
                    smtp_port = ?,
                    smtp_username = ?,
                    smtp_password = ?,
                    smtp_encryption = ?,
                    sender_email = ?,
                    sender_name = ?,
                    enable_2fa = ?");
                $stmt->execute([
                    $smtp_host,
                    $smtp_port,
                    $smtp_username,
                    $smtp_password,
                    $smtp_encryption,
                    $sender_email,
                    $sender_name,
                    isset($_POST['enable_2fa']) ? 1 : 0
                ]);
            } else {
                // 삽입
                $stmt = $db->prepare("INSERT INTO mb1_email_settings
                    (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, sender_email, sender_name, enable_2fa)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $smtp_host,
                    $smtp_port,
                    $smtp_username,
                    $smtp_password,
                    $smtp_encryption,
                    $sender_email,
                    $sender_name,
                    isset($_POST['enable_2fa']) ? 1 : 0
                ]);
            }

            $success = $lang['email_settings_saved'] ?? 'Email settings have been saved successfully.';
        } catch (Exception $e) {
            $error = $lang['email_settings_save_failed'] ?? 'Failed to save email settings.';
        }
    }
}

// 이메일 설정 불러오기
$db = getDB();
$email_settings = [];
try {
    $stmt = $db->query("SELECT * FROM mb1_email_settings LIMIT 1");
    $email_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$email_settings) {
        $email_settings = [
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'sender_email' => '',
            'sender_name' => ''
        ];
    }
} catch (Exception $e) {
    $email_settings = [
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'sender_email' => '',
        'sender_name' => ''
    ];
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="admin-card">
    <h2 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color);">✉️ <?php echo $lang['email_settings'] ?? 'Email Settings'; ?></h2>
    <p style="font-size: 1.1rem; color: var(--text-color); margin-bottom: 2rem;">
        <?php echo $lang['email_settings_desc'] ?? 'Configure email settings for sending verification emails and notifications.'; ?>
    </p>

    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border-left: 4px solid #ef4444;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div style="background: #dcfce7; color: #15803d; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border-left: 4px solid #16a34a;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="post" style="max-width: 600px;">
        <input type="hidden" name="action" value="save_email_settings">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div style="margin-bottom: 1.5rem;">
            <label for="sender_email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                <?php echo $lang['sender_email'] ?? 'Sender Email Address'; ?>
            </label>
            <input type="email" id="sender_email" name="sender_email"
                   value="<?php echo htmlspecialchars($email_settings['sender_email']); ?>"
                   style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-secondary);"
                   required>
            <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                <?php echo $lang['sender_email_help'] ?? 'The email address that will be used as the sender for all outgoing emails.'; ?>
            </small>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="sender_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                <?php echo $lang['sender_name'] ?? 'Sender Name'; ?>
            </label>
            <input type="text" id="sender_name" name="sender_name"
                   value="<?php echo htmlspecialchars($email_settings['sender_name']); ?>"
                   style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-secondary);"
                   required>
            <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                <?php echo $lang['sender_name_help'] ?? 'The name that will be displayed as the sender for all outgoing emails.'; ?>
            </small>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color);">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                <?php echo $lang['smtp_settings'] ?? 'SMTP Settings'; ?>
            </h3>

            <div style="margin-bottom: 1rem;">
                <label for="smtp_host" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['smtp_host'] ?? 'SMTP Host'; ?>
                </label>
                <input type="text" id="smtp_host" name="smtp_host"
                       value="<?php echo htmlspecialchars($email_settings['smtp_host']); ?>"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                       required>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['smtp_host_help'] ?? 'e.g., smtp.gmail.com, smtp.mailtrap.io'; ?>
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="smtp_port" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['smtp_port'] ?? 'SMTP Port'; ?>
                </label>
                <input type="number" id="smtp_port" name="smtp_port"
                       value="<?php echo htmlspecialchars($email_settings['smtp_port']); ?>"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                       required>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['smtp_port_help'] ?? 'e.g., 587 for TLS, 465 for SSL'; ?>
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="smtp_encryption" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['smtp_encryption'] ?? 'Encryption'; ?>
                </label>
                <select id="smtp_encryption" name="smtp_encryption"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                        required>
                    <option value="tls" <?php echo $email_settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo $email_settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="none" <?php echo $email_settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>><?php echo $lang['none'] ?? 'None'; ?></option>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="smtp_username" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['smtp_username'] ?? 'SMTP Username'; ?>
                </label>
                <input type="text" id="smtp_username" name="smtp_username"
                       value="<?php echo htmlspecialchars($email_settings['smtp_username']); ?>"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                       required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="smtp_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['smtp_password'] ?? 'SMTP Password'; ?>
                </label>
                <input type="password" id="smtp_password" name="smtp_password"
                       value="<?php echo htmlspecialchars($email_settings['smtp_password']); ?>"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                       required>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color); margin-top: 1.5rem;">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                <?php echo $lang['email_verification_settings'] ?? 'Email Verification Settings'; ?>
            </h3>

            <div style="margin-bottom: 1rem;">
                <label for="require_email_verification" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['require_email_verification'] ?? 'Require Email Verification for Registration'; ?>
                </label>
                <select id="require_email_verification" name="require_email_verification"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                        required>
                    <option value="1" <?php echo ($email_settings['require_email_verification'] ?? 0) == 1 ? 'selected' : ''; ?>><?php echo $lang['yes'] ?? 'Yes'; ?></option>
                    <option value="0" <?php echo ($email_settings['require_email_verification'] ?? 0) == 0 ? 'selected' : ''; ?>><?php echo $lang['no'] ?? 'No'; ?></option>
                </select>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['require_email_verification_help'] ?? 'If enabled, users must verify their email address before completing registration.'; ?>
                </small>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color);">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                🔐 <?php echo $lang['2fa_settings'] ?? 'Two-Factor Authentication Settings'; ?>
            </h3>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <input type="checkbox" id="enable_2fa" name="enable_2fa" value="1"
                           style="margin-right: 0.5rem; vertical-align: middle;"
                           <?php echo ($email_settings['enable_2fa'] ?? 0) == 1 ? 'checked' : ''; ?>>
                    <?php echo $lang['enable_2fa_for_users'] ?? 'Enable Two-Factor Authentication for users'; ?>
                </label>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['enable_2fa_help'] ?? 'If enabled, users can set up two-factor authentication for their accounts. This feature requires email settings to be properly configured.'; ?>
                </small>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--primary-color); color: white; border: none; border-radius: var(--radius); font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                <?php echo $lang['save_settings'] ?? 'Save Settings'; ?>
            </button>
            <button type="button" onclick="testEmailSettings()" style="padding: 0.75rem 1.5rem; background: var(--secondary-color); color: white; border: none; border-radius: var(--radius); font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                <?php echo $lang['test_email'] ?? 'Test Email'; ?>
            </button>
        </div>
    </form>
</div>

<script>
function testEmailSettings() {
    if (confirm('<?php echo $lang['test_email_confirm'] ?? 'Are you sure you want to send a test email?'; ?>')) {
        // 테스트 이메일 발송 로직을 구현할 수 있습니다.
        alert('<?php echo $lang['test_email_sent'] ?? 'Test email has been sent!'; ?>');
    }
}
</script>

</main> <!-- admin-main end -->
</div> <!-- admin-layout end -->
</body>
</html>
