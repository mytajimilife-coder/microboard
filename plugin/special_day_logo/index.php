<?php
if (!defined('MICROBOARD_VERSION')) exit;

// 플러그인 정보
$plugin_info = [
    'name' => 'Special Day Logo',
    'version' => '1.0',
    'author' => 'MicroBoard Team',
    'description' => '특정일(기념일)에 다른 로고를 사용할 수 있는 플러그인입니다.'
];

// 특정일 로고 설정
$special_day_logos = [
    '01-01' => 'logo_newyear.png',  // 신정
    '02-14' => 'logo_valentine.png', // 발렌타인데이
    '03-08' => 'logo_women.png',    // 국제여성의날
    '05-05' => 'logo_children.png', // 어린이날
    '12-25' => 'logo_christmas.png' // 크리스마스
];

// 특정일 로고 적용 함수
function apply_special_day_logo($current_logo) {
    global $special_day_logos;

    // 현재 날짜 가져오기
    $today = date('m-d');

    // 특정일 로고 확인
    if (isset($special_day_logos[$today])) {
        $special_logo = $special_day_logos[$today];
        $special_logo_path = __DIR__ . '/logos/' . $special_logo;

        // 특정일 로고 파일이 존재하면 적용
        if (file_exists($special_logo_path)) {
            return 'plugin/special_day_logo/logos/' . $special_logo;
        }
    }

    // 특정일 로고가 없으면 기본 로고 반환
    return $current_logo;
}

// 로고 출력 훅 등록
add_event('before_logo_display', function($logo) {
    return apply_special_day_logo($logo);
});

// 관리자 설정 페이지 추가
add_event('admin_menu', function() {
    echo '<li><a href="?page=special_day_logo_settings">특정일 로고 설정</a></li>';
});

// 관리자 설정 페이지 처리
add_event('admin_page_special_day_logo_settings', function() {
    global $special_day_logos;

    // 설정 저장 처리
    if (isset($_POST['save_settings'])) {
        $new_settings = [];
        $errors = [];

        // 디렉토리 생성
        if (!is_dir(__DIR__ . '/logos')) {
            mkdir(__DIR__ . '/logos', 0755, true);
        }

        foreach ($_POST['dates'] as $index => $date) {
            if (!empty($date)) {
                // 날짜 유효성 검사
                if (!preg_match('/^\d{2}-\d{2}$/', $date)) {
                    $errors[] = "날짜 형식이 잘못되었습니다: $date (형식: MM-DD)";
                    continue;
                }

                // 파일 업로드 처리
                if (!empty($_FILES['logos']['name'][$index])) {
                    $file = $_FILES['logos']['tmp_name'][$index];
                    $filename = basename($_FILES['logos']['name'][$index]);
                    $target_path = __DIR__ . '/logos/' . $filename;

                    // 파일 유효성 검사
                    $allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
                    $file_info = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($file_info, $file);
                    finfo_close($file_info);

                    if (!in_array($mime_type, $allowed_types)) {
                        $errors[] = "허용되지 않는 파일 형식: $filename (허용: PNG, JPG, JPEG, GIF, SVG)";
                        continue;
                    }

                    // 파일 크기 검증 (5MB 제한)
                    if ($_FILES['logos']['size'][$index] > 5 * 1024 * 1024) {
                        $errors[] = "파일 크기가 너무 큽니다: $filename (최대 5MB)";
                        continue;
                    }

                    // 파일 업로드
                    if (move_uploaded_file($file, $target_path)) {
                        $new_settings[$date] = $filename;
                    } else {
                        $errors[] = "파일 업로드 실패: $filename";
                    }
                } else {
                    // 기존 로고 유지
                    if (isset($special_day_logos[$date])) {
                        $new_settings[$date] = $special_day_logos[$date];
                    }
                }
            }
        }

        // 설정 파일 저장
        if (empty($errors)) {
            file_put_contents(__DIR__ . '/settings.json', json_encode($new_settings, JSON_PRETTY_PRINT));
            $special_day_logos = $new_settings;

            echo '<div style="padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">';
            echo '설정이 저장되었습니다!';
            echo '</div>';
        } else {
            echo '<div style="padding: 10px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;">';
            echo '<strong>오류:</strong><br>';
            foreach ($errors as $error) {
                echo '- ' . htmlspecialchars($error) . '<br>';
            }
            echo '</div>';
        }
    }

    // 설정 파일 로드
    $settings_file = __DIR__ . '/settings.json';
    if (file_exists($settings_file)) {
        $saved_settings = json_decode(file_get_contents($settings_file), true);
        if (is_array($saved_settings)) {
            $special_day_logos = array_merge($special_day_logos, $saved_settings);
        }
    }

    // 설정 페이지 출력
    echo '<div class="admin-card">';
    echo '<h2>특정일 로고 설정</h2>';
    echo '<p>특정일에 표시할 로고를 설정할 수 있습니다.</p>';

    echo '<form method="post" enctype="multipart/form-data">';
    echo '<table class="table">';
    echo '<thead><tr><th>날짜</th><th>로고 이미지</th><th>현재 로고</th></tr></thead>';
    echo '<tbody>';

    // 기본 날짜 설정
    $default_dates = ['01-01', '02-14', '03-08', '05-05', '12-25'];
    foreach ($default_dates as $index => $date) {
        echo '<tr>';
        echo '<td><input type="text" name="dates[]" value="' . htmlspecialchars($date) . '" placeholder="MM-DD" pattern="\d{2}-\d{2}" required></td>';
        echo '<td><input type="file" name="logos[]" accept=".png,.jpg,.jpeg,.gif,.svg"></td>';
        echo '<td>';
        if (isset($special_day_logos[$date]) && file_exists(__DIR__ . '/logos/' . $special_day_logos[$date])) {
            echo '<img src="plugin/special_day_logo/logos/' . htmlspecialchars($special_day_logos[$date]) . '" style="max-width: 100px; max-height: 30px;">';
        }
        echo '</td>';
        echo '</tr>';
    }

    // 추가 날짜 입력 필드
    for ($i = 0; $i < 5; $i++) {
        echo '<tr>';
        echo '<td><input type="text" name="dates[]" placeholder="MM-DD" pattern="\d{2}-\d{2}"></td>';
        echo '<td><input type="file" name="logos[]" accept=".png,.jpg,.jpeg,.gif,.svg"></td>';
        echo '<td></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo '<button type="submit" name="save_settings" class="btn btn-primary">설정 저장</button>';
    echo '</form>';

    echo '</div>';
});
?>
