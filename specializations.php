<?php
// تضمين ملف الإعدادات
require_once 'config.php';

// معالجة معاملات الفلترة
$college_filter = $_GET['college'] ?? '';
$university_filter = $_GET['university'] ?? '';

// بناء استعلام التخصصات مع الفلترة
$where_conditions = ["s.status = 'متاح'"];

if (!empty($college_filter)) {
    $where_conditions[] = "c.name LIKE '%" . $conn->real_escape_string($college_filter) . "%'";
}

if (!empty($university_filter)) {
    if ($university_filter === 'مستقلة') {
        $where_conditions[] = "c.university_id IS NULL";
    } else {
        $where_conditions[] = "u.type = '" . $conn->real_escape_string($university_filter) . "'";
    }
}

$where_clause = implode(' AND ', $where_conditions);

// استعلام جلب التخصصات
$sql = "SELECT
    s.*,
    c.name as college_name,
    c.id as college_id,
    c.university_id,
    c.type as college_type,
    u.name as university_name,
    u.type as university_type,
    u.location as university_location,
    CASE 
        WHEN c.university_id IS NULL THEN c.location 
        ELSE u.location 
    END as location
FROM specializations s
JOIN colleges c ON s.college_id = c.id
LEFT JOIN universities u ON c.university_id = u.id
WHERE $where_clause
ORDER BY 
    CASE WHEN c.university_id IS NULL THEN 1 ELSE 0 END,
    s.name";

$result = $conn->query($sql);

// جلب إحصائيات التخصصات
$stats_sql = "SELECT
    COUNT(*) as total_specializations,
    COUNT(DISTINCT s.college_id) as total_colleges,
    (SELECT COUNT(*) FROM universities WHERE status = 'نشطة') as total_universities
FROM specializations s
JOIN colleges c ON s.college_id = c.id
WHERE s.status = 'متاح'";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// جلب التخصصات الأكثر شعبية (محسن)
$popular_sql = "SELECT
    s.name,
    COUNT(DISTINCT c.id) as college_count
FROM specializations s
JOIN colleges c ON s.college_id = c.id
LEFT JOIN universities u ON c.university_id = u.id
WHERE s.status = 'متاح'
GROUP BY s.name
ORDER BY college_count DESC
LIMIT 6";

$popular_result = $conn->query($popular_sql);

if (!$popular_result) {
    die("خطأ في استعلام التخصصات الشائعة: " . $conn->error);
}

