<?php
session_start();
// 언어 처리
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ko';
$lang_file = "lang/{$lang_code}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require 'lang/ja.php';
}

// 인증 검증
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $lang['not_logged_in']]);
    exit;
}

header('Content-Type: application/json');

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    echo json_encode(['error' => $lang['csrf_token_invalid']]);
    exit;
}

// 파일 업로드 보안 검증
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // 파일 오류 검증
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => $lang['upload_error_ini_size'],
            UPLOAD_ERR_FORM_SIZE => $lang['upload_error_ini_size'],
            UPLOAD_ERR_PARTIAL => $lang['upload_error_partial'],
            UPLOAD_ERR_NO_FILE => $lang['upload_error_no_file'],
            UPLOAD_ERR_NO_TMP_DIR => $lang['upload_error_no_tmp_dir'],
            UPLOAD_ERR_CANT_WRITE => $lang['upload_error_cant_write'],
            UPLOAD_ERR_EXTENSION => $lang['upload_error_extension']
        ];
        echo json_encode(['error' => $errors[$file['error']] ?? $lang['upload_error_unknown']]);
        exit;
    }
    
    // 파일 크기 제한 (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['error' => $lang['upload_error_size_limit']]);
        exit;
    }
    
    // 허용된 파일 확장자
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_extensions)) {
        echo json_encode(['error' => $lang['upload_error_type_limit']]);
        exit;
    }
    
    // MIME 타입 검증
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mime_types = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/bmp'
    ];
    
    if (!in_array($mime_type, $allowed_mime_types)) {
        echo json_encode(['error' => $lang['upload_error_type']]);
        exit;
    }
    
    // 악성 코드 검사를 위한 파일 내용 확인
    $file_content = file_get_contents($file['tmp_name']);
    
    // PHP 코드가 포함되어 있는지 검사
    if (strpos($file_content, '<?php') !== false || 
        strpos($file_content, '<?') !== false || 
        strpos($file_content, '<%') !== false) {
        echo json_encode(['error' => $lang['upload_error_malicious']]);
        exit;
    }
    
    // 업로드 디렉토리 생성
    $upload_dir = __DIR__ . '/img/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['error' => $lang['upload_error_dir_create']]);
            exit;
        }
    }
    
    // 보안 강화를 위한 파일명 생성
    $filename = time() . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // 파일 권한 설정
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        chmod($filepath, 0644);
        echo json_encode(['url' => 'img/' . $filename]);
    } else {
        echo json_encode(['error' => $lang['upload_error_failed']]);
    }
} else {
    echo json_encode(['error' => $lang['upload_error_no_data']]);
}
?>
