<?php
// تضمين ملف الإعدادات
require_once 'config.php';

// معالجة إرسال النموذج
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');

    // التحقق من صحة البيانات
    $errors = [];

    if (empty($name)) {
        $errors[] = 'الاسم مطلوب';
    }

    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }

    if (empty($subject)) {
        $errors[] = 'الموضوع مطلوب';
    }

    if (empty($message_text)) {
        $errors[] = 'الرسالة مطلوبة';
    }

    // إذا لم تكن هناك أخطاء، يمكن إرسال الرسالة
    if (empty($errors)) {
        // حفظ الرسالة في قاعدة البيانات
        $insert_sql = "INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message_text);

        if ($stmt->execute()) {
            $message = 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.';
            $message_type = 'success';

            // إعادة تعيين النموذج
            $name = $email = $phone = $subject = $message_text = '';
        } else {
            $message = 'حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة مرة أخرى.';
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = 'يرجى تصحيح الأخطاء التالية: ' . implode(', ', $errors);
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اتصل بنا - بوابة الجامعات اليمنية</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/contact.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
    <!-- شريط التنقل -->
   <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="Image/1.jpg" style ="width: 90px; height: 81px;">
            <div class="logo-text">
                    <span class="logo-title">دليل الجامعات اليمنية</span>
                    <span class="logo-subtitle">الالكتروني الشامل</span>
                </div>
            </div>
   <!-- زر القائمة للشاشات الصغيرة -->
            <button class="nav-toggle" aria-label="قائمة التنقل">
                <span></span>
                <span></span>
                <span></span>
            </button>

           <ul class="nav-menu">
    <li class="nav-item">
        <a href="index.php" class="nav-link">
            <i class="fas fa-home"></i>
            <span>الرئيسية</span>
        </a>
    </li>
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
        <a href="specializations.php" class="nav-link">
            <i class="fas fa-book"></i>
            <span>التخصصات</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="search.php" class="nav-link">
            <i class="fas fa-search"></i>
            <span>البحث</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="contact.php" class="nav-link active">
            <i class="fas fa-phone"></i>
            <span>اتصل بنا</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="admin/login.php" class="nav-link admin-link" title="لوحة الإدارة">
            <i class="fas fa-cog"></i>
            <span>الإدارة</span>
        </a>
    </li>
</ul>
        </div>
    </nav>


    <!-- رأس الصفحة -->
    <header class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="index.php">الرئيسية</a>
                    <i class="fas fa-chevron-left"></i>
                    <span>اتصل بنا</span>
                </div>

                <div class="header-hero">
                    <div class="header-text">
                        <h1>اتصل بنا</h1>
                        <p>نحن هنا لمساعدتك في العثور على الجامعة والتخصص المناسب</p>
                    </div>

                    <div class="header-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="main-content">
        <div class="container">
            <div class="contact-grid">
                <!-- معلومات التواصل -->
                <section class="contact-info-section">
                    <div class="section-header">
                        <h2>معلومات التواصل</h2>
                        <p>يمكنك التواصل معنا من خلال الطرق التالية</p>
                    </div>

                    <div class="contact-info-grid">
                        <div class="contact-info-card">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h3>العنوان</h3>
                                <p>صنعاء، اليمن<br>شارع تعز ، جامعة الاندلس جوار دار سلم</p>
                            </div>
                        </div>

                        <div class="contact-info-card">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h3>الهاتف</h3>
                                <p>+967 123 456 789<br>+967 987 654 321</p>
                            </div>
                        </div>

                        <div class="contact-info-card">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h3>البريد الإلكتروني</h3>
                                <p>info@yemeni-universities.com<br>support@yemeni-universities.com</p>
                            </div>
                        </div>

                        <div class="contact-info-card">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-details">
                                <h3>ساعات العمل</h3>
                                <p>الأحد - الخميس: 8:00 ص - 4:00 م<br>الجمعة - السبت: مغلق</p>
                            </div>
                        </div>
                    </div>


                </section>

                <!-- نموذج الاتصال -->
                <section class="contact-form-section">
                    <div class="section-header">
                        <h2>أرسل لنا رسالة</h2>
                        <p>املأ النموذج أدناه وسنرد عليك في أقرب وقت ممكن</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>">
                            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <form class="contact-form" method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">الاسم الكامل *</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">البريد الإلكتروني *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">رقم الهاتف</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="subject">الموضوع *</label>
                                <select id="subject" name="subject" required>
                                    <option value="">اختر الموضوع</option>
                                    <option value="استفسار عام" <?php echo ($subject ?? '') === 'استفسار عام' ? 'selected' : ''; ?>>استفسار عام</option>
                                    <option value="معلومات عن جامعة" <?php echo ($subject ?? '') === 'معلومات عن جامعة' ? 'selected' : ''; ?>>معلومات عن جامعة</option>
                                    <option value="معلومات عن تخصص" <?php echo ($subject ?? '') === 'معلومات عن تخصص' ? 'selected' : ''; ?>>معلومات عن تخصص</option>
                                    <option value="مشكلة تقنية" <?php echo ($subject ?? '') === 'مشكلة تقنية' ? 'selected' : ''; ?>>مشكلة تقنية</option>
                                    <option value="اقتراح" <?php echo ($subject ?? '') === 'اقتراح' ? 'selected' : ''; ?>>اقتراح</option>
                                    <option value="شكوى" <?php echo ($subject ?? '') === 'شكوى' ? 'selected' : ''; ?>>شكوى</option>
                                    <option value="أخرى" <?php echo ($subject ?? '') === 'أخرى' ? 'selected' : ''; ?>>أخرى</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">الرسالة *</label>
                            <textarea id="message" name="message" rows="6" required><?php echo htmlspecialchars($message_text ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                <span>إرسال الرسالة</span>
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- الخريطة -->
            <section class="map-section">
                <div class="section-header">
                    <h2>موقعنا</h2>
                    <p>يمكنك زيارة مكتبنا في العاصمة صنعاء</p>
                </div>

                <div class="map-container">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <h3>خريطة الموقع</h3>
                        <p>صنعاء، اليمن<br>شارع تعز ، جامعة الاندلس جوار دار سلم</p>
                        <a href="https://maps.app.goo.gl/A6a9Dw2ADFHZveuCA" target="_blank" class="btn btn-outline">
                            <i class="fas fa-external-link-alt"></i>
                            <span>فتح في خرائط جوجل</span>
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>
  <!-- التذييل -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-graduation-cap"></i>
                        <span>بوابة الجامعات اليمنية</span>
                    </div>
                    <p>نحن نساعد الطلاب في العثور على الجامعة والكلية والتخصص المناسب لهم</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>روابط سريعة</h4>
                    <ul>
                        <li><a href="universities.php">الجامعات</a></li>
                        <li><a href="colleges.php">الكليات</a></li>
                        <li><a href="specializations.php">التخصصات</a></li>
                        <li><a href="search.php">البحث</a></li>
                        <li><a href="contact.php">اتصل بنا</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>تواصل معنا</h4>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope"></i> kyanalahdl19@gmail.com</p>
                        <p><i class="fas fa-phone"></i> 720 087 777 967+</p>
                        <p><i class="fas fa-map-marker-alt"></i> صنعاء، اليمن</p>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p> دليل الجامعات اليمنية الالكترونية الشامل 2025 &copy; .</p>
            </div>
        </div>
    </footer>
    <!-- ملف JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
