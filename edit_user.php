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

// جلب بيانات المستخدم للتعديل
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $user_sql = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        $error_message = 'المستخدم غير موجود';
        header('Location: users.php');
        exit();
    }

    $user = $user_result->fetch_assoc();
    $user_stmt->close();
} else {
    $error_message = 'معرف المستخدم غير صحيح';
    header('Location: users.php');
    exit();
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'editor';
    $status = $_POST['status'] ?? 'active';
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    $errors = [];

    // التحقق من البيانات
    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    }

    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }

    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }

    // التحقق من عدم وجود مستخدم بنفس اسم المستخدم (باستثناء المستخدم الحالي)
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $username, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errors[] = 'يوجد مستخدم بنفس اسم المستخدم';
        }
        $check_stmt->close();
    }

    // التحقق من عدم وجود مستخدم بنفس البريد الإلكتروني (باستثناء المستخدم الحالي)
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errors[] = 'يوجد مستخدم بنفس البريد الإلكتروني';
        }
        $check_stmt->close();
    }

    // التحقق من كلمة المرور إذا تم تغييرها
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'كلمة المرور وتأكيدها غير متطابقين';
        }
    }

    // تحديث المستخدم إذا لم تكن هناك أخطاء
    if (empty($errors)) {
        if (!empty($password)) {
            // تحديث مع كلمة المرور الجديدة
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ?, password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssssi", $username, $email, $full_name, $role, $status, $hashed_password, $id);
        } else {
            // تحديث بدون تغيير كلمة المرور
            $update_sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssssi", $username, $email, $full_name, $role, $status, $id);
        }

        if ($update_stmt->execute()) {
            $success_message = 'تم تحديث المستخدم بنجاح!';
        } else {
            $error_message = 'خطأ في تحديث المستخدم: ' . $update_stmt->error;
        }
        $update_stmt->close();
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
    <title>تعديل المستخدم - لوحة الإدارة</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <!-- الشريط الجانبي -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h2>لوحة الإدارة</h2>
                </div>
                 <p>دليل الشامل للجامعات اليمنية</p>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">القائمة الرئيسية</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>لوحة التحكم</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">إدارة المحتوى</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="universities.php" class="nav-link">
                                <i class="fas fa-university"></i>
                                <span>الجامعات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="colleges.php" class="nav-link">
                                <i class="fas fa-building"></i>
                                <span>الكليات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="specializations.php" class="nav-link">
                                <i class="fas fa-book"></i>
                                <span>التخصصات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">التواصل والدعم</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="messages.php" class="nav-link">
                                <i class="fas fa-envelope"></i>
                                <span>رسائل الاتصال</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">إدارة النظام</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="users.php" class="nav-link active">
                                <i class="fas fa-users"></i>
                                <span>المستخدمين</span>
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="main-content">
            <!-- شريط العنوان -->
            <header class="page-header">
                <h1 class="page-title">تعديل المستخدم</h1>

                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo $_SESSION['admin_full_name']; ?></div>
                        <div class="user-role"><?php echo $_SESSION['admin_role'] === 'admin' ? 'مدير النظام' : 'محرر'; ?></div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>تسجيل الخروج</span>
                    </a>
                </div>
            </header>

            <!-- رسائل النجاح/الخطأ -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- نموذج تعديل المستخدم -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-edit"></i>
                        تعديل المستخدم: <?php echo htmlspecialchars($user['full_name']); ?>
                    </h2>
                </div>

                <div class="card-body">
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username" class="form-label">اسم المستخدم *</label>
                                <input type="text" id="username" name="username" class="form-input"
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">البريد الإلكتروني *</label>
                                <input type="email" id="email" name="email" class="form-input"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name" class="form-label">الاسم الكامل *</label>
                                <input type="text" id="full_name" name="full_name" class="form-input"
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="role" class="form-label">الدور</label>
                                <select id="role" name="role" class="form-input">
                                    <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>محرر</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>مدير</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status" class="form-label">الحالة</label>
                                <select id="status" name="status" class="form-input">
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="form-label">كلمة المرور الجديدة (اتركها فارغة إذا لم ترد تغييرها)</label>
                                <input type="password" id="password" name="password" class="form-input">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                حفظ التغييرات
                            </button>
                            <a href="users.php" class="btn btn-outline">
                                <i class="fas fa-arrow-right"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
