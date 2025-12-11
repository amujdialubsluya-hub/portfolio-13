<?php
// تضمين ملف الإعدادات
require_once 'config.php';

// معالجة البحث
$search_results = [];
$search_performed = false;
$search_query = '';
$search_type = 'all';

// الحصول على الصفحة الحالية لتحديد الزر النشط
$current_page = basename($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['q'])) {
    $search_performed = true;
    $search_query = isset($_POST['search_query']) ? trim($_POST['search_query']) : trim($_GET['q']);
    $search_type = isset($_POST['search_type']) ? $_POST['search_type'] : 'all';

    if (!empty($search_query)) {
        // البحث في الجامعات (بالاسم فقط)
        if ($search_type === 'universities' || $search_type === 'all') {
            $university_sql = "SELECT
                u.*,
                COUNT(DISTINCT c.id) as colleges_count,
                COUNT(DISTINCT s.id) as specializations_count
            FROM universities u
            LEFT JOIN colleges c ON u.id = c.university_id
            LEFT JOIN specializations s ON c.id = s.college_id
            WHERE u.status = 'نشطة'
            AND u.name LIKE ?
            GROUP BY u.id
            ORDER BY u.name";

            $stmt = $conn->prepare($university_sql);
            $search_param = "%$search_query%";
            $stmt->bind_param("s", $search_param);
            $stmt->execute();
            $university_results = $stmt->get_result();

            if ($university_results->num_rows > 0) {
                $search_results['universities'] = $university_results->fetch_all(MYSQLI_ASSOC);
            }
        }

        // البحث في الكليات (بالاسم فقط)
        if ($search_type === 'colleges' || $search_type === 'all') {
            $college_sql = "SELECT
                c.*,
                u.name as university_name,
                u.type as university_type,
                u.location as university_location,
                COUNT(s.id) as specializations_count,
                CASE 
                    WHEN c.university_id IS NULL THEN c.type
                    ELSE u.type 
                END as display_type
            FROM colleges c
            LEFT JOIN universities u ON c.university_id = u.id
            LEFT JOIN specializations s ON c.id = s.college_id
            WHERE c.status = 'نشطة'
            AND c.name LIKE ?
            GROUP BY c.id
            ORDER BY c.name";

            $stmt = $conn->prepare($college_sql);
            $search_param = "%$search_query%";
            $stmt->bind_param("s", $search_param);
            $stmt->execute();
            $college_results = $stmt->get_result();

            if ($college_results->num_rows > 0) {
                $search_results['colleges'] = $college_results->fetch_all(MYSQLI_ASSOC);
            }
        }

        // البحث في التخصصات (بالاسم فقط)
        if ($search_type === 'specializations' || $search_type === 'all') {
            $specialization_sql = "SELECT
                s.*,
                c.name as college_name,
                u.name as university_name,
                u.type as university_type,
                u.location as university_location
            FROM specializations s
            LEFT JOIN colleges c ON s.college_id = c.id
            LEFT JOIN universities u ON c.university_id = u.id
            WHERE s.status = 'متاح'
            AND s.name LIKE ?
            ORDER BY s.name";

            $stmt = $conn->prepare($specialization_sql);
            $search_param = "%$search_query%";
            $stmt->bind_param("s", $search_param);
            $stmt->execute();
            $specialization_results = $stmt->get_result();

            if ($specialization_results->num_rows > 0) {
                $search_results['specializations'] = $specialization_results->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
}

// جلب إحصائيات البحث
$stats_sql = "SELECT
    (SELECT COUNT(*) FROM universities WHERE status = 'نشطة') as total_universities,
    (SELECT COUNT(*) FROM specializations WHERE status = 'متاح') as total_specializations,
    (SELECT COUNT(*) FROM colleges WHERE status = 'نشطة') as total_colleges";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث المتقدم - بوابة الجامعات اليمنية</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/search.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
        .search-info {
            text-align: center;
            margin-top: 1rem;
        }

        .search-hint {
            color: #64748b;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .search-hint i {
            color: #6366f1;
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
                    <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>الرئيسية</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="universities.php" class="nav-link <?php echo $current_page == 'universities.php' ? 'active' : ''; ?>">
                        <i class="fas fa-university"></i>
                        <span>الجامعات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="colleges.php" class="nav-link <?php echo $current_page == 'colleges.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>الكليات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="specializations.php" class="nav-link <?php echo $current_page == 'specializations.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>التخصصات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="search.php" class="nav-link <?php echo $current_page == 'search.php' ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i>
                        <span>البحث</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="contact.php" class="nav-link <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">
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
            
            <!-- زر القائمة المتنقلة للهواتف -->
            <div class="nav-toggle" id="navToggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- رأس الصفحة -->
    <header class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">البحث المتقدم</h1>
                    <p class="page-subtitle">ابحث في أسماء الجامعات والتخصصات والكليات</p>
                </div>
                <div class="header-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_universities']; ?>+</div>
                        <div class="stat-label">جامعة</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_specializations']; ?>+</div>
                        <div class="stat-label">تخصص</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_colleges']; ?>+</div>
                        <div class="stat-label">كلية</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- قسم البحث -->
    <section class="search-section">
        <div class="container">
            <div class="search-container">
                <form method="POST" class="search-form" id="search-form">
                    <div class="search-input-group">
                        <div class="search-input-wrapper">
                           
                            <input
                                type="text"
                                name="search_query"
                                id="search-input"
                                class="search-input"
                                placeholder="ابحث بالاسم فقط: جامعة، تخصص، كلية..."
                                value="<?php echo htmlspecialchars($search_query); ?>"
                                required
                            >
                            <button type="submit" class="search-button">
                                <i class="fas fa-search"></i>
                                <span>بحث</span>
                            </button>
                        </div>
                    </div>

                    <div class="search-options">
                        <div class="search-type-group">
                            <label class="search-type-label">
                                <input type="radio" name="search_type" value="all" <?php echo ($search_type === 'all' || empty($search_type)) ? 'checked' : ''; ?>>
                                <span class="radio-custom"></span>
                                <span class="radio-text">البحث الشامل</span>
                            </label>
                            <label class="search-type-label">
                                <input type="radio" name="search_type" value="universities" <?php echo $search_type === 'universities' ? 'checked' : ''; ?>>
                                <span class="radio-custom"></span>
                                <span class="radio-text">الجامعات فقط</span>
                            </label>
                            <label class="search-type-label">
                                <input type="radio" name="search_type" value="colleges" <?php echo $search_type === 'colleges' ? 'checked' : ''; ?>>
                                <span class="radio-custom"></span>
                                <span class="radio-text">الكليات فقط</span>
                            </label>
                            <label class="search-type-label">
                                <input type="radio" name="search_type" value="specializations" <?php echo $search_type === 'specializations' ? 'checked' : ''; ?>>
                                <span class="radio-custom"></span>
                                <span class="radio-text">التخصصات فقط</span>
                            </label>
                        </div>
                    </div>

                    <!-- نص توضيحي -->
                    <div class="search-info">
                        <p class="search-hint">
                            <i class="fas fa-info-circle"></i>
                            البحث يتم في أسماء الجامعات والتخصصات والكليات فقط
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- نتائج البحث -->
    <?php if ($search_performed): ?>
    <section class="results-section">
        <div class="container">
            <?php if (empty($search_results)): ?>
                <!-- لا توجد نتائج -->
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h2>لا توجد نتائج</h2>
                    <p>لم نتمكن من العثور على نتائج لـ "<?php echo htmlspecialchars($search_query); ?>"</p>
                    <div class="no-results-suggestions">
                        <h4>اقتراحات للبحث:</h4>
                        <ul>
                            <li>تأكد من صحة اسم الجامعة أو التخصص أو الكلية</li>
                            <li>جرب اسم بحث مختلف</li>
                            <li>استخدم كلمات أقصر</li>
                            <li>جرب البحث الشامل</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <!-- عرض النتائج -->
                <div class="results-header">
                    <h2>نتائج البحث</h2>
                    <p>تم العثور على <?php 
                        $total_results = 0;
                        foreach ($search_results as $type => $results) {
                            $total_results += count($results);
                        }
                        echo $total_results;
                    ?> نتيجة لـ "<?php echo htmlspecialchars($search_query); ?>"</p>
                </div>

                <!-- نتائج الجامعات -->
                <?php if (isset($search_results['universities']) && !empty($search_results['universities'])): ?>
                <div class="results-group">
                    <div class="results-group-header">
                        <h3><i class="fas fa-university"></i> الجامعات (<?php echo count($search_results['universities']); ?>)</h3>
                    </div>
                    <div class="universities-grid">
                        <?php foreach ($search_results['universities'] as $university): ?>
                        <div class="university-card">
                            <div class="university-header">
                                <div class="university-logo">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="university-type <?php echo $university['type'] == 'حكومية' ? 'government' : 'private'; ?>">
                                    <?php echo $university['type']; ?>
                                </div>
                            </div>

                            <div class="university-content">
                                <h3><?php echo htmlspecialchars($university['name']); ?></h3>
                                <p>
                                <?php 
                                    $desc = trim($university['description'] ?? '');
                                    if (empty($desc)) {
                                        echo '<span style="color:#64748b;font-style:italic;">لا يوجد وصف</span>';
                                    } else {
                                        if (function_exists('mb_substr')) {
                                            $short = mb_substr($desc, 0, 80, 'UTF-8');
                                            if (mb_strlen($desc, 'UTF-8') > 80) $short .= '...';
                                        } else {
                                            $short = substr($desc, 0, 80) . (strlen($desc) > 80 ? '...' : '');
                                        }
                                        echo htmlspecialchars($short);
                                    }
                                ?>
                                </p>

                                <div class="university-details">
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($university['location']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-building"></i>
                                        <span><?php echo $university['colleges_count']; ?> كلية</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?php echo $university['specializations_count']; ?> تخصص</span>
                                    </div>
                                </div>
                            </div>

                            <div class="university-footer">
                                <a href="university-details.php?id=<?php echo $university['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-info-circle"></i>
                                    <span>عرض التفاصيل</span>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- نتائج الكليات -->
                <?php if (isset($search_results['colleges']) && !empty($search_results['colleges'])): ?>
                <div class="results-group">
                    <div class="results-group-header">
                        <h3><i class="fas fa-building"></i> الكليات (<?php echo count($search_results['colleges']); ?>)</h3>
                    </div>
                    <div class="colleges-grid">
                        <?php foreach ($search_results['colleges'] as $college): ?>
                        <div class="college-card">
                            <div class="college-header">
                                <div class="college-logo">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="college-type <?php 
                                    if ($college['university_id'] === null) {
                                        echo 'independent';
                                    } else {
                                        echo $college['display_type'] == 'حكومية' ? 'government' : 'private';
                                    }
                                ?>">
                                    <?php 
                                    if ($college['university_id'] === null) {
                                        echo 'مستقلة';
                                    } else {
                                        echo $college['display_type'];
                                    }
                                    ?>
                                    <?php if ($college['university_id'] === null): ?>
                                        <br><small style="font-size: 0.7rem; opacity: 0.8;">كلية مستقلة</small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="college-content">
                                <h3><?php echo htmlspecialchars($college['name']); ?></h3>
                                
                                <?php if ($college['university_name']): ?>
                                    <p class="college-university">
                                        <i class="fas fa-university"></i>
                                        <?php echo htmlspecialchars($college['university_name']); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="college-university">
                                        <i class="fas fa-school"></i>
                                        كلية مستقلة
                                    </p>
                                <?php endif; ?>
                                
                                <p>
                                <?php 
                                    $desc = trim($college['description'] ?? '');
                                    if (empty($desc)) {
                                        echo '<span style="color:#64748b;font-style:italic;">لا يوجد وصف</span>';
                                    } else {
                                        if (function_exists('mb_substr')) {
                                            $short = mb_substr($desc, 0, 80, 'UTF-8');
                                            if (mb_strlen($desc, 'UTF-8') > 80) $short .= '...';
                                        } else {
                                            $short = substr($desc, 0, 80) . (strlen($desc) > 80 ? '...' : '');
                                        }
                                        echo htmlspecialchars($short);
                                    }
                                ?>
                                </p>

                                <div class="college-details">
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($college['university_location'] ?? $college['location'] ?? 'غير محدد'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?php echo $college['specializations_count']; ?> تخصص</span>
                                    </div>
                                </div>
                            </div>

                            <div class="college-footer">
                                <a href="college-details.php?id=<?php echo $college['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-info-circle"></i>
                                    <span>عرض التفاصيل</span>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- نتائج التخصصات -->
                <?php if (isset($search_results['specializations']) && !empty($search_results['specializations'])): ?>
                <div class="results-group">
                    <div class="results-group-header">
                        <h3><i class="fas fa-book"></i> التخصصات (<?php echo count($search_results['specializations']); ?>)</h3>
                    </div>
                    <div class="specializations-grid">
                        <?php foreach ($search_results['specializations'] as $specialization): ?>
                        <div class="specialization-card">
                            <div class="specialization-header">
                                <div class="specialization-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="specialization-type <?php echo $specialization['university_type'] == 'حكومية' ? 'government' : 'private'; ?>">
                                    <?php echo $specialization['university_type']; ?>
                                </div>
                            </div>

                            <div class="specialization-content">
                                <h3><?php echo htmlspecialchars($specialization['name']); ?></h3>
                                <p>
                                <?php 
                                    $desc = trim($specialization['description'] ?? '');
                                    if (empty($desc)) {
                                        echo '<span style="color:#64748b;font-style:italic;">لا يوجد وصف</span>';
                                    } else {
                                        if (function_exists('mb_substr')) {
                                            $short = mb_substr($desc, 0, 80, 'UTF-8');
                                            if (mb_strlen($desc, 'UTF-8') > 80) $short .= '...';
                                        } else {
                                            $short = substr($desc, 0, 80) . (strlen($desc) > 80 ? '...' : '');
                                        }
                                        echo htmlspecialchars($short);
                                    }
                                ?>
                                </p>

                                <div class="specialization-details">
                                    <div class="detail-item">
                                        <i class="fas fa-university"></i>
                                        <span><?php echo htmlspecialchars($specialization['university_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-building"></i>
                                        <span><?php echo htmlspecialchars($specialization['college_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo $specialization['duration']; ?> سنوات</span>
                                    </div>
                                </div>
                            </div>

                            <div class="specialization-footer">
                                <a href="specialization-details.php?id=<?php echo $specialization['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-info-circle"></i>
                                    <span>عرض التفاصيل</span>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

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
       
        const increment = finalNumber / 50;
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