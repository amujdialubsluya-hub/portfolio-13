<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/includes/logger.php';

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
// حذف تخصص
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $delete_sql = "DELETE FROM specializations WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);

    if ($delete_stmt->execute()) {
        $_SESSION['success_message'] = 'تم حذف التخصص بنجاح';
        log_admin("Deleted specialization id=$id");
    } else {
        $_SESSION['error_message'] = 'خطأ في حذف التخصص';
        log_admin("Failed delete specialization id=$id");
    }
    $delete_stmt->close();
    header('Location: specializations.php');
    exit();
}

// جلب قائمة التخصصات مع معلومات الكلية والجامعة
$specializations_sql = "SELECT
    s.*,
    c.name as college_name,
    c.university_id,
    u.name as university_name,
    u.type as university_type
FROM specializations s
JOIN colleges c ON s.college_id = c.id
LEFT JOIN universities u ON c.university_id = u.id
ORDER BY s.name";

$specializations_result = $conn->query($specializations_sql);

// جلب قائمة الكليات للفلتر
$colleges_sql = "SELECT
    c.id,
    c.name AS college_name,
    u.name AS university_name
FROM colleges c
LEFT JOIN universities u ON c.university_id = u.id
ORDER BY u.name, c.name";

$colleges_result = $conn->query($colleges_sql);
if ($colleges_result === false || $colleges_result->num_rows === 0) {
    log_admin('Specializations page colleges fallback query engaged');
    $fallback_sql = "SELECT id, name AS college_name, '' AS university_name FROM colleges ORDER BY name";
    $colleges_result = $conn->query($fallback_sql);
}

// إحصائيات التخصصات
$stats_sql = "SELECT
    COUNT(*) as total_specializations,
    AVG(duration) as avg_duration,
    COUNT(DISTINCT college_id) as colleges_with_specializations,
    COUNT(CASE WHEN degree_type = 'بكالوريوس' THEN 1 END) as bachelor_count
