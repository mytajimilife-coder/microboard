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
            <?php echo $lang['about_title'] ?? 'About MicroBoard'; ?>
        </h2>
        <p style="line-height: 1.6; color: #444;">
            MicroBoard is a lightweight, high-performance bulletin board system designed for simplicity and ease of use. 
            Built with modern PHP standards, it offers a robust platform for community engagement without the bloat of larger CMS platforms.
        </p>
        
        <h3 style="margin-top: 30px; color: #333;">Key Features</h3>
        <ul style="list-style-type: none; padding: 0;">
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>🚀 Lightweight & Fast:</strong> Optimized for performance with minimal dependencies.
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>🌍 Multi-language Support:</strong> Native support for Korean, English, Japanese, and Chinese.
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>🔒 Secure:</strong> Built-in protection against common web vulnerabilities.
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>📱 Responsive Design:</strong> Works perfectly on desktop, tablet, and mobile devices.
            </li>
        </ul>
    </div>

    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h2 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; color: #007cba;">
            License
        </h2>
        <p style="line-height: 1.6; color: #444;">
            MicroBoard is open-source software licensed under the <a href="LICENSE" target="_blank" style="color: #007cba; text-decoration: none;">MIT License</a>.
        </p>
        <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
            &copy; <?php echo date('Y'); ?> MicroBoard Team. All rights reserved.
        </p>
    </div>
</div>

<?php
// 푸터 포함
require_once 'inc/footer.php';
?>
