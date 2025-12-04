<?php
require_once 'config.php';

$policy_type = $_GET['type'] ?? 'terms'; // terms 또는 privacy

// 유효한 타입인지 확인
if (!in_array($policy_type, ['terms', 'privacy'])) {
    $policy_type = 'terms';
}

// 정책 내용 가져오기
$policy = getPolicy($policy_type);

if (!$policy) {
    // 기본 내용
    if ($policy_type === 'terms') {
        $policy = [
            'policy_title' => $lang['terms_of_service'],
            'policy_content' => '<h2>이용약관</h2><p>이용약관이 아직 설정되지 않았습니다.</p>',
            'updated_at' => null
        ];
    } else {
        $policy = [
            'policy_title' => $lang['privacy_policy'],
            'policy_content' => '<h2>개인정보 보호정책</h2><p>개인정보 보호정책이 아직 설정되지 않았습니다.</p>',
            'updated_at' => null
        ];
    }
}

$page_title = $policy['policy_title'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - MicroBoard</title>
    <link rel="stylesheet" href="skin/default/style.css">
    <link rel="icon" type="image/png" href="img/favicon.png">
    <style>
        .policy-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .policy-header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .policy-header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .policy-updated {
            color: #666;
            font-size: 14px;
        }
        .policy-content {
            line-height: 1.8;
            color: #444;
        }
        .policy-content h2 {
            color: #007bff;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .policy-content h3 {
            color: #333;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        .policy-content p {
            margin-bottom: 15px;
        }
        .policy-content ul, .policy-content ol {
            margin-bottom: 15px;
            padding-left: 30px;
        }
        .policy-content li {
            margin-bottom: 8px;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .back-link:hover {
            background: #5a6268;
        }
        .policy-tabs {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
        }
        .policy-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .policy-tab:hover {
            background: #e9ecef;
        }
        .policy-tab.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="policy-container">
        <div class="policy-tabs">
            <a href="?type=terms" class="policy-tab <?php echo $policy_type === 'terms' ? 'active' : ''; ?>">
                <?php echo $lang['terms_of_service']; ?>
            </a>
            <a href="?type=privacy" class="policy-tab <?php echo $policy_type === 'privacy' ? 'active' : ''; ?>">
                <?php echo $lang['privacy_policy']; ?>
            </a>
        </div>
        
        <div class="policy-header">
            <h1><?php echo htmlspecialchars($policy['policy_title']); ?></h1>
            <?php if ($policy['updated_at']): ?>
                <p class="policy-updated">
                    <?php echo $lang['last_updated']; ?>: <?php echo htmlspecialchars($policy['updated_at']); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="policy-content">
            <?php echo $policy['policy_content']; ?>
        </div>
        
        <a href="javascript:history.back()" class="back-link"><?php echo $lang['list']; ?></a>
    </div>
</body>
</html>
