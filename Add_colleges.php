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

// حذف كلية
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // حذف ملفات صور الكلية من النظام + سجلات الصور
    $imgs_sql = "SELECT image_path FROM college_images WHERE college_id = ?";
    $imgs_stmt = $conn->prepare($imgs_sql);
    $imgs_stmt->bind_param("i", $id);
    if ($imgs_stmt->execute()) {
        $res = $imgs_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rel = $row['image_path'];
            $abs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
            if ($abs && is_file($abs)) { @unlink($abs); }
        }
    }
    $imgs_stmt->close();

    // حذف مجلد uploads/colleges/{id} كاملاً إن وُجد
    $collegeDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'colleges' . DIRECTORY_SEPARATOR . $id;
    if (is_dir($collegeDir)) {
        $it = new RecursiveDirectoryIterator($collegeDir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) { @rmdir($file->getRealPath()); } else { @unlink($file->getRealPath()); }
        }
        @rmdir($collegeDir);
    }

    // حذف السجلات: الصور ثم الكلية
    $conn->query("DELETE FROM college_images WHERE college_id = " . (int)$id);

    $delete_sql = "DELETE FROM colleges WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);

    if ($delete_stmt->execute()) {
        $_SESSION['success_message'] = 'تم حذف الكلية بنجاح';
        log_admin("Deleted college id=$id and removed images");
    } else {
        $_SESSION['error_message'] = 'خطأ في حذف الكلية';
        log_admin("Failed deleting college id=$id: " . $delete_stmt->error);
    }
    $delete_stmt->close();
    header('Location: colleges.php');
    exit();
}

// جلب قائمة الكليات مع معلومات الجامعة
$colleges_sql = "SELECT
    c.*,
    u.name as university_name,
    u.type as university_type,
    COUNT(s.id) as specializations_count
FROM colleges c
LEFT JOIN universities u ON c.university_id = u.id
LEFT JOIN specializations s ON c.id = s.college_id
GROUP BY c.id
ORDER BY c.name";

$colleges_result = $conn->query($colleges_sql);

