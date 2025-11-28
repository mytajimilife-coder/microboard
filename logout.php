<?php
require_once 'config.php';

// 언어 처리
$lang_code = $_SESSION['lang'] ?? 'ko';
$lang_file = "lang/{$lang_code}.php";
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require 'lang/ko.php';
}

session_destroy();
$_SESSION = array();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: login.php');
exit;
?>
