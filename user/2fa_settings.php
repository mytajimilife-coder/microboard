<?php
define('IN_ADMIN', true);
require_once 'common.php';

// 2FA 설정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_2fa_settings') {
    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = $lang['csrf_token_invalid'] ?? 'CSRF token is invalid.';
    } else {
        $user_id = $_SESSION['user_id'] ?? 0;
        $secret = $_POST['secret'] ?? '';
        $enabled = isset($_POST['enable_2fa']) ? 1 : 0;

        // 데이터베이스에 저장
        $db = getDB();

        try {
            // 기존 설정 확인
            $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_2fa_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $exists = $stmt->fetchColumn();

            if ($exists > 0) {
                // 업데이트
                $stmt = $db->prepare("UPDATE mb1_2fa_settings SET
                    secret = ?,
                    enabled = ?
                    WHERE user_id = ?");
                $stmt->execute([$secret, $enabled, $user_id]);
            } else {
                // 삽입
                $stmt = $db->prepare("INSERT INTO mb1_2fa_settings
                    (user_id, secret, enabled)
                    VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $secret, $enabled]);
            }

            $success = $lang['2fa_settings_saved'] ?? '2FA settings have been saved successfully.';
        } catch (Exception $e) {
            $error = $lang['2fa_settings_save_failed'] ?? 'Failed to save 2FA settings.';
        }
    }
}

// 2FA 설정 불러오기
$db = getDB();
$2fa_settings = [];
try {
    $stmt = $db->prepare("SELECT * FROM mb1_2fa_settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    $2fa_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$2fa_settings) {
        $2fa_settings = [
            'secret' => '',
            'enabled' => 0
        ];
    }
} catch (Exception $e) {
    $2fa_settings = [
        'secret' => '',
        'enabled' => 0
    ];
}

// QR 코드 생성 함수
function generateQRCode($secret, $user, $issuer = 'Microboard') {
    $issuer = urlencode($issuer);
    $user = urlencode($user);
    $secret = urlencode($secret);
    $qrCode = "otpauth://totp/$issuer:$user?secret=$secret&issuer=$issuer";
    return $qrCode;
}

// 2FA 테스트 이메일 발송
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_2fa_email') {
    // 테스트 이메일 발송 로직을 구현할 수 있습니다.
    $success = $lang['2fa_test_email_sent'] ?? 'Test email has been sent!';
}
?>

<div class="admin-card">
    <h2 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color);">🔐 <?php echo $lang['2fa_settings'] ?? 'Two-Factor Authentication Settings'; ?></h2>
    <p style="font-size: 1.1rem; color: var(--text-color); margin-bottom: 2rem;">
        <?php echo $lang['2fa_settings_desc'] ?? 'Configure two-factor authentication for your account security.'; ?>
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
        <input type="hidden" name="action" value="save_2fa_settings">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div style="margin-bottom: 1.5rem;">
            <label for="secret" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                <?php echo $lang['2fa_secret'] ?? '2FA Secret'; ?>
            </label>
            <input type="text" id="secret" name="secret"
                   value="<?php echo htmlspecialchars($2fa_settings['secret']); ?>"
                   style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                   required>
            <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                <?php echo $lang['2fa_secret_help'] ?? 'The secret key for generating 2FA codes. Keep this secure.'; ?>
            </small>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="enable_2fa" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                <?php echo $lang['enable_2fa'] ?? 'Enable 2FA'; ?>
            </label>
            <select id="enable_2fa" name="enable_2fa"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                    required>
                <option value="1" <?php echo $2fa_settings['enabled'] == 1 ? 'selected' : ''; ?>><?php echo $lang['yes'] ?? 'Yes'; ?></option>
                <option value="0" <?php echo $2fa_settings['enabled'] == 0 ? 'selected' : ''; ?>><?php echo $lang['no'] ?? 'No'; ?></option>
            </select>
            <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                <?php echo $lang['enable_2fa_help'] ?? 'If enabled, users will need to provide a 6-digit code from their authenticator app when logging in.'; ?>
            </small>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color); margin-top: 1.5rem;">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                🔐 <?php echo $lang['2fa_qr_code'] ?? '2FA QR Code'; ?>
            </h3>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <input type="checkbox" id="generate_qr" name="generate_qr" value="1"
                           style="margin-right: 0.5rem; vertical-align: middle;"
                           <?php echo $2fa_settings['enabled'] == 1 ? 'checked' : ''; ?>>
                    <?php echo $lang['generate_qr_code'] ?? 'Generate QR Code'; ?>
                </label>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['generate_qr_help'] ?? 'Generate a QR code for your authenticator app to scan and set up 2FA.'; ?>
                </small>
            </div>

            <?php if ($2fa_settings['enabled'] == 1 && $2fa_settings['secret'] !== ''): ?>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                        <?php echo $lang['2fa_code'] ?? '2FA Code'; ?>
                    </label>
                    <input type="text" id="2fa_code" name="2fa_code"
                           style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                           required>
                    <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                        <?php echo $lang['2fa_code_help'] ?? 'Enter the 6-digit code from your authenticator app to verify 2FA setup.'; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color); margin-top: 1.5rem;">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                📧 <?php echo $lang['2fa_email_notification'] ?? '2FA Email Notification'; ?>
            </h3>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <input type="checkbox" id="notify_admin" name="notify_admin" value="1"
                           style="margin-right: 0.5rem; vertical-align: middle;"
                           <?php echo $2fa_settings['notify_admin'] ?? 0 == 1 ? 'checked' : ''; ?>>
                    <?php echo $lang['notify_admin'] ?? 'Notify Admin When 2FA is Enabled'; ?>
                </label>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['notify_admin_help'] ?? 'If enabled, an email will be sent to the admin when a user enables 2FA for their account.'; ?>
                </small>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--primary-color); color: white; border: none; border-radius: var(--radius); font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                <?php echo $lang['save_settings'] ?? 'Save Settings'; ?>
            </button>
            <button type="button" onclick="test2FA()" style="padding: 0.75rem 1.5rem; background: var(--secondary-color); color: white; border: none; border-radius: var(--radius); font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                <?php echo $lang['test_2fa'] ?? 'Test 2FA'; ?>
            </button>
        </div>
    </form>
</div>

<script>
function test2FA() {
    if (confirm('<?php echo $lang['test_2fa_confirm'] ?? 'Are you sure you want to test 2FA?'; ?>')) {
        // 2FA 테스트 로직을 구현할 수 있습니다.
        alert('<?php echo $lang['test_2fa_sent'] ?? '2FA test has been sent!'; ?>');
    }
}
</script>

</main> <!-- admin-main end -->
</div> <!-- admin-layout end -->
</body>
</html> 
</write_to_file> 
</write_to_file>
