    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MicroBoard</h3>
                    <p><?php echo $lang['board_list']; ?> <?php echo $lang['system']; ?></p>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo $lang['board_list']; ?></h4>
                    <ul>
                        <li><a href="../index.php"><?php echo $lang['board_list']; ?></a></li>
                        <li><a href="../user/mypage.php"><?php echo $lang['mypage']; ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo $lang['login']; ?></h4>
                    <?php if (!isLoggedIn()): ?>
                        <ul>
                            <li><a href="../login.php"><?php echo $lang['login']; ?></a></li>
                            <li><a href="../register.php"><?php echo $lang['register']; ?></a></li>
                        </ul>
                    <?php else: ?>
                        <ul>
                            <li><a href="../logout.php"><?php echo $lang['logout']; ?></a></li>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo $lang['user_management']; ?></h4>
                    <?php if (isAdmin()): ?>
                        <ul>
                            <li><a href="../admin/index.php"><?php echo $lang['welcome']; ?></a></li>
                            <li><a href="../admin/users.php"><?php echo $lang['user_management']; ?></a></li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="language-selector">
                    <strong><?php echo $lang['language']; ?>:</strong>
                    <form method="post" style="display: inline;">
                        <select name="language" onchange="this.form.submit()" style="margin-left: 8px;">
                            <option value="ko" <?php echo $lang_code == 'ko' ? 'selected' : ''; ?>>한국어</option>
                            <option value="en" <?php echo $lang_code == 'en' ? 'selected' : ''; ?>>English</option>
                            <option value="ja" <?php echo $lang_code == 'ja' ? 'selected' : ''; ?>>日本語</option>
                            <option value="zh" <?php echo $lang_code == 'zh' ? 'selected' : ''; ?>>中文</option>
                        </select>
                        <noscript><input type="submit" value="<?php echo $lang['apply']; ?>"></noscript>
                    </form>
                </div>
                
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> MicroBoard. <?php echo $lang['board_list']; ?>.
                </div>
            </div>
        </div>
    </footer>

    <?php
    // 언어 변경 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
        $selected_lang = $_POST['language'];
        if (in_array($selected_lang, ['ko', 'en', 'ja', 'zh'])) {
            $_SESSION['lang'] = $selected_lang;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    ?>
    
    <link rel="stylesheet" href="../skin/inc/footer.css">
    <link rel="stylesheet" href="../skin/inc/content.css">
    
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
                    if (!confirm('<?php echo $lang['delete_confirm']; ?>')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
