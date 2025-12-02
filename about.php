<?php
require_once 'config.php';

// 페이지 제목 설정
$page_title = 'About MicroBoard';

// 헤더 포함
require_once 'inc/header.php';
?>

<div class="content-wrapper" style="max-width: 800px; margin: 0 auto; padding: 40px 20px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 2.5em; color: #333; margin-bottom: 10px;">MicroBoard</h1>
        <p style="font-size: 1.2em; color: #666;">Version <?php echo defined('MICROBOARD_VERSION') ? MICROBOARD_VERSION : '1.0.0'; ?></p>
    </div>

    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h2 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; color: #007cba;">
            <?php echo $lang['about_title']; ?>
        </h2>
        <p style="line-height: 1.6; color: #444;">
            <?php echo $lang['about_description']; ?>
        </p>
        
        <h3 style="margin-top: 30px; color: #333;"><?php echo $lang['key_features']; ?></h3>
        <ul style="list-style-type: none; padding: 0;">
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>🚀 <?php echo $lang['feature_lightweight']; ?>:</strong> <?php echo $lang['feature_lightweight_desc']; ?>
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>🌍 <?php echo $lang['feature_multilang']; ?>:</strong> <?php echo $lang['feature_multilang_desc']; ?>
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>🔒 <?php echo $lang['feature_secure']; ?>:</strong> <?php echo $lang['feature_secure_desc']; ?>
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>📱 <?php echo $lang['feature_responsive']; ?>:</strong> <?php echo $lang['feature_responsive_desc']; ?>
            </li>
        </ul>
    </div>

    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h2 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; color: #007cba;">
            <?php echo $lang['license_title']; ?>
        </h2>
        <p style="line-height: 1.6; color: #444;">
            <?php echo $lang['license_description']; ?>
        </p>
        <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
            &copy; <?php echo date('Y'); ?> MicroBoard Team. <?php echo $lang['all_rights_reserved']; ?>
        </p>
    </div>
</div>

<?php
// 푸터 포함
require_once 'inc/footer.php';
?>