// جلب قائمة الجامعات للفلتر
$universities_sql = "SELECT id, name, type FROM universities WHERE status = 'نشطة' ORDER BY name";
$universities_result = $conn->query($universities_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الكليات - لوحة الإدارة</title>
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
        
        .universities-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .universities-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .no-results {
            text-align: center;
            color: #64748b;
            padding: 2rem;
        }
        
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
        }
        
        .type-badge.type-government {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .type-badge.type-private {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .type-badge.type-independent {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
        }
        
        .status-badge.status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .link {
            color: #3b82f6;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        
        .coordination-link {
            color: #10b981;
        }
        
        .coordination-link:hover {
            color: #047857;
        }
        
        .text-muted {
            color: #6b7280;
            font-style: italic;
        }
        
        /* تنسيق checkbox المعطل */
        .checkbox-disabled {
            opacity: 0.5;
            cursor: not-allowed !important;
        }
        
        .checkbox-disabled-label {
            color: #999 !important;
            cursor: not-allowed !important;
        }
        
        .form-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
        }
        
        .hint-checkbox {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            margin-right: 28px;
            display: block;
        }
        
        .featured-star {
            color: #f59e0b;
            margin-right: 4px;
        }
        
        /* تنسيق جديد للـcheckbox */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
            padding: 0;
        }
        
        .checkbox-wrapper label {
            margin: 0;
            padding: 0;
            cursor: pointer;
            font-weight: normal;
        }
        
        .checkbox-field {
            margin: 15px 0;
        }
    </style>

    <script>
        function toggleLocationAndTypeFields() {
            const universitySelect = document.getElementById('university_id');
            const locationGroup = document.getElementById('location-group');
            const locationInput = document.getElementById('location');
            const collegeTypeGroup = document.getElementById('college-type-group');
            const collegeTypeInput = document.getElementById('college_type');
            
            if (universitySelect.value === '') {
                locationGroup.style.display = 'block';
                locationInput.required = true;
                collegeTypeGroup.style.display = 'block';
                collegeTypeInput.required = true;
            } else {
                locationGroup.style.display = 'none';
                locationInput.required = false;
                locationInput.value = '';
                collegeTypeGroup.style.display = 'none';
                collegeTypeInput.required = false;
                collegeTypeInput.value = '';
            }
        }
        
        // دالة لتحديد حالة checkbox كلية مميزة بناءً على اختيار الجامعة
        function toggleFeaturedCheckbox() {
            const universitySelect = document.getElementById('university_id');
            const featuredCheckbox = document.getElementById('featured-checkbox');
            const featuredLabel = document.querySelector('label[for="featured-checkbox"]');
            const featuredHint = document.querySelector('.hint-checkbox');
            
            // إذا كانت الكلية تابعة لجامعة (تم اختيار جامعة)
            if (universitySelect.value !== '') {
                // تعطيل checkbox كلية مميزة
                if (featuredCheckbox) {
                    featuredCheckbox.disabled = true;
                    featuredCheckbox.checked = false;
                    featuredCheckbox.classList.add('checkbox-disabled');
                }
                if (featuredLabel) {
                    featuredLabel.classList.add('checkbox-disabled-label');
                }
                if (featuredHint) {
                    featuredHint.style.color = '#999';
                    featuredHint.innerHTML = 'هذه الخاصية غير متاحة للكليات التابعة للجامعات';
                }
            } 
            // إذا كانت الكلية غير تابعة لجامعة (لم يتم اختيار جامعة)
            else {
                // تفعيل checkbox كلية مميزة
                if (featuredCheckbox) {
                    featuredCheckbox.disabled = false;
                    featuredCheckbox.classList.remove('checkbox-disabled');
                }
                if (featuredLabel) {
                    featuredLabel.classList.remove('checkbox-disabled-label');
                }
                if (featuredHint) {
                    featuredHint.style.color = '#666';
                    featuredHint.innerHTML = 'يمكن تفعيل هذه الخاصية فقط للكليات غير التابعة لأي جامعة';
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            toggleLocationAndTypeFields();
            toggleFeaturedCheckbox(); // تعيين الحالة الابتدائية
            
            // إضافة مستمع للأحداث لحقل اختيار الجامعة
            const universitySelect = document.getElementById('university_id');
            if (universitySelect) {
                universitySelect.addEventListener('change', function() {
                    toggleLocationAndTypeFields();
                    toggleFeaturedCheckbox();
                });
            }
            
            const searchInput = document.getElementById('college-search');
            const tableRows = document.querySelectorAll('.universities-table tbody tr');
            const totalCount = document.querySelector('.total-count');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    let visibleCount = 0;
                    
                    tableRows.forEach(row => {
                        const collegeName = row.cells[0].textContent.toLowerCase();
                        const universityName = row.cells[1].textContent.toLowerCase();
                        const collegeType = row.cells[2].textContent.toLowerCase();
                        const specializationsCount = row.cells[3].textContent.toLowerCase();
                        const status = row.cells[4].textContent.toLowerCase();
                        
                        const matches = collegeName.includes(searchTerm) || 
                                       universityName.includes(searchTerm) || 
                                       collegeType.includes(searchTerm) ||
                                       specializationsCount.includes(searchTerm) ||
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
                            totalCount.textContent = `إجمالي الكليات: ${tableRows.length}`;
                        } else {
                            totalCount.textContent = `عرض ${visibleCount} من ${tableRows.length} كلية`;
                        }
                    }
                    
                    const noResultsRow = document.querySelector('.no-results');
                    if (visibleCount === 0 && searchTerm !== '') {
                        if (!noResultsRow) {
                            const tbody = document.querySelector('.universities-table tbody');
                            const newRow = document.createElement('tr');
                            newRow.className = 'no-results';
                            newRow.innerHTML = `
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">
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
            }
        });
    </script>
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
                            <a href="colleges.php" class="nav-link active">
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
                <h1 class="page-title">إدارة الكليات</h1>

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

            <!-- بطاقة إضافة كلية جديدة -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">إضافة كلية جديدة</h2>
                </div>

                <form class="form-container" method="POST" action="add_college.php" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم الكلية *</label>
                            <input type="text" id="name" name="name" value="<?php echo isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="university_id">الجامعة (اختياري)</label>
                            <select id="university_id" name="university_id" onchange="toggleLocationAndTypeFields(); toggleFeaturedCheckbox();">
                                <option value="">كلية غير تابعة لأي جامعة</option>
                                <?php 
                                // إعادة تعيين مؤشر النتائج لاستخدامه مرة أخرى
                                if ($universities_result->num_rows > 0): 
                                    $universities_result->data_seek(0);
                                    while ($university = $universities_result->fetch_assoc()): ?>
                                        <option value="<?php echo $university['id']; ?>" <?php echo (isset($_SESSION['form_data']['university_id']) && (int)$_SESSION['form_data']['university_id'] === (int)$university['id']) ? 'selected' : ''; ?> >
                                            <?php echo htmlspecialchars($university['name']); ?>
                                            (<?php echo $university['type']; ?>)
                                        </option>
                                    <?php endwhile; 
                                endif; ?>
                            </select>
                            <small class="hint">اترك هذا الحقل فارغاً إذا كانت الكلية غير تابعة لأي جامعة</small>
                        </div>
                    </div>

                    <!-- حقل نوع الكلية (يظهر فقط للكليات المستقلة) -->
                    <div class="form-row" id="college-type-group" style="display: none;">
                        <div class="form-group">
                            <label for="college_type" class="form-label">نوع الكلية *</label>
                            <select id="college_type" name="college_type" class="form-input" required>
                                <option value="">اختر نوع الكلية</option>
                                <option value="حكومية" <?php echo (isset($_SESSION['form_data']['college_type']) && $_SESSION['form_data']['college_type'] === 'حكومية') ? 'selected' : ''; ?>>حكومية</option>
                                <option value="أهلية" <?php echo (isset($_SESSION['form_data']['college_type']) && $_SESSION['form_data']['college_type'] === 'أهلية') ? 'selected' : ''; ?>>أهلية</option>
                            </select>
                            <small class="hint">هذا الحقل مطلوب للكليات غير التابعة لأي جامعة</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">وصف الكلية</label>
                        <textarea id="description" name="description" rows="4"><?php echo isset($_SESSION['form_data']['description']) ? htmlspecialchars($_SESSION['form_data']['description']) : ''; ?></textarea>
                    </div>

                    <!-- حقل الموقع للكليات المستقلة -->
                    <div class="form-row" id="location-group" style="display: none;">
                        <div class="form-group">
                            <label for="location" class="form-label">موقع الكلية *</label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo isset($_SESSION['form_data']['location']) ? htmlspecialchars($_SESSION['form_data']['location']) : ''; ?>" 
                                   placeholder="أدخل موقع الكلية (مثال: صنعاء، اليمن)">
                            <small class="hint">هذا الحقل مطلوب للكليات غير التابعة لأي جامعة</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="coordination_link">رابط تنسيق الكلية</label>
                            <input type="url" id="coordination_link" name="coordination_link" 
                                   value="<?php echo isset($_SESSION['form_data']['coordination_link']) ? htmlspecialchars($_SESSION['form_data']['coordination_link']) : ''; ?>" 
                                   placeholder="https://example.com/coordination">
                        </div>

                        <div class="form-group">
                            <label for="website">الموقع الرسمي للكلية</label>
                            <input type="url" id="website" name="website" 
                                   value="<?php echo isset($_SESSION['form_data']['website']) ? htmlspecialchars($_SESSION['form_data']['website']) : ''; ?>" 
                                   placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">رقم الهاتف</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo isset($_SESSION['form_data']['phone']) ? htmlspecialchars($_SESSION['form_data']['phone']) : ''; ?>" 
                                   placeholder="+967123456789">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>" 
                                   placeholder="college@example.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="map_url">رابط الموقع الجغرافي (Google Maps)</label>
                        <input type="url" id="map_url" name="map_url" 
                               value="<?php echo isset($_SESSION['form_data']['map_url']) ? htmlspecialchars($_SESSION['form_data']['map_url']) : ''; ?>" 
                               placeholder="https://maps.google.com/?q=latitude,longitude">
                        <small class="hint">رابط الخريطة من Google Maps (اختياري)</small>
                    </div>

                    <div class="form-group">
                        <label for="status">الحالة</label>
                        <select id="status" name="status">
                            <option value="نشطة" <?php echo (isset($_SESSION['form_data']['status']) && $_SESSION['form_data']['status'] === 'نشطة') ? 'selected' : ''; ?>>نشطة</option>
                            <option value="غير نشطة" <?php echo (isset($_SESSION['form_data']['status']) && $_SESSION['form_data']['status'] === 'غير نشطة') ? 'selected' : ''; ?>>غير نشطة</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="college_images">صور الكلية (يمكن اختيار عدة صور)</label>
                        <input type="file" id="college_images" name="college_images[]" accept="image/*" multiple>
                        <small class="hint">الأنواع المسموحة: JPG, PNG, WEBP, GIF. الحد الأقصى للحجم: 5MB لكل صورة.</small>
                    </div>
                    
                    <!-- حقل كلية مميزة - تم تعديله ليظهر بشكل صحيح -->
                    <div class="form-group checkbox-field">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="featured-checkbox" name="is_featured" value="1"  
                                   <?php if(!empty($_SESSION['form_data']['is_featured'])) echo 'checked'; ?>>
                            <label for="featured-checkbox" id="featured-label" style="display: inline; margin: 0; padding: 0; cursor: pointer;">كلية مميزة؟</label>
                        </div>
                        <small class="hint-checkbox">
                            يمكن تفعيل هذه الخاصية فقط للكليات غير التابعة لأي جامعة
                        </small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>إضافة الكلية</span>
                        </button>
                    </div>
                </form>
            </div>

            <?php if (isset($_SESSION['form_data'])) { unset($_SESSION['form_data']); } ?>

            <!-- قائمة الكليات -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">قائمة الكليات</h2>
                    <div class="card-actions">
                        <span class="total-count">إجمالي الكليات: <?php echo $colleges_result->num_rows; ?></span>
                    </div>
                </div>

                <!-- شريط البحث -->
                <div class="search-container">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="college-search" class="search-input" placeholder="ابحث في الكليات... (اسم الكلية، الجامعة، النوع، الحالة)">
                    <div class="search-hint">
                        ابدأ بالكتابة للبحث التلقائي في جميع حقول الكليات
                    </div>
                </div>

                <div class="table-container">
                    <table class="table universities-table">
                        <thead>
                            <tr>
                                <th>اسم الكلية</th>
                                <th>الجامعة</th>
                                <th>النوع</th>
                                <th>عدد التخصصات</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($colleges_result->num_rows > 0): ?>
                                <?php while ($college = $colleges_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($college['name']); ?></strong>
                                            <?php if ($college['email']): ?>
                                                <br><small><?php echo htmlspecialchars($college['email']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($college['coordination_link']): ?>
                                                <br><a href="<?php echo htmlspecialchars($college['coordination_link']); ?>" target="_blank" class="link coordination-link" style="font-size: 0.8rem;">
                                                    <i class="fas fa-link"></i> رابط التنسيق
                                                </a>
                                            <?php endif; ?>
                                            <?php if (isset($college['is_featured']) && $college['is_featured'] == 1): ?>
                                                <br><small style="color: #f59e0b; font-weight: bold;">
                                                    <i class="fas fa-star featured-star"></i> كلية مميزة
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($college['university_name']): ?>
                                                <strong><?php echo htmlspecialchars($college['university_name']); ?></strong>
                                                <br><small class="type-badge type-<?php echo $college['university_type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                                    <?php echo $college['university_type']; ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="type-badge type-independent">غير تابعة</span>
                                                <?php if ($college['location']): ?>
                                                    <br><small><?php echo htmlspecialchars($college['location']); ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($college['university_id'] === null): ?>
                                                <span class="type-badge type-independent">مستقلة</span>
                                                <br><small><?php echo htmlspecialchars($college['type'] ?? ''); ?></small>
                                            <?php else: ?>
                                                <span class="type-badge type-<?php echo $college['university_type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                                    <?php echo $college['university_type']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active">
                                                <?php echo $college['specializations_count']; ?> تخصص
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $college['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                                <?php echo $college['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_college.php?id=<?php echo $college['id']; ?>" class="btn btn-edit btn-sm" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_college.php?id=<?php echo $college['id']; ?>" class="btn btn-view btn-sm" title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../college-details.php?id=<?php echo $college['id']; ?>" class="btn btn-view btn-sm" title="عرض التفاصيل (الواجهة العامة)" target="_blank" rel="noopener">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <a href="?delete=<?php echo $college['id']; ?>" class="btn btn-delete btn-sm" title="حذف"
                                                   onclick="return confirm('هل أنت متأكد من حذف كلية <?php echo htmlspecialchars($college['name']); ?>؟')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">
                                        <i class="fas fa-building" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                        <p>لا توجد كليات مضافة حالياً</p>
                                        <p>قم بإضافة كلية جديدة من النموذج أعلاه</p>
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
</body>
</html>
