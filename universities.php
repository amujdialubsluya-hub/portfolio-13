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
// معالجة حذف جامعة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // التحقق من وجود الجامعة
    $check_sql = "SELECT name FROM universities WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $university = $result->fetch_assoc();

        // حذف ملفات صور الجامعة من النظام + سجلات الصور
        $imgs_sql = "SELECT image_path FROM university_images WHERE university_id = ?";
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

        // حذف مجلد uploads/universities/{id} كاملاً إن وُجد
        $uniDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'universities' . DIRECTORY_SEPARATOR . $id;
        if (is_dir($uniDir)) {
            $it = new RecursiveDirectoryIterator($uniDir, FilesystemIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) { @rmdir($file->getRealPath()); } else { @unlink($file->getRealPath()); }
            }
            @rmdir($uniDir);
        }

        // حذف السجلات: الصور ثم الجامعة
        $conn->query("DELETE FROM university_images WHERE university_id = " . (int)$id);

        // حذف الجامعة
        $delete_sql = "DELETE FROM universities WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "تم حذف جامعة '" . $university['name'] . "' بنجاح";
            log_admin("Deleted university id=$id and removed images");
        } else {
            $_SESSION['error_message'] = "حدث خطأ أثناء حذف الجامعة";
            log_admin("Failed deleting university id=$id: " . $delete_stmt->error);
        }
    } else {
        $_SESSION['error_message'] = "الجامعة غير موجودة";
    }
    header('Location: universities.php');
    exit();
}

