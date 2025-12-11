<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['admin_role'] !== 'admin') {
    // إيقاف تحميل باقي الصفحة
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <title>غير مصرح</title>
        <link rel="stylesheet" href="assets/css/admin.css">
         <style>
            body {
                margin: 0;
                padding: 0;
                font-family: "Cairo", sans-serif;
                background: linear-gradient(135deg, #1d3557, #457b9d);
                color: #fff;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .error-page {
                background: rgba(255, 255, 255, 0.1);
                padding: 40px;
                border-radius: 15px;
                text-align: center;
                box-shadow: 0 8px 20px rgba(0,0,0,0.3);
                animation: fadeIn 0.8s ease-in-out;
                max-width: 400px;
                width: 90%;
            }
            .error-page h1 {
                font-size: 28px;
                margin-bottom: 15px;
                color: #f1faee;
            }
            .error-page p {
                font-size: 16px;
                margin-bottom: 25px;
                color: #dceefb;
            }
            .error-page a {
                display: inline-block;
                padding: 12px 25px;
                background: #e63946;
                color: #fff;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                transition: background 0.3s ease;
            }
            .error-page a:hover {
                background: #d62828;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>
        <div class="error-page">
            <h1>غير مصرح لك بالدخول</h1>
            <p>ليس لديك صلاحية للوصول إلى هذه الصفحة</p>
            <a href="dashboard.php">العودة للوحة التحكم</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}
// حذف مستخدم
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // منع حذف المدير الرئيسي
    if ($id == 1) {
        $error_message = 'لا يمكن حذف المدير الرئيسي';
    } else {
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            $success_message = 'تم حذف المستخدم بنجاح';
        } else {
            $error_message = 'خطأ في حذف المستخدم';
        }
        $delete_stmt->close();
    }
}

// جلب قائمة المستخدمين
$users_sql = "SELECT id, username, email, full_name, role, status, last_login, created_at FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - لوحة الإدارة</title>

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
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">التواصل والدعم</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="messages.php" class="nav-link">
                                <i class="fas fa-envelope"></i>
                                <span>رسائل الاتصال</span>
                                <?php
                                // عدد الرسائل الجديدة
                                $new_messages_sql = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'جديد'";
                                $new_messages_result = $conn->query($new_messages_sql);
                                $new_messages = $new_messages_result->fetch_assoc();
                                if ($new_messages['count'] > 0): ?>
                                    <span class="badge"><?php echo $new_messages['count']; ?></span>
                                <?php endif; ?>
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
                <h1 class="page-title">إدارة المستخدمين</h1>

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
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- بطاقة إضافة مستخدم جديد -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">إضافة مستخدم جديد</h2>
                </div>

                <form class="form-container" method="POST" action="add_user.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">اسم المستخدم *</label>
                            <input type="text" id="username" name="username" required>
                        </div>

                        <div class="form-group">
                            <label for="email">البريد الإلكتروني *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">الاسم الكامل *</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>

                        <div class="form-group">
                            <label for="role">الدور</label>
                            <select id="role" name="role">
                                <option value="editor">محرر</option>
                                <option value="admin">مدير</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">كلمة المرور *</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">تأكيد كلمة المرور *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>إضافة المستخدم</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- قائمة المستخدمين -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">قائمة المستخدمين</h2>
                    <div class="card-actions">
                        <span class="total-count">إجمالي المستخدمين: <?php echo $users_result->num_rows; ?></span>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>اسم المستخدم</th>
                                <th>الاسم الكامل</th>
                                <th>البريد الإلكتروني</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>آخر تسجيل دخول</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users_result->num_rows > 0): ?>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['role'] === 'admin' ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['role'] === 'admin' ? 'مدير' : 'محرر'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                                <?php echo date('Y/m/d H:i', strtotime($user['last_login'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">لم يسجل دخول</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-edit btn-sm" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-view btn-sm" title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($user['id'] != 1): ?>
                                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-delete btn-sm" title="حذف"
                                                       onclick="return confirm('هل أنت متأكد من حذف المستخدم <?php echo htmlspecialchars($user['full_name']); ?>؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #64748b; padding: 2rem;">
                                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                        <p>لا توجد مستخدمين مضافة حالياً</p>
                                        <p>قم بإضافة مستخدم جديد من النموذج أعلاه</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ملف JavaScript -->
    <script src="assets/js/admin.js"></script>
</body>
</html>
