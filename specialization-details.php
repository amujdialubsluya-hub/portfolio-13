<?php
// تضمين ملف الإعدادات
require_once 'config.php';

// التحقق من وجود معرف التخصص
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: specializations.php');
    exit();
}

$specialization_id = (int)$_GET['id'];

// جلب معلومات التخصص مع الجامعة والكلية - استعلام محسن
$specialization_sql = "SELECT 
    s.*, 
    c.name as college_name, 
    c.description as college_description, 
    c.id as college_id,
    c.location as college_location, 
    c.website as college_website,
    c.phone as college_phone, 
    c.email as college_email, 
    c.coordination_link as college_coordination_link,
    c.status as college_status,
    u.name as university_name, 
    u.type as university_type, 
    u.id as university_id,
    u.location as university_location, 
    u.website as university_website,
    u.phone as university_phone, 
    u.email as university_email,
    CASE 
        WHEN c.university_id IS NULL THEN 'independent'
        ELSE 'affiliated'
    END as college_type
FROM specializations s
JOIN colleges c ON s.college_id = c.id
LEFT JOIN universities u ON c.university_id = u.id
WHERE s.id = ? AND s.status = 'متاح'";

$stmt = $conn->prepare($specialization_sql);
$stmt->bind_param("i", $specialization_id);
$stmt->execute();
$specialization_result = $stmt->get_result();

if ($specialization_result->num_rows === 0) {
    header('Location: specializations.php');
    exit();
}

$specialization = $specialization_result->fetch_assoc();

