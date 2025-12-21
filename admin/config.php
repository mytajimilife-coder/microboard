<?php
define('IN_ADMIN', true);
require_once 'common.php';

// 설정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = $lang['csrf_token_invalid'] ?? 'CSRF token is invalid.';
    } else {
        $use_point = isset($_POST['use_point']) ? 1 : 0;
        $write_point = $_POST['write_point'] ?? 0;
        $language_mode = $_POST['language_mode'] ?? 'multilingual';
        $default_language = $_POST['default_language'] ?? 'en';
        $site_title = $_POST['site_title'] ?? 'MicroBoard';

        // 데이터베이스에 저장
        $db = getDB();

        try {
            // 기존 설정 확인
            $stmt = $db->prepare("SELECT COUNT(*) FROM mb1_config");
            $stmt->execute();
            $exists = $stmt->fetchColumn();

            if ($exists > 0) {
                // 업데이트
                $stmt = $db->prepare("UPDATE mb1_config SET
                    cf_use_point = ?,
                    cf_write_point = ?,
                    cf_language_mode = ?,
                    cf_default_language = ?,
                    cf_site_title = ?,
                    cf_copyright = ?");
            } else {
                // 삽입
                $stmt = $db->prepare("INSERT INTO mb1_config
                    (cf_use_point, cf_write_point, cf_language_mode, cf_default_language, cf_site_title, cf_copyright)
                    VALUES (?, ?, ?, ?, ?, ?)");
            }

            $stmt->execute([
                $use_point,
                $write_point,
                $language_mode,
                $default_language,
                $site_title,
                $_POST['copyright'] ?? ''
            ]);

            // 커스텀 변수 저장
            $db->exec("DELETE FROM mb1_variables"); // 기존 변수 초기화
            if (isset($_POST['var_key']) && is_array($_POST['var_key'])) {
                $stmt_var = $db->prepare("INSERT INTO mb1_variables (va_key, va_value) VALUES (?, ?)");
                foreach ($_POST['var_key'] as $index => $key) {
                    $key = trim($key);
                    $val = $_POST['var_val'][$index] ?? '';
                    if (!empty($key)) {
                        $stmt_var->execute([$key, $val]);
                    }
                }
            }

            $success = $lang['settings_saved'] ?? 'Settings have been saved successfully.';
        } catch (Exception $e) {
            $error = $lang['error_occurred'] ?? 'An error occurred while saving settings.' . $e->getMessage();
        }
    }
}

// 설정 불러오기
$db = getDB();
$config = [];
try {
    $stmt = $db->query("SELECT * FROM mb1_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$config) {
        $config = [
            'cf_use_point' => 0,
            'cf_write_point' => 0,
            'cf_language_mode' => 'multilingual',
            'cf_default_language' => 'en'
        ];
    }
} catch (Exception $e) {
    $config = [
        'cf_use_point' => 0,
        'cf_write_point' => 0,
        'cf_language_mode' => 'multilingual',
        'cf_default_language' => 'en'
    ];
}

// 커스텀 변수 가져오기
$custom_vars = [];
try {
    $stmt = $db->query("SELECT * FROM mb1_variables ORDER BY va_id ASC");
    $custom_vars = $stmt->fetchAll();
} catch (Exception $e) {
    // 테이블이 없을 경우 무시
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="admin-card">
    <h2 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color);">⚙️ <?php echo $lang['config_management'] ?? 'Configuration'; ?></h2>
    <p style="font-size: 1.1rem; color: var(--text-color); margin-bottom: 2rem;">
        <?php echo $lang['config_management_desc'] ?? 'Configure general settings for your MicroBoard installation.'; ?>
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
        <input type="hidden" name="action" value="save_config">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color);">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                <?php echo $lang['point_settings'] ?? 'Point Settings'; ?>
            </h3>

            <div style="margin-bottom: 1rem;">
                <label for="use_point" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['use_point'] ?? 'Use Points'; ?>
                </label>
                <select id="use_point" name="use_point"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                        required>
                    <option value="1" <?php echo ($config['cf_use_point'] ?? 0) == 1 ? 'selected' : ''; ?>><?php echo $lang['point_enabled'] ?? 'Enabled'; ?></option>
                    <option value="0" <?php echo ($config['cf_use_point'] ?? 0) == 0 ? 'selected' : ''; ?>><?php echo $lang['point_disabled'] ?? 'Disabled'; ?></option>
                </select>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['point_settings_desc'] ?? 'Enable or disable the point system for user activities.'; ?>
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="write_point" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['write_point'] ?? 'Points for Writing'; ?>
                </label>
                <input type="number" id="write_point" name="write_point"
                       value="<?php echo htmlspecialchars($config['cf_write_point'] ?? 0); ?>"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                       required>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['point_description'] ?? 'Points awarded for writing a post (negative value to deduct)'; ?>
                </small>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color); margin-top: 1.5rem;">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                <?php echo $lang['language_settings'] ?? 'Language Settings'; ?>
            </h3>

            <div style="margin-bottom: 1rem;">
                <label for="language_mode" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['language_mode'] ?? 'Language Mode'; ?>
                </label>
                <select id="language_mode" name="language_mode"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                        required>
                    <option value="multilingual" <?php echo ($config['cf_language_mode'] ?? 'multilingual') === 'multilingual' ? 'selected' : ''; ?>><?php echo $lang['multilingual'] ?? 'Multilingual'; ?></option>
                    <option value="single" <?php echo ($config['cf_language_mode'] ?? 'multilingual') === 'single' ? 'selected' : ''; ?>><?php echo $lang['single_language'] ?? 'Single Language'; ?></option>
                </select>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['language_mode_help'] ?? 'Choose between multilingual support or single language mode.'; ?>
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="site_title" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    Site Title
                </label>
                <input type="text" id="site_title" name="site_title"
                       value="<?php echo htmlspecialchars($config['cf_site_title'] ?? 'MicroBoard'); ?>"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                       required>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    Set the title of your site
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="copyright" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    Copyright
                </label>
                <input type="text" id="copyright" name="copyright"
                       value="<?php echo htmlspecialchars($config['cf_copyright'] ?? ''); ?>"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);">
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    Footer Copyright Text (e.g. All rights reserved.)
                </small>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="default_language" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-color);">
                    <?php echo $lang['default_language'] ?? 'Default Language'; ?>
                </label>
                <select id="default_language" name="default_language"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 1rem; background: var(--bg-color);"
                        required>
                    <option value="en" <?php echo ($config['cf_default_language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                    <option value="ko" <?php echo ($config['cf_default_language'] ?? 'en') === 'ko' ? 'selected' : ''; ?>>한국어</option>
                    <option value="ja" <?php echo ($config['cf_default_language'] ?? 'en') === 'ja' ? 'selected' : ''; ?>>日本語</option>
                    <option value="zh" <?php echo ($config['cf_default_language'] ?? 'en') === 'zh' ? 'selected' : ''; ?>>中文</option>
                </select>
                <small style="display: block; margin-top: 0.375rem; color: var(--text-light); font-size: 0.8rem;">
                    <?php echo $lang['default_language_help'] ?? 'Select the default language for your site.'; ?>
                </small>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--radius); border: 1px solid var(--border-color); margin-top: 1.5rem;">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--secondary-color); font-size: 1.1rem;">
                <?php echo $lang['custom_variables'] ?? 'Custom Variables'; ?>
            </h3>
            <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                <?php echo $lang['custom_variables_desc'] ?? '게시물이나 페이지에서 <code>{{key}}</code> 형태로 사용할 수 있는 변수를 정의합니다.'; ?>
            </p>

            <div id="variable-list">
                <?php foreach ($custom_vars as $var): ?>
                <div class="variable-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <input type="text" name="var_key[]" value="<?php echo htmlspecialchars($var['va_key']); ?>" placeholder="<?php echo $lang['variable_key_placeholder'] ?? 'Key'; ?>" style="flex: 1; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
                    <input type="text" name="var_val[]" value="<?php echo htmlspecialchars($var['va_value']); ?>" placeholder="<?php echo $lang['variable_value_placeholder'] ?? 'Value'; ?>" style="flex: 2; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
                    <button type="button" class="btn-remove-var" style="padding: 0.5rem 0.8rem; background: var(--danger-color); color: white; border: none; border-radius: var(--radius); cursor: pointer;">-</button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="btn-add-var" style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: var(--bg-tertiary); color: var(--text-color); border: 1px solid var(--border-color); border-radius: var(--radius); cursor: pointer;">
                + <?php echo $lang['add_variable'] ?? '변수 추가'; ?>
            </button>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--primary-color); color: white; border: none; border-radius: var(--radius); font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                <?php echo $lang['save_settings'] ?? 'Save Settings'; ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('variable-list');
    const addButton = document.getElementById('btn-add-var');

    // 변수 추가 함수
    addButton.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'variable-row';
        div.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';
        div.innerHTML = `
            <input type="text" name="var_key[]" placeholder="<?php echo $lang['variable_key_placeholder'] ?? 'Key'; ?>" style="flex: 1; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
            <input type="text" name="var_val[]" placeholder="<?php echo $lang['variable_value_placeholder'] ?? 'Value'; ?>" style="flex: 2; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">
            <button type="button" class="btn-remove-var" style="padding: 0.5rem 0.8rem; background: var(--danger-color); color: white; border: none; border-radius: var(--radius); cursor: pointer;">-</button>
        `;
        container.appendChild(div);
    });

    // 변수 삭제 함수 (이벤트 위임)
    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-remove-var')) {
            e.target.parentElement.remove();
        }
    });
});
</script>

</main> <!-- admin-main end -->
</div> <!-- admin-layout end -->
</body>
</html>
