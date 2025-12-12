<?php
define('IN_ADMIN', true);
require_once 'common.php';

// 이미지 업로드 디렉토리 설정
$upload_dir = __DIR__ . '/../img/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 최대 파일 크기 설정 (5MB)
$max_file_size = 5 * 1024 * 1024;

// 현재 설정 가져오기
$current_settings = [
    'favicon' => '',
    'logo' => '',
    'favicon_size' => '32x32',
    'logo_size' => '200x50'
];

// 설정 파일에서 현재 설정 읽기
$settings_file = __DIR__ . '/../theme_settings.json';
if (file_exists($settings_file)) {
    $current_settings = json_decode(file_get_contents($settings_file), true);
    if (!is_array($current_settings)) {
        $current_settings = [
            'favicon' => '',
            'logo' => '',
            'favicon_size' => '32x32',
            'logo_size' => '200x50'
        ];
    }
}

// 이미지 업로드 처리
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = $lang['csrf_token_invalid'] ?? 'Invalid CSRF token';
    } else {
        // 파비콘 업로드 처리
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $favicon_file = $_FILES['favicon'];
            $favicon_size = $_POST['favicon_size'] ?? '32x32';

            // 파일 크기 검증
            if ($favicon_file['size'] > $max_file_size) {
                $error = $lang['upload_error_size_limit'] ?? 'File size cannot exceed 5MB';
            } else {
                // 파일 유형 검증
                $allowed_types = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg', 'image/gif'];
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $favicon_file['tmp_name']);
                finfo_close($file_info);

                if (!in_array($mime_type, $allowed_types)) {
                    $error = $lang['invalid_file_type'] ?? 'Invalid file type for favicon';
                } else {
                    // 파일 확장자 결정
                    $ext = pathinfo($favicon_file['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) === 'ico') {
                        $favicon_filename = 'favicon.ico';
                    } else {
                        $favicon_filename = 'favicon.' . $ext;
                    }

                    // 파일 이동
                    if (move_uploaded_file($favicon_file['tmp_name'], $upload_dir . $favicon_filename)) {
                        $current_settings['favicon'] = $favicon_filename;
                        $current_settings['favicon_size'] = $favicon_size;
                        $success = $lang['favicon_updated'] ?? 'Favicon updated successfully';
                    } else {
                        $error = $lang['upload_failed'] ?? 'Failed to upload favicon';
                    }
                }
            }
        }

        // 로고 업로드 처리
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logo_file = $_FILES['logo'];
            $logo_size = $_POST['logo_size'] ?? '200x50';

            // 파일 크기 검증
            if ($logo_file['size'] > $max_file_size) {
                $error = $lang['upload_error_size_limit'] ?? 'File size cannot exceed 5MB';
            } else {
                // 파일 유형 검증
                $allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $logo_file['tmp_name']);
                finfo_close($file_info);

                if (!in_array($mime_type, $allowed_types)) {
                    $error = $lang['invalid_file_type'] ?? 'Invalid file type for logo';
                } else {
                    // SVG 파일 검증
                    if (strtolower(pathinfo($logo_file['name'], PATHINFO_EXTENSION)) === 'svg') {
                        $svg_content = file_get_contents($logo_file['tmp_name']);
                        if (strpos($svg_content, '<script') !== false || strpos($svg_content, 'javascript:') !== false) {
                            $error = $lang['upload_error_malicious'] ?? 'File may contain malicious code';
                        }
                    }

                    // 파일 확장자 결정
                    $ext = pathinfo($logo_file['name'], PATHINFO_EXTENSION);
                    $logo_filename = 'logo.' . $ext;

                    // 파일 이동
                    if (move_uploaded_file($logo_file['tmp_name'], $upload_dir . $logo_filename)) {
                        $current_settings['logo'] = $logo_filename;
                        $current_settings['logo_size'] = $logo_size;
                        $success = $lang['logo_updated'] ?? 'Logo updated successfully';
                    } else {
                        $error = $lang['upload_failed'] ?? 'Failed to upload logo';
                    }
                }
            }
        }

        // 설정 저장
        $settings_json = json_encode($current_settings, JSON_PRETTY_PRINT);
        if (file_put_contents($settings_file, $settings_json) === false) {
            $error = $lang['upload_error_failed'] ?? 'Failed to save settings';
        }
    }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="admin-card">
    <h2 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color);">🎨 <?php echo $lang['theme_settings'] ?? 'Theme Settings'; ?></h2>
    <p style="font-size: 1.1rem; color: var(--text-color); margin-bottom: 2rem;">
        <?php echo $lang['theme_settings_desc'] ?? 'Update your site\'s favicon and logo images.'; ?>
    </p>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border-left: 4px solid #ef4444;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #dcfce7; color: #15803d; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border-left: 4px solid #16a34a;">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">🖼️ <?php echo $lang['favicon_settings'] ?? 'Favicon Settings'; ?></h3>

            <div style="margin-bottom: 1rem;">
                <label for="favicon" style="display: block; margin-bottom: 0.5rem; font-weight: 500;"><?php echo $lang['upload_favicon'] ?? 'Upload Favicon'; ?></label>
                <input type="file" name="favicon" id="favicon" accept=".ico,.png,.jpg,.jpeg,.gif" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
                <small style="display: block; margin-top: 0.5rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['favicon_help'] ?? 'Recommended size: 32x32 or 64x64 pixels. Supports ICO, PNG, JPG, GIF formats.'; ?>
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="favicon_size" style="display: block; margin-bottom: 0.5rem; font-weight: 500;"><?php echo $lang['favicon_size'] ?? 'Favicon Size'; ?></label>
                <select name="favicon_size" id="favicon_size" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
                    <option value="16x16" <?php echo ($current_settings['favicon_size'] ?? '') === '16x16' ? 'selected' : ''; ?>>16x16</option>
                    <option value="32x32" <?php echo ($current_settings['favicon_size'] ?? '') === '32x32' ? 'selected' : ''; ?>>32x32 (Recommended)</option>
                    <option value="64x64" <?php echo ($current_settings['favicon_size'] ?? '') === '64x64' ? 'selected' : ''; ?>>64x64</option>
                </select>
            </div>

            <?php if (!empty($current_settings['favicon'])): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                    <p style="margin: 0 0 0.5rem 0; font-weight: 500;"><?php echo $lang['current_favicon'] ?? 'Current Favicon'; ?>:</p>
                    <img src="../img/<?php echo htmlspecialchars($current_settings['favicon']); ?>" alt="Current Favicon" style="width: 32px; height: 32px; object-fit: contain;">
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8rem; color: var(--text-light);">
                        <?php echo htmlspecialchars($current_settings['favicon']); ?> (<?php echo htmlspecialchars($current_settings['favicon_size']); ?>)
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">🏷️ <?php echo $lang['logo_settings'] ?? 'Logo Settings'; ?></h3>

            <div style="margin-bottom: 1rem;">
                <label for="logo" style="display: block; margin-bottom: 0.5rem; font-weight: 500;"><?php echo $lang['upload_logo'] ?? 'Upload Logo'; ?></label>
                <input type="file" name="logo" id="logo" accept=".png,.jpg,.jpeg,.gif,.svg" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
                <small style="display: block; margin-top: 0.5rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['logo_help'] ?? 'Recommended size: 200x50 pixels. Supports PNG, JPG, GIF, SVG formats.'; ?>
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="logo_size" style="display: block; margin-bottom: 0.5rem; font-weight: 500;"><?php echo $lang['logo_size'] ?? 'Logo Size'; ?></label>
                <select name="logo_size" id="logo_size" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
                    <option value="150x40" <?php echo ($current_settings['logo_size'] ?? '') === '150x40' ? 'selected' : ''; ?>>150x40</option>
                    <option value="200x50" <?php echo ($current_settings['logo_size'] ?? '') === '200x50' ? 'selected' : ''; ?>>200x50 (Recommended)</option>
                    <option value="250x60" <?php echo ($current_settings['logo_size'] ?? '') === '250x60' ? 'selected' : ''; ?>>250x60</option>
                    <option value="300x75" <?php echo ($current_settings['logo_size'] ?? '') === '300x75' ? 'selected' : ''; ?>>300x75</option>
                </select>
            </div>

            <?php if (!empty($current_settings['logo'])): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                    <p style="margin: 0 0 0.5rem 0; font-weight: 500;"><?php echo $lang['current_logo'] ?? 'Current Logo'; ?>:</p>
                    <img src="../img/<?php echo htmlspecialchars($current_settings['logo']); ?>" alt="Current Logo" style="max-width: 200px; max-height: 50px; object-fit: contain;">
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8rem; color: var(--text-light);">
                        <?php echo htmlspecialchars($current_settings['logo']); ?> (<?php echo htmlspecialchars($current_settings['logo_size']); ?>)
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" style="background: var(--primary-color); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: var(--radius); cursor: pointer; font-size: 1rem; font-weight: 600; transition: background 0.2s;">
            <?php echo $lang['save_settings'] ?? 'Save Settings'; ?>
        </button>
    </form>
</div>

</main> <!-- admin-main end -->
</div> <!-- admin-layout end -->
</body>
</html>
