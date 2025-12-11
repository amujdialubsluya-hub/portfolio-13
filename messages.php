<?php
session_start();
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// معالجة تغيير حالة الرسالة
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];

    if (in_array($status, ['new', 'read', 'replied', 'archived'])) {
        $update_sql = "UPDATE contact_messages SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $status, $id);

        if ($update_stmt->execute()) {
            $success_message = "تم تحديث حالة الرسالة بنجاح";
        } else {
            $error_message = "حدث خطأ أثناء تحديث حالة الرسالة";
        }
    }
}

// معالجة حذف رسالة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $delete_sql = "DELETE FROM contact_messages WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);

    if ($delete_stmt->execute()) {
        $success_message = "تم حذف الرسالة بنجاح";
    } else {
        $error_message = "حدث خطأ أثناء حذف الرسالة";
    }
}

// جلب الرسائل
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ssss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$messages_sql = "SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC";
$messages_stmt = $conn->prepare($messages_sql);

if (!empty($params)) {
    $messages_stmt->bind_param($param_types, ...$params);
}

$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();

// إحصائيات الرسائل
$stats_sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
FROM contact_messages";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسائل الاتصال - لوحة الإدارة</title>

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
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">التواصل والدعم</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="messages.php" class="nav-link active">
                                <i class="fas fa-envelope"></i>
                                <span>رسائل الاتصال</span>
                                <?php if ($stats['new_count'] > 0): ?>
                                    <span class="badge"><?php echo $stats['new_count']; ?></span>
                                <?php endif; ?>
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
                <h1 class="page-title">رسائل الاتصال</h1>

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

            <!-- إحصائيات الرسائل -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">إجمالي الرسائل</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['new_count']; ?></div>
                    <div class="stat-label">رسائل جديدة</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['read_count']; ?></div>
                    <div class="stat-label">رسائل مقروءة</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['replied_count']; ?></div>
                    <div class="stat-label">رسائل تم الرد عليها</div>
                </div>
            </div>

            <!-- فلاتر البحث -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">البحث والفلترة</h2>
                </div>

                <form method="GET" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="search">البحث</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="البحث في الاسم، البريد الإلكتروني، الموضوع...">
                        </div>

                        <div class="form-group">
                            <label for="status">حالة الرسالة</label>
                            <select id="status" name="status">
                                <option value="">جميع الرسائل</option>
                                <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>جديدة</option>
                                <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>مقروءة</option>
                                <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>تم الرد عليها</option>
                                <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>مؤرشفة</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            <span>بحث</span>
                        </button>
                        <a href="messages.php" class="btn btn-outline">
                            <i class="fas fa-times"></i>
                            <span>إلغاء</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- قائمة الرسائل -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">قائمة الرسائل</h2>
                    <div class="card-actions">
                        <span class="total-count">إجمالي الرسائل: <?php echo $messages_result->num_rows; ?></span>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المرسل</th>
                                <th>البريد الإلكتروني</th>
                                <th>الموضوع</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($messages_result->num_rows > 0): ?>
                                <?php while($message = $messages_result->fetch_assoc()): ?>
                                    <tr class="<?php echo $message['status'] === 'new' ? 'new-message' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                            <?php if ($message['phone']): ?>
                                                <br><small><?php echo htmlspecialchars($message['phone']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="link">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($message['email']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($message['subject']); ?></strong>
                                            <br><small><?php echo substr(htmlspecialchars($message['message']), 0, 100) . '...'; ?></small>
                                        </td>
                                        <td>
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
                                        </td>
                                        <td><?php echo date('Y/m/d H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-view btn-sm" title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <?php if ($message['status'] === 'new'): ?>
                                                    <a href="?status=read&id=<?php echo $message['id']; ?>" class="btn btn-edit btn-sm" title="تحديد كمقروء">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($message['status'] !== 'archived'): ?>
                                                    <a href="?status=archived&id=<?php echo $message['id']; ?>" class="btn btn-edit btn-sm" title="أرشفة">
                                                        <i class="fas fa-archive"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <a href="?delete=<?php echo $message['id']; ?>" class="btn btn-delete btn-sm" title="حذف"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">
                                        <i class="fas fa-envelope" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                        <p>لا توجد رسائل</p>
                                        <?php if (!empty($search) || !empty($status_filter)): ?>
                                            <p>جرب تغيير معايير البحث</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ملف JavaScript -->
    <script src="assets/js/admin.js"></script>
</body>
</html>
