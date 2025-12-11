<?php
// ========================================
// نظام الصلاحيات المتقدم - دليل الجامعات اليمنية
// ========================================

// تعريف الصلاحيات المتاحة
define('PERMISSIONS', [
    'dashboard' => 'dashboard',
    'universities' => 'universities',
    'colleges' => 'colleges', 
    'specializations' => 'specializations',
    'messages' => 'messages',
    'users' => 'users'
]);

// تعريف الأدوار والصلاحيات المسموحة لكل دور
define('ROLE_PERMISSIONS', [
    'admin' => [
        'dashboard',
        'universities', 
        'colleges',
        'specializations',
        'messages',
        'users'
    ],
    'editor' => [
        'dashboard',
        'messages'
    ]
]);

/**
 * التحقق من صلاحية المستخدم للوصول إلى صفحة معينة
 * @param string $permission الصلاحية المطلوبة
 * @return bool true إذا كان مسموح، false إذا لم يكن مسموح
 */
function hasPermission($permission) {
    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // التحقق من وجود الدور في الجلسة
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    
    $user_role = $_SESSION['admin_role'];
    
    // التحقق من وجود الدور في الصلاحيات
    if (!isset(ROLE_PERMISSIONS[$user_role])) {
        return false;
    }
    
    // التحقق من وجود الصلاحية للدور
    return in_array($permission, ROLE_PERMISSIONS[$user_role]);
}

/**
 * التحقق من الصلاحية وإعادة التوجيه إذا لم تكن مسموحة
 * @param string $permission الصلاحية المطلوبة
 * @param string $redirect_url الرابط البديل (اختياري)
 */
function requirePermission($permission, $redirect_url = null) {
    if (!hasPermission($permission)) {
        // إذا لم يتم تحديد رابط بديل، استخدم لوحة التحكم
        if ($redirect_url === null) {
            $redirect_url = 'dashboard.php';
        }
        
        // إضافة رسالة خطأ للجلسة
        $_SESSION['permission_error'] = 'غير مصرح لك بالوصول إلى هذه الصفحة';
        
        // إعادة التوجيه
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * الحصول على قائمة الصفحات المسموحة للمستخدم الحالي
 * @return array قائمة الصفحات المسموحة
 */
function getAllowedPages() {
    if (!isset($_SESSION['admin_role'])) {
        return [];
    }
    
    $user_role = $_SESSION['admin_role'];
    
    if (!isset(ROLE_PERMISSIONS[$user_role])) {
        return [];
    }
    
    return ROLE_PERMISSIONS[$user_role];
}

/**
 * التحقق من إمكانية عرض رابط في القائمة
 * @param string $permission الصلاحية المطلوبة
 * @return bool true إذا كان مسموح بالعرض، false إذا لم يكن مسموح
 */
function canShowLink($permission) {
    return hasPermission($permission);
}

/**
 * الحصول على رسالة الخطأ من الجلسة وحذفها
 * @return string|null رسالة الخطأ أو null إذا لم تكن موجودة
 */
function getPermissionError() {
    if (isset($_SESSION['permission_error'])) {
        $error = $_SESSION['permission_error'];
        unset($_SESSION['permission_error']);
        return $error;
    }
    return null;
}

/**
 * عرض رسالة خطأ الصلاحيات
 */
function showPermissionError() {
    $error = getPermissionError();
    if ($error) {
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo '<span>' . htmlspecialchars($error) . '</span>';
        echo '</div>';
    }
}

/**
 * الحصول على اسم الدور باللغة العربية
 * @param string $role الدور بالإنجليزية
 * @return string اسم الدور بالعربية
 */
function getRoleName($role) {
    $role_names = [
        'admin' => 'مدير النظام',
        'editor' => 'محرر'
    ];
    
    return isset($role_names[$role]) ? $role_names[$role] : 'غير محدد';
}

/**
 * التحقق من كون المستخدم مدير
 * @return bool true إذا كان مدير، false إذا لم يكن
 */
function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

/**
 * التحقق من كون المستخدم محرر
 * @return bool true إذا كان محرر، false إذا لم يكن
 */
function isEditor() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'editor';
}
?>
