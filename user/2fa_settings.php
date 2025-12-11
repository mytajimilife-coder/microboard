<?php
require_once '../config.php';
requireLogin();

$username = $_SESSION['user'];
$member_info = getMemberInfo($username);

        // 2FA 설정 처리
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF 토큰 검증
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $error = $lang['csrf_token_invalid'] ?? 'CSRF token is invalid.';
            } else {
                if (isset($_POST['action'])) {
                    if ($_POST['action'] === 'enable_2fa') {
                        // 메일 설정 확인
                        $db = getDB();
                        try {
                            $stmt = $db->query("SELECT enable_2fa FROM mb1_email_settings LIMIT 1");
                            $email_settings = $stmt->fetch(PDO::FETCH_ASSOC);

                            if (!$email_settings || ($email_settings['enable_2fa'] ?? 0) == 0) {
                                $error = $lang['2fa_not_enabled_by_admin'] ?? 'Two-factor authentication is not enabled by the administrator.';
                            } else {
                                // 2FA 활성화
                                $result = enableTwoFactorAuth($username);
                                if ($result['success']) {
                                    $success = $lang['2fa_enabled_success'] ?? 'Two-factor authentication has been enabled successfully.';
                                    $member_info = getMemberInfo($username); // 갱신

                                    // QR 코드 URL 생성 (간단한 구현)
                                    $qr_code_url = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/MicroBoard:" . urlencode($username) . "?secret=" . urlencode($result['secret']) . "&issuer=MicroBoard";

                                    // 메일 발송
                                    sendTwoFactorSetupEmail($username, $username . '@example.com'); // 실제 이메일 주소로 변경 필요

                                    // QR 코드 URL과 시크릿 키를 세션에 저장
                                    $_SESSION['2fa_qr_code_url'] = $qr_code_url;
                                    $_SESSION['2fa_secret_key'] = $result['secret'];
                                } else {
                                    $error = $result['message'] ?? 'Failed to enable two-factor authentication.';
                                }
                            }
                        } catch (Exception $e) {
                            $error = $lang['2fa_not_enabled_by_admin'] ?? 'Two-factor authentication is not enabled by the administrator.';
                        }
                    } elseif ($_POST['action'] === 'disable_2fa') {
                        // 2FA 비활성화
                        $result = disableTwoFactorAuth($username);
                        if ($result['success']) {
                            $success = $lang['2fa_disabled_success'] ?? 'Two-factor authentication has been disabled successfully.';
                            $member_info = getMemberInfo($username); // 갱신

                            // 세션에서 QR 코드 및 시크릿 키 제거
                            unset($_SESSION['2fa_qr_code_url']);
                            unset($_SESSION['2fa_secret_key']);
                        } else {
                            $error = $result['message'] ?? 'Failed to disable two-factor authentication.';
                        }
                    } elseif ($_POST['action'] === 'verify_2fa') {
                        // 2FA 코드 검증
                        $code = $_POST['code'] ?? '';
                        $result = verifyTwoFactorCode($username, $code);
                        if ($result['success']) {
                            $success = $lang['2fa_verification_success'] ?? 'Two-factor authentication code verified successfully.';
                            $member_info = getMemberInfo($username); // 갱신
                        } else {
                            $error = $result['message'] ?? 'Invalid two-factor authentication code.';
                        }
                    }
                }
            }
        }

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = $lang['2fa_settings'] ?? '2FA Settings';
require_once '../inc/header.php';
?>

<style>
.2fa-settings-card {
    max-width: 600px;
    margin: 0 auto;
    background: var(--bg-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    padding: 2rem;
}

.2fa-status {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 2rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}

.2fa-status.enabled {
    background: #dcfce7;
    border-color: #16a34a;
}

.2fa-status.disabled {
    background: #fee2e2;
    border-color: #ef4444;
}

.2fa-status-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.2fa-status.enabled .2fa-status-icon {
    background: #16a34a;
    color: white;
}

.2fa-status.disabled .2fa-status-icon {
    background: #ef4444;
    color: white;
}

.2fa-qr-code {
    text-align: center;
    margin: 2rem 0;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
}

.2fa-qr-code img {
    max-width: 200px;
    height: auto;
    margin: 0 auto;
    display: block;
}

.2fa-secret-key {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    margin: 1rem 0;
    word-break: break-all;
    font-family: monospace;
    font-size: 0.9rem;
}

.2fa-backup-codes {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    margin: 1rem 0;
    word-break: break-all;
    font-family: monospace;
    font-size: 0.9rem;
}

.2fa-instructions {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    margin: 1rem 0;
    color: var(--text-color);
    line-height: 1.6;
}

.2fa-form {
    margin-top: 2rem;
}

.2fa-form input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 1rem;
    background: var(--bg-secondary);
    color: var(--text-color);
    margin-bottom: 1rem;
}

