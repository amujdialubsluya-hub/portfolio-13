<?php
// بداية الجلسة - التحقق من عدم بدئها مسبقاً
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$success_message = '';
$error_message = '';

// جلب بيانات الكلية للتعديل
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $college_sql = "SELECT * FROM colleges WHERE id = ?";
    $college_stmt = $conn->prepare($college_sql);
    $college_stmt->bind_param("i", $id);
    $college_stmt->execute();
    $college_result = $college_stmt->get_result();

    if ($college_result->num_rows === 0) {
        $_SESSION['error_message'] = 'الكلية غير موجودة';
        header('Location: colleges.php');
        exit();
    }

    $college = $college_result->fetch_assoc();
    $college_stmt->close();

    // جلب صور الكلية
    $images = [];
    $imgs_sql = "SELECT * FROM college_images WHERE college_id = ? ORDER BY sort_order, id";
    $imgs_stmt = $conn->prepare($imgs_sql);
    $imgs_stmt->bind_param("i", $id);
    $imgs_stmt->execute();
    $imgs_result = $imgs_stmt->get_result();
    if ($imgs_result) { 
        $images = $imgs_result->fetch_all(MYSQLI_ASSOC); 
    }
    $imgs_stmt->close();
} else {
    $_SESSION['error_message'] = 'معرف الكلية غير صحيح';
    header('Location: colleges.php');
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
        $one_sql = "SELECT image_path FROM college_images WHERE id = ? AND college_id = ?";
        $one_stmt = $conn->prepare($one_sql);
        $one_stmt->bind_param("ii", $img_id, $id);
        $one_stmt->execute();
        $one_res = $one_stmt->get_result();
        if ($one_res->num_rows === 1) {
            $row = $one_res->fetch_assoc();
            $abs = realpath(__DIR__ . '/../' . str_replace(['\\','/'], DIRECTORY_SEPARATOR, $row['image_path']));
            $del_sql = "DELETE FROM college_images WHERE id = ? AND college_id = ?";
            $del_stmt = $conn->prepare($del_sql);
            $del_stmt->bind_param("ii", $img_id, $id);
            if ($del_stmt->execute()) {
                if ($abs && is_file($abs)) { 
                    @unlink($abs); 
                    log_admin("Deleted college image: " . $abs);
                }
                $success_message = 'تم حذف الصورة بنجاح';
                log_admin("Deleted image id=$img_id for college id=$id");
            } else {
                $error_message = 'تعذر حذف الصورة';
                log_admin("Failed to delete image id=$img_id for college id=$id: " . $del_stmt->error);
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
    // إذا لم يكن طلب حذف صورة، فمعالجة تحديث بيانات الكلية
    elseif (isset($_POST['update_college'])) {
        // تحديث بيانات الكلية
        $name = trim($_POST['name'] ?? '');
        $university_id = !empty($_POST['university_id']) ? (int)$_POST['university_id'] : null;
        $college_type = trim($_POST['college_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $coordination_link = trim($_POST['coordination_link'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $map_url = trim($_POST['map_url'] ?? '');
        $status = $_POST['status'] ?? 'نشطة';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        $errors = [];

        // التحقق من البيانات
        if (empty($name)) {
            $errors[] = 'اسم الكلية مطلوب';
        }

        // إذا كانت الكلية مستقلة (غير تابعة لأي جامعة)
        if (empty($university_id)) {
            if (empty($location)) {
                $errors[] = 'موقع الكلية مطلوب للكليات المستقلة';
            }
            if (empty($college_type)) {
                $errors[] = 'نوع الكلية مطلوب للكليات المستقلة';
            }
        } else {
            // إذا كانت الكلية تابعة لجامعة، لا يمكن أن تكون مميزة
            $is_featured = 0;
        }

        // التحقق من عدم وجود كلية بنفس الاسم في نفس الجامعة (باستثناء الكلية الحالية)
        if (empty($errors)) {
            $check_sql = "SELECT id FROM colleges WHERE name = ? AND (university_id = ? OR (? IS NULL AND university_id IS NULL)) AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("siii", $name, $university_id, $university_id, $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $errors[] = 'يوجد كلية بنفس الاسم في هذه الجامعة';
            }
            $check_stmt->close();
        }

        // تحديث الكلية إذا لم تكن هناك أخطاء
        if (empty($errors)) {
            $update_sql = "UPDATE colleges SET 
                name = ?, 
                university_id = ?, 
                type = ?,
                description = ?, 
                location = ?,
                coordination_link = ?,
                website = ?,
                phone = ?,
                email = ?,
                map_url = ?,
                status = ?,
                is_featured = ? 
                WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param(
                "sisssssssssii", 
                $name, 
                $university_id, 
                $college_type,
                $description, 
                $location,
                $coordination_link,
                $website,
                $phone,
                $email,
                $map_url,
                $status,
                $is_featured,
                $id
            );

            if ($update_stmt->execute()) {
                $success_message = 'تم تحديث الكلية بنجاح!';
                log_admin("Updated college id=$id: $name (Featured: $is_featured)");

                // معالجة رفع صور جديدة
                if (!empty($_FILES['college_images']) && isset($_FILES['college_images']['name']) && is_array($_FILES['college_images']['name'])) {
                    $upload_root = __DIR__ . '/../uploads';
                    if (!is_dir($upload_root)) { @mkdir($upload_root, 0777, true); }
                    $college_dir = $upload_root . '/colleges/' . $id;
                    if (!is_dir($college_dir)) { @mkdir($college_dir, 0777, true); }

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
                        $dest_path = $college_dir . '/' . $safe_name;
                        
                        if (@move_uploaded_file($tmp_names[$i], $dest_path)) {
                            $relative_path = 'uploads/colleges/' . $id . '/' . $safe_name;
                            $img_sql = "INSERT INTO college_images (college_id, image_path, alt_text, sort_order) VALUES (?, ?, ?, 0)";
                            $img_stmt = $conn->prepare($img_sql);
                            $alt_text = $name;
                            $img_stmt->bind_param("iss", $id, $relative_path, $alt_text);
                            $img_stmt->execute();
                            $img_stmt->close();
                            $saved++;
                            log_admin("Uploaded image for college id=$id: $safe_name");
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
                $error_message = 'خطأ في تحديث الكلية: ' . $update_stmt->error;
                log_admin("Failed to update college id=$id: " . $update_stmt->error);
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
    <title>تعديل الكلية - لوحة الإدارة</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
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
        
        /* تنسيق checkbox المعطل */
        .checkbox-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .checkbox-disabled-label {
            color: #999;
            cursor: not-allowed;
        }
        
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
                locationInput.value = ''; // إفراغ الحقل عند الاختفاء
                collegeTypeGroup.style.display = 'none';
                collegeTypeInput.required = false;
                collegeTypeInput.value = ''; // إفراغ الحقل عند الاختفاء
            }
        }
        
        // دالة لتحديد حالة checkbox كلية مميزة بناءً على اختيار الجامعة
        function toggleFeaturedCheckbox() {
            const universitySelect = document.getElementById('university_id');
            const featuredCheckbox = document.querySelector('input[name="is_featured"]');
            const featuredLabel = document.querySelector('label[for="featured-checkbox"]');
            
            // إذا كانت الكلية تابعة لجامعة (تم اختيار جامعة)
            if (universitySelect.value !== '') {
                // تعطيل checkbox كلية مميزة
                featuredCheckbox.disabled = true;
                featuredCheckbox.checked = false;
                featuredCheckbox.classList.add('checkbox-disabled');
                if (featuredLabel) {
                    featuredLabel.classList.add('checkbox-disabled-label');
                }
            } 
            // إذا كانت الكلية غير تابعة لجامعة (لم يتم اختيار جامعة)
            else {
                // تفعيل checkbox كلية مميزة
                featuredCheckbox.disabled = false;
                featuredCheckbox.classList.remove('checkbox-disabled');
                if (featuredLabel) {
                    featuredLabel.classList.remove('checkbox-disabled-label');
                }
            }
        }
        
        // تفعيل عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            toggleLocationAndTypeFields();
            toggleFeaturedCheckbox(); // تعيين الحالة الابتدائية
            
            // تحديث الأحداث
            document.getElementById('university_id').addEventListener('change', function() {
                toggleLocationAndTypeFields();
                toggleFeaturedCheckbox();
            });
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
                <h1 class="page-title">تعديل الكلية</h1>

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

            <!-- نموذج تعديل الكلية -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-edit"></i>
                        تعديل الكلية: <?php echo htmlspecialchars($college['name']); ?>
                    </h2>
                </div>

                <!-- النموذج الرئيسي لتحديث بيانات الكلية -->
                <form method="POST" class="form-container" enctype="multipart/form-data">
                    <input type="hidden" name="update_college" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم الكلية *</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($college['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="university_id">الجامعة (اختياري)</label>
                            <select id="university_id" name="university_id" onchange="toggleLocationAndTypeFields(); toggleFeaturedCheckbox();">
                                <option value="">كلية غير تابعة لأي جامعة</option>
                                <?php 
                                // إعادة تعيين مؤشر النتائج لاستخدامه مرة أخرى
                                $universities_result->data_seek(0);
                                if ($universities_result->num_rows > 0): ?>
                                    <?php while ($university = $universities_result->fetch_assoc()): ?>
                                        <option value="<?php echo $university['id']; ?>" 
                                                <?php echo ($college['university_id'] == $university['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($university['name']); ?>
                                            (<?php echo $university['type']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <small class="hint">اترك هذا الحقل فارغاً إذا كانت الكلية غير تابعة لأي جامعة</small>
                        </div>
                    </div>

                    <!-- حقل نوع الكلية (يظهر فقط للكليات المستقلة) -->
                    <div class="form-row" id="college-type-group" style="display: <?php echo empty($college['university_id']) ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="college_type" class="form-label">نوع الكلية *</label>
                            <select id="college_type" name="college_type" class="form-input" required>
                                <option value="">اختر نوع الكلية</option>
                                <option value="حكومية" <?php echo ($college['type'] === 'حكومية') ? 'selected' : ''; ?>>حكومية</option>
                                <option value="أهلية" <?php echo ($college['type'] === 'أهلية') ? 'selected' : ''; ?>>أهلية</option>
                            </select>
                            <small class="hint">هذا الحقل مطلوب للكليات غير التابعة لأي جامعة</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">وصف الكلية</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($college['description']); ?></textarea>
                    </div>

                    <!-- حقل الموقع للكليات المستقلة -->
                    <div class="form-row" id="location-group" style="display: <?php echo empty($college['university_id']) ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="location" class="form-label">موقع الكلية *</label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($college['location']); ?>" 
                                   placeholder="أدخل موقع الكلية (مثال: صنعاء، اليمن)">
                            <small class="hint">هذا الحقل مطلوب للكليات غير التابعة لأي جامعة</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="coordination_link">رابط تنسيق الكلية</label>
                            <input type="url" id="coordination_link" name="coordination_link" 
                                   value="<?php echo htmlspecialchars($college['coordination_link']); ?>" 
                                   placeholder="https://example.com/coordination">
                            <small class="hint">رابط موقع تنسيق الكلية (اختياري)</small>
                        </div>

                        <div class="form-group">
                            <label for="website">الموقع الرسمي للكلية</label>
                            <input type="url" id="website" name="website" 
                                   value="<?php echo htmlspecialchars($college['website']); ?>" 
                                   placeholder="https://example.com">
                            <small class="hint">الموقع الرسمي للكلية (اختياري)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">رقم الهاتف</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($college['phone']); ?>" 
                                   placeholder="+967123456789">
                            <small class="hint">رقم هاتف الكلية (اختياري)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($college['email']); ?>" 
                                   placeholder="college@example.com">
                            <small class="hint">البريد الإلكتروني للكلية (اختياري)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="map_url">رابط الخريطة الجغرافية</label>
                        <input type="url" id="map_url" name="map_url" 
                               value="<?php echo htmlspecialchars($college['map_url']); ?>" 
                               placeholder="https://maps.google.com/?q=latitude,longitude">
                        <small class="hint">رابط الخريطة من Google Maps (اختياري)</small>
                    </div>

                    <div class="form-group">
                        <label for="status">الحالة</label>
                        <select id="status" name="status">
                            <option value="نشطة" <?php echo ($college['status'] === 'نشطة') ? 'selected' : ''; ?>>نشطة</option>
                            <option value="غير نشطة" <?php echo ($college['status'] === 'غير نشطة') ? 'selected' : ''; ?>>غير نشطة</option>
                        </select>
                    </div>
            

                    <div class="form-group">
                        <label for="college_images">صور الكلية (يمكن اختيار عدة صور)</label>
                        <input type="file" id="college_images" name="college_images[]" accept="image/*" multiple>
                        <small class="hint">الأنواع المسموحة: JPG, PNG, WEBP, GIF. الحد الأقصى للحجم: 5MB لكل صورة.</small>
                    </div>
                                 <!-- حقل كلية مميزة -->
                             <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" id="featured-checkbox" name="is_featured" value="1" 
                                <?php echo ($college['is_featured'] == 1) ? 'checked' : ''; ?>>
                            <label for="featured-checkbox" id="featured-label">كلية مميزة؟</label>
                        </div>
                        <small class="hint-checkbox" id="featured-hint">
                            يمكن تفعيل هذه الخاصية فقط للكليات غير التابعة لأي جامعة
                        </small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span>حفظ التغييرات</span>
                        </button>
                        <a href="colleges.php" class="btn btn-outline">
                            <i class="fas fa-arrow-right"></i>
                            <span>إلغاء والعودة</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- صور الكلية الحالية -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-images"></i>
                        صور الكلية الحالية
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
                                            <img src="../<?php echo htmlspecialchars($rel); ?>" alt="صورة الكلية">
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
                            <p>لا توجد صور لهذه الكلية حالياً</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
