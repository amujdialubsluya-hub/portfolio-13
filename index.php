<?php
// تضمين ملف الإعدادات
require_once 'config.php';
// حساب الإحصائيات الحقيقية من قاعدة البيانات
$stats_sql = "SELECT 
(SELECT COUNT(*) FROM universities WHERE status = 'نشطة') as total_universities, 
(SELECT COUNT(*) FROM colleges WHERE status = 'نشطة') as total_colleges, 
(SELECT COUNT(*) FROM specializations WHERE status = 'متاح') as total_specializations, 
(SELECT COUNT(*) FROM universities WHERE type = 'حكومية' AND status = 'نشطة') as government_universities, 
(SELECT COUNT(*) FROM universities WHERE type = 'أهلية' AND status = 'نشطة') as private_universities";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
// جلب أفضل 3 جامعات للعرض في القسم المميز
$featured_universities_sql = "SELECT * FROM universities WHERE status = 'نشطة' AND is_featured = 1 ORDER BY id DESC LIMIT 100";
$featured_result = $conn->query($featured_universities_sql);
// جلب الكليات المميزة المستقلة فقط
$featured_colleges_sql = "SELECT * FROM colleges WHERE is_featured = 1 AND university_id IS NULL AND status = 'نشطة' ORDER BY id DESC LIMIT 100";
$featured_colleges_result = $conn->query($featured_colleges_sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head> 
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>دليل الجامعات اليمنية الالكترونية الشامل- الصفحة الرئيسية</title> 
<!-- ملفات CSS --> 
<link rel="stylesheet" href="assets/css/main.css"> 
<link rel="stylesheet" href="assets/css/landing.css"> 
<!-- خطوط عربية --> 
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet"> 
<!-- أيقونات Font Awesome --> 
<link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body> 
<!-- شريط التنقل الجديد --> 
<nav class="navbar"> 
<div class="nav-container"> 
<div class="nav-logo"> 
<img src="Image/1.jpg" style="width: 90px; height: 81px;" alt="شعار الجامعات اليمنية"> 
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
<a href="index.php" class="nav-link active"> 
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
<!-- القسم الرئيسي الجديد --> 
<main class="hero-section"> 
<div class="hero-background"> 
<div class="hero-pattern"></div> 
</div> 
<div class="hero-content"> 
<div class="hero-text"> 
<div class="hero-badge"> 
<i class="fas fa-star"></i> 
<span>البوابة الرسمية</span> 
</div> 
<h1 class="hero-title"> 
<span class="title-line">اكتشف جامعتك</span> 
<span class="title-line highlight">المثالية في اليمن</span> 
</h1> 
<p class="hero-description"> 
بوابة إلكترونية موحدة تحتوي على قاعدة بيانات شاملة لجميع الجامعات اليمنية 
الحكومية والأهلية. نساعدك في العثور على التخصص المناسب والتسجيل بسهولة. 
</p> 
<div class="hero-stats"> 
<div class="stat-item"> 
<div class="stat-number"><?php echo $stats['total_universities']; ?>+</div> 
<div class="stat-label">جامعة</div> 
</div> 
<div class="stat-item"> 
<div class="stat-number"><?php echo $stats['total_colleges']; ?>+</div> 
<div class="stat-label">كلية</div> 
</div> 
<div class="stat-item"> 
<div class="stat-number"><?php echo $stats['total_specializations']; ?>+</div> 
<div class="stat-label">تخصص</div> 
</div> 
</div> 
<div class="hero-actions"> 
<a href="universities.php" class="btn btn-primary"> 
<i class="fas fa-rocket"></i> 
<span>ابدأ رحلتك</span> 
</a> 
<a href="colleges.php" class="btn btn-outline"> 
<i class="fas fa-building"></i> 
<span>استكشف الكليات</span> 
</a> 
</div> 
</div> 
<div class="hero-visual"> 
<div class="visual-container"> 
<div class="floating-card card-1"> 
<i class="fas fa-university"></i> 
<span>جامعات حكومية</span> 
</div> 
<div class="floating-card card-2"> 
<i class="fas fa-building"></i> 
<span>كليات متنوعة</span> 
</div> 
<div class="floating-card card-3"> 
<i class="fas fa-users"></i> 
<span>آلاف الطلاب</span> 
</div> 
<div class="main-illustration"> 
<div class="illustration-circle"> 
<i class="fas fa-map-marker-alt"></i> 
</div> 
</div> 
</div> 
</div> 
</div> 
</main> 
<!-- قسم المميزات الجديد --> 
<section class="features-section"> 
<div class="container"> 
<div class="section-header"> 
<h2 class="section-title">لماذا تختارنا؟</h2> 
<p class="section-subtitle">نقدم لك أفضل الخدمات لتسهيل رحلتك التعليمية</p> 
</div> 
<div class="features-grid"> 
<div class="feature-item"> 
<div class="feature-icon"> 
<i class="fas fa-database"></i> 
</div> 
<div class="feature-content"> 
<h3>قاعدة بيانات شاملة</h3> 
<p>جميع الجامعات اليمنية الحكومية والأهلية والكليات في مكان واحد</p> 
</div> 
</div> 
<div class="feature-item"> 
<div class="feature-icon"> 
<i class="fas fa-clock"></i> 
</div> 
<div class="feature-content"> 
<h3>معلومات محدثة</h3> 
<p>بيانات دقيقة ومحدثة باستمرار عن الجامعات والكليات والتخصصات</p> 
</div> 
</div> 
<div class="feature-item"> 
<div class="feature-icon"> 
<i class="fas fa-mobile-alt"></i> 
</div> 
<div class="feature-content"> 
<h3>تصميم متجاوب</h3> 
<p>يمكنك الوصول للمعلومات من أي جهاز وفي أي وقت</p> 
</div> 
</div> 
<div class="feature-item"> 
<div class="feature-icon"> 
<i class="fas fa-headset"></i> 
</div> 
<div class="feature-content"> 
<h3>دعم متواصل</h3> 
<p>فريق دعم متخصص لمساعدتك في أي استفسار</p> 
</div> 
</div> 
</div> 
</div> 
</section> 
<!-- قسم الجامعات المميزة --> 
<section class="universities-section"> 
<div class="container"> 
<div class="section-header"> 
<h2 class="section-title">الجامعات والكليات المميزة</h2> 
<p class="section-subtitle">تعرف على أفضل الجامعات اليمنية والكليات المستقلة المميزة</p> 
</div> 
<div class="universities-grid"> 
<?php if ($featured_result->num_rows > 0): ?> 
<?php while($university = $featured_result->fetch_assoc()): ?> 
<div class="university-card"> 
<div class="university-header"> 
<div class="university-logo"> 
<i class="fas fa-university"></i> 
</div> 
<div class="university-type <?php echo $university['type'] == 'حكومية' ? 'government' : 'private'; ?>"><?php echo $university['type']; ?></div> 
</div> 
<div class="university-content"> 
<h3><?php echo $university['name']; ?></h3> 
<p> 
<?php  
$text = $university['description']; 
echo mb_strimwidth($text, 0, 60, "..."); // يطبع أول 60 حرف فقط 
?> 
</p> 
<div class="university-stats"> 
<span><i class="fas fa-building"></i> <?php echo $university['location']; ?></span> 
<span><i class="fas fa-users"></i> جامعة نشطة</span> 
</div> 
</div> 
<div class="university-footer"> 
<a href="university-details.php?id=<?php echo $university['id']; ?>" class="btn btn-sm">عرض التفاصيل</a> 
</div> 
</div> 
<?php endwhile; ?> 
<?php endif; ?> 
<!-- عرض الكليات المميزة المستقلة --> 
<?php if (isset($featured_colleges_result) && $featured_colleges_result && $featured_colleges_result->num_rows > 0): ?> 
<!-- هنا تضع الكود الخاص بعرض الكليات المميزة -->


<?php while($college = $featured_colleges_result->fetch_assoc()): ?> 
<div class="university-card"> 
<div class="university-header"> 
<div class="university-logo"> 
<i class="fas fa-building"></i> 
</div> 
<div class="university-type independent">مستقلة</div> 
</div> 
<div class="university-content"> 
<h3><?php echo $college['name']; ?></h3> 
<p> 
<?php  
$text = $college['description']; 
echo mb_strimwidth($text, 0, 60, "..."); // يطبع أول 60 حرف فقط 
?> 
</p> 
<div class="university-stats"> 
<span><i class="fas fa-map-marker-alt"></i> <?php echo $college['location']; ?></span> 
<span><i class="fas fa-users"></i> كلية مستقلة</span> 
</div> 
</div> 
<div class="university-footer"> 
<a href="college-details.php?id=<?php echo $college['id']; ?>" class="btn btn-sm">عرض التفاصيل</a> 
</div> 
</div> 
<?php endwhile; ?> 
<?php endif; ?> 
<?php if ($featured_result->num_rows == 0 && $featured_colleges_result->num_rows == 0): ?> 
<div class="no-results"> 
<i class="fas fa-university"></i> 
<h3>لا توجد جامعات أو كليات متاحة حالياً</h3> 
<p>يرجى المحاولة مرة أخرى لاحقاً</p> 
</div> 
<?php endif; ?> 
</div> 
</div> 
</section> 
<!-- قسم الإحصائيات الجديد --> 
<section class="stats-section"> 
<div class="container"> 
<div class="stats-content"> 
<div class="stats-text"> 
<h2>أرقام تتحدث عن نفسها</h2> 
<p>إحصائيات حقيقية تعكس حجم الخدمة التي نقدمها</p> 
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
<div class="stat-number"><?php echo $stats['total_colleges']; ?>+</div> 
<div class="stat-label">كلية</div> 
</div> 
</div> 
<div class="stat-card"> 
<div class="stat-icon"> 
<i class="fas fa-graduation-cap"></i> 
</div> 
<div class="stat-info"> 
<div class="stat-number"><?php echo $stats['total_specializations']; ?>+</div> 
<div class="stat-label">تخصص</div> 
</div> 
</div> 
</div> 
</div> 
</div> 
</section> 
<!-- التذييل الجديد --> 
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
<!-- ملف JavaScript المبسط --> 
<script src="assets/js/main.js"></script>
</body>
</html>