// جلب قائمة الجامعات
$universities_sql = "SELECT * FROM universities ORDER BY created_at DESC";
$universities_result = $conn->query($universities_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الجامعات - لوحة الإدارة</title>
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
        
        /* تنسيقات الصور - نفس تنسيق صفحة الكليات */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #fff;
            font-size: 1rem;
            transition: border-color 0.3s ease;
          
        }
        
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
          
        }
        
        .form-group .hint {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.4;
        }
        
        /* تنسيق حقل رفع الصور - نفس تصميم صفحة الكليات */
        .form-group input[type="file"] {
            cursor: pointer;
            color: #374151;
        }
        
        .form-group input[type="file"]::-webkit-file-upload-button {
            padding: 0.5rem 1rem;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .form-group input[type="file"]::-webkit-file-upload-button:hover {
            background-color: #2563eb;
        }
        
        .form-group input[type="file"]::file-selector-button {
            padding: 0.5rem 1rem;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .form-group input[type="file"]::file-selector-button:hover {
            background-color: #2563eb;
        }
        
        /* تنسيق checkbox جامعة مميزة */
        .form-groupq {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .form-groupq label {
            margin: 0;
            cursor: pointer;
            font-weight: 600;
            color: #495057;
        }
        
        .form-groupq input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            
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
                <h1 class="page-title">إدارة الجامعات</h1>

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

            <?php if (isset($_SESSION['form_data'])) { unset($_SESSION['form_data']); } ?>

            <!-- بطاقة إضافة جامعة جديدة -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">إضافة جامعة جديدة</h2>
                </div>

                <form class="form-container" method="POST" action="add_university.php" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم الجامعة *</label>
                            <input type="text" id="name" name="name" value="<?php echo isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="type">نوع الجامعة *</label>
                            <select id="type" name="type" required>
                                <option value="">اختر النوع</option>
                                <option value="حكومية" <?php echo (isset($_SESSION['form_data']['type']) && $_SESSION['form_data']['type'] === 'حكومية') ? 'selected' : ''; ?>>حكومية</option>
                                <option value="أهلية" <?php echo (isset($_SESSION['form_data']['type']) && $_SESSION['form_data']['type'] === 'أهلية') ? 'selected' : ''; ?>>أهلية</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">الموقع *</label>
                            <input type="text" id="location" name="location" value="<?php echo isset($_SESSION['form_data']['location']) ? htmlspecialchars($_SESSION['form_data']['location']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="website">الموقع الإلكتروني</label>
                            <input type="url" id="website" name="website" value="<?php echo isset($_SESSION['form_data']['website']) ? htmlspecialchars($_SESSION['form_data']['website']) : ''; ?>" placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">رقم الهاتف</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo isset($_SESSION['form_data']['phone']) ? htmlspecialchars($_SESSION['form_data']['phone']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="map_url">رابط الموقع الجغرافي (Google Maps)</label>
                            <input type="url" id="map_url" name="map_url" value="<?php echo isset($_SESSION['form_data']['map_url']) ? htmlspecialchars($_SESSION['form_data']['map_url']) : ''; ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                            <small class="form-help">انسخ رابط التضمين (Embed) من Google Maps</small>
                        </div>

                        <div class="form-group">
                            <label for="coordination_link">رابط تنسيق الجامعة</label>
                            <input type="url" id="coordination_link" name="coordination_link" value="<?php echo isset($_SESSION['form_data']['coordination_link']) ? htmlspecialchars($_SESSION['form_data']['coordination_link']) : ''; ?>" placeholder="https://example.edu.ye/coordination">
                                                  </div>
                    </div>

                    <div class="form-group">
                        <label for="description">الوصف</label>
                        <textarea id="description" name="description" rows="4"><?php echo isset($_SESSION['form_data']['description']) ? htmlspecialchars($_SESSION['form_data']['description']) : ''; ?></textarea>
                    </div>
                    
                    <!-- حقل رفع الصور - نفس تصميم صفحة الكليات -->
                    <div class="form-group">
                        <label for="college_images">صور الجامعة (يمكن اختيار عدة صور)</label>
                        <input type="file" id="college_images" name="college_images[]" accept="image/*" multiple>
                        <small class="hint">الأنواع المسموحة: JPG, PNG, WEBP, GIF. الحد الأقصى للحجم: 5MB لكل صورة.</small>
                    </div>

                    <div class="form-group">
                        <label for="status">الحالة</label>
                        <select id="status" name="status">
                            <option value="نشطة" <?php echo (isset($_SESSION['form_data']['status']) && $_SESSION['form_data']['status'] === 'نشطة') ? 'selected' : ''; ?>>نشطة</option>
                            <option value="غير نشطة" <?php echo (isset($_SESSION['form_data']['status']) && $_SESSION['form_data']['status'] === 'غير نشطة') ? 'selected' : ''; ?>>غير نشطة</option>
                        </select>
                    </div>
                    
                    <div class="form-groupq"> 
                        <label>جامعة مميزة</label>
                        <input type="checkbox" name="is_featured" value="1"  
                            <?php if(!empty($_SESSION['form_data']['is_featured'])) echo 'checked'; ?>> 
                    </div>
                    <small class="hint-checkbox" id="featured-hint">
                            يمكن تفعيل هذه الخاصية للجامعات المميزة
                        </small>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>إضافة الجامعة</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- قائمة الجامعات -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">قائمة الجامعات</h2>
                    <div class="card-actions">
                        <span class="total-count">إجمالي الجامعات: <?php echo $universities_result->num_rows; ?></span>
                    </div>
                </div>

                <!-- شريط البحث -->
                <div class="search-container">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="university-search" class="search-input" placeholder="ابحث في الجامعات... (اسم الجامعة، الموقع، النوع، الحالة)">
                    <div class="search-hint">
                        ابدأ بالكتابة للبحث التلقائي في جميع حقول الجامعات
                    </div>
                </div>

                <div class="table-container">
                    <table class="table universities-table">
                        <thead>
                            <tr>
                                <th>اسم الجامعة</th>
                                <th>النوع</th>
                                <th>الموقع</th>
                                <th>الموقع الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($universities_result->num_rows > 0): ?>
                                <?php while($university = $universities_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($university['name']); ?></strong>
                                            <?php if ($university['email']): ?>
                                                <br><small><?php echo htmlspecialchars($university['email']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($university['coordination_link']): ?>
                                                <br><a href="<?php echo htmlspecialchars($university['coordination_link']); ?>" target="_blank" class="link coordination-link" style="font-size: 0.8rem;">
                                                    <i class="fas fa-link"></i> رابط التنسيق
                                                </a>
                                            <?php endif; ?>
                                       
                                            
                                         <?php if (isset($university['is_featured']) && $university['is_featured'] == 1): ?>
                                                <br><small style="color: #f59e0b; font-weight: bold;">
                                                    <i class="fas fa-star featured-star"></i>جامعة مميزة
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>

                                            <span class="type-badge type-<?php echo $university['type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                                <?php echo $university['type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($university['location']); ?></td>
                                        <td>
                                            <?php if ($university['website']): ?>
                                                <a href="<?php echo htmlspecialchars($university['website']); ?>" target="_blank" class="link">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    زيارة الموقع
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">غير متوفر</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($university['phone']): ?>
                                                <a href="tel:<?php echo htmlspecialchars($university['phone']); ?>" class="link">
                                                    <i class="fas fa-phone"></i>
                                                    <?php echo htmlspecialchars($university['phone']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">غير متوفر</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $university['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                                <?php echo $university['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_university.php?id=<?php echo $university['id']; ?>" class="btn btn-edit btn-sm" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_university.php?id=<?php echo $university['id']; ?>" class="btn btn-view btn-sm" title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../university-details.php?id=<?php echo $university['id']; ?>" class="btn btn-view btn-sm" title="عرض التفاصيل (الواجهة العامة)" target="_blank" rel="noopener">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <a href="?delete=<?php echo $university['id']; ?>" class="btn btn-delete btn-sm" title="حذف"
                                                   onclick="return confirm('هل أنت متأكد من حذف جامعة <?php echo htmlspecialchars($university['name']); ?>؟')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b; padding: 2rem;">
                                        <i class="fas fa-university" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                        <p>لا توجد جامعات مضافة حالياً</p>
                                        <p>قم بإضافة جامعة جديدة من النموذج أعلاه</p>
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
            const searchInput = document.getElementById('university-search');
            const tableRows = document.querySelectorAll('.universities-table tbody tr');
            const totalCount = document.querySelector('.total-count');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;
                
                tableRows.forEach(row => {
                    const universityName = row.cells[0].textContent.toLowerCase();
                    const universityType = row.cells[1].textContent.toLowerCase();
                    const location = row.cells[2].textContent.toLowerCase();
                    const website = row.cells[3].textContent.toLowerCase();
                    const phone = row.cells[4].textContent.toLowerCase();
                    const status = row.cells[5].textContent.toLowerCase();
                    
                    const matches = universityName.includes(searchTerm) || 
                                   universityType.includes(searchTerm) || 
                                   location.includes(searchTerm) ||
                                   website.includes(searchTerm) ||
                                   phone.includes(searchTerm) ||
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
                        totalCount.textContent = `إجمالي الجامعات: ${tableRows.length}`;
                    } else {
                        totalCount.textContent = `عرض ${visibleCount} من ${tableRows.length} جامعة`;
                    }
                }
                
                const noResultsRow = document.querySelector('.no-results');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResultsRow) {
                        const tbody = document.querySelector('.universities-table tbody');
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
