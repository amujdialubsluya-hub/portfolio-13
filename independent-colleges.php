<?php
require_once 'config.php';

// استعلام لجلب الكليات غير التابعة لأي جامعة
$sql = "SELECT 
    c.*,
    COUNT(s.id) as specializations_count
FROM colleges c
LEFT JOIN specializations s ON c.id = s.college_id
WHERE c.status = 'نشطة' AND c.university_id IS NULL
GROUP BY c.id
ORDER BY c.name";

$result = $conn->query($sql);

// جلب إحصائيات الكليات المستقلة
$stats_sql = "SELECT
    COUNT(*) as total_colleges,
    SUM(CASE WHEN c.status = 'نشطة' THEN 1 ELSE 0 END) as active_colleges,
    AVG(spec_count) as avg_specializations
FROM (
    SELECT c.id, COUNT(s.id) as spec_count
    FROM colleges c
    LEFT JOIN specializations s ON c.id = s.college_id
    WHERE c.university_id IS NULL
    GROUP BY c.id
) as college_stats";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// جلب أحدث الكليات المستقلة
$latest_sql = "SELECT c.*, COUNT(s.id) as specializations_count
               FROM colleges c
               LEFT JOIN specializations s ON c.id = s.college_id
               WHERE c.status = 'نشطة' AND c.university_id IS NULL
               GROUP BY c.id
               ORDER BY c.created_at DESC
               LIMIT 6";