.2fa-form button {
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.2fa-form button:hover {
    background: var(--primary-dark);
}

.2fa-form button:disabled {
    background: var(--bg-tertiary);
    cursor: not-allowed;
}

.2fa-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.2fa-actions button {
    flex: 1;
}

.2fa-actions .btn-danger {
    background: var(--danger-color);
}

.2fa-actions .btn-danger:hover {
    background: #dc2626;
}

.2fa-actions .btn-secondary {
    background: var(--secondary-color);
}

.2fa-actions .btn-secondary:hover {
    background: var(--secondary-dark);
}
</style>

<div class="content-wrapper">
    <div class="2fa-settings-card">
        <h2 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--secondary-color);">🔐 <?php echo $lang['2fa_settings'] ?? 'Two-Factor Authentication'; ?></h2>

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

        <div class="2fa-status <?php echo ($member_info['mb_2fa_enabled'] ?? 0) ? 'enabled' : 'disabled'; ?>">
            <div class="2fa-status-icon">
                <?php echo ($member_info['mb_2fa_enabled'] ?? 0) ? '✓' : '✗'; ?>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-color);">
                    <?php echo ($member_info['mb_2fa_enabled'] ?? 0) ? ($lang['2fa_enabled'] ?? 'Enabled') : ($lang['2fa_disabled'] ?? 'Disabled'); ?>
                </h3>
                <p style="margin: 0.25rem 0 0; color: var(--text-light); font-size: 0.9rem;">
                    <?php echo ($member_info['mb_2fa_enabled'] ?? 0) ? ($lang['2fa_enabled_desc'] ?? 'Two-factor authentication is currently enabled for your account.') : ($lang['2fa_disabled_desc'] ?? 'Two-factor authentication is currently disabled for your account.'); ?>
                </p>
            </div>
        </div>

        <?php if (!($member_info['mb_2fa_enabled'] ?? 0)): ?>
            <?php if (isset($_SESSION['2fa_qr_code_url']) && isset($_SESSION['2fa_secret_key'])): ?>
                <!-- 2FA 설정 완료 후 QR 코드 및 시크릿 키 표시 -->
                <div class="2fa-instructions">
                    <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--secondary-color);">📋 <?php echo $lang['2fa_setup_complete'] ?? '2FA Setup Complete'; ?></h3>
                    <p style="margin: 0.5rem 0 0; color: var(--text-color);">
                        <?php echo $lang['2fa_setup_complete_desc'] ?? 'Two-factor authentication has been enabled. Please scan the QR code below with your authenticator app.'; ?>
                    </p>
                </div>

                <div class="2fa-qr-code">
                    <h3 style="margin-top: 0; font-size: 1rem; color: var(--secondary-color);">📱 <?php echo $lang['scan_qr_code'] ?? 'Scan QR Code'; ?></h3>
                    <p style="margin: 0.5rem 0 1rem; color: var(--text-light); font-size: 0.9rem;">
                        <?php echo $lang['scan_qr_code_desc'] ?? 'Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)'; ?>
                    </p>
                    <img src="<?php echo htmlspecialchars($_SESSION['2fa_qr_code_url']); ?>" alt="QR Code">
                </div>

                <div class="2fa-secret-key">
                    <h3 style="margin-top: 0; font-size: 1rem; color: var(--secondary-color);">🔑 <?php echo $lang['secret_key'] ?? 'Secret Key'; ?></h3>
                    <p style="margin: 0.5rem 0 0; color: var(--text-light); font-size: 0.9rem;">
                        <?php echo $lang['secret_key_desc'] ?? 'If you cannot scan the QR code, you can manually enter this secret key into your authenticator app:'; ?>
                    </p>
                    <div style="margin-top: 1rem; padding: 0.5rem; background: var(--bg-color); border-radius: var(--radius); font-family: monospace;">
                        <?php echo htmlspecialchars($_SESSION['2fa_secret_key']); ?>
                    </div>
                </div>

                <div class="2fa-instructions" style="margin-top: 1.5rem;">
                    <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--secondary-color);">📋 <?php echo $lang['next_steps'] ?? 'Next Steps'; ?></h3>
                    <ol style="padding-left: 1.5rem; margin: 0.5rem 0 0 0;">
                        <li><?php echo $lang['next_step_1'] ?? 'Scan the QR code or enter the secret key in your authenticator app'; ?></li>
                        <li><?php echo $lang['next_step_2'] ?? 'Your authenticator app will generate a 6-digit code'; ?></li>
                        <li><?php echo $lang['next_step_3'] ?? 'Enter this code on the login page when prompted'; ?></li>
                        <li><?php echo $lang['next_step_4'] ?? 'Save your backup codes in a safe place (shown below)'; ?></li>
                    </ol>
                </div>

                <?php if (!empty($member_info['mb_2fa_backup_codes'])): ?>
                    <div class="2fa-backup-codes">
                        <h3 style="margin-top: 0; font-size: 1rem; color: var(--secondary-color);">🔑 <?php echo $lang['backup_codes'] ?? 'Backup Codes'; ?></h3>
                        <p style="margin: 0.5rem 0 0; color: var(--text-color); font-size: 0.9rem;">
                            <?php echo $lang['backup_codes_desc'] ?? 'These codes can be used to access your account if you lose access to your authenticator app. Each code can only be used once.'; ?>
                        </p>
                        <div style="margin-top: 1rem; padding: 0.5rem; background: var(--bg-color); border-radius: var(--radius);">
                            <?php
                            $backup_codes = explode("\n", trim($member_info['mb_2fa_backup_codes']));
                            foreach ($backup_codes as $code): ?>
                                <div style="padding: 0.25rem 0; font-family: monospace;"><?php echo htmlspecialchars($code); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="mypage.php" style="color: var(--primary-color); font-size: 0.9rem; text-decoration: underline; font-weight: 600;">
                        ✓ <?php echo $lang['setup_complete'] ?? 'Setup Complete - Return to My Page'; ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="2fa-instructions">
                    <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--secondary-color);">📋 <?php echo $lang['2fa_instructions'] ?? 'How to enable 2FA'; ?></h3>
                    <ol style="padding-left: 1.5rem; margin: 0.5rem 0 0 0;">
                        <li><?php echo $lang['2fa_instruction_1'] ?? 'Click the "Enable 2FA" button below'; ?></li>
                        <li><?php echo $lang['2fa_instruction_2'] ?? 'Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.)'; ?></li>
                        <li><?php echo $lang['2fa_instruction_3'] ?? 'Enter the 6-digit code from your authenticator app to verify'; ?></li>
                        <li><?php echo $lang['2fa_instruction_4'] ?? 'Save your backup codes in a safe place'; ?></li>
                    </ol>
                </div>

                <form method="post" class="2fa-form">
                    <input type="hidden" name="action" value="enable_2fa">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn" style="width: 100%;">
                        🔐 <?php echo $lang['enable_2fa'] ?? 'Enable Two-Factor Authentication'; ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="2fa-instructions">
                <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--secondary-color);">📋 <?php echo $lang['2fa_active_instructions'] ?? '2FA is currently active'; ?></h3>
                <p style="margin: 0.5rem 0 0; color: var(--text-color);">
                    <?php echo $lang['2fa_active_desc'] ?? 'Your account is protected with two-factor authentication. You will need to enter a verification code from your authenticator app when logging in.'; ?>
                </p>
            </div>

            <?php if (!empty($member_info['mb_2fa_backup_codes'])): ?>
                <div class="2fa-backup-codes">
                    <h3 style="margin-top: 0; font-size: 1rem; color: var(--secondary-color);">🔑 <?php echo $lang['backup_codes'] ?? 'Backup Codes'; ?></h3>
                    <p style="margin: 0.5rem 0 0; color: var(--text-color); font-size: 0.9rem;">
                        <?php echo $lang['backup_codes_desc'] ?? 'These codes can be used to access your account if you lose access to your authenticator app. Each code can only be used once.'; ?>
                    </p>
                    <div style="margin-top: 1rem; padding: 0.5rem; background: var(--bg-color); border-radius: var(--radius);">
                        <?php
                        $backup_codes = explode("\n", trim($member_info['mb_2fa_backup_codes']));
                        foreach ($backup_codes as $code): ?>
                            <div style="padding: 0.25rem 0; font-family: monospace;"><?php echo htmlspecialchars($code); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" class="2fa-form">
                <input type="hidden" name="action" value="verify_2fa">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <label for="code" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['verify_2fa_code'] ?? 'Verify 2FA Code'; ?>
                </label>
                <p style="margin: 0 0 1rem 0; color: var(--text-light); font-size: 0.9rem;">
                    <?php echo $lang['verify_2fa_code_desc'] ?? 'Enter a 6-digit code from your authenticator app to verify that 2FA is working correctly.'; ?>
                </p>

                <input type="text" id="code" name="code" placeholder="<?php echo $lang['enter_6_digit_code'] ?? 'Enter 6-digit code'; ?>" maxlength="6" required>

                <button type="submit" class="btn" style="width: 100%;">
                    ✓ <?php echo $lang['verify_code'] ?? 'Verify Code'; ?>
                </button>
            </form>

            <div class="2fa-actions">
                <form method="post" style="flex: 1;">
                    <input type="hidden" name="action" value="disable_2fa">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-danger" style="width: 100%;">
                        🚫 <?php echo $lang['disable_2fa'] ?? 'Disable 2FA'; ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../inc/footer.php'; ?>
