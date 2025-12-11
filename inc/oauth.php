<?php
// OAuth 헬퍼 함수들

function getOAuthConfig($provider) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM mb1_oauth_config WHERE provider = ?");
    $stmt->execute([$provider]);
    return $stmt->fetch();
}

function getEnabledOAuthProviders() {
    $db = getDB();
    $stmt = $db->query("SELECT provider FROM mb1_oauth_config WHERE enabled = 1 AND client_id != '' AND client_secret != ''");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getOAuthLoginUrl($provider) {
    $config = getOAuthConfig($provider);
    if (!$config || !$config['enabled'] || empty($config['client_id']) || empty($config['client_secret'])) {
        return null;
    }
    
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $redirect_uri = $base_url . dirname($_SERVER['PHP_SELF']) . '/oauth_callback.php';
    
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_provider'] = $provider;
    
    switch ($provider) {
        case 'google':
            $params = [
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirect_uri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state
            ];
            return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
            
        case 'line':
            $params = [
                'response_type' => 'code',
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirect_uri,
                'state' => $state,
                'scope' => 'profile openid email'
            ];
            return 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);
            
        case 'apple':
            $params = [
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirect_uri,
                'response_type' => 'code',
                'state' => $state,
                'scope' => 'name email',
                'response_mode' => 'form_post'
            ];
            return 'https://appleid.apple.com/auth/authorize?' . http_build_query($params);
            
        default:
            return null;
    }
}

function exchangeOAuthCode($provider, $code) {
    $config = getOAuthConfig($provider);
    if (!$config) {
        return null;
    }
    
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $redirect_uri = $base_url . dirname($_SERVER['PHP_SELF']) . '/oauth_callback.php';
    
    switch ($provider) {
        case 'google':
            $token_url = 'https://oauth2.googleapis.com/token';
            $params = [
                'code' => $code,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            ];
            break;
            
        case 'line':
            $token_url = 'https://api.line.me/oauth2/v2.1/token';
            $params = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirect_uri,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret']
            ];
            break;
            
        case 'apple':
            // Apple OAuth는 JWT 토큰 생성이 필요합니다
            // 여기서는 기본 구조만 제공 (실제 구현 시에는 Apple 개발자 문서 참조)
            // Apple은 클라이언트 시크릿을 JWT로 생성해야 합니다
            return null;
            
        default:
            return null;
    }
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

function getOAuthUserInfo($provider, $access_token) {
    switch ($provider) {
        case 'google':
            $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
            break;
            
        case 'line':
            $url = 'https://api.line.me/v2/profile';
            break;
            
        default:
            return null;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

function createOrUpdateOAuthUser($provider, $provider_user_id, $user_info) {
    $db = getDB();

    // 이미 연동된 계정이 있는지 확인
    $stmt = $db->prepare("SELECT mb_id FROM mb1_oauth_users WHERE provider = ? AND provider_user_id = ?");
    $stmt->execute([$provider, $provider_user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 기존 사용자 로그인
        return $existing['mb_id'];
    }

    // 새 사용자 생성
    // 이메일이나 고유 ID를 기반으로 사용자명 생성
    $username = null;
    $email = null;
    $nickname = null;

    // 이메일 주소 추출
    if (isset($user_info['email'])) {
        $email = $user_info['email'];
    }

    // 닉네임 추출
    if (isset($user_info['name'])) {
        $nickname = $user_info['name'];
    } elseif (isset($user_info['displayName'])) {
        $nickname = $user_info['displayName'];
    } elseif (isset($user_info['display_name'])) {
        $nickname = $user_info['display_name'];
    }

    if ($provider === 'google' && isset($user_info['email'])) {
        $username = explode('@', $user_info['email'])[0];
    } elseif ($provider === 'line' && isset($user_info['userId'])) {
        $username = 'line_' . substr($user_info['userId'], 0, 15);
    }

    // 사용자명 중복 체크 및 고유화
    if ($username) {
        $base_username = $username;
        $counter = 1;
        while (isUsernameExists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
    } else {
        // 기본 사용자명 생성
        $username = $provider . '_' . substr($provider_user_id, 0, 10);
    }

    // 랜덤 비밀번호 생성 (OAuth 사용자는 비밀번호 로그인 불가)
    $random_password = bin2hex(random_bytes(32));
    $password_hash = password_hash($random_password, PASSWORD_DEFAULT);

    try {
        $db->beginTransaction();

        // 회원 생성 (이메일과 닉네임 포함)
        $stmt = $db->prepare("INSERT INTO mb1_member (mb_id, mb_password, mb_nickname, mb_email, oauth_provider) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $nickname, $email, $provider]);

        // OAuth 연동 정보 저장
        $stmt = $db->prepare("INSERT INTO mb1_oauth_users (mb_id, provider, provider_user_id) VALUES (?, ?, ?)");
        $stmt->execute([$username, $provider, $provider_user_id]);

        $db->commit();

        return $username;
    } catch (Exception $e) {
        $db->rollBack();
        return null;
    }
}
?>