// دالة للجمع بالعربية
function getArabicCollegeCount($count) {
    if ($count == 1) {
        return "كلية واحدة";
    } elseif ($count == 2) {
        return "كليتين";
    } elseif ($count >= 3 && $count <= 10) {
        return "$count كليات";
    } else {
        return "$count كلية";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التخصصات الجامعية - بوابة الجامعات اليمنية</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/specializations.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="Image/1.jpg" alt="شعار الجامعات اليمنية" style="width: 90px; height: 81px;">
                <div class="logo-text">
                    <span class="logo-title">دليل الجامعات اليمنية</span>
                    <span class="logo-subtitle">الالكتروني الشامل</span>
                </div>
            </div>

            <ul class="nav-menu" id="navMenu">
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

            <button class="nav-toggle" id="navToggle" aria-label="قائمة التنقل">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </nav>

    <!-- رأس الصفحة -->
    <header class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">التخصصات الجامعية</h1>
                    <p class="page-subtitle">اكتشف جميع التخصصات المتاحة في الجامعات والكليات المستقلة اليمنية</p>
                    <?php if (!empty($college_filter) || !empty($university_filter)): ?>
                        <div class="filter-message">
                            <i class="fas fa-filter"></i>
                            <span>عرض التخصصات:
                                <?php if (!empty($college_filter)): ?>
                                    كلية <?php echo htmlspecialchars($college_filter); ?>
                                <?php endif; ?>
                                <?php if (!empty($college_filter) && !empty($university_filter)): ?> و <?php endif; ?>
                                <?php if (!empty($university_filter)): ?>
                                    <?php 
                                        if ($university_filter === 'حكومية') echo 'جامعات حكومية';
                                        elseif ($university_filter === 'أهلية') echo 'جامعات أهلية';
                                        else echo 'كليات مستقلة';
                                    ?>
                                <?php endif; ?>
                            </span>
                            <a href="specializations.php" class="clear-filter">مسح الفلتر</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="header-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_universities']; ?></div>
                        <div class="stat-label">جامعة</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_colleges']; ?></div>
                        <div class="stat-label">كلية</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_specializations']; ?>+</div>
                        <div class="stat-label">تخصص</div>
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
                    <label for="university-filter">نوع المؤسسة:</label>
                    <select id="university-filter" class="filter-select">
                        <option value="">جميع المؤسسات</option>
                        <option value="حكومية">جامعات حكومية</option>
                        <option value="أهلية">جامعات أهلية</option>
                        <option value="مستقلة">كليات مستقلة</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="college-filter">الكلية:</label>
                    <select id="college-filter" class="filter-select">
                        <option value="">جميع الكليات</option>
                        <option value="الهندسة">كلية الهندسة</option>
                        <option value="الطب">كلية الطب</option>
                        <option value="العلوم">كلية العلوم</option>
                        <option value="التجارة">كلية التجارة</option>
                        <option value="الزراعة">كلية الزراعة</option>
                        <option value="الاعلام والاتصال">كليةالاعلام والاتصال</option>
                        <option value="التربية">كلية التربية</option>
                        <option value="الثروات النفطية والموارد الطبيعية">كلية الثروات النفطية والموارد الطبيعية</option>
                        <option value="الصيدلة">كلية الصيدلة</option>
                        <option value="الطب البيطري">كلية الطب البيطري</option>
                        <option value="الشريعة والقانون">كلية الشريعة والقانون</option>
                        <option value="اللغات">كلية اللغات</option>
                        <option value="طب الاسنان">كلية طب الاسنان</option>
                        <option value="الحاسوب وتكنولوجيا المعلومات">كلية الحاسوب وتكنولوجيا المعلومات</option>
                        <option value="الادارة">كلية الادارة</option>
                        <option value="الاداب">كلية الاداب</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search-input">البحث:</label>
                    <input type="text" id="search-input" class="filter-input" placeholder="ابحث عن تخصص...">
                </div>

                <button id="clear-filters" class="btn btn-outline">
                    <i class="fas fa-times"></i>
                    إعادة الضبط
                </button>
            </div>
        </div>
    </section>

    <!-- قسم التخصصات -->
    <section class="specializations-section">
        <div class="container">
            <div class="specializations-grid" id="specializations-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($specialization = $result->fetch_assoc()): ?>
                        <?php
                        // تحديد نوع الكلية للعرض
                        if ($specialization['university_id']) {
                            $type_class = $specialization['university_type'] == 'حكومية' ? 'government' : 'private';
                            $display_text = $specialization['university_type'];
                        } else {
                            $type_class = $specialization['college_type'] == 'حكومية' ? 'government' : 'private';
                            $display_text = $specialization['college_type'] ?: 'أهلية';
                        }
                        ?>
                        
                        <div class="specialization-card"
                             data-university="<?php echo $specialization['university_id'] ? $specialization['university_type'] : 'مستقلة'; ?>"
                             data-college="<?php echo htmlspecialchars($specialization['college_name']); ?>"
                             data-name="<?php echo htmlspecialchars($specialization['name']); ?>">

                            <div class="specialization-header">
                                <div class="specialization-logo">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="specialization-type <?php echo $type_class; ?>">
                                    <?php echo $display_text; ?>
                                </div>
                            </div>

                            <div class="specialization-content">
                                <h3><?php echo htmlspecialchars($specialization['name']); ?></h3>
                                
                                <?php if ($specialization['university_id']): ?>
                                    <p class="specialization-university">
                                        <i class="fas fa-university"></i>
                                        تابعة ل: <?php echo htmlspecialchars($specialization['university_name']); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="specialization-university">
                                        <i class="fas fa-school"></i>
                                        كلية مستقلة
                                    </p>
                                <?php endif; ?>
                                
                                <div class="description-section">
                                    <p class="description-text">
                                    <?php 
                                        $desc = trim($specialization['description'] ?? '');
                                        if (empty($desc)) {
                                            echo '<span class="no-description">لا يوجد وصف متاح</span>';
                                        } else {
                                            if (function_exists('mb_substr')) {
                                                $short = mb_substr($desc, 0, 40, 'UTF-8');
                                                if (mb_strlen($desc, 'UTF-8') > 40) $short .= '...';
                                            } else {
                                                $short = substr($desc, 0, 40) . (strlen($desc) >40 ? '...' : '');
                                            }
                                            echo htmlspecialchars($short);
                                        }
                                    ?>
                                    </p>
                                </div>

                                <div class="specialization-details">
                                    <div class="detail-item">
                                        <i class="fas fa-building"></i>
                                        <span><?php echo htmlspecialchars($specialization['college_name']); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo (int)$specialization['duration']; ?> سنوات</span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-certificate"></i>
                                        <span><?php echo htmlspecialchars($specialization['degree_type']); ?></span>
                                    </div>
                                    
                                    <?php if ($specialization['location']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($specialization['location']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                             
                            </div>

                            <div class="specialization-footer">
                                <a href="specialization-details.php?id=<?php echo $specialization['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-info-circle"></i>
                                    <span>تفاصيل التخصص</span>
                                </a>
                                
                                <a href="college-details.php?id=<?php echo $specialization['college_id']; ?>" class="btn btn-sm btn-outline">
                                    <i class="fas fa-building"></i>
                                    <span>تفاصيل الكلية</span>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>لا توجد تخصصات متاحة حالياً</h3>
                        <p>يرجى المحاولة مرة أخرى لاحقاً</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- قسم التخصصات الأكثر شعبية -->
    <section class="popular-section">
        <div class="container">
            <div class="section-header">
                <h2>التخصصات الأكثر شعبية</h2>
                <p>التخصصات المتاحة في أكبر عدد من الكليات اليمنية</p>
            </div>

            <div class="popular-grid">
                <?php if ($popular_result->num_rows > 0): ?>
                    <?php while($popular = $popular_result->fetch_assoc()): ?>
                        <div class="popular-item">
                            <div class="popular-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="popular-content">
                                <h4><?php echo htmlspecialchars($popular['name']); ?></h4>
                                <p>متوفر في <?php echo getArabicCollegeCount((int)$popular['college_count']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-popular">
                        <p>لا توجد بيانات كافية لتحديد التخصصات الشائعة</p>
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
                    <h2>إحصائيات التخصصات</h2>
                    <p>نظرة عامة على التخصصات المتاحة في الجامعات والكليات اليمنية</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['total_specializations']; ?>+</div>
                            <div class="stat-label">تخصص</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['total_colleges']; ?></div>
                            <div class="stat-label">كلية</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['total_universities']; ?></div>
                            <div class="stat-label">جامعة</div>
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
    <script>
        // فلترة التخصصات
        document.addEventListener('DOMContentLoaded', function() {
            const universityFilter = document.getElementById('university-filter');
            const collegeFilter = document.getElementById('college-filter');
            const searchInput = document.getElementById('search-input');
            const clearFiltersBtn = document.getElementById('clear-filters');
            const specializationCards = document.querySelectorAll('.specialization-card');

            function filterSpecializations() {
                const universityValue = universityFilter.value;
                const collegeValue = collegeFilter.value;
                const searchValue = searchInput.value.toLowerCase().trim();

                let visibleCount = 0;

                specializationCards.forEach(card => {
                    const cardUniversity = card.getAttribute('data-university');
                    const cardCollege = card.getAttribute('data-college').toLowerCase();
                    const cardName = card.getAttribute('data-name').toLowerCase();

                    const universityMatch = !universityValue || 
                        (universityValue === 'مستقلة' && cardUniversity === 'مستقلة') ||
                        (universityValue !== 'مستقلة' && cardUniversity === universityValue);
                    
                    const collegeMatch = !collegeValue || cardCollege.includes(collegeValue.toLowerCase());
                    const searchMatch = !searchValue || cardName.includes(searchValue) || cardCollege.includes(searchValue);

                    if (universityMatch && collegeMatch && searchMatch) {
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
                        const grid = document.getElementById('specializations-grid');
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.className = 'no-results';
                        noResultsDiv.innerHTML = `
                            <i class="fas fa-search"></i>
                            <h3>لا توجد تخصصات تطابق معايير البحث</h3>
                            <p>جرب تعديل الفلاتر أو مصطلحات البحث</p>
                        `;
                        grid.appendChild(noResultsDiv);
                    }
                } else if (existingNoResults) {
                    existingNoResults.remove();
                }
            }

            universityFilter.addEventListener('change', filterSpecializations);
            collegeFilter.addEventListener('change', filterSpecializations);
            searchInput.addEventListener('input', filterSpecializations);

            clearFiltersBtn.addEventListener('click', function() {
                universityFilter.value = '';
                collegeFilter.value = '';
                searchInput.value = '';
                filterSpecializations();
            });

            // إدارة القائمة المتنقلة للهواتف
            const navToggle = document.getElementById('navToggle');
            const navMenu = document.getElementById('navMenu');

            if (navToggle && navMenu) {
                navToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    navMenu.classList.toggle('active');
                    navToggle.classList.toggle('active');
                    
                    // منع التمرير عند فتح القائمة
                    if (navMenu.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });

                // إغلاق القائمة عند النقر على رابط
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('active');
                        navToggle.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });

                // إغلاق القائمة عند النقر خارجها
                document.addEventListener('click', function(e) {
                    if (navMenu.classList.contains('active') && 
                        !navMenu.contains(e.target) && 
                        !navToggle.contains(e.target)) {
                        navMenu.classList.remove('active');
                        navToggle.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });

                // إغلاق القائمة عند تغيير حجم النافذة
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768) {
                        navMenu.classList.remove('active');
                        navToggle.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
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
        }, 20);
    });}
    
    enhanceStats();
    </script>
</body>
</html>