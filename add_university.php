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
    $type = trim($_POST['type']); 
    $location = trim($_POST['location']); 
    $website = trim($_POST['website']); 
    $phone = trim($_POST['phone']); 
    $email = trim($_POST['email']); 
    $map_url = trim($_POST['map_url']); 
    $coordination_link = trim($_POST['coordination_link']); 
    $description = trim($_POST['description']); 
    $status = trim($_POST['status']); 
    $is_featured = isset($_POST['is_featured']) ? 1 : 0; 
    
    // حفظ بيانات النموذج مؤقتاً في الجلسة عند وجود خطأ 
    $_SESSION['form_data'] = $_POST; 
    
    // التحقق من الحقول المطلوبة 
    if (empty($name) || empty($type) || empty($location)) { 
        $_SESSION['error_message'] = 'يرجى ملء جميع الحقول المطلوبة'; 
        header('Location: universities.php'); 
        exit(); 
    } 
    
    // إضافة الجامعة
    $stmt = $conn->prepare(" 
        INSERT INTO universities  
        (name, type, location, website, phone, email, map_url, coordination_link, description, status, is_featured)  
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
    "); 
    
    $stmt->bind_param( 
        "ssssssssssi", 
        $name, 
        $type, 
        $location, 
        $website, 
        $phone, 
        $email, 
        $map_url, 
        $coordination_link, 
        $description, 
        $status, 
        $is_featured 
    ); 
    
    if ($stmt->execute()) { 
        $university_id = $stmt->insert_id; 
        
        // رفع الصور إذا تم اختيارها 
        if (!empty($_FILES['college_images']['name'][0])) { 
            $uploadDir = __DIR__ . '/../uploads/universities/' . $university_id; 
            
            if (!is_dir($uploadDir)) { 
                mkdir($uploadDir, 0755, true); 
            } 
            
            foreach ($_FILES['college_images']['name'] as $key => $filename) { 
                $tmpName = $_FILES['college_images']['tmp_name'][$key]; 
                
                // التحقق من وجود خطأ في الرفع
                if ($_FILES['college_images']['error'][$key] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                // التحقق من نوع الملف
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                
                $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // التحقق من نوع الملف
                if (!in_array($mimeType, $allowedMimeTypes) || !in_array($ext, $allowedExtensions)) {
                    continue;
                }
                
                // التحقق من حجم الملف (5MB كحد أقصى)
                if ($_FILES['college_images']['size'][$key] > 5 * 1024 * 1024) {
                    continue;
                }
                
                // إنشاء اسم فريد للملف
                $newName = uniqid('img_', true) . '.' . $ext; 
                $destination = $uploadDir . '/' . $newName; 
                
                if (move_uploaded_file($tmpName, $destination)) { 
                    // حفظ المسار في حقل image_path في جدول university_images
                    $image_path = 'uploads/universities/' . $university_id . '/' . $newName; 
                    
                    // إضافة الصورة إلى جدول university_images
                    $img_stmt = $conn->prepare("INSERT INTO university_images (university_id, image_path) VALUES (?, ?)");
                    $img_stmt->bind_param("is", $university_id, $image_path);
                    $img_stmt->execute();
                    $img_stmt->close();
                } 
            } 
        } 
        
        $_SESSION['success_message'] = 'تمت إضافة الجامعة بنجاح'; 
        log_admin("Added university id=$university_id: $name"); 
        unset($_SESSION['form_data']); 
    } else { 
        $_SESSION['error_message'] = 'حدث خطأ أثناء إضافة الجامعة'; 
        log_admin("Failed adding university: " . $stmt->error); 
    } 
    
    $stmt->close(); 
    header('Location: universities.php'); 
    exit();
} else { 
    // إذا تم الوصول مباشرة بدون POST 
    header('Location: universities.php'); 
    exit();
}
