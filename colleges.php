<?php
// تضمين ملف الإعدادات
require_once 'config.php';

// استعلام لجلب الكليات من قاعدة البيانات مع معلومات الجامعات
$sql = "SELECT 
    c.*, 
    u.name as university_name,
    u.type as university_type,
    u.location as university_location,
    COUNT(s.id) as specializations_count,
    -- استخدام نوع الكلية إذا موجود، وإلا استخدام نوع الجامعة
    COALESCE(c.type, u.type) as display_type
FROM colleges c
LEFT JOIN universities u ON c.university_id = u.id
LEFT JOIN specializations s ON c.id = s.college_id
WHERE c.status = 'نشطة'
GROUP BY c.id
ORDER BY c.name";

$result = $conn->query($sql);

// جلب إحصائيات الكليات
$stats_sql = "SELECT
    COUNT(*) as total_colleges,
    SUM(CASE WHEN COALESCE(c.type, u.type) = 'حكومية' THEN 1 ELSE 0 END) as government_colleges,
    SUM(CASE WHEN COALESCE(c.type, u.type) = 'أهلية' THEN 1 ELSE 0 END) as private_colleges,
    SUM(CASE WHEN c.university_id IS NULL THEN 1 ELSE 0 END) as independent_colleges
FROM colleges c
LEFT JOIN universities u ON c.university_id = u.id
WHERE c.status = 'نشطة'";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// جلب الجامعات للفلتر
$universities_sql = "SELECT id, name FROM universities WHERE status = 'نشطة' ORDER BY name";
$universities_result = $conn->query($universities_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكليات اليمنية - بوابة الجامعات اليمنية</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/universities.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
        /* تخصيصات إضافية للكليات */
        .college-logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .college-type.independent {
            background: rgba(139, 92, 246, 0.2);
            color: #8b5cf6;
        }

        .college-stats {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .college-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .college-stat i {
            color: var(--primary-color);
            width: 16px;
        }

        /* تعديلات بسيطة للبطاقات */
        .university-card .college-content h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .university-card .college-content p {
            margin-bottom: 15px;
        }

        /* تحسينات للعرض */
        .college-university {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .college-contact-info {
            margin: 10px 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 8px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .contact-item i {
            color: var(--primary-color);
            width: 16px;
        }

        /* 
قسم الفلترة
الوظيفة: يحتوي على خيارات التصفية والبحث للجامعات
*/
.filter-section {
    background: var(--background-white); /* خلفية بيضاء *********************************************************************/
    padding: 40px 0; /* مسافة داخلية علوية وسفلية */
    border-bottom: 1px solid var(--border-color); /* خط فاصل أسفل القسم */
}

.filter-container {
    display: flex; /* عرض حقول الفلترة في صف */
    gap: 20px; /* مسافة بين الحقول */
    align-items: end; /* محاذاة من الأسفل */
    flex-wrap: wrap; /* السماح بنقل العناصر لسطر جديد */
}

.filter-group {
    display: flex; /* تخطيط مرن */
    flex-direction: column; /* ترتيب العناصر عمودياً */
    gap: 8px; /* مسافة بين التسمية وحقل الإدخال */
    min-width: 200px; /* أقل عرض لمجموعة الفلترة */
}

.filter-group label {
    font-weight: 600; /* نص سميك للتسميات */
    color: var(--text-primary); /* لون النص الرئيسي */
    font-size: 0.9rem; /* حجم خط صغير */
}

.filter-select,
.filter-input {
    padding: 12px 16px; /* مسافة داخلية مريحة */
    border: 2px solid var(--border-color); /* حدود بارزة */
    border-radius: 12px; /* زوايا دائرية */
    font-size: 1rem; /* حجم خط مقروء */
    transition: var(--transition); /* تأثيرات انتقالية سلسة */
    background: var(--background-white); /* خلفية بيضاء */
    font-family: 'Cairo', sans-serif; /* خط عربي */
}

.filter-select:focus,
.filter-input:focus {
    outline: none; /* إزالة الحدود الافتراضية */
    border-color: var(--primary-color); /* تغيير لون الحدود عند التركيز */
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); /* ظل خفيف حول الحقل */
}

        /* تحسينات للاستجابة */
        @media (max-width: 1024px) {
            .universities-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-container {
                gap: 15px;
            }
            
            .filter-group {
                min-width: 180px;
            }
        }

        @media (max-width: 768px) {
            .universities-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-group {
                min-width: 100%;
                width: 100%;
            }
            
            .college-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-stats {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .stat-item {
                flex: 1;
                min-width: 120px;
            }
            
            .university-card {
                margin-bottom: 20px;
            }
            
            .hero-stats {
                gap: 20px;
            }
            
            .page-header {
                padding: 40px 0;
            }
            
            .header-content {
                flex-direction: column;
                gap: 30px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .page-subtitle {
                font-size: 1rem;
            }
            
            .header-stats {
                gap: 10px;
            }
            
            .stat-item {
                min-width: 100px;
                padding: 10px;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .university-header {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .university-content {
                padding: 20px;
            }
            
            .university-footer {
                padding: 0 20px 20px;
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .stat-card {
                padding: 20px 15px;
            }
            
            .stat-info .stat-number {
                font-size: 2rem;
            }
        }

        @media (max-width: 360px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-stats {
                flex-direction: column;
            }
            
            .stat-item {
                width: 100%;
            }
        }

        /* إصلاح مشاكل العرض */
        .hidden {
            display: none !important;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
        }

        .no-results i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .no-results h3 {
            color: var(--text-secondary);
            margin-bottom: 10px;
        }

        .no-results p {
            color: var(--text-light);
        }

        /* تحسينات للبطاقات */
        .university-card {
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* إصلاح مشكلة عرض نوع الكلية */
        .university-type {
            text-align: center;
            line-height: 1.3;
        }

        .university-type small {
            display: block;
            margin-top: 2px;
            font-size: 0.65rem;
            opacity: 0.8;
        }

        /* تحسينات للرأس */
        .page-header {
            padding: 60px 0;
            background: var(--gradient-primary);
            color: white;
            margin-top: 80px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;
        }

        .header-text {
            flex: 1;
            min-width: 300px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .page-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .header-stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="Image/1.jpg" style="width: 90px; height: 81px;">
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

            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- رأس الصفحة -->
    <header class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">الكليات اليمنية</h1>
                    <p class="page-subtitle">اكتشف جميع الكليات في الجامعات اليمنية الحكومية والأهلية والكليات المستقلة</p>
                </div>
                <div class="header-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_colleges']; ?>+</div>
                        <div class="stat-label">كلية</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['government_colleges']; ?>+</div>
                        <div class="stat-label">حكومية</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['private_colleges']; ?>+</div>
                        <div class="stat-label">أهلية</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['independent_colleges']; ?>+</div>
                        <div class="stat-label">مستقلة</div>
                    </div>
                </div>
            </div>
        </div>
        
    </header>

    <!-- قسم الفلترة -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-container">
                <div class="filter-group">
                    <label for="type-filter">نوع الكلية:</label>
                    <select id="type-filter" class="filter-select">
                        <option value="">جميع الأنواع</option>
                        <option value="government">حكومية</option>
                        <option value="private">أهلية</option>
                        <option value="independent">مستقلة</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="university-filter">الجامعة:</label>
                    <select id="university-filter" class="filter-select">
                        <option value="">جميع الجامعات</option>
                        <?php if ($universities_result->num_rows > 0): ?>
                            <?php while($university = $universities_result->fetch_assoc()): ?>
                                <option value="<?php echo $university['id']; ?>"><?php echo $university['name']; ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search-input">البحث:</label>
                    <input type="text" id="search-input" class="filter-input" placeholder="ابحث عن كلية...">
                </div>

                <button id="clear-filters" class="btn btn-outline">
                    <i class="fas fa-times"></i>
                    إعادة الضبط
                </button>
            </div>
        </div>
    </section>

   <!-- قسم الكليات -->
<section class="universities-section">
    <div class="container">
        <div class="universities-grid" id="colleges-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($college = $result->fetch_assoc()): ?>
                  <?php
// تحديد نوع الكلية للعرض - تم التصحيح
if ($college['university_id'] === null) {
    // الكليات المستقلة نستخدم نوعها المحدد في قاعدة البيانات
    $type_class = $college['type'] == 'حكومية' ? 'government' : 'private';
    $display_text = $college['type']; // سيظهر "حكومية" أو "أهلية" حسب البيانات
} else {
    // الكليات التابعة لجامعات - نستخدم نوع الكلية أولاً إذا كان موجوداً وغير فارغ
    if (!empty($college['type']) && $college['type'] !== null) {
        // إذا كان للكلية نوع محدد نستخدمه
        $display_text = $college['type'];
        $type_class = $college['type'] == 'حكومية' ? 'government' : 'private';
    } else {
        // إذا لم يكن للكلية نوع نستخدم نوع الجامعة
        $display_text = $college['display_type'];
        $type_class = $college['display_type'] == 'حكومية' ? 'government' : 'private';
    }
}

// التأكد من أن display_text ليس فارغاً
if (empty($display_text) || $display_text === null) {
    $display_text = $college['display_type'] ?: 'أهلية'; // افتراضي أهلية إذا كان كل شيء فارغ
    $type_class = $display_text == 'حكومية' ? 'government' : 'private';
}
?>
                    <div class="university-card" 
                         data-type="<?php echo $type_class; ?>" 
                         data-university="<?php echo $college['university_id'] ?: 'independent'; ?>"
                         data-name="<?php echo htmlspecialchars($college['name']); ?>">
                        
                        <div class="university-header">
                            <div class="university-logo">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="university-type <?php echo $type_class; ?>">
                                <?php echo $display_text; ?>
                            </div>
                        </div>

                        <!-- باقي محتوى البطاقة -->
                        <div class="university-content">
                            <h3><?php echo htmlspecialchars($college['name']); ?></h3>
                            
                            <?php if ($college['university_name']): ?>
                                <p class="college-university">
                                    <i class="fas fa-university"></i>
                                    تابعة ل: <?php echo htmlspecialchars($college['university_name']); ?>
                                </p>
                            <?php else: ?>
                                <p class="college-university">
                                    <i class="fas fa-school"></i>
                                    كلية مستقلة
                                </p>
                            <?php endif; ?>
                            
                            <p>
                                <?php 
                                $text = $college['description'] ?: 'كلية متخصصة في التعليم العالي توفر بيئة تعليمية متميزة للطلاب';
                                echo mb_strimwidth(htmlspecialchars($text), 0, 100, "...");
                                ?>
                            </p>

                            <div class="university-details">
                                <?php if ($college['location']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($college['location']); ?></span>
                                </div>
                                <?php elseif ($college['university_location']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($college['university_location']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($college['phone']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($college['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($college['email']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($college['email']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="college-stats">
                                <div class="college-stat">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo $college['specializations_count']; ?> تخصص</span>
                                </div>
                                <div class="college-stat">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo $display_text; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="university-footer">
                            <a href="college-details.php?id=<?php echo $college['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-info-circle"></i>
                                <span>عرض التفاصيل</span>
                                
                            </a>
                            <?php if ($college['website']): ?>
                            <a href="<?php echo htmlspecialchars($college['website']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                <i class="fas fa-external-link-alt"></i>
                                <span>موقع الكلية</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($college['coordination_link']): ?>
                            <a href="<?php echo htmlspecialchars($college['coordination_link']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                <i class="fas fa-link"></i>
                                <span>رابط التنسيق</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-building"></i>
                    <h3>لا توجد كليات متاحة حالياً</h3>
                    <p>يرجى المحاولة مرة أخرى لاحقاً</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

    <!-- قسم الإحصائيات -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-content">
                <div class="stats-text">
                    <h2>إحصائيات الكليات</h2>
                    <p>نظرة عامة على نظام الكليات في التعليم العالي اليمني</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['total_colleges']; ?>+</div>
                            <div class="stat-label">كلية</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['government_colleges']; ?></div>
                            <div class="stat-label">كلية حكومية</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['private_colleges']; ?></div>
                            <div class="stat-label">كلية أهلية</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-landmark"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['independent_colleges']; ?></div>
                            <div class="stat-label">كلية مستقلة</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
    <script>
        // تفعيل القائمة المتنقلة
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('navToggle');
            const navMenu = document.querySelector('.nav-menu');

            // تفعيل القائمة عند النقر على زر القائمة
            if (navToggle) {
                navToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    navToggle.classList.toggle('active');
                    
                    // منع التمرير عند فتح القائمة
                    if (navMenu.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
            }

            // إغلاق القائمة عند النقر على رابط
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    navMenu.classList.remove('active');
                    navToggle.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });

            // إغلاق القائمة عند النقر خارجها
            document.addEventListener('click', function(event) {
                const isClickInsideNav = navToggle.contains(event.target) || navMenu.contains(event.target);
                
                if (!isClickInsideNav && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    navToggle.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            // فلترة الكليات
            const typeFilter = document.getElementById('type-filter');
            const universityFilter = document.getElementById('university-filter');
            const searchInput = document.getElementById('search-input');
            const clearFiltersBtn = document.getElementById('clear-filters');
            const collegeCards = document.querySelectorAll('.university-card');

            function filterColleges() {
                const typeValue = typeFilter.value;
                const universityValue = universityFilter.value;
                const searchValue = searchInput.value.toLowerCase().trim();

                let visibleCount = 0;

                collegeCards.forEach(card => {
                    const cardType = card.getAttribute('data-type');
                    const cardUniversity = card.getAttribute('data-university');
                    const cardName = card.getAttribute('data-name').toLowerCase();

                    const typeMatch = !typeValue || cardType === typeValue;
                    const universityMatch = !universityValue || cardUniversity === universityValue;
                    const searchMatch = !searchValue || cardName.includes(searchValue);

                    if (typeMatch && universityMatch && searchMatch) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // التحقق إذا كانت هناك نتائج
                const existingNoResults = document.querySelector('.no-results');
                
                if (visibleCount === 0) {
                    if (!existingNoResults) {
                        const grid = document.getElementById('colleges-grid');
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.className = 'no-results';
                        noResultsDiv.innerHTML = `
                            <i class="fas fa-building"></i>
                            <h3>لا توجد كليات تطابق معايير البحث</h3>
                            <p>جرب تعديل الفلاتر أو مصطلحات البحث</p>
                        `;
                        grid.appendChild(noResultsDiv);
                    }
                } else if (existingNoResults) {
                    existingNoResults.remove();
                }
            }

            typeFilter.addEventListener('change', filterColleges);
            universityFilter.addEventListener('change', filterColleges);
            searchInput.addEventListener('input', filterColleges);

            clearFiltersBtn.addEventListener('click', function() {
                typeFilter.value = '';
                universityFilter.value = '';
                searchInput.value = '';
                filterColleges();
            });

            // إضافة تأثيرات للبطاقات
            collegeCards.forEach((card, index) => {
                card.style.animationDelay = `${(index % 6) * 0.1}s`;
            });

            // تحسين الأداء على الهواتف
            if (window.innerWidth <= 768) {
                // تحسين الأداء للشاشات الصغيرة
                document.querySelectorAll('.university-card').forEach(card => {
                    card.style.willChange = 'transform';
                });
            }
        });

        // تحسين الاستجابة عند تغيير حجم النافذة
        window.addEventListener('resize', function() {
            const navMenu = document.querySelector('.nav-menu');
            const navToggle = document.getElementById('navToggle');
            
            // إغلاق القائمة عند توسيع الشاشة إذا كانت مفتوحة
            if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        function enhanceStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(stat => {
        const finalNumber = parseInt(stat.textContent);
        let currentNumber =0 ;
       
        const increment = finalNumber/50;
        const timer = setInterval(() => {
            currentNumber += increment;
            if (currentNumber >= finalNumber) {
                currentNumber = finalNumber;
                clearInterval(timer);
            }
            stat.textContent = Math.floor(currentNumber) + '+';
        }, 35);
    });}
    
    enhanceStats();
           </script>
</body>
</html>