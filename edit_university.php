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

$success_message = '';
$error_message = '';

// جلب بيانات الجامعة للتعديل
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $university_sql = "SELECT * FROM universities WHERE id = ?";
    $university_stmt = $conn->prepare($university_sql);
    $university_stmt->bind_param("i", $id);
    $university_stmt->execute();
    $university_result = $university_stmt->get_result();

    if ($university_result->num_rows === 0) {
        $_SESSION['error_message'] = 'الجامعة غير موجودة';
        header('Location: universities.php');
        exit();
    }

    $university = $university_result->fetch_assoc();
    $university_stmt->close();

    // جلب صور الجامعة
    $images = [];
    $imgs_sql = "SELECT * FROM university_images WHERE university_id = ? ORDER BY sort_order, id";
    $imgs_stmt = $conn->prepare($imgs_sql);
    $imgs_stmt->bind_param("i", $id);
    $imgs_stmt->execute();
    $imgs_result = $imgs_stmt->get_result();
    if ($imgs_result) { 
        $images = $imgs_result->fetch_all(MYSQLI_ASSOC); 
    }
    $imgs_stmt->close();
} else {
    $_SESSION['error_message'] = 'معرف الجامعة غير صحيح';
    header('Location: universities.php');
    exit();
}

// جلب قائمة الجامعات
$universities_sql = "SELECT id, name, type FROM universities WHERE status = 'نشطة' ORDER BY name";
$universities_result = $conn->query($universities_sql);

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // حذف صورة - التحقق أولاً من وجود طلب حذف صورة
    if (isset($_POST['delete_image']) && is_numeric($_POST['delete_image'])) {
        $img_id = (int)$_POST['delete_image'];
        $one_sql = "SELECT image_path FROM university_images WHERE id = ? AND university_id = ?";
        $one_stmt = $conn->prepare($one_sql);
        $one_stmt->bind_param("ii", $img_id, $id);
        $one_stmt->execute();
        $one_res = $one_stmt->get_result();
        if ($one_res->num_rows === 1) {
            $row = $one_res->fetch_assoc();
            $abs = realpath(__DIR__ . '/../' . str_replace(['\\','/'], DIRECTORY_SEPARATOR, $row['image_path']));
            $del_sql = "DELETE FROM university_images WHERE id = ? AND university_id = ?";
            $del_stmt = $conn->prepare($del_sql);
            $del_stmt->bind_param("ii", $img_id, $id);
            if ($del_stmt->execute()) {
                if ($abs && is_file($abs)) { 
                    @unlink($abs); 
                    log_admin("Deleted university image: " . $abs);
                }
                $success_message = 'تم حذف الصورة بنجاح';
                log_admin("Deleted image id=$img_id for university id=$id");
            } else {
                $error_message = 'تعذر حذف الصورة';
                log_admin("Failed to delete image id=$img_id for university id=$id: " . $del_stmt->error);
            }
            $del_stmt->close();
        } else {
            $error_message = 'الصورة غير موجودة';
        }
        $one_stmt->close();

        // إعادة تحميل الصور بعد التعديل
        $imgs_stmt2 = $conn->prepare($imgs_sql);
        $imgs_stmt2->bind_param("i", $id);
        $imgs_stmt2->execute();
        $images = $imgs_stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $imgs_stmt2->close();
    } 
    // إذا لم يكن طلب حذف صورة، فمعالجة تحديث بيانات الجامعة
    elseif (isset($_POST['update_university'])) {
        // تحديث بيانات الجامعة
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $map_url = trim($_POST['map_url'] ?? '');
        $coordination_link = trim($_POST['coordination_link'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'نشطة';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        $errors = [];

        // التحقق من البيانات
        if (empty($name)) {
            $errors[] = 'اسم الجامعة مطلوب';
        }
        if (empty($type)) {
            $errors[] = 'نوع الجامعة مطلوب';
        }
        if (empty($location)) {
            $errors[] = 'موقع الجامعة مطلوب';
        }

        // تحديث الجامعة إذا لم تكن هناك أخطاء
        if (empty($errors)) {
            $update_sql = "UPDATE universities SET 
                name = ?, 
                type = ?, 
                location = ?, 
                website = ?, 
                phone = ?, 
                email = ?, 
                map_url = ?, 
                coordination_link = ?, 
                description = ?, 
                status = ?, 
                is_featured = ? 
                WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param(
                "ssssssssssii", 
                $name, 
                $type, 
                $location,
                $website,
                $phone,
                $email,
                $map_url,
                $coordination_link,
                $description,
                $status,
                $is_featured,
                $id
            );

            if ($update_stmt->execute()) {
                $success_message = 'تم تحديث الجامعة بنجاح!';
                log_admin("Updated university id=$id: $name (Featured: $is_featured)");

                // معالجة رفع صور جديدة
                if (!empty($_FILES['college_images']) && isset($_FILES['college_images']['name']) && is_array($_FILES['college_images']['name'])) {
                    $upload_root = __DIR__ . '/../uploads';
                    if (!is_dir($upload_root)) { @mkdir($upload_root, 0777, true); }
                    $university_dir = $upload_root . '/universities/' . $id;
                    if (!is_dir($university_dir)) { @mkdir($university_dir, 0777, true); }

                    $allowed_types = ['image/jpeg','image/png','image/webp','image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    $names = $_FILES['college_images']['name'];
                    $tmp_names = $_FILES['college_images']['tmp_name'];
                    $types = $_FILES['college_images']['type'];
                    $sizes = $_FILES['college_images']['size'];
                    $errors_upload = $_FILES['college_images']['error'];

                    $saved = 0; 
                    $skipped = 0;
                    
                    for ($i = 0; $i < count($names); $i++) {
                        if ($errors_upload[$i] !== UPLOAD_ERR_OK) { $skipped++; continue; }
                        if (!in_array($types[$i], $allowed_types)) { $skipped++; continue; }
                        if ($sizes[$i] > $max_size) { $skipped++; continue; }
                        if (!is_uploaded_file($tmp_names[$i])) { $skipped++; continue; }

                        $ext = pathinfo($names[$i], PATHINFO_EXTENSION);
                        $safe_name = uniqid('img_', true) . '.' . strtolower($ext);
                        $dest_path = $university_dir . '/' . $safe_name;
                        
                        if (@move_uploaded_file($tmp_names[$i], $dest_path)) {
                            $relative_path = 'uploads/universities/' . $id . '/' . $safe_name;
                            $img_sql = "INSERT INTO university_images (university_id, image_path) VALUES (?, ?)";
                            $img_stmt = $conn->prepare($img_sql);
                            $img_stmt->bind_param("is", $id, $relative_path);
                            $img_stmt->execute();
                            $img_stmt->close();
                            $saved++;
                            log_admin("Uploaded image for university id=$id: $safe_name");
                        }
                    }

                    // تحديث القائمة بعد الرفع
                    $imgs_stmt3 = $conn->prepare($imgs_sql);
                    $imgs_stmt3->bind_param("i", $id);
                    $imgs_stmt3->execute();
                    $images = $imgs_stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
                    $imgs_stmt3->close();

                    if ($saved > 0) {
                        $success_message .= ' | تم رفع ' . $saved . ' صورة.';
                    }
                }
            } else {
                $error_message = 'خطأ في تحديث الجامعة: ' . $update_stmt->error;
                log_admin("Failed to update university id=$id: " . $update_stmt->error);
            }
            $update_stmt->close();
        } else {
            $error_message = 'يرجى تصحيح الأخطاء التالية: ' . implode(', ', $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الجامعة - لوحة الإدارة</title>
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
        
        /* تنسيقات الصور - نفس تنسيق صفحة تعديل الكلية */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-top: 1rem;
        }
        
        .image-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .image-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .image-preview {
            aspect-ratio: 16/9;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .image-actions {
            padding: 8px;
            text-align: center;
        }
        
        .delete-image-form {
            margin: 0;
            padding: 0;
        }
        
        /* تنسيق checkbox جامعة مميزة */
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .hint-checkbox {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
            margin-right: 26px;
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
        
        .aa{
            margin-bottom:50px;
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
                <h1 class="page-title">تعديل الجامعة</h1>

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

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['error_message']; ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

<section class="aa">
            <!-- بطاقة تعديل جامعة -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-edit"></i> تعديل بيانات الجامعة: <?php echo htmlspecialchars($university['name']); ?></h2>
                </div>

                <!-- النموذج الرئيسي لتحديث بيانات الجامعة -->
                <form method="POST" class="form-container" enctype="multipart/form-data">
                    <input type="hidden" name="update_university" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم الجامعة *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($university['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="type">نوع الجامعة *</label>
                            <select id="type" name="type" required>
                                <option value="">اختر النوع</option>
                                <option value="حكومية" <?php echo $university['type'] === 'حكومية' ? 'selected' : ''; ?>>حكومية</option>
                                <option value="أهلية" <?php echo $university['type'] === 'أهلية' ? 'selected' : ''; ?>>أهلية</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">الموقع *</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($university['location']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="website">الموقع الإلكتروني</label>
                            <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($university['website']); ?>" placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">رقم الهاتف</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($university['phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($university['email']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="map_url">رابط الموقع الجغرافي (Google Maps)</label>
                            <input type="url" id="map_url" name="map_url" value="<?php echo htmlspecialchars($university['map_url']); ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                            <small class="form-help">انسخ رابط التضمين (Embed) من Google Maps</small>
                        </div>

                        <div class="form-group">
                            <label for="coordination_link">رابط تنسيق الجامعة</label>
                            <input type="url" id="coordination_link" name="coordination_link" value="<?php echo htmlspecialchars($university['coordination_link']); ?>" placeholder="https://example.edu.ye/coordination">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">الوصف</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($university['description']); ?></textarea>
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
                            <option value="نشطة" <?php echo $university['status'] === 'نشطة' ? 'selected' : ''; ?>>نشطة</option>
                            <option value="غير نشطة" <?php echo $university['status'] === 'غير نشطة' ? 'selected' : ''; ?>>غير نشطة</option>
                        </select>
                    </div>
                    
                    <!-- حقل جامعة مميزة -->
                    <div class="form-group">
                        <div class="form-checkbox">
                        <label for="featured-checkbox" id="featured-label">جامعة مميزة؟</label>
                            <input type="checkbox" id="featured-checkbox" name="is_featured" value="1" 
                                <?php echo ($university['is_featured'] == 1) ? 'checked' : ''; ?>>
                          
                        </div>
                        <small class="hint-checkbox" id="featured-hint">
                            يمكن إلغاء هذه الخاصية من الجامعات المميزة
                        </small>
                    </div>
                   
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التغييرات</button>
                        <a href="universities.php" class="btn btn-outline"><i class="fas fa-arrow-right"></i> إلغاء والعودة</a>
                    </div>
                </form>
            </div>
            
            <!-- صور الجامعة الحالية -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-images"></i>
                        صور الجامعة الحالية
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($images)): ?>
                        <div class="image-grid">
                            <?php foreach ($images as $img): ?>
                                <?php
                                    $rel = $img['image_path'];
                                    $abs = __DIR__ . '/../' . str_replace(['\\','/'], DIRECTORY_SEPARATOR, $rel);
                                    $exists = is_file($abs);
                                ?>
                                <div class="image-item">
                                    <div class="image-preview">
                                        <?php if ($exists): ?>
                                            <img src="../<?php echo htmlspecialchars($rel); ?>" alt="صورة الجامعة">
                                        <?php else: ?>
                                            <span style="color:#64748b;font-size:12px;">صورة غير موجودة</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="image-actions">
                                        <!-- نموذج منفصل لحذف كل صورة -->
                                        <form method="POST" class="delete-image-form">
                                            <input type="hidden" name="delete_image" value="<?php echo (int)$img['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="width:100%;" 
                                                    onclick="return confirm('هل أنت متأكد من حذف هذه الصورة؟')">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results" style="text-align:center;color:#64748b;padding:2rem;">
                            <i class="fas fa-images" style="font-size:3rem;margin-bottom:1rem;display:block;opacity:0.5;"></i>
                            <p>لا توجد صور لهذه الجامعة حالياً</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </section>
            <!-- قائمة الجامعات -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">قائمة الجامعات</h2>
        <div class="card-actions">
            <span class="total-count">إجمالي الجامعات: <?php 
                // احصل على عدد الجامعات مرة أخرى
                $count_sql = "SELECT COUNT(*) as total FROM universities WHERE status = 'نشطة'";
                $count_result = $conn->query($count_sql);
                $count_row = $count_result->fetch_assoc();
                echo $count_row['total'];
                $count_result->close();
            ?></span>
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
                <?php 
                // إعادة جلب البيانات لعرضها
                $universities_query = "SELECT id, name, type, location, website, phone, email, coordination_link, status FROM universities WHERE status = 'نشطة' ORDER BY name";
                $universities_result = $conn->query($universities_query);
                
                if ($universities_result && $universities_result->num_rows > 0): 
                    while($univ = $universities_result->fetch_assoc()): 
                ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($univ['name']); ?></strong>
                            <?php if ($univ['email']): ?>
                                <br><small><?php echo htmlspecialchars($univ['email']); ?></small>
                            <?php endif; ?>
                            <?php if ($univ['coordination_link']): ?>
                                <br><a href="<?php echo htmlspecialchars($univ['coordination_link']); ?>" target="_blank" class="link coordination-link" style="font-size: 0.8rem;">
                                    <i class="fas fa-link"></i> رابط التنسيق
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="type-badge type-<?php echo $univ['type'] === 'حكومية' ? 'government' : 'private'; ?>">
                                <?php echo $univ['type']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($univ['location']); ?></td>
                        <td>
                            <?php if ($univ['website']): ?>
                                <a href="<?php echo htmlspecialchars($univ['website']); ?>" target="_blank" class="link">
                                    <i class="fas fa-external-link-alt"></i>
                                    زيارة الموقع
                                </a>
                            <?php else: ?>
                                <span class="text-muted">غير متوفر</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($univ['phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($univ['phone']); ?>" class="link">
                                    <i class="fas fa-phone"></i>
                                    <?php echo htmlspecialchars($univ['phone']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">غير متوفر</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $univ['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                <?php echo $univ['status']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit_university.php?id=<?php echo $univ['id']; ?>" class="btn btn-edit btn-sm" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="view_university.php?id=<?php echo $univ['id']; ?>" class="btn btn-view btn-sm" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="../university-details.php?id=<?php echo $univ['id']; ?>" class="btn btn-view btn-sm" title="عرض التفاصيل (الواجهة العامة)" target="_blank" rel="noopener">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="?delete=<?php echo $univ['id']; ?>" class="btn btn-delete btn-sm" title="حذف"
                                   onclick="return confirm('هل أنت متأكد من حذف جامعة <?php echo htmlspecialchars($univ['name']); ?>؟')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                    $universities_result->close();
                else: 
                ?>
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
