<?php
define('IN_ADMIN', true);
$admin_title_key = 'config_management';
require_once 'common.php';

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_data = [];
    
    // 포인트 설정
    $config_data['cf_use_point'] = isset($_POST['cf_use_point']) ? 1 : 0;
    $config_data['cf_write_point'] = intval($_POST['cf_write_point']);
    
    // 테마 설정
    $config_data['cf_theme'] = $_POST['cf_theme'] ?? 'light';
    $config_data['cf_bg_type'] = $_POST['cf_bg_type'] ?? 'color';
    $config_data['cf_bg_value'] = $_POST['cf_bg_value'] ?? '#ffffff';
    
    // 배경 이미지 업로드 처리
    if (isset($_FILES['cf_bg_image']) && $_FILES['cf_bg_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../img/bg/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['cf_bg_image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = 'bg_' . time() . '.' . $file_ext;
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['cf_bg_image']['tmp_name'], $dest_path)) {
                $config_data['cf_bg_type'] = 'image';
                $config_data['cf_bg_value'] = 'img/bg/' . $new_filename;
            }
        }
    }
    
    update_config($config_data);
    $success_message = $lang['settings_saved'];
}

// 현재 설정 가져오기
$config = get_config();

// 기본값 설정
if (!isset($config['cf_theme'])) $config['cf_theme'] = 'light';
if (!isset($config['cf_bg_type'])) $config['cf_bg_type'] = 'color';
if (!isset($config['cf_bg_value'])) $config['cf_bg_value'] = '#ffffff';
?>

<style>
.config-group {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1rem;
    margin-bottom: 1rem;
}

.radio-group {
    display: flex;
    gap: 1.5rem;
}

.radio-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

.help-text {
    margin-top: 0.5rem;
    color: var(--text-light);
    font-size: 0.9rem;
}

.input-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
}
</style>

<?php if (isset($success_message)): ?>
<div style="padding: 1rem; background: var(--success-color, #28a745); color: white; border-radius: var(--radius); margin-bottom: 2rem;">
    <?php echo $success_message; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="act" value="save_config">
    
    <!-- 포인트 설정 -->
    <div class="admin-card">
        <h2 style="margin-top: 0; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); color: var(--secondary-color);"><?php echo $lang['point_settings']; ?></h2>
        
        <div class="config-group">
            <h4 style="margin: 0 0 1rem 0;"><?php echo $lang['use_point']; ?></h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="cf_use_point" value="1" <?php echo $config['cf_use_point'] ? 'checked' : ''; ?>>
                    <?php echo $lang['point_enabled']; ?>
                </label>
                <label class="radio-label">
                    <input type="radio" name="cf_use_point" value="0" <?php echo !$config['cf_use_point'] ? 'checked' : ''; ?>>
                    <?php echo $lang['point_disabled']; ?>
                </label>
            </div>
        </div>
        
        <div class="config-group">
            <h4 style="margin: 0 0 1rem 0;"><?php echo $lang['write_point']; ?></h4>
            <input type="number" name="cf_write_point" value="<?php echo $config['cf_write_point']; ?>" class="form-control" style="width: 200px;">
            <p class="help-text"><?php echo $lang['point_description']; ?></p>
        </div>
    </div>

    <!-- 테마 설정 -->
    <div class="admin-card">
        <h2 style="margin-top: 0; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); color: var(--secondary-color);"><?php echo $lang['theme_settings']; ?></h2>
        
        <div class="config-group">
            <h4 style="margin: 0 0 1rem 0;"><?php echo $lang['default_theme_mode']; ?></h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="cf_theme" value="light" <?php echo $config['cf_theme'] === 'light' ? 'checked' : ''; ?>>
                    ☀️ Light Mode
                </label>
                <label class="radio-label">
                    <input type="radio" name="cf_theme" value="dark" <?php echo $config['cf_theme'] === 'dark' ? 'checked' : ''; ?>>
                    🌙 Dark Mode
                </label>
            </div>
            <p class="help-text"><?php echo $lang['default_theme_desc']; ?></p>
        </div>

        <div class="config-group">
            <h4 style="margin: 0 0 1rem 0;"><?php echo $lang['background_settings']; ?></h4>
            <div class="radio-group" style="margin-bottom: 1rem;">
                <label class="radio-label">
                    <input type="radio" name="cf_bg_type" value="color" <?php echo $config['cf_bg_type'] === 'color' ? 'checked' : ''; ?> onclick="toggleBgInput('color')">
                    🎨 <?php echo $lang['bg_type_color']; ?>
                </label>
                <label class="radio-label">
                    <input type="radio" name="cf_bg_type" value="image" <?php echo $config['cf_bg_type'] === 'image' ? 'checked' : ''; ?> onclick="toggleBgInput('image')">
                    🖼️ <?php echo $lang['bg_type_image']; ?>
                </label>
            </div>

            <!-- 색상 입력 -->
            <div id="bg_color_input" class="input-preview" style="display: <?php echo $config['cf_bg_type'] === 'color' ? 'block' : 'none'; ?>;">
                <input type="text" name="cf_bg_value" id="cf_bg_value_color" value="<?php echo $config['cf_bg_type'] === 'color' ? htmlspecialchars($config['cf_bg_value']) : ''; ?>" 
                       placeholder="<?php echo $lang['bg_color_placeholder']; ?>"
                       class="form-control">
                <p class="help-text">
                    <?php echo $lang['bg_color_help']; ?><br>
                    Ex: <code>linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)</code>
                </p>
            </div>

            <!-- 이미지 업로드 -->
            <div id="bg_image_input" class="input-preview" style="display: <?php echo $config['cf_bg_type'] === 'image' ? 'block' : 'none'; ?>;">
                <?php if ($config['cf_bg_type'] === 'image' && !empty($config['cf_bg_value'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <img src="../<?php echo htmlspecialchars($config['cf_bg_value']); ?>" style="max-width: 200px; max-height: 150px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                        <p class="help-text"><?php echo $lang['current_bg']; ?>: <?php echo htmlspecialchars($config['cf_bg_value']); ?></p>
                    </div>
                <?php endif; ?>
                <input type="file" name="cf_bg_image" accept="image/*" class="form-control">
                <p class="help-text"><?php echo $lang['bg_image_help']; ?></p>
            </div>
        </div>
    </div>
    
    <div style="text-align: right;">
        <button type="submit" class="btn-primary" style="background: var(--primary-color); color: white; border: none; padding: 0.75rem 2rem; border-radius: var(--radius); font-weight: 600; cursor: pointer;">
            💾 <?php echo $lang['save']; ?>
        </button>
    </div>
</form>

<script>
function toggleBgInput(type) {
    document.getElementById('bg_color_input').style.display = type === 'color' ? 'block' : 'none';
    document.getElementById('bg_image_input').style.display = type === 'image' ? 'block' : 'none';
}
</script>

</main>
</div>
</body>
</html>