$latest_result = $conn->query($latest_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكليات المستقلة - بوابة الجامعات اليمنية</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/universities.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
        /* تخصيصات إضافية للكليات المستقلة */
        .independent-college-logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 15px;
        }

        .independent-badge {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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
            color: #8b5cf6;
            width: 16px;
        }

        .college-contact-info {
            margin: 10px 0;
            padding: 12px;
            background: rgba(139, 92, 246, 0.05);
            border-radius: 10px;
            border-right: 3px solid #8b5cf6;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 6px 0;
            font-size: 0.9rem;
        }

        .contact-item i {
            color: #8b5cf6;
            width: 16px;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-top: 4px solid #8b5cf6;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        /* تحسينات للبطاقات */
        .university-card.independent {
            border-top: 4px solid #8b5cf6;
            position: relative;
            overflow: hidden;
        }

        .university-card.independent::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
        }

        .university-card .college-content h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: #1e293b;
        }

        .university-card .college-content p {
            margin-bottom: 15px;
            color: #64748b;
            line-height: 1.6;
        }

        /* تحسينات للاستجابة */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .college-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        /* أقسام خاصة بالكليات المستقلة */
        .independent-section {
            background: #f8fafc;
            padding: 60px 0;
            margin: 40px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-header h2 {
            font-size: 2.2rem;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
        }

        .highlight-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 30px 0;
        }

        .highlight-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .highlight-card p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
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
                    <a href="colleges.php" class="nav-link">
                        <i class="fas fa-building"></i>
                        <span>الكليات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="independent-colleges.php" class="nav-link active">
                        <i class="fas fa-landmark"></i>
                        <span>الكليات المستقلة</span>
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

            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- قسم البطل -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>الكليات المستقلة</h1>
                <p>اكتشف الكليات اليمنية المستقلة غير التابعة لأي جامعة</p>
                <div class="header-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_colleges']; ?>+</div>
                        <div class="stat-label">كلية مستقلة</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['active_colleges']; ?></div>
                        <div class="stat-label">نشطة حالياً</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo round($stats['avg_specializations'], 1); ?></div>
                        <div class="stat-label">متوسط التخصصات</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- قسم المميزات -->
    <section class="independent-section">
        <div class="container">
            <div class="section-header">
                <h2>مميزات الكليات المستقلة</h2>
                <p>تعرف على أهم المميزات التي تقدمها الكليات المستقلة للطلاب</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>شهادات معتمدة</h3>
                    <p>شهادات معترف بها محلياً ودولياً في تخصصات متنوعة</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>مرونة في الدراسة</h3>
                    <p>برامج دراسية مرنة تناسب مختلف الاحتياجات والظروف</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>تخصصات متميزة</h3>
                    <p>تخصصات تلبي متطلبات سوق العمل المحلي والعالمي</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>كادر تدريسي متميز</h3>
                    <p>أعضاء هيئة تدريسية ذات خبرة وكفاءة عالية</p>
                </div>
            </div>
        </div>
    </section>

    <!-- قسم الكليات المستقلة -->
    <section class="universities-section">
        <div class="container">
            <div class="section-header">
                <h2>الكليات المستقلة المتاحة</h2>
                <p>استعرض قائمة الكليات المستقلة المعتمدة في اليمن</p>
            </div>

            <div class="universities-grid" id="colleges-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($college = $result->fetch_assoc()): ?>
                        <div class="university-card independent" 
                             data-name="<?php echo htmlspecialchars($college['name']); ?>">
                            
                            <div class="university-header">
                                <div class="independent-college-logo">
                                    <i class="fas fa-landmark"></i>
                                </div>
                                <div class="independent-badge">
                                    كلية مستقلة
                                </div>
                            </div>

                            <div class="university-content">
                                <h3><?php echo htmlspecialchars($college['name']); ?></h3>
                                
                                <p>
                                    <?php 
                                    $text = $college['description'] ?: 'كلية مستقلة تقدم برامج تعليمية متميزة في تخصصات متنوعة';
                                    echo mb_strimwidth(htmlspecialchars($text), 0, 120, "...");
                                    ?>
                                </p>

                                <!-- معلومات الاتصال -->
                                <?php if ($college['phone'] || $college['email'] || $college['coordination_link']): ?>
                                <div class="college-contact-info">
                                    <?php if ($college['phone']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($college['phone']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($college['email']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($college['email']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($college['coordination_link']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-link"></i>
                                        <a href="<?php echo htmlspecialchars($college['coordination_link']); ?>" target="_blank">رابط التنسيق</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="college-stats">
                                    <div class="college-stat">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?php echo $college['specializations_count']; ?> تخصص</span>
                                    </div>
                                    <div class="college-stat">
                                        <i class="fas fa-check-circle"></i>
                                        <span>الحالة: <?php echo $college['status']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="university-footer">
                                <a href="college-details.php?id=<?php echo $college['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-info-circle"></i>
                                    <span>عرض التفاصيل</span>
                                </a>
                                <?php if ($college['coordination_link']): ?>
                                <a href="<?php echo htmlspecialchars($college['coordination_link']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                    <i class="fas fa-external-link-alt"></i>
                                    <span>موقع الكلية</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results" style="text-align: center; padding: 60px 20px; grid-column: 1 / -1;">
                        <i class="fas fa-landmark" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                        <h3 style="color: #64748b; margin-bottom: 10px;">لا توجد كليات مستقلة مسجلة حالياً</h3>
                        <p style="color: #94a3b8;">سيتم إضافة الكليات المستقلة قريباً</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- قسم أحدث الكليات -->
    <?php if ($latest_result->num_rows > 0): ?>
    <section class="independent-section" style="background: white;">
        <div class="container">
            <div class="section-header">
                <h2>أحدث الكليات المستقلة</h2>
                <p>أحدث الكليات المستقلة المنضمة إلى منصتنا</p>
            </div>

            <div class="universities-grid">
                <?php while($college = $latest_result->fetch_assoc()): ?>
                    <div class="university-card independent">
                        <div class="university-header">
                            <div class="independent-college-logo">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="independent-badge">
                                جديد
                            </div>
                        </div>

                        <div class="university-content">
                            <h3><?php echo htmlspecialchars($college['name']); ?></h3>
                            <p><?php echo mb_strimwidth(htmlspecialchars($college['description'] ?: 'كلية مستقلة جديدة'), 0, 100, "..."); ?></p>
                            
                            <div class="college-stats">
                                <div class="college-stat">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo $college['specializations_count']; ?> تخصص</span>
                                </div>
                            </div>
                        </div>

                        <div class="university-footer">
                            <a href="college-details.php?id=<?php echo $college['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-info-circle"></i>
                                <span>استكشاف</span>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- قسم الإحصائيات -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-content">
                <div class="stats-text">
                    <h2>إحصائيات الكليات المستقلة</h2>
                    <p>نظرة شاملة على واقع التعليم المستقل في اليمن</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-landmark"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['total_colleges']; ?>+</div>
                            <div class="stat-label">كلية مستقلة</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['active_colleges']; ?></div>
                            <div class="stat-label">كلية نشطة</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo round($stats['avg_specializations'], 1); ?></div>
                            <div class="stat-label">متوسط التخصصات</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">جودة التعليم</div>
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
                        <li><a href="independent-colleges.php">الكليات المستقلة</a></li>
                        <li><a href="specializations.php">التخصصات</a></li>
                        <li><a href="contact.php">اتصل بنا</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>الأقسام</h4>
                    <ul>
                        <li><a href="universities.php">الجامعات الحكومية</a></li>
                        <li><a href="universities.php?type=أهلية">الجامعات الأهلية</a></li>
                        <li><a href="independent-colleges.php">الكليات المستقلة</a></li>
                        <li><a href="search.php">البحث المتقدم</a></li>
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
        // فلترة الكليات
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const collegeCards = document.querySelectorAll('.university-card');

            function filterColleges() {
                const searchValue = searchInput.value.toLowerCase().trim();

                collegeCards.forEach(card => {
                    const cardName = card.getAttribute('data-name').toLowerCase();
                    const searchMatch = !searchValue || cardName.includes(searchValue);

                    if (searchMatch) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', filterColleges);
            }

            // تفعيل القائمة المتنقلة
            const navToggle = document.querySelector('.nav-toggle');
            const navMenu = document.querySelector('.nav-menu');

            if (navToggle) {
                navToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    navToggle.classList.toggle('active');
                });
            }

            // إغلاق القائمة عند النقر على رابط
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    navMenu.classList.remove('active');
                    navToggle.classList.remove('active');
                });
            });
        });
    </script>
</body>
</html>