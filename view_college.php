<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// جلب بيانات الكلية
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // ✅ التعديل هنا: استخدام LEFT JOIN بدلاً من JOIN
    $college_sql = "SELECT c.*, u.name AS university_name, u.type AS university_type
                    FROM colleges c
                    LEFT JOIN universities u ON c.university_id = u.id
                    WHERE c.id = ?";
    $college_stmt = $conn->prepare($college_sql);
    $college_stmt->bind_param("i", $id);
    $college_stmt->execute();
    $college_result = $college_stmt->get_result();

    if ($college_result->num_rows === 0) {
        header('Location: colleges.php');
        exit();
    }

    $college = $college_result->fetch_assoc();
    $college_stmt->close();

    // جلب إحصائيات الكلية
    $stats_sql = "SELECT
        (SELECT COUNT(*) FROM specializations WHERE college_id = ?) AS specializations_count";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("i", $id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();

    // جلب قائمة التخصصات
    $specializations_sql = "SELECT id, name, duration, degree_type, status, created_at
                            FROM specializations
                            WHERE college_id = ?
                            ORDER BY name";
    $specializations_stmt = $conn->prepare($specializations_sql);
    $specializations_stmt->bind_param("i", $id);
    $specializations_stmt->execute();
    $specializations_result = $specializations_stmt->get_result();
    $specializations_stmt->close();

} else {
    header('Location: colleges.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الكلية - لوحة الإدارة</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
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
                        <li><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>لوحة التحكم</span></a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">إدارة المحتوى</div>
                    <ul class="nav-menu">
                        <li><a href="universities.php" class="nav-link"><i class="fas fa-university"></i><span>الجامعات</span></a></li>
                        <li><a href="colleges.php" class="nav-link active"><i class="fas fa-building"></i><span>الكليات</span></a></li>
                        <li><a href="specializations.php" class="nav-link"><i class="fas fa-book"></i><span>التخصصات</span></a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">إدارة النظام</div>
                    <ul class="nav-menu">
                        <li><a href="users.php" class="nav-link"><i class="fas fa-users"></i><span>المستخدمين</span></a></li>
                    </ul>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <h1 class="page-title">عرض الكلية</h1>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo $_SESSION['admin_full_name']; ?></div>
                        <div class="user-role"><?php echo $_SESSION['admin_role'] === 'admin' ? 'مدير النظام' : 'محرر'; ?></div>
                    </div>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>تسجيل الخروج</span></a>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-building"></i> تفاصيل الكلية: <?php echo htmlspecialchars($college['name']); ?></h2>
                    <div class="card-actions">
                        <a href="edit_college.php?id=<?php echo $college['id']; ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> تعديل</a>
                        <a href="colleges.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-right"></i> العودة</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="college-details">
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-building"></i> اسم الكلية:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($college['name']); ?></div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-university"></i> الجامعة:</div>
                            <div class="detail-value">
                                <?php if (!empty($college['university_name'])): ?>
                                    <strong><?php echo htmlspecialchars($college['university_name']); ?></strong>
                                    <br><small class="type-badge type-<?php echo $college['university_type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                        <?php echo $college['university_type']; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">كلية مستقلة</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-circle"></i> الحالة:</div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $college['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                    <?php echo $college['status']; ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($college['description'])): ?>
                            <div class="detail-row">
                                <div class="detail-label"><i class="fas fa-info-circle"></i> الوصف:</div>
                                <div class="detail-value"><?php echo nl2br(htmlspecialchars($college['description'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-calendar-plus"></i> تاريخ الإنشاء:</div>
                            <div class="detail-value"><?php echo date('Y/m/d H:i:s', strtotime($college['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-number"><?php echo $stats['specializations_count']; ?></div>
                    <div class="stat-label">التخصص</div>
                </div>
            </div>

            <?php if ($specializations_result->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header"><h2 class="card-title"><i class="fas fa-book"></i> تخصصات الكلية</h2></div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>اسم التخصص</th><th>مدة الدراسة</th><th>نوع الدرجة</th><th>الحالة</th><th>تاريخ الإضافة</th><th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($specialization = $specializations_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($specialization['name']); ?></strong></td>
                                        <td><?php echo $specialization['duration']; ?> سنوات</td>
                                        <td><?php echo htmlspecialchars($specialization['degree_type']); ?></td>
                                        <td><span class="status-badge status-<?php echo $specialization['status'] === 'متاح' ? 'active' : 'inactive'; ?>"><?php echo $specialization['status']; ?></span></td>
                                        <td><?php echo date('Y/m/d', strtotime($specialization['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_specialization.php?id=<?php echo $specialization['id']; ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i></a>
                                                <a href="view_specialization.php?id=<?php echo $specialization['id']; ?>" class="btn btn-view btn-sm"><i class="fas fa-eye"></i></a>
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
        .college-details { display: flex; flex-direction: column; gap: 1rem; }
        .detail-row { display: flex; align-items: center; padding: 1rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
        .detail-label { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: #1e293b; min-width: 200px; }
        .detail-label i { color: #667eea; width: 20px; }
        .detail-value { color: #64748b; flex: 1; }
        .btn-outline { background: transparent; color: #667eea; border: 2px solid #667eea; }
        .btn-outline:hover { background: #667eea; color: white; }
    </style>
</body>
</html>
