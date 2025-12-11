<?php
// بسيط: تسجيل أحداث لوحة الإدارة في ملف logs/admin.log

if (!function_exists('log_admin')) {
    function log_admin(string $message): void {
        $logsDir = __DIR__ . '/../logs';
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0775, true);
        }

        $file = $logsDir . '/admin.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = $_SESSION['admin_username'] ?? 'guest';
        $line = "[$timestamp] [$ip] [$user] $message" . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
?>


