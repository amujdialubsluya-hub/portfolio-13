<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// جلب الإحصائيات
$stats_sql = "SELECT
    (SELECT COUNT(*) FROM universities WHERE status = 'نشطة') as total_universities,
    (SELECT COUNT(*) FROM colleges WHERE status = 'نشطة') as total_colleges,
    (SELECT COUNT(*) FROM specializations WHERE status = 'متاح') as total_specializations,
    (SELECT COUNT(*) FROM contact_messages WHERE status = 'new') as new_messages,
    (SELECT COUNT(*) FROM universities WHERE type = 'حكومية' AND status = 'نشطة') as government_universities,
    (SELECT COUNT(*) FROM universities WHERE type = 'أهلية' AND status = 'نشطة') as private_universities";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// جلب آخر الرسائل
$recent_messages_sql = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5";
$recent_messages_result = $conn->query($recent_messages_sql);

// جلب آخر الجامعات المضافة
$recent_universities_sql = "SELECT * FROM universities ORDER BY created_at DESC LIMIT 5";
$recent_universities_result = $conn->query($recent_universities_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة - دليل الجامعات اليمنية الالكترونية الشامل</title>

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
                            <a href="dashboard.php" class="nav-link active">
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
                                <?php if ($stats['new_messages'] > 0): ?>
                                    <span class="badge"><?php echo $stats['new_messages']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">إدارة النظام</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="users.php" class="nav-link">
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
                <h1 class="page-title">لوحة التحكم</h1>

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

            <!-- الإحصائيات -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_universities']; ?></div>
                    <div class="stat-label">الجامعات</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_colleges']; ?></div>
                    <div class="stat-label">الكليات</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_specializations']; ?></div>
                    <div class="stat-label">التخصصات</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['new_messages']; ?></div>
                    <div class="stat-label">رسائل جديدة</div>
                </div>
                  <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['government_universities']; ?></div>
                    <div class="stat-label">جامعات حكومية</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['private_universities']; ?></div>
                    <div class="stat-label">جامعات أهلية</div>
                </div>
            </div>

            <!-- تفاصيل إضافية
            <div class="stats-grid">
              
            </div> -->

            <!-- آخر الرسائل -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">آخر رسائل الاتصال</h2>
                    <a href="messages.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i>
                        <span>عرض الكل</span>
                    </a>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الموضوع</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_messages_result->num_rows > 0): ?>
                                <?php while($message = $recent_messages_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                        <td><?php echo htmlspecialchars($message['email']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $message['status']; ?>">
                                                <?php
                                                switch($message['status']) {
                                                    case 'new': echo 'جديد'; break;
                                                    case 'read': echo 'مقروء'; break;
                                                    case 'replied': echo 'تم الرد'; break;
                                                    case 'archived': echo 'مؤرشف'; break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y/m/d', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-view btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b;">لا توجد رسائل جديدة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- آخر الجامعات المضافة -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">آخر الجامعات المضافة</h2>
                    <a href="universities.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i>
                        <span>إضافة جامعة</span>
                    </a>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>اسم الجامعة</th>
                                <th>النوع</th>
                                <th>الموقع</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_universities_result->num_rows > 0): ?>
                                <?php while($university = $recent_universities_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($university['name']); ?></td>
                                        <td>
                                            <span class="type-badge type-<?php echo $university['type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                                <?php echo $university['type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($university['location']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $university['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                                <?php echo $university['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y/m/d', strtotime($university['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_university.php?id=<?php echo $university['id']; ?>" class="btn btn-edit btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_university.php?id=<?php echo $university['id']; ?>" class="btn btn-view btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b;">لا توجد جامعات مضافة</td>
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
