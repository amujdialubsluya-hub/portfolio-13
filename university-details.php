<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: universities.php');
    exit();
}

$university_id = (int)$_GET['id'];

// جلب معلومات الجامعة
$university_sql = "SELECT * FROM universities WHERE id = ? AND status = 'نشطة'";
$stmt = $conn->prepare($university_sql);
$stmt->bind_param("i", $university_id);
$stmt->execute();
$university_result = $stmt->get_result();

if ($university_result->num_rows === 0) {
    header('Location: universities.php');
    exit();
}

$university = $university_result->fetch_assoc();

// صور الجامعة
$images_sql = "SELECT * FROM university_images WHERE university_id = ? ORDER BY sort_order, id";
$stmt = $conn->prepare($images_sql);
$stmt->bind_param("i", $university_id);
$stmt->execute();
$images_result = $stmt->get_result();
$images = $images_result->fetch_all(MYSQLI_ASSOC);

// تصفية الصور الموجودة فعلياً
$valid_images = [];
foreach ($images as $img) {
    $rel = $img['image_path'];
    $abs = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
    if (is_file($abs)) { 
        $valid_images[] = $img; 
    }
}

// جلب كليات الجامعة
$colleges_sql = "SELECT * FROM colleges WHERE university_id = ? ORDER BY name";
$stmt = $conn->prepare($colleges_sql);
$stmt->bind_param("i", $university_id);
$stmt->execute();
$colleges_result = $stmt->get_result();

// جلب التخصصات مع معلومات الكليات
$specializations_sql = "SELECT s.*, c.name as college_name
                       FROM specializations s
                       JOIN colleges c ON s.college_id = c.id
                       WHERE c.university_id = ? AND s.status = 'متاح'
                       ORDER BY c.name, s.name";
$stmt = $conn->prepare($specializations_sql);
$stmt->bind_param("i", $university_id);
$stmt->execute();
$specializations_result = $stmt->get_result();

