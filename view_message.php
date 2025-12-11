<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// التحقق من وجود معرف الرسالة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: messages.php');
    exit();
}

$message_id = (int)$_GET['id'];

// جلب الرسالة
$message_sql = "SELECT * FROM contact_messages WHERE id = ?";
$message_stmt = $conn->prepare($message_sql);
$message_stmt->bind_param("i", $message_id);
$message_stmt->execute();
$message_result = $message_stmt->get_result();

if ($message_result->num_rows === 0) {
    header('Location: messages.php');
    exit();
}

$message = $message_result->fetch_assoc();

// تحديث حالة الرسالة إلى "مقروءة" إذا كانت جديدة
if ($message['status'] === 'new') {
    $update_sql = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $message_id);
    $update_stmt->execute();
    $message['status'] = 'read';
}

// معالجة إرسال الرد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $admin_reply = trim($_POST['admin_reply']);

    if (!empty($admin_reply)) {
        $reply_sql = "UPDATE contact_messages SET admin_reply = ?, status = 'replied', replied_at = NOW() WHERE id = ?";
        $reply_stmt = $conn->prepare($reply_sql);
        $reply_stmt->bind_param("si", $admin_reply, $message_id);

        if ($reply_stmt->execute()) {
            $success_message = "تم إرسال الرد بنجاح";
            $message['admin_reply'] = $admin_reply;
            $message['status'] = 'replied';
            $message['replied_at'] = date('Y-m-d H:i:s');
        } else {
            $error_message = "حدث خطأ أثناء إرسال الرد";
        }
    } else {
        $error_message = "يرجى كتابة الرد";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الرسالة - لوحة الإدارة</title>

    <!-- ملفات CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">

    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- أيقونات Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <!-- الشريط الجانبي -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h2>لوحة الإدارة</h2>
                </div>
                 <p>دليل الشامل للجامعات اليمنية</p>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">القائمة الرئيسية</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>لوحة التحكم</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">إدارة المحتوى</div>
                    <ul class="nav-menu">
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
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">التواصل والدعم</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="messages.php" class="nav-link active">
                                <i class="fas fa-envelope"></i>
                                <span>رسائل الاتصال</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">إدارة النظام</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="users.php" class="nav-link">
                                <i class="fas fa-users"></i>
                                <span>المستخدمين</span>
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="main-content">
            <!-- شريط العنوان -->
            <header class="page-header">
                <div class="page-title-section">
                    <h1 class="page-title">عرض الرسالة</h1>
                    <div class="breadcrumb">
                        <a href="messages.php">رسائل الاتصال</a>
                        <i class="fas fa-chevron-left"></i>
                        <span>عرض الرسالة</span>
                    </div>
                </div>

                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo $_SESSION['admin_full_name']; ?></div>
                        <div class="user-role"><?php echo $_SESSION['admin_role'] === 'admin' ? 'مدير النظام' : 'محرر'; ?></div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>تسجيل الخروج</span>
                    </a>
                </div>
            </header>

            <!-- رسائل النجاح/الخطأ -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- تفاصيل الرسالة -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">تفاصيل الرسالة</h2>
                    <div class="card-actions">
                        <span class="status-badge status-<?php echo $message['status']; ?>">
                            <?php
                            switch($message['status']) {
                                case 'new': echo 'جديد'; break;
                                case 'read': echo 'مقروء'; break;
                                case 'replied': echo 'تم الرد'; break;
                                case 'archived': echo 'مؤرشف'; break;
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <div class="message-details">
                    <div class="message-info">
                        <div class="info-row">
                            <div class="info-label">المرسل:</div>
                            <div class="info-value">
                                <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                <?php if ($message['phone']): ?>
                                    <br><small>الهاتف: <?php echo htmlspecialchars($message['phone']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">البريد الإلكتروني:</div>
                            <div class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="link">
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($message['email']); ?>
                                </a>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">الموضوع:</div>
                            <div class="info-value">
                                <strong><?php echo htmlspecialchars($message['subject']); ?></strong>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">التاريخ:</div>
                            <div class="info-value">
                                <?php echo date('Y/m/d H:i', strtotime($message['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="message-content">
                        <h3>محتوى الرسالة:</h3>
                        <div class="message-text">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                    </div>

                    <?php if ($message['admin_reply']): ?>
                        <div class="admin-reply">
                            <h3>الرد:</h3>
                            <div class="reply-text">
                                <?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?>
                            </div>
                            <div class="reply-info">
                                <small>تم الرد في: <?php echo date('Y/m/d H:i', strtotime($message['replied_at'])); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- نموذج الرد -->
            <?php if ($message['status'] !== 'replied'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">الرد على الرسالة</h2>
                    </div>

                    <form method="POST" class="form-container">
                        <div class="form-group">
                            <label for="admin_reply">الرد:</label>
                            <textarea id="admin_reply" name="admin_reply" rows="6" required
                                      placeholder="اكتب ردك هنا..."><?php echo htmlspecialchars($_POST['admin_reply'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="reply" class="btn btn-primary">
                                <i class="fas fa-reply"></i>
                                <span>إرسال الرد</span>
                            </button>
                            <a href="messages.php" class="btn btn-outline">
                                <i class="fas fa-arrow-right"></i>
                                <span>العودة للرسائل</span>
                            </a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">الإجراءات</h2>
                    </div>

                    <div class="form-actions">
                        <a href="messages.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i>
                            <span>العودة للرسائل</span>
                        </a>
                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-outline">
                            <i class="fas fa-envelope"></i>
                            <span>إرسال بريد إلكتروني</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- ملف JavaScript -->
    <script src="assets/js/admin.js"></script>
</body>
</html>