FROM specializations";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التخصصات - لوحة الإدارة</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    
    <style>
        .search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 3rem 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-icon {
              position: absolute;
        top: 32%;
        transform: translateY(-50%);
        color: #64748b;
        left: 1rem;
        }
        
        .search-hint {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
            text-align: right;
        }
        
        .specializations-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .specializations-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .no-results {
            text-align: center;
            color: #64748b;
            padding: 2rem;
        }
    </style>
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
                <h1 class="page-title">إدارة التخصصات</h1>

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
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $_SESSION['success_message']; ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['error_message']; ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- بطاقة إضافة تخصص جديد -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">إضافة تخصص جديد</h2>
                </div>

                <form class="form-container" method="POST" action="add_specialization.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم التخصص *</label>
                            <input type="text" id="name" name="name" value="<?php echo isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="college_id">الكلية *</label>
                            <select id="college_id" name="college_id" required>
                                <option value="">اختر الكلية</option>
                                <?php if ($colleges_result->num_rows > 0): ?>
                                    <?php while ($college = $colleges_result->fetch_assoc()): ?>
                                        <option value="<?php echo $college['id']; ?>" <?php echo (isset($_SESSION['form_data']['college_id']) && (int)$_SESSION['form_data']['college_id'] === (int)$college['id']) ? 'selected' : ''; ?> >
                                            <?php echo htmlspecialchars($college['college_name']); ?>
                                            <?php if (!empty($college['university_name'])): ?>
                                                - <?php echo htmlspecialchars($college['university_name']); ?>
                                            <?php else: ?>
                                                (كلية مستقلة)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration">مدة الدراسة (سنوات) *</label>
                        <input type="number" id="duration" name="duration" min="1" max="10" value="<?php echo isset($_SESSION['form_data']['duration']) ? (int)$_SESSION['form_data']['duration'] : 4; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="degree_type">نوع الدرجة</label>
                            <select id="degree_type" name="degree_type">
                                <option value="بكالوريوس" <?php echo (isset($_SESSION['form_data']['degree_type']) && $_SESSION['form_data']['degree_type'] === 'بكالوريوس') ? 'selected' : ''; ?>>بكالوريوس</option>
                                <option value="ماجستير" <?php echo (isset($_SESSION['form_data']['degree_type']) && $_SESSION['form_data']['degree_type'] === 'ماجستير') ? 'selected' : ''; ?>>ماجستير</option>
                                <option value="دكتوراه" <?php echo (isset($_SESSION['form_data']['degree_type']) && $_SESSION['form_data']['degree_type'] === 'دكتوراه') ? 'selected' : ''; ?>>دكتوراه</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">وصف التخصص</label>
                        <textarea id="description" name="description" rows="4"><?php echo isset($_SESSION['form_data']['description']) ? htmlspecialchars($_SESSION['form_data']['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="admission_requirement">شروط القبول</label>
                        <textarea id="admission_requirement" name="admission_requirement" rows="3" ><?php echo isset($_SESSION['form_data']['admission_requirement']) ? htmlspecialchars($_SESSION['form_data']['admission_requirement']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="admission_prerequisites">متطلبات القبول</label>
                        <textarea id="admission_prerequisites" name="admission_prerequisites" rows="3" ><?php echo isset($_SESSION['form_data']['admission_prerequisites']) ? htmlspecialchars($_SESSION['form_data']['admission_prerequisites']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">الحالة</label>
                        <select id="status" name="status">
                            <option value="متاح" <?php echo (isset($_SESSION['form_data']['status']) && $_SESSION['form_data']['status'] === 'متاح') ? 'selected' : ''; ?>>متاح</option>
                            <option value="غير متاح" <?php echo (isset($_SESSION['form_data']['status']) && $_SESSION['form_data']['status'] === 'غير متاح') ? 'selected' : ''; ?>>غير متاح</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>إضافة التخصص</span>
                        </button>
                    </div>
                </form>
            </div>

            <?php if (isset($_SESSION['form_data'])) { unset($_SESSION['form_data']); } ?>

            <!-- قائمة التخصصات -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">قائمة التخصصات</h2>
                    <div class="card-actions">
                        <span class="total-count">إجمالي التخصصات: <?php echo $specializations_result->num_rows; ?></span>
                    </div>
                </div>

                <!-- شريط البحث -->
                <div class="search-container">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="specialization-search" class="search-input" placeholder="ابحث في التخصصات... (اسم التخصص، الكلية، الجامعة، نوع الدرجة، الحالة)">
                    <div class="search-hint">
                        ابدأ بالكتابة للبحث التلقائي في جميع حقول التخصصات
                    </div>
                </div>

                <div class="table-container">
                    <table class="table specializations-table">
                        <thead>
                            <tr>
                                <th>اسم التخصص</th>
                                <th>الكلية</th>
                                <th>الجامعة</th>
                                <th>مدة الدراسة</th>
                                <th>نوع الدرجة</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($specializations_result->num_rows > 0): ?>
                                <?php while ($specialization = $specializations_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($specialization['name']); ?></strong>
                                            <?php if ($specialization['description']): ?>
                                                <br><small class="truncate-1"><?php echo htmlspecialchars($specialization['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($specialization['college_name']); ?></td>
                                        <td>
                                            <?php if ($specialization['university_name']): ?>
                                                <strong><?php echo htmlspecialchars($specialization['university_name']); ?></strong>
                                                <br><small class="type-badge type-<?php echo $specialization['university_type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                                    <?php echo $specialization['university_type']; ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="type-badge type-independent">كلية مستقلة</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active">
                                                <?php echo $specialization['duration']; ?> سنوات
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active">
                                                <?php echo $specialization['degree_type']; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="status-badge status-<?php echo $specialization['status'] === 'متاح' ? 'active' : 'inactive'; ?>">
                                                <?php echo $specialization['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_specialization.php?id=<?php echo $specialization['id']; ?>" class="btn btn-edit btn-sm" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_specialization.php?id=<?php echo $specialization['id']; ?>" class="btn btn-view btn-sm" title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?delete=<?php echo $specialization['id']; ?>" class="btn btn-delete btn-sm" title="حذف"
                                                   onclick="return confirm('هل أنت متأكد من حذف تخصص <?php echo htmlspecialchars($specialization['name']); ?>؟')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b; padding: 2rem;">
                                        <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                        <p>لا توجد تخصصات مضافة حالياً</p>
                                        <p>قم بإضافة تخصص جديد من النموذج أعلاه</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('specialization-search');
            const tableRows = document.querySelectorAll('.specializations-table tbody tr');
            const totalCount = document.querySelector('.total-count');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;
                
                tableRows.forEach(row => {
                    const specializationName = row.cells[0].textContent.toLowerCase();
                    const collegeName = row.cells[1].textContent.toLowerCase();
                    const universityName = row.cells[2].textContent.toLowerCase();
                    const duration = row.cells[3].textContent.toLowerCase();
                    const degreeType = row.cells[4].textContent.toLowerCase();
                    const status = row.cells[5].textContent.toLowerCase();
                    
                    const matches = specializationName.includes(searchTerm) || 
                                   collegeName.includes(searchTerm) || 
                                   universityName.includes(searchTerm) ||
                                   duration.includes(searchTerm) ||
                                   degreeType.includes(searchTerm) ||
                                   status.includes(searchTerm);
                    
                    if (matches || searchTerm === '') {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                if (totalCount) {
                    if (searchTerm === '') {
                        totalCount.textContent = `إجمالي التخصصات: ${tableRows.length}`;
                    } else {
                        totalCount.textContent = `عرض ${visibleCount} من ${tableRows.length} تخصص`;
                    }
                }
                
                const noResultsRow = document.querySelector('.no-results');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResultsRow) {
                        const tbody = document.querySelector('.specializations-table tbody');
                        const newRow = document.createElement('tr');
                        newRow.className = 'no-results';
                        newRow.innerHTML = `
                            <td colspan="7" style="text-align: center; color: #64748b; padding: 2rem;">
                                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                <p>لا توجد نتائج بحث تطابق "${searchTerm}"</p>
                                <p>حاول استخدام كلمات بحث أخرى</p>
                            </td>
                        `;
                        tbody.appendChild(newRow);
                    }
                } else if (noResultsRow) {
                    noResultsRow.remove();
                }
            });
        });
    </script>
</body>
</html>