<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// جلب بيانات التخصص
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $specialization_sql = "SELECT s.*, c.name as college_name, u.name as university_name, u.type as university_type
                          FROM specializations s
                          JOIN colleges c ON s.college_id = c.id
                          JOIN universities u ON c.university_id = u.id
                          WHERE s.id = ?";
    $specialization_stmt = $conn->prepare($specialization_sql);
    $specialization_stmt->bind_param("i", $id);
    $specialization_stmt->execute();
    $specialization_result = $specialization_stmt->get_result();

    if ($specialization_result->num_rows === 0) {
        header('Location: specializations.php');
        exit();
    }

    $specialization = $specialization_result->fetch_assoc();
    $specialization_stmt->close();


} else {
    header('Location: specializations.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض التخصص - لوحة الإدارة</title>

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
                            <a href="specializations.php" class="nav-link active">
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
                <h1 class="page-title">عرض التخصص</h1>

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

            <!-- تفاصيل التخصص -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-book"></i>
                        تفاصيل التخصص: <?php echo htmlspecialchars($specialization['name']); ?>
                    </h2>
                    <div class="card-actions">
                        <a href="edit_specialization.php?id=<?php echo $specialization['id']; ?>" class="btn btn-edit btn-sm">
                            <i class="fas fa-edit"></i>
                            تعديل
                        </a>
                        <a href="specializations.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-right"></i>
                            العودة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="specialization-details">
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-book"></i>
                                اسم التخصص:
                            </div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($specialization['name']); ?>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-building"></i>
                                الكلية:
                            </div>
                            <div class="detail-value">
                                <strong><?php echo htmlspecialchars($specialization['college_name']); ?></strong>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-university"></i>
                                الجامعة:
                            </div>
                            <div class="detail-value">
                                <strong><?php echo htmlspecialchars($specialization['university_name']); ?></strong>
                                <br><small class="type-badge type-<?php echo $specialization['university_type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                    <?php echo $specialization['university_type']; ?>
                                </small>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-clock"></i>
                                مدة الدراسة:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-active">
                                    <?php echo $specialization['duration']; ?> سنوات
                                </span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-graduation-cap"></i>
                                نوع الدرجة:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-active">
                                    <?php echo $specialization['degree_type']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-percentage"></i>
                                شروط القبول:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-active">
                                    <?php echo htmlspecialchars($specialization['admission_requirement'] ?? 'غير محدد'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-circle"></i>
                                الحالة:
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $specialization['status'] === 'متاح' ? 'active' : 'inactive'; ?>">
                                    <?php echo $specialization['status']; ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($specialization['description'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-info-circle"></i>
                                الوصف:
                            </div>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($specialization['description'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-calendar-plus"></i>
                                تاريخ الإنشاء:
                            </div>
                            <div class="detail-value">
                                <?php echo date('Y/m/d H:i:s', strtotime($specialization['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات التخصص -->
            <div class="stats-grid">
            </div>

        </main>
    </div>

    <script src="assets/js/admin.js"></script>

    <style>
        .specialization-details {
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
