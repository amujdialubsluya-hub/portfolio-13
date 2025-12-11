<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// جلب بيانات المستخدم
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $user_sql = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        header('Location: users.php');
        exit();
    }

    $user = $user_result->fetch_assoc();
    $user_stmt->close();
} else {
    header('Location: users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض المستخدم - لوحة الإدارة</title>

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
                <h1 class="page-title">عرض المستخدم</h1>

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

            <!-- تفاصيل المستخدم -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user"></i>
                        تفاصيل المستخدم: <?php echo htmlspecialchars($user['full_name']); ?>
                    </h2>
                    <div class="card-actions">
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-edit btn-sm">
                            <i class="fas fa-edit"></i>
                            تعديل
                        </a>
                        <a href="users.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-right"></i>
                            العودة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="user-details">
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-user"></i>
                                اسم المستخدم:
                            </div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-id-card"></i>
                                الاسم الكامل:
                            </div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-envelope"></i>
                                البريد الإلكتروني:
                            </div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-user-tag"></i>
                                الدور:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $user['role'] === 'admin' ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'مدير' : 'محرر'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-circle"></i>
                                الحالة:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-clock"></i>
                                آخر تسجيل دخول:
                            </div>
                            <div class="detail-value">
                                <?php if ($user['last_login']): ?>
                                    <?php echo date('Y/m/d H:i:s', strtotime($user['last_login'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">لم يسجل دخول بعد</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-calendar-plus"></i>
                                تاريخ الإنشاء:
                            </div>
                            <div class="detail-value">
                                <?php echo date('Y/m/d H:i:s', strtotime($user['created_at'])); ?>
                            </div>
                        </div>

                        <?php if ($user['id'] == 1): ?>
                            <div class="detail-row">
                                <div class="detail-label">
                                    <i class="fas fa-shield-alt"></i>
                                    ملاحظة:
                                </div>
                                <div class="detail-value">
                                    <span class="status-badge status-active">
                                        هذا هو المدير الرئيسي للنظام
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>

    <style>
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .detail-row {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #1e293b;
            min-width: 200px;
        }

        .detail-label i {
            color: #667eea;
            width: 20px;
        }

        .detail-value {
            color: #64748b;
            flex: 1;
        }

        .btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
    </style>
</body>
</html>
