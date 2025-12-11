<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// جلب بيانات الجامعة
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $university_sql = "SELECT * FROM universities WHERE id = ?";
    $university_stmt = $conn->prepare($university_sql);
    $university_stmt->bind_param("i", $id);
    $university_stmt->execute();
    $university_result = $university_stmt->get_result();

    if ($university_result->num_rows === 0) {
        header('Location: universities.php');
        exit();
    }

    $university = $university_result->fetch_assoc();
    $university_stmt->close();

    // جلب إحصائيات الجامعة
    $stats_sql = "SELECT
        (SELECT COUNT(*) FROM colleges WHERE university_id = ?) AS colleges_count,
        (SELECT COUNT(*) FROM specializations s
         JOIN colleges c ON s.college_id = c.id
         WHERE c.university_id = ?) AS specializations_count";

    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("ii", $id, $id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();

    // جلب قائمة الكليات
    $colleges_sql = "SELECT id, name, status, created_at FROM colleges WHERE university_id = ? ORDER BY name";
    $colleges_stmt = $conn->prepare($colleges_sql);
    $colleges_stmt->bind_param("i", $id);
    $colleges_stmt->execute();
    $colleges_result = $colleges_stmt->get_result();
    $colleges_stmt->close();

} else {
    header('Location: universities.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الجامعة - لوحة الإدارة</title>

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
                            <a href="universities.php" class="nav-link active">
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
                <h1 class="page-title">عرض الجامعة</h1>

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

            <!-- تفاصيل الجامعة -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-university"></i>
                        تفاصيل الجامعة: <?php echo htmlspecialchars($university['name']); ?>
                    </h2>
                    <div class="card-actions">
                        <a href="edit_university.php?id=<?php echo $university['id']; ?>" class="btn btn-edit btn-sm">
                            <i class="fas fa-edit"></i>
                            تعديل
                        </a>
                        <a href="universities.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-right"></i>
                            العودة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="university-details">
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-university"></i>
                                اسم الجامعة:
                            </div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($university['name']); ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-tag"></i>
                                نوع الجامعة:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $university['type'] === 'حكومية' ? 'active' : 'inactive'; ?>">
                                    <?php echo $university['type']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-map-marker-alt"></i>
                                الموقع:
                            </div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($university['location']); ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-circle"></i>
                                الحالة:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $university['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                    <?php echo $university['status']; ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($university['website'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-globe"></i>
                                الموقع الإلكتروني:
                            </div>
                            <div class="detail-value">
                                <a href="<?php echo htmlspecialchars($university['website']); ?>" target="_blank" class="link">
                                    <?php echo htmlspecialchars($university['website']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($university['phone'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-phone"></i>
                                رقم الهاتف:
                            </div>
                            <div class="detail-value">
                                <a href="tel:<?php echo htmlspecialchars($university['phone']); ?>" class="link">
                                    <?php echo htmlspecialchars($university['phone']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($university['email'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-envelope"></i>
                                البريد الإلكتروني:
                            </div>
                            <div class="detail-value">
                                <a href="mailto:<?php echo htmlspecialchars($university['email']); ?>" class="link">
                                    <?php echo htmlspecialchars($university['email']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($university['description'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-info-circle"></i>
                                الوصف:
                            </div>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($university['description'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-calendar-plus"></i>
                                تاريخ الإنشاء:
                            </div>
                            <div class="detail-value">
                                <?php echo date('Y/m/d H:i:s', strtotime($university['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الجامعة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['colleges_count']; ?></div>
                    <div class="stat-label">الكلية</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['specializations_count']; ?></div>
                    <div class="stat-label">التخصص</div>
                </div>

            </div>

            <!-- قائمة الكليات -->
            <?php if ($colleges_result->num_rows > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-building"></i>
                        كليات الجامعة
                    </h2>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>اسم الكلية</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($college = $colleges_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($college['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $college['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                            <?php echo $college['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y/m/d', strtotime($college['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_college.php?id=<?php echo $college['id']; ?>" class="btn btn-edit btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_college.php?id=<?php echo $college['id']; ?>" class="btn btn-view btn-sm" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>

    <style>
        .university-details {
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

        .link {
            color: #667eea;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }
    </style>
</body>
</html>