// إحصائيات الجامعة
$stats_sql = "SELECT
    (SELECT COUNT(*) FROM colleges WHERE university_id = ?) as total_colleges,
    (SELECT COUNT(*) FROM specializations s
     JOIN colleges c ON s.college_id = c.id
     WHERE c.university_id = ? AND s.status = 'متاح') as total_specializations";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("ii", $university_id, $university_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($university['name']); ?> - بوابة الجامعات اليمنية</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(trim($university['description'] ?? 'جامعة في اليمن'), 0, 160)); ?>">

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/university-details.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    
    <style>
        /* تصميم الخريطة الموحد */
        .map-section {
            margin: 3rem 0;
        }

        .map-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .map-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .map-placeholder .fa-map-marked-alt {
            font-size: 4rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 1rem;
        }

        .map-placeholder h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: white;
        }

        .map-placeholder p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .map-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .map-actions .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .map-actions .btn-primary {
            background: rgba(255,255,255,0.9);
            color: #667eea;
            border-color: rgba(255,255,255,0.9);
        }

        .map-actions .btn-outline {
            background: transparent;
            color: white;
            border-color: rgba(255,255,255,0.5);
        }

        .map-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .map-actions .btn-primary:hover {
            background: white;
            color: #667eea;
        }

        .map-actions .btn-outline:hover {
            background: rgba(255,255,255,0.1);
            border-color: white;
        }

        /* التجاوب مع الشاشات الصغيرة */
        @media (max-width: 768px) {
            .map-container {
                padding: 2rem 1rem;
            }
            
            .map-placeholder .fa-map-marked-alt {
                font-size: 3rem;
            }
            
            .map-placeholder h3 {
                font-size: 1.5rem;
            }
            
            .map-actions {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }
            
            .map-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* تحسينات إضافية */
        .description {
            line-height: 1.6;
            text-align: justify;
            margin-bottom: 1.5rem;
            white-space: pre-line;
        }
        
        .info-content {
            padding: 1.5rem;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .contact-item i {
            color: #007bff;
            margin-top: 0.25rem;
        }
        
        .college-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .college-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .slider-btn {
            background: rgba(255,255,255,0.8);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .slider-btn:hover {
            background: rgba(255,255,255,1);
            transform: scale(1.1);
        }
        
        .slider-btn.prev {
            right: 15px;
        }
        
        .slider-btn.next {
            left: 15px;
        }
        
        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .slider-btn {
                width: 35px;
                height: 35px;
            }
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="Image/1.jpg" style="width: 90px; height: 81px;" alt="شعار دليل الجامعات اليمنية">
                <div class="logo-text">
                    <span class="logo-title">دليل الجامعات اليمنية</span>
                    <span class="logo-subtitle">الالكتروني الشامل</span>
                </div>
            </div>
   <button class="nav-toggle" aria-label="قائمة التنقل">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>الرئيسية</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="universities.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'universities.php') ? 'active' : ''; ?>">
                        <i class="fas fa-university"></i>
                        <span>الجامعات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="colleges.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'colleges.php' || basename($_SERVER['PHP_SELF']) == 'college-details.php') ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>الكليات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="specializations.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'specializations.php' || basename($_SERVER['PHP_SELF']) == 'specialization-details.php') ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>التخصصات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="search.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'search.php') ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i>
                        <span>البحث</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="contact.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">
                        <i class="fas fa-phone"></i>
                        <span>اتصل بنا</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin/login.php" class="nav-link admin-link <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>" title="لوحة الإدارة">
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
                    <a href="universities.php">الجامعات</a>
                    <i class="fas fa-chevron-left"></i>
                    <span><?php echo htmlspecialchars($university['name']); ?></span>
                </div>

                <div class="university-hero">
                    <div class="university-info">
                        <div class="university-logo">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="university-details">
                            <h1><?php echo htmlspecialchars($university['name']); ?></h1>
                            <div class="university-meta">
                                <span class="university-type <?php echo $university['type'] == 'حكومية' ? 'government' : 'private'; ?>">
                                    <?php echo $university['type']; ?>
                                </span>
                                <span class="university-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($university['location']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="university-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_colleges']; ?></div>
                            <div class="stat-label">كلية</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_specializations']; ?></div>
                            <div class="stat-label">تخصص</div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($valid_images)): ?>
                <section class="gallery-section">
                    <div class="slider" id="gallerySlider" data-index="0" data-count="<?php echo count($valid_images); ?>">
                        <div class="slides">
                            <?php foreach ($valid_images as $index => $img): ?>
                                <div class="slide" data-index="<?php echo $index; ?>">
                                    <img src="<?php echo htmlspecialchars($img['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($img['alt_text'] ?: $university['name']); ?>" 
                                         loading="lazy"
                                         decoding="async" 
                                         onerror="this.onerror=null;this.src='img/imge.jpeg'">
                                    <div class="slide-overlay">
                                        <div class="slide-info">
                                            <span class="slide-number"><?php echo $index + 1; ?> / <?php echo count($valid_images); ?></span>
                                            <span class="slide-title"><?php echo htmlspecialchars($img['alt_text'] ?: $university['name']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="slider-btn prev" aria-label="السابق" title="الصورة السابقة">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="slider-btn next" aria-label="التالي" title="الصورة التالية">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        
                        <!-- نقاط التنقل -->
                        <div class="dots">
                            <?php foreach ($valid_images as $index => $img): ?>
                                <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                <!-- المعلومات الأساسية -->
                <section class="info-section">
                    <div class="section-header">
                        <h2>معلومات الجامعة</h2>
                    </div>

                    <div class="info-card">
                        <div class="info-content">
                            <p class="description">
                                <?php 
                                if (!empty($university['description'])) {
                                    // تنظيف النص من المسافات الزائدة
                                    $cleaned_description = preg_replace('/\s+/', ' ', trim($university['description']));
                                    echo nl2br(htmlspecialchars($cleaned_description));
                                } else {
                                    echo 'لا يوجد وصف متاح للجامعة حالياً.';
                                }
                                ?>
                            </p>

                            <div class="contact-info">
                                <h3>معلومات التواصل</h3>
                                <div class="contact-grid">
                                    <?php if ($university['phone']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <div>
                                            <strong>هاتف الجامعة:</strong>
                                            <span><?php echo htmlspecialchars($university['phone']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($university['email']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <div>
                                            <strong>البريد الإلكتروني:</strong>
                                            <span><?php echo htmlspecialchars($university['email']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="contact-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div>
                                            <strong>الموقع:</strong>
                                            <span><?php echo htmlspecialchars($university['location']); ?></span>
                                        </div>
                                    </div>

                                    <?php if ($university['website']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-globe"></i>
                                        <div>
                                            <strong>الموقع الرسمي:</strong>
                                            <a href="<?php echo htmlspecialchars($university['website']); ?>" target="_blank" rel="noopener">زيارة الموقع</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($university['coordination_link']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-link"></i>
                                        <div>
                                            <strong>رابط التنسيق:</strong>
                                            <a href="<?php echo htmlspecialchars($university['coordination_link']); ?>" target="_blank" rel="noopener">زيارة موقع التنسيق</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

           <!-- الخريطة - استخدام الرابط المباشر فقط -->
<?php if (!empty($university['map_url'])): ?>
<section class="map-section">
    <div class="section-header">
        <h2>الموقع الجغرافي</h2>
        <p>موقع الجامعة على الخريطة</p>
    </div>

    <div class="map-container">
        <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <h3>خريطة الموقع</h3>
            <p><?php echo htmlspecialchars($university['location']); ?></p>
            <div class="map-actions">
                <a href="<?php echo htmlspecialchars($university['map_url']); ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i>
                    <span>فتح في خرائط جوجل</span>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

                <!-- الكليات -->
                <?php
                // جلب كليات الجامعة
                $spec_sql = "SELECT * FROM colleges WHERE university_id = ? ORDER BY name";
                $spec_stmt = $conn->prepare($spec_sql);
                $spec_stmt->bind_param('i', $university_id);
                $spec_stmt->execute();
                $spec_result = $spec_stmt->get_result();
                ?>
                <section class="colleges-section">
                    <div class="section-header">
                        <h2>كليات الجامعة</h2>
                        <p>جميع الكليات المتاحة في هذه الجامعة</p>
                    </div>

                    <?php if ($spec_result->num_rows > 0): ?>
                        <div class="colleges-grid">
                            <?php while($college = $spec_result->fetch_assoc()): ?>
                                <div class="college-card">
                                    <div class="college-header">
                                        <div class="college-icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="college-title-section">
                                            <h3><?php echo htmlspecialchars($college['name']); ?></h3>
                                            <span class="college-status status-<?php echo $college['status'] === 'نشطة' ? 'active' : 'inactive'; ?>">
                                                <?php echo htmlspecialchars($college['status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="college-content">
                                        <?php if (!empty($college['description'])): ?>
                                        <p class="truncate-description">
                                            <?php 
                                            $desc = trim((string)($college['description'] ?? ''));
                                            // تنظيف النص من المسافات الزائدة
                                            $desc = preg_replace('/\s+/', ' ', $desc);
                                            // تقصير الوصف إلى 80 حرف فقط
                                            if (function_exists('mb_substr')) {
                                                $short = mb_substr($desc, 0, 80, 'UTF-8');
                                                if (mb_strlen($desc, 'UTF-8') > 80) { $short .= '...'; }
                                            } else {
                                                $short = substr($desc, 0, 80) . (strlen($desc) > 80 ? '...' : '');
                                            }
                                            echo htmlspecialchars($short);
                                            ?>
                                        </p>
                                        <?php endif; ?>

                                        <?php if ($college['phone']): ?>
                                        <div class="contact-info">
                                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($college['phone']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="college-footer">
                                        <a href="college-details.php?id=<?php echo $college['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-info-circle"></i>
                                            عرض التفاصيل الكاملة
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fas fa-building"></i>
                            <h3>لا توجد كليات متاحة حالياً</h3>
                            <p>سيتم إضافة الكليات قريباً</p>
                        </div>
                    <?php endif; ?>
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
    (function(){
        const slider = document.getElementById('gallerySlider');
        if (!slider) return;
        const slidesContainer = slider.querySelector('.slides');
        const slides = slider.querySelectorAll('.slide');
        const prevBtn = slider.querySelector('.prev');
        const nextBtn = slider.querySelector('.next');
        const dots = slider.querySelectorAll('.dot');
        let index = 0;
        const total = slides.length;

        function layout(){
            slidesContainer.style.width = (total * 100) + '%';
            slides.forEach(slide => { slide.style.width = (100 / total) + '%'; });
        }
        function update(){
            const step = 100 / total;
            slidesContainer.style.transform = 'translateX(' + (-index * step * (document.dir === 'rtl' ? -1 : 1)) + '%)';
            dots.forEach((d,i)=>{ d.classList.toggle('active', i===index); });
        }
        if (slides.length <= 1) {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            const dotsWrap = slider.querySelector('.dots');
            if (dotsWrap) dotsWrap.style.display = 'none';
        } else {
            prevBtn.addEventListener('click', ()=>{ index = (index - 1 + slides.length) % slides.length; update(); });
            nextBtn.addEventListener('click', ()=>{ index = (index + 1) % slides.length; update(); });
            dots.forEach((d,i)=>{
                d.style.cursor = 'pointer';
                d.addEventListener('click', ()=>{ index = i; update(); });
            });
        }
        let startX = 0; let currentX = 0; let isDown = false;
        slidesContainer.addEventListener('touchstart', (e)=>{ isDown=true; startX=e.touches[0].clientX; });
        slidesContainer.addEventListener('touchmove', (e)=>{ if(!isDown) return; currentX=e.touches[0].clientX; });
        slidesContainer.addEventListener('touchend', ()=>{ if(!isDown) return; const dx=currentX-startX; if (dx>50) { index=(index-1+slides.length)%slides.length; } else if (dx<-50) { index=(index+1)%slides.length; } update(); isDown=false; startX=0; currentX=0;});
        window.addEventListener('resize', ()=>{ layout(); update(); });

        const imgs = slider.querySelectorAll('img');
        let loaded = 0;
        if (imgs.length === 0) { layout(); update(); }
        imgs.forEach(img => {
            if (img.complete) {
                if (++loaded === imgs.length) { layout(); update(); }
            } else {
                img.addEventListener('load', ()=>{ if (++loaded === imgs.length) { layout(); update(); } });
                img.addEventListener('error', ()=>{ if (++loaded === imgs.length) { layout(); update(); } });
            }
        });
        setTimeout(()=>{ layout(); update(); }, 0);
    })();
    </script>
</body>
</html>