// تحديد نوع الكلية
$is_independent_college = ($specialization['college_type'] === 'independent');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($specialization['name']); ?> - بوابة الجامعات اليمنية</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/specialization-details.css">

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
                    <a href="specializations.php" class="nav-link active">
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
                    <a href="contact.php" class="nav-link">
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
                    <a href="specializations.php">التخصصات</a>
                    <i class="fas fa-chevron-left"></i>
                    <span><?php echo htmlspecialchars($specialization['name']); ?></span>
                </div>

                <div class="specialization-hero">
                    <div class="specialization-info">
                        <div class="specialization-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="specialization-details">
                            <h1><?php echo htmlspecialchars($specialization['name']); ?></h1>
                            <div class="specialization-meta">
                                <?php if ($is_independent_college): ?>
                                    <span class="university-type independent">
                                        <i class="fas fa-building"></i>
                                        كلية مستقلة
                                    </span>
                                <?php else: ?>
                                    <span class="university-type <?php echo $specialization['university_type'] == 'حكومية' ? 'government' : 'private'; ?>">
                                        <i class="fas fa-university"></i>
                                        <?php echo htmlspecialchars($specialization['university_type']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="specialization-degree">
                                    <i class="fas fa-certificate"></i>
                                    <?php echo htmlspecialchars($specialization['degree_type']); ?>
                                </span>
                                <span class="specialization-duration">
                                    <i class="fas fa-clock"></i>
                                    <?php echo (int)$specialization['duration']; ?> سنوات
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="specialization-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo (int)$specialization['duration']; ?></div>
                            <div class="stat-label">سنوات الدراسة</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number status-available"><?php echo htmlspecialchars($specialization['status']); ?></div>
                            <div class="stat-label">حالة التخصص</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                <!-- معلومات التخصص -->
                <section class="specialization-section">
                    <div class="section-header">
                        <h2>معلومات التخصص</h2>
                    </div>

                    <div class="specialization-card">
                        <div class="specialization-content">
                            <?php if (!empty($specialization['description'])): ?>
                            <div class="description">
                                <h3><i class="fas fa-info-circle"></i> وصف التخصص</h3>
                                <p><?php echo nl2br(htmlspecialchars($specialization['description'])); ?></p>
                            </div>
                            <?php endif; ?>

                            <!-- تفاصيل التخصص -->
                            <div class="specialization-details-grid">
                                <!-- معلومات الجامعة (للجامعات فقط) -->
                                <?php if (!$is_independent_college && !empty($specialization['university_name'])): ?>
                                <div class="detail-item">
                                    <i class="fas fa-university"></i>
                                    <div>
                                        <strong>الجامعة:</strong>
                                        <span><?php echo htmlspecialchars($specialization['university_name']); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- معلومات الكلية -->
                                <div class="detail-item">
                                    <i class="fas fa-building"></i>
                                    <div>
                                        <strong>الكلية:</strong>
                                        <span><?php echo htmlspecialchars($specialization['college_name']); ?></span>
                                        <?php if ($is_independent_college): ?>
                                            <small class="college-indicator">(كلية مستقلة)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                

                                <!-- الموقع -->
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>الموقع:</strong>
                                        <span>
                                            <?php 
                                            if (!$is_independent_college && !empty($specialization['university_location'])) {
                                                echo htmlspecialchars($specialization['university_location']);
                                            } elseif ($is_independent_college && !empty($specialization['college_location'])) {
                                                echo htmlspecialchars($specialization['college_location']);
                                            } else {
                                                echo 'غير محدد';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- مدة الدراسة -->
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>مدة الدراسة:</strong>
                                        <span><?php echo (int)$specialization['duration']; ?> سنوات</span>
                                    </div>
                                </div>

                                <!-- الدرجة العلمية -->
                                <div class="detail-item">
                                    <i class="fas fa-certificate"></i>
                                    <div>
                                        <strong>الدرجة العلمية:</strong>
                                        <span><?php echo htmlspecialchars($specialization['degree_type']); ?></span>
                                    </div>
                                </div>

                                <!-- الحالة -->
                                <div class="detail-item">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <strong>الحالة:</strong>
                                        <span class="status-available"><?php echo htmlspecialchars($specialization['status']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- شروط القبول -->
                            <?php if (!empty($specialization['admission_requirement'])): ?>
                            <div class="admission-section">
                                <h3><i class="fas fa-check-circle"></i> شروط القبول</h3>
                                <div class="admission-requirements">
                                    <?php 
                                    $requirements = explode("\n", $specialization['admission_requirement']);
                                    echo '<ul>';
                                    foreach ($requirements as $requirement) {
                                        $requirement = trim($requirement);
                                        if (!empty($requirement)) {
                                            echo '<li>' . htmlspecialchars($requirement) . '</li>';
                                        }
                                    }
                                    echo '</ul>';
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- المتطلبات السابقة -->
                            <?php if (!empty($specialization['admission_prerequisites'])): ?>
                            <div class="admission-section">
                                <h3><i class="fas fa-clipboard-list"></i> متطلبات القبول</h3>
                                <div class="admission-prerequisites">
                                    <?php 
                                    $prerequisites = explode("\n", $specialization['admission_prerequisites']);
                                    echo '<ul>';
                                    foreach ($prerequisites as $prerequisite) {
                                        $prerequisite = trim($prerequisite);
                                        if (!empty($prerequisite)) {
                                            echo '<li>' . htmlspecialchars($prerequisite) . '</li>';
                                        }
                                    }
                                    echo '</ul>';
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- معلومات الكلية -->
                <section class="college-section">
                    <div class="section-header">
                        <h2>معلومات <?php echo $is_independent_college ? 'الكلية' : 'الكلية والجامعة'; ?></h2>
                    </div>

                    <div class="college-card">
                        <div class="college-header">
                            <div class="college-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="college-title">
                                <h3><?php echo htmlspecialchars($specialization['college_name']); ?></h3>
                                <?php if ($is_independent_college): ?>
                                    <span class="college-type independent">كلية مستقلة</span>
                                <?php else: ?>
                                    <span class="college-type affiliated">تابعة لـ <?php echo htmlspecialchars($specialization['university_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="college-content">
                           <?php if (!empty($specialization['college_description'])): ?>
    <div class="college-description">
        <h4><i class="fas fa-info-circle"></i> عن الكلية</h4>
        <p>
            <?php 
            // قص الوصف لعرض سطر واحد فقط
            $short_description = $specialization['college_description'];
            if (strlen($short_description) > 100) {
                $short_description = substr($short_description, 0, 100) . '...';
            }
            echo htmlspecialchars($short_description);
            ?>
        </p>
    </div>
<?php endif; ?>
                            <!-- معلومات التواصل -->
                            <div class="contact-details">
                                <h4><i class="fas fa-address-card"></i> معلومات التواصل</h4>
                                <div class="contact-grid">
                                    <?php if (!empty($specialization['college_phone'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <div>
                                            <strong>هاتف الكلية:</strong>
                                            <span><?php echo htmlspecialchars($specialization['college_phone']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($specialization['college_email'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <div>
                                            <strong>البريد الإلكتروني:</strong>
                                            <span><?php echo htmlspecialchars($specialization['college_email']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($specialization['college_website'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-globe"></i>
                                        <div>
                                            <strong>الموقع الإلكتروني:</strong>
                                            <a href="<?php echo htmlspecialchars($specialization['college_website']); ?>" target="_blank">زيارة الموقع</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($specialization['college_coordination_link'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-link"></i>
                                        <div>
                                            <strong>رابط التنسيق:</strong>
                                            <a href="<?php echo htmlspecialchars($specialization['college_coordination_link']); ?>" target="_blank">زيارة موقع التنسيق</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="college-actions">
                                <?php if (!$is_independent_college && !empty($specialization['university_id'])): ?>
                                    <a href="university-details.php?id=<?php echo $specialization['university_id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-university"></i>
                                        <span>عرض الجامعة</span>
                                    </a>
                                <?php endif; ?>
                                <a href="college-details.php?id=<?php echo $specialization['college_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-building"></i>
                                    <span>عرض الكلية</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
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
                    <p>نحن نساعد الطلاب في العثور على الجامعة والتخصص المناسب لهم</p>
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