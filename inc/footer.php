    </main>
    
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-links" style="margin-bottom: 10px; text-align: center;">
                <a href="policy.php?type=terms" style="color: #666; text-decoration: none; margin: 0 10px;"><?php echo $lang['terms_of_service'] ?? '이용약관'; ?></a>
                <span style="color: #999;">|</span>
                <a href="policy.php?type=privacy" style="color: #666; text-decoration: none; margin: 0 10px;"><?php echo $lang['privacy_policy'] ?? '개인정보 보호정책'; ?></a>
            </div>
            <p style="text-align: center; color: #999; margin: 0;">&copy; <?php echo date('Y'); ?> MicroBoard v<?php echo MICROBOARD_VERSION; ?>. <?php echo $lang['all_rights_reserved'] ?? 'All rights reserved.'; ?></p>
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
</body>
</html>
