<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/includes/logger.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// جلب بيانات التخصص للتعديل
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $specialization_sql = "SELECT * FROM specializations WHERE id = ?";
    $specialization_stmt = $conn->prepare($specialization_sql);
    $specialization_stmt->bind_param("i", $id);
    $specialization_stmt->execute();
    $specialization_result = $specialization_stmt->get_result();

    if ($specialization_result->num_rows === 0) {
        $error_message = 'التخصص غير موجود';
        header('Location: specializations.php');
        exit();
    }

    $specialization = $specialization_result->fetch_assoc();
    $specialization_stmt->close();
} else {
    $error_message = 'معرف التخصص غير صحيح';
    header('Location: specializations.php');
    exit();
}

// جلب قائمة الكليات
$colleges_sql = "SELECT
    c.id,
    c.name AS college_name,
    u.name AS university_name
FROM colleges c
LEFT JOIN universities u ON c.university_id = u.id
ORDER BY u.name, c.name";
$colleges_result = $conn->query($colleges_sql);

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $college_id = (int)($_POST['college_id'] ?? 0);
    $duration = (int)($_POST['duration'] ?? 4);
    $degree_type = $_POST['degree_type'] ?? 'بكالوريوس';
    $description = trim($_POST['description'] ?? '');
    $admission_requirement = trim($_POST['admission_requirement'] ?? '');
    $admission_prerequisites = trim($_POST['admission_prerequisites'] ?? '');
    $status = $_POST['status'] ?? 'متاح';

    $errors = [];

    // التحقق من البيانات
    if (empty($name)) {
        $errors[] = 'اسم التخصص مطلوب';
    }

    if ($college_id <= 0) {
        $errors[] = 'يجب اختيار الكلية';
    }

    if ($duration < 1 || $duration > 10) {
        $errors[] = 'مدة الدراسة يجب أن تكون بين 1 و 10 سنوات';
    }

    // التحقق من عدم وجود تخصص بنفس الاسم في نفس الكلية (باستثناء التخصص الحالي)
    if (empty($errors)) {
        $check_sql = "SELECT id FROM specializations WHERE name = ? AND college_id = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sii", $name, $college_id, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errors[] = 'يوجد تخصص بنفس الاسم في هذه الكلية';
        }
        $check_stmt->close();
    }

    // تحديث التخصص إذا لم تكن هناك أخطاء
    if (empty($errors)) {
        $update_sql = "UPDATE specializations SET name = ?, college_id = ?, duration = ?, degree_type = ?, description = ?, admission_requirement = ?, admission_prerequisites = ?, status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("siisssssi", $name, $college_id, $duration, $degree_type, $description, $admission_requirement, $admission_prerequisites, $status, $id);


        if ($update_stmt->execute()) {
            $success_message = 'تم تحديث التخصص بنجاح!';
            $_SESSION['success_message'] = $success_message;
            log_admin("Updated specialization id=$id");
            header('Location: specializations.php');
            exit();
        } else {
            $error_message = 'خطأ في تحديث التخصص: ' . $update_stmt->error;
            log_admin('Update specialization failed: ' . $update_stmt->error);
        }
        $update_stmt->close();
    } else {
        $error_message = 'يرجى تصحيح الأخطاء التالية: ' . implode(', ', $errors);
        $_SESSION['error_message'] = $error_message;
        $_SESSION['form_data'] = [
            'name' => $name,
            'college_id' => $college_id,
            'duration' => $duration,
            'degree_type' => $degree_type,
            'description' => $description,
            'admission_requirement' => $admission_requirement,
            'admission_prerequisites' => $admission_prerequisites,
            'status' => $status,
        ];
        header('Location: specializations.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل التخصص - لوحة الإدارة</title>

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
                <h1 class="page-title">تعديل التخصص</h1>

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

            <!-- نموذج تعديل التخصص -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-edit"></i>
                        تعديل التخصص: <?php echo htmlspecialchars($specialization['name']); ?>
                    </h2>
                </div>

                <div class="card-body">
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name" class="form-label">اسم التخصص *</label>
                                <input type="text" id="name" name="name" class="form-input"
                                       value="<?php echo htmlspecialchars($specialization['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="college_id" class="form-label">الكلية *</label>
                                <select id="college_id" name="college_id" class="form-input" required>
                                    <option value="">اختر الكلية</option>
                                    <?php if ($colleges_result->num_rows > 0): ?>
                                        <?php while ($college = $colleges_result->fetch_assoc()): ?>
                                            <option value="<?php echo $college['id']; ?>"
                                                    <?php echo $specialization['college_id'] == $college['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($college['college_name']); ?>
                                                - <?php echo htmlspecialchars($college['university_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="duration" class="form-label">مدة الدراسة (سنوات) *</label>
                                <input type="number" id="duration" name="duration" class="form-input"
                                       value="<?php echo $specialization['duration']; ?>" min="1" max="10" required>
                            </div>

                            <div class="form-group">
                                <label for="degree_type" class="form-label">نوع الدرجة</label>
                                <select id="degree_type" name="degree_type" class="form-input">
                                    <option value="بكالوريوس" <?php echo $specialization['degree_type'] === 'بكالوريوس' ? 'selected' : ''; ?>>بكالوريوس</option>
                                    <option value="ماجستير" <?php echo $specialization['degree_type'] === 'ماجستير' ? 'selected' : ''; ?>>ماجستير</option>
                                    <option value="دكتوراه" <?php echo $specialization['degree_type'] === 'دكتوراه' ? 'selected' : ''; ?>>دكتوراه</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">وصف التخصص</label>
                            <textarea id="description" name="description" class="form-input" rows="4"><?php echo htmlspecialchars($specialization['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="admission_requirement" class="form-label">شروط القبول</label>
                            <textarea id="admission_requirement" name="admission_requirement" class="form-input" rows="3" ><?php echo htmlspecialchars($specialization['admission_requirement'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="admission_prerequisites" class="form-label">متطلبات القبول</label>
                            <textarea id="admission_prerequisites" name="admission_prerequisites" class="form-input" rows="3" ><?php echo htmlspecialchars($specialization['admission_prerequisites'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">الحالة</label>
                            <select id="status" name="status" class="form-input">
                                <option value="متاح" <?php echo $specialization['status'] === 'متاح' ? 'selected' : ''; ?>>متاح</option>
                                <option value="غير متاح" <?php echo $specialization['status'] === 'غير متاح' ? 'selected' : ''; ?>>غير متاح</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                حفظ التغييرات
                            </button>
                            <a href="specializations.php" class="btn btn-outline">
                                <i class="fas fa-arrow-right"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
