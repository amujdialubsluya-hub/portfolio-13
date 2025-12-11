<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'editor';
    
    $errors = [];
    
    // التحقق من البيانات
    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    } elseif (strlen($username) < 3) {
        $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
    }
    
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    } elseif (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'كلمة المرور غير متطابقة';
    }
    
    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    
    // التحقق من عدم وجود اسم المستخدم أو البريد الإلكتروني مسبقاً
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = 'اسم المستخدم أو البريد الإلكتروني موجود مسبقاً';
        }
        $check_stmt->close();
    }
    
    // إضافة المستخدم إذا لم تكن هناك أخطاء
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);
        
        if ($insert_stmt->execute()) {
            $success_message = 'تم إضافة المستخدم بنجاح!';
            // إعادة تعيين النموذج
            $username = $email = $password = $confirm_password = $full_name = '';
            $role = 'editor';
        } else {
            $error_message = 'خطأ في إضافة المستخدم: ' . $insert_stmt->error;
        }
        $insert_stmt->close();
    } else {
        $error_message = 'يرجى تصحيح الأخطاء التالية: ' . implode(', ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مستخدم جديد - لوحة الإدارة</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- الشريط الجانبي -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>لوحة الإدارة</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="universities.php" class="nav-link">
                    <i class="fas fa-university"></i>
                    <span>الجامعات</span>
                </a>
                <a href="colleges.php" class="nav-link">
                    <i class="fas fa-building"></i>
                    <span>الكليات</span>
                </a>
                <a href="specializations.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>التخصصات</span>
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>الرسائل</span>
                </a>
                <a href="users.php" class="nav-link active">
                    <i class="fas fa-users"></i>
                    <span>المستخدمين</span>
                </a>
                <a href="logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </nav>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <div class="page-title-section">
                        <h1 class="page-title">إضافة مستخدم جديد</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">الرئيسية</a>
                            <i class="fas fa-chevron-left"></i>
                            <a href="users.php">المستخدمين</a>
                            <i class="fas fa-chevron-left"></i>
                            <span>إضافة مستخدم</span>
                        </div>
                    </div>
                    
                    <div class="user-info">
                        <span>مرحباً، <?php echo htmlspecialchars($_SESSION['admin_full_name']); ?></span>
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <!-- رسائل النجاح والخطأ -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                        <button class="alert-close" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                        <button class="alert-close" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- نموذج إضافة المستخدم -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-user-plus"></i>
                            إضافة مستخدم جديد
                        </h2>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username" class="form-label">اسم المستخدم *</label>
                                    <input type="text" id="username" name="username" class="form-input" 
                                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">البريد الإلكتروني *</label>
                                    <input type="email" id="email" name="email" class="form-input" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password" class="form-label">كلمة المرور *</label>
                                    <div class="password-input">
                                        <input type="password" id="password" name="password" class="form-input" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور *</label>
                                    <div class="password-input">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name" class="form-label">الاسم الكامل *</label>
                                    <input type="text" id="full_name" name="full_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="role" class="form-label">الدور</label>
                                    <select id="role" name="role" class="form-input">
                                        <option value="editor" <?php echo ($role ?? '') === 'editor' ? 'selected' : ''; ?>>محرر</option>
                                        <option value="admin" <?php echo ($role ?? '') === 'admin' ? 'selected' : ''; ?>>مدير</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    إضافة المستخدم
                                </button>
                                <a href="users.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-right"></i>
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 