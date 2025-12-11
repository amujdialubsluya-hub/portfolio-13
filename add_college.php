<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/includes/logger.php';
// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { 
header('Location: login.php'); 
exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
// استقبال البيانات 
$name = trim($_POST['name']); 
$university_id = !empty($_POST['university_id']) ? (int)$_POST['university_id'] : null; 
$college_type = trim($_POST['college_type']); 
$description = trim($_POST['description']); 
$location = trim($_POST['location']); 
$coordination_link = trim($_POST['coordination_link']); 
$website = trim($_POST['website']); 
$phone = trim($_POST['phone']); 
$email = trim($_POST['email']); 
$map_url = trim($_POST['map_url']); 
$status = trim($_POST['status']); 
// ⭐ إضافة قراءة خيار الكلية المميزة 
$is_featured = isset($_POST['is_featured']) ? 1 : 0; 
// حفظ بيانات النموذج مؤقتاً في الجلسة عند وجود خطأ 
$_SESSION['form_data'] = $_POST; 
// التحقق من الحقول المطلوبة 
if (empty($name) || ($university_id === null && (empty($college_type) || empty($location)))) { 
$_SESSION['error_message'] = 'يرجى ملء جميع الحقول المطلوبة'; 
header('Location: colleges.php'); 
exit(); 
} 
// ⭐ إضافة is_featured داخل الاستعلام 
$stmt = $conn->prepare(" 
INSERT INTO colleges  
(name, university_id, type, description, location, coordination_link, website, phone, email, map_url, status, is_featured)  
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
"); 
$stmt->bind_param( 
"sisssssssssi", 
$name, 
$university_id, 
$college_type, 
$description, 
$location, 
$coordination_link, 
$website, 
$phone, 
$email, 
$map_url, 
$status, 
$is_featured 
); 
if ($stmt->execute()) { 
$college_id = $stmt->insert_id; 
// رفع الصور إذا تم اختيارها 
if (!empty($_FILES['college_images']['name'][0])) { 
$uploadDir = __DIR__ . '/../uploads/colleges/' . $college_id; 
if (!is_dir($uploadDir)) { 
mkdir($uploadDir, 0755, true); 
} 
foreach ($_FILES['college_images']['name'] as $key => $filename) { 
$tmpName = $_FILES['college_images']['tmp_name'][$key]; 
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION)); 
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif']; 
if (in_array($ext, $allowed)) { 
$newName = uniqid() . '.' . $ext; 
$destination = $uploadDir . '/' . $newName; 
if (move_uploaded_file($tmpName, $destination)) { 
$relPath = 'uploads/colleges/' . $college_id . '/' . $newName; 
$conn->query("INSERT INTO college_images (college_id, image_path) VALUES ($college_id, '$relPath')"); 
} 
} 
} 
} 
$_SESSION['success_message'] = 'تمت إضافة الكلية بنجاح'; 
log_admin("Added college id=$college_id: $name"); 
unset($_SESSION['form_data']); 
} else { 
$_SESSION['error_message'] = 'حدث خطأ أثناء إضافة الكلية'; 
log_admin("Failed adding college: " . $stmt->error); 
} 
$stmt->close(); 
header('Location: colleges.php'); 
exit();
} else { 
// إذا تم الوصول مباشرة بدون POST 
header('Location: colleges.php'); 
exit();}
?>