<?php
// تضمين ملف الإعدادات
require_once 'config.php';

// استعلام لجلب الجامعات من قاعدة البيانات
$sql = "SELECT * FROM universities WHERE status = 'نشطة' ORDER BY name";
$result = $conn->query($sql);

// جلب إحصائيات الجامعات
$stats_sql = "SELECT
    COUNT(*) as total_universities,
    SUM(CASE WHEN type = 'حكومية' THEN 1 ELSE 0 END) as government_universities,
    SUM(CASE WHEN type = 'أهلية' THEN 1 ELSE 0 END) as private_universities
FROM universities WHERE status = 'نشطة'";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الجامعات اليمنية - بوابة الجامعات اليمنية</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/universities.css">

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
                 <span class="logo-title">دليل الجامعات اليمنية </span>
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

    <!-- رأس الصفحة -->
    <header class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">الجامعات اليمنية</h1>
                    <p class="page-subtitle">اكتشف جميع الجامعات اليمنية الحكومية والأهلية</p>
                </div>
                <div class="header-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_universities']; ?>+</div>
                        <div class="stat-label">جامعة</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['government_universities']; ?></div>
                        <div class="stat-label">حكومية</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['private_universities']; ?></div>
                        <div class="stat-label">أهلية</div>
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
                    <label for="type-filter">نوع الجامعة:</label>
                    <select id="type-filter" class="filter-select">
                        <option value="">جميع الأنواع</option>
                        <option value="government">حكومية</option>
                        <option value="private">أهلية</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="location-filter">الموقع:</label>
                    <select id="location-filter" class="filter-select">
                        <option value="">جميع المواقع</option>
                        <option value="صنعاء">صنعاء</option>
                        <option value="عدن">عدن</option>
                        <option value="تعز">تعز</option>
                        <option value="الحديدة">الحديدة</option>
                        <option value="إب">إب</option>
                        <option value="ذمار">ذمار</option>
                        <option value="حجة">حجة</option>
                        <option value="المحويت">المحويت</option>
                        <option value="البيضاء">البيضاء</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search-input">البحث:</label>
                    <input type="text" id="search-input" class="filter-input" placeholder="ابحث عن جامعة...">
                </div>

                <button id="clear-filters" class="btn btn-outline">
                    <i class="fas fa-times"></i>
                    إعادة الضبط
                </button>
            </div>
        </div>
    </section>

    <!-- قسم الجامعات -->
    <section class="universities-section">
        <div class="container">
            <div class="universities-grid" id="universities-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($university = $result->fetch_assoc()): ?>
                        <?php
                        // تحديد نوع الجامعة للعرض
                        $type_class = $university['type'] == 'حكومية' ? 'government' : 'private';
                        $display_text = $university['type'];
                        ?>
                        <div class="university-card" 
                             data-type="<?php echo $type_class; ?>" 
                             data-location="<?php echo htmlspecialchars($university['location']); ?>"
                             data-name="<?php echo htmlspecialchars($university['name']); ?>">
                            
                            <div class="university-header">
                                <div class="university-logo">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="university-type <?php echo $type_class; ?>">
                                    <?php echo $display_text; ?>
                                </div>
                            </div>

                            <div class="university-content">
                                <h3><?php echo htmlspecialchars($university['name']); ?></h3>
                                
                                <p>
                                    <?php 
                                    $text = $university['description'] ?: 'جامعة رائدة في التعليم العالي توفر بيئة تعليمية متميزة للطلاب';
                                    echo mb_strimwidth(htmlspecialchars($text), 0, 100, "...");
                                    ?>
                                </p>

                                <div class="university-details">
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($university['location']); ?></span>
                                    </div>
                                    
                                    <?php if ($university['phone']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($university['phone']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($university['email']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($university['email']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="university-stats">
                                    <div class="university-stat">
                                        <i class="fas fa-tag"></i>
                                        <span><?php echo $display_text; ?></span>
                                    </div>
                                    <?php if ($university['website']): ?>
                                    <div class="university-stat">
                                        <i class="fas fa-globe"></i>
                                        <span>موقع إلكتروني</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($university['coordination_link']): ?>
                                    <div class="university-stat">
                                        <i class="fas fa-link"></i>
                                        <span>نظام تنسيق</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="university-footer">
                                <a href="university-details.php?id=<?php echo $university['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-info-circle"></i>
                                    <span>عرض التفاصيل</span>
                                </a>
                                <?php if ($university['website']): ?>
                                <a href="<?php echo htmlspecialchars($university['website']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                    <i class="fas fa-external-link-alt"></i>
                                    <span>موقع الجامعة</span>
                                </a>
                                <?php endif; ?>
                                <?php if ($university['coordination_link']): ?>
                                <a href="<?php echo htmlspecialchars($university['coordination_link']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                    <i class="fas fa-link"></i>
                                    <span>رابط التنسيق</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-university"></i>
                        <h3>لا توجد جامعات متاحة حالياً</h3>
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
                    <h2>إحصائيات الجامعات</h2>
                    <p>نظرة عامة على التعليم العالي في اليمن</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['total_universities']; ?>+</div>
                            <div class="stat-label">جامعة</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['government_universities']; ?></div>
                            <div class="stat-label">جامعة حكومية</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $stats['private_universities']; ?></div>
                            <div class="stat-label">جامعة أهلية</div>
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
    <script src="assets/js/main.js"></script>
    <script>
        // فلترة الجامعات
        document.addEventListener('DOMContentLoaded', function() {
            const typeFilter = document.getElementById('type-filter');
            const locationFilter = document.getElementById('location-filter');
            const searchInput = document.getElementById('search-input');
            const clearFiltersBtn = document.getElementById('clear-filters');
            const universityCards = document.querySelectorAll('.university-card');

            function filterUniversities() {
                const typeValue = typeFilter.value;
                const locationValue = locationFilter.value;
                const searchValue = searchInput.value.toLowerCase().trim();

                let visibleCount = 0;

                universityCards.forEach(card => {
                    const cardType = card.getAttribute('data-type');
                    const cardLocation = card.getAttribute('data-location').toLowerCase();
                    const cardName = card.getAttribute('data-name').toLowerCase();

                    const typeMatch = !typeValue || cardType === typeValue;
                    const locationMatch = !locationValue || cardLocation.includes(locationValue.toLowerCase());
                    const searchMatch = !searchValue || cardName.includes(searchValue) || cardLocation.includes(searchValue);

                    if (typeMatch && locationMatch && searchMatch) {
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
                        const grid = document.getElementById('universities-grid');
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.className = 'no-results';
                        noResultsDiv.innerHTML = `
                            <i class="fas fa-university"></i>
                            <h3>لا توجد جامعات تطابق معايير البحث</h3>
                            <p>جرب تعديل الفلاتر أو مصطلحات البحث</p>
                        `;
                        grid.appendChild(noResultsDiv);
                    }
                } else if (existingNoResults) {
                    existingNoResults.remove();
                }
            }

            typeFilter.addEventListener('change', filterUniversities);
            locationFilter.addEventListener('change', filterUniversities);
            searchInput.addEventListener('input', filterUniversities);

            clearFiltersBtn.addEventListener('click', function() {
                typeFilter.value = '';
                locationFilter.value = '';
                searchInput.value = '';
                filterUniversities();
            });

            // إضافة تأثيرات للبطاقات
            universityCards.forEach((card, index) => {
                card.style.animationDelay = `${(index % 6) * 0.1}s`;
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
        }, 35);
    });}
    
    enhanceStats();

    </script>
</body>
</html>