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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $college_id = (int)($_POST['college_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration'] ?? 4);
    $degree_type = $_POST['degree_type'] ?? 'بكالوريوس';
    // دعم عدة شروط ومتطلبات
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

    if ($duration <= 0) {
        $errors[] = 'مدة الدراسة يجب أن تكون أكبر من صفر';
    }

    // التحقق من عدم وجود تخصص بنفس الاسم في نفس الكلية
    if (empty($errors)) {
        $check_sql = "SELECT id FROM specializations WHERE name = ? AND college_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $name, $college_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errors[] = 'يوجد تخصص بنفس الاسم في هذه الكلية';
        }
        $check_stmt->close();
    }

    // إضافة التخصص
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO specializations
            (name, college_id, duration, degree_type, description, admission_requirement, admission_prerequisites, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "siisssss",
            $name,
            $college_id,
            $duration,
            $degree_type,
            $description,
            $admission_requirement,
            $admission_prerequisites,
            $status
        );

        if ($stmt->execute()) {
            $success_message = 'تم إضافة التخصص بنجاح!';
            $_SESSION['success_message'] = $success_message;
            log_admin("Added specialization '$name' in college_id=$college_id");
            header('Location: specializations.php');
            exit();
        } else {
            $error_message = 'خطأ في إضافة التخصص: ' . $stmt->error;
            log_admin('Add specialization failed: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = 'يرجى تصحيح الأخطاء التالية: ' . implode(', ', $errors);
        $_SESSION['error_message'] = $error_message;
        $_SESSION['form_data'] = [
            'name' => $name,
            'college_id' => $college_id,
            'description' => $description,
            'duration' => $duration,
            'degree_type' => $degree_type,
            'admission_requirement' => $admission_requirement,
            'admission_prerequisites' => $admission_prerequisites,
            'status' => $status,
        ];
        header('Location: specializations.php');
        exit();
    }
}

// جلب قائمة الكليات مع معلومات الجامعة
$colleges_sql = "SELECT
    c.id,
    c.name AS college_name,
    u.name AS university_name,
    u.type AS university_type
FROM colleges c
LEFT JOIN universities u ON c.university_id = u.id
ORDER BY u.name, c.name";

$colleges_result = $conn->query($colleges_sql);
if ($colleges_result === false) {
    log_admin('Add specialization colleges query failed: ' . $conn->error);
}

// Fallback: إذا كانت النتيجة فارغة، اجلب كل الكليات بغض النظر عن الحالة/الربط
if ($colleges_result === false || $colleges_result->num_rows === 0) {
    log_admin('Add specialization colleges fallback query engaged');
    $fallback_sql = "SELECT id, name AS college_name FROM colleges ORDER BY name";
    $colleges_result = $conn->query($fallback_sql);
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تخصص جديد - لوحة الإدارة</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet"href="assets/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- الشريط الجانبي -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>لوحة الإدارة</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="universities.php" class="nav-link">
                    <i class="fas fa-university"></i>
                    <span>الجامعات</span>
                </a>
                <a href="colleges.php" class="nav-link">
                    <i class="fas fa-building"></i>
                    <span>الكليات</span>
                </a>
                <a href="specializations.php" class="nav-link active">
                    <i class="fas fa-book"></i>
                    <span>التخصصات</span>
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>الرسائل</span>
                </a>
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>المستخدمين</span>
                </a>
                <a href="logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </nav>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <div class="page-title-section">
                        <h1 class="page-title">إضافة تخصص جديد</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">الرئيسية</a>
                            <i class="fas fa-chevron-left"></i>
                            <a href="specializations.php">التخصصات</a>
                            <i class="fas fa-chevron-left"></i>
                            <span>إضافة تخصص</span>
                        </div>
                    </div>

                    <div class="user-info">
                        <span>مرحباً، <?php echo htmlspecialchars($_SESSION['admin_full_name']); ?></span>
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <!-- رسائل النجاح والخطأ -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                        <button class="alert-close" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                        <button class="alert-close" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- نموذج إضافة التخصص -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-plus"></i>
                            إضافة تخصص جديد
                        </h2>
                    </div>

                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name" class="form-label">اسم التخصص *</label>
                                    <input type="text" id="name" name="name" class="form-input"
                                           value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="college_id" class="form-label">الكلية *</label>
                                    <select id="college_id" name="college_id" class="form-input" required>
                                        <option value="">اختر الكلية</option>
                                        <?php if ($colleges_result->num_rows > 0): ?>
                                            <?php while ($college = $colleges_result->fetch_assoc()): ?>
                                                <option value="<?php echo $college['id']; ?>"
                                                        <?php echo ($college_id ?? 0) == $college['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($college['college_name']); ?>
                                                    - <?php echo htmlspecialchars($college['university_name']); ?>
                                                    (<?php echo $college['university_type']; ?>)
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
                                           value="<?php echo $duration ?? 4; ?>" min="1" max="10" required>
                                </div>

                                <div class="form-group">
                                    <label for="degree_type" class="form-label">نوع الدرجة</label>
                                    <select id="degree_type" name="degree_type" class="form-input">
                                        <option value="بكالوريوس" <?php echo ($degree_type ?? '') === 'بكالوريوس' ? 'selected' : ''; ?>>بكالوريوس</option>
                                        <option value="ماجستير" <?php echo ($degree_type ?? '') === 'ماجستير' ? 'selected' : ''; ?>>ماجستير</option>
                                        <option value="دكتوراه" <?php echo ($degree_type ?? '') === 'دكتوراه' ? 'selected' : ''; ?>>دكتوراه</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="description" class="form-label">وصف التخصص</label>
                                    <textarea id="description" name="description" class="form-input" rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="admission_requirement" class="form-label">شروط القبول (أكثر من شرط)</label>
                                    <textarea id="admission_requirement" name="admission_requirement" class="form-input" rows="3" placeholder="اكتب كل شرط في سطر مستقل مثل: \n- نسبة الثانوية 85%\n- اجتياز اختبار القبول\n- صورة من الهوية"><?php echo htmlspecialchars($admission_requirement ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="admission_prerequisites" class="form-label">متطلبات إضافية</label>
                                    <textarea id="admission_prerequisites" name="admission_prerequisites" class="form-input" rows="3" placeholder="مثال: ملف إنجاز، خطاب توصية، شهادة لغة..."><?php echo htmlspecialchars($admission_prerequisites ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status" class="form-label">الحالة</label>
                                    <select id="status" name="status" class="form-input">
                                        <option value="متاح" <?php echo ($status ?? '') === 'متاح' ? 'selected' : ''; ?>>متاح</option>
                                        <option value="غير متاح" <?php echo ($status ?? '') === 'غير متاح' ? 'selected' : ''; ?>>غير متاح</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    إضافة التخصص
                                </button>
                                <a href="specializations.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-right"></i>
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
