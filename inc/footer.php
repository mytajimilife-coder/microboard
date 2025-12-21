    </main>
    
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-links" style="margin-bottom: 10px; text-align: center;">
                <a href="policy.php?type=terms" style="color: #666; text-decoration: none; margin: 0 10px;"><?php echo $lang['terms_of_service'] ?? '이용약관'; ?></a>
                <span style="color: #999;">|</span>
                <a href="policy.php?type=privacy" style="color: #666; text-decoration: none; margin: 0 10px;"><?php echo $lang['privacy_policy'] ?? '개인정보 보호정책'; ?></a>
            </div>
            <?php
            $config = get_config();
            $copyright = $config['cf_copyright'] ?? '';
            if (empty($copyright)) {
                $copyright = "MicroBoard v" . MICROBOARD_VERSION . ". " . ($lang['all_rights_reserved'] ?? 'All rights reserved.');
            }
            ?>
            <p style="text-align: center; color: #999; margin: 0;">&copy; <?php echo date('Y'); ?> <?php echo $copyright; ?></p>
        </div>
    </footer>
    
    <script>
        // 기본 자바스크립트 기능
        function confirmAction(message) {
            return confirm(message);
        }
        
        // 폼 제출 확인
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('form[action*="delete"]');
            deleteForms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('<?php echo $lang['delete_confirm'] ?? '정말 삭제하시겠습니까?'; ?>')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
    
    <?php
    // 푸터 추가 스크립트
    try {
        $db = getDB();
        $stmt = $db->query("SELECT footer_script FROM mb1_seo_config WHERE id = 1");
        $seo_config = $stmt->fetch();
        
        if ($seo_config && !empty($seo_config['footer_script'])) {
            echo "<!-- Custom Footer Script -->\n    ";
            echo $seo_config['footer_script'] . "\n    ";
        }
    } catch (Exception $e) {
        // SEO 테이블이 없을 경우 무시
    }
    ?>
</body>
</html>
