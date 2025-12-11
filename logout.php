<?php
session_start();

// حذف جميع متغيرات الجلسة
$_SESSION = array();

// حذف كوكيز الجلسة
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// تدمير الجلسة
session_destroy();

// توجيه المستخدم لصفحة تسجيل الدخول
header('Location: login.php');
exit();
?> 
