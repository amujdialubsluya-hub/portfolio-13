-- ========================================
-- قاعدة بيانات مشروع الجامعات اليمنية - محدثة
-- Database: universities_db
-- ========================================

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS universities_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE universities_db;

-- جدول المستخدمين (للوحة الإدارة)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') DEFAULT 'editor',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول رسائل الاتصال
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    admin_reply TEXT,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الجامعات (محدث بإضافة حقل coordination_link)
CREATE TABLE universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('حكومية', 'أهلية') NOT NULL,
    location VARCHAR(255) NOT NULL,
    website VARCHAR(500),
    phone VARCHAR(50),
    email VARCHAR(255),
    map_url TEXT,
    coordination_link VARCHAR(500) NULL, 
    description TEXT,
    status ENUM('نشطة', 'غير نشطة') DEFAULT 'نشطة',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- جدول صور الجامعات (لدعم صور متعددة لكل جامعة)
CREATE TABLE university_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- جدول الكليات
CREATE TABLE colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('حكومية', 'أهلية') NULL,
    description TEXT,
    coordination_link VARCHAR(500) NULL,
    website VARCHAR(500) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    location VARCHAR(255) NULL,
    map_url TEXT NULL,
    status ENUM('نشطة', 'غير نشطة') DEFAULT 'نشطة',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL
);

-- جدول صور الكليات (لدعم صور متعددة لكل كلية)
CREATE TABLE college_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- جدول التخصصات - محدث مع حقل شروط القبول
CREATE TABLE specializations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL DEFAULT 4,
    degree_type ENUM('بكالوريوس', 'ماجستير', 'دكتوراه') DEFAULT 'بكالوريوس',
    admission_requirement TEXT DEFAULT NULL,
    admission_prerequisites TEXT DEFAULT NULL,
    status ENUM('متاح', 'غير متاح') DEFAULT 'متاح',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- ========================================
-- إدخال البيانات
-- ========================================

-- إدخال بيانات المستخدم الافتراضي (admin/admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@university.com', '$2y$10$h5aCWFjKpNqEFtZ4O8MQJOn0o7iG7LwAndxsGASwI9oZTo8iBW6qu', 'مدير النظام', 'admin');

-- إدخال بيانات الجامعات
INSERT INTO universities (name, type, location, website, phone, email, map_url, description) VALUES
('جامعة صنعاء', 'حكومية', 'صنعاء', 'https://su.edu.ye', '+967-1-123456', 'info@su.edu.ye', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3908.123456789!2d44.123456!3d15.123456!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTXCsDA3JzI0LjQiTiA0NMKwMDcnMjQuNCJF!5e0!3m2!1sen!2sye!4v1234567890123!5m2!1sen!2sye', 'أكبر وأقدم جامعة في اليمن تأسست عام 1970 وتضم العديد من الكليات والتخصصات.'),
('جامعة الاندلس', 'أهلية', 'صنعاء', 'https://alandalus.edu.ye', '+967-1-654321', 'info@alandalus.edu.ye', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3908.987654321!2d44.987654!3d15.987654!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTXCsDU5JzE1LjYiTiA0NMKwNTknMTUuNiJF!5e0!3m2!1sen!2sye!4v1234567890123!5m2!1sen!2sye', 'جامعة أهلية رائدة في التعليم العالي تقدم برامج متنوعة تلبي احتياجات سوق العمل.');

-- إدخال بيانات الكليات
INSERT INTO colleges (university_id, name, type, description, coordination_link, website, phone, email, location, map_url, status) VALUES
(1, 'كلية الطب والعلوم الصحية', NULL, 'كلية الطب البشري وعلوم الصحة تقدم برامج دراسية متميزة في مجال الطب والتمريض والصيدلة.', 'https://medicine.su.edu.ye/coordination', 'https://medicine.su.edu.ye', '+967-1-111111', 'medicine@su.edu.ye', NULL, 'https://maps.google.com/?q=15.369445,44.191007', 'نشطة'),
(1, 'كلية الهندسة', NULL, 'كلية الهندسة تقدم برامج في الهندسة المدنية والمعمارية والميكانيكية والكهربائية.', 'https://engineering.su.edu.ye/coordination', 'https://engineering.su.edu.ye', '+967-1-222222', 'engineering@su.edu.ye', NULL, 'https://maps.google.com/?q=15.369445,44.191007', 'نشطة'),
(2, 'كلية الحاسوب وتكنولوجيا المعلومات', NULL, 'كلية متخصصة في علوم الحاسوب وتكنولوجيا المعلومات والبرمجة والشبكات.', 'https://cs.alandalus.edu.ye/coordination', 'https://cs.alandalus.edu.ye', '+967-1-333333', 'cs@alandalus.edu.ye', NULL, 'https://maps.google.com/?q=15.369445,44.191007', 'نشطة'),
(NULL, 'الكلية اليمنية للعلوم الطبية', 'أهلية', 'كلية مستقلة متخصصة في العلوم الطبية والمخبرية والتقنيات الصحية.', 'https://ymc.edu.ye/coordination', 'https://ymc.edu.ye', '+967-1-444444', 'info@ymc.edu.ye', 'صنعاء - شارع الزبيري', 'https://maps.google.com/?q=15.369445,44.191007', 'نشطة'),
(NULL, 'كلية الإدارة والعلوم المالية', 'حكومية', 'كلية مستقلة تقدم برامج في الإدارة والمحاسبة والتمويل والتسويق.', 'https://business.edu.ye/coordination', 'https://business.edu.ye', '+967-1-555555', 'admin@business.edu.ye', 'صنعاء - حدة', 'https://maps.google.com/?q=15.369445,44.191007', 'نشطة');

-- إدخال بيانات التخصصات مع شروط القبول المحدثة
INSERT INTO specializations (college_id, name, description, duration, degree_type, admission_requirement, status) VALUES
(1, 'طب وجراحة', 'برنامج متكامل في الطب البشري يشمل العلوم الأساسية والسريرية', 6, 'بكالوريوس', '85% في الثانوية العامة - قسم العلمي', 'متاح'),
(1, 'تمريض', 'برنامج تأهيل الممرضين والممرضات لتقديم الرعاية الصحية', 4, 'بكالوريوس', '75% في الثانوية العامة - قسم العلمي', 'متاح'),
(2, 'هندسة مدنية', 'تخصص في تصميم وتنفيذ المشاريع الإنشائية والبنية التحتية', 5, 'بكالوريوس', '80% في الثانوية العامة - قسم العلمي', 'متاح'),
(2, 'هندسة حاسوب', 'تخصص في تصميم الأنظمة المدمجة والشبكات والبرمجيات', 5, 'بكالوريوس', '80% في الثانوية العامة - قسم العلمي', 'متاح'),
(3, 'علوم حاسوب', 'برنامج شامل في البرمجة وقواعد البيانات والذكاء الاصطناعي', 4, 'بكالوريوس', '75% في الثانوية العامة', 'متاح'),
(3, 'أمن سيبراني', 'تخصص في حماية الأنظمة والشبكات من الهجمات الإلكترونية', 4, 'بكالوريوس', '78% في الثانوية العامة', 'متاح'),
(4, 'تحاليل طبية', 'برنامج في العلوم المخبرية والتشخيص المرضي', 4, 'بكالوريوس', '80% في الثانوية العامة - قسم العلمي', 'متاح'),
(5, 'إدارة أعمال', 'برنامج شامل في الإدارة والتسويق والموارد البشرية', 4, 'بكالوريوس', '70% في الثانوية العامة', 'متاح');

-- إدخال صور للجامعات (نموذجية)
INSERT INTO university_images (university_id, image_path, alt_text, sort_order) VALUES
(1, 'uploads/universities/1/main.jpg', 'المبنى الرئيسي لجامعة صنعاء', 1),
(1, 'uploads/universities/1/campus.jpg', 'الحرم الجامعي لجامعة صنعاء', 2),
(2, 'uploads/universities/2/main.jpg', 'المبنى الرئيسي لجامعة الاندلس', 1);

-- إدخال صور للكليات (نموذجية)
INSERT INTO college_images (college_id, image_path, alt_text, sort_order) VALUES
(1, 'uploads/colleges/1/building.jpg', 'مبنى كلية الطب', 1),
(2, 'uploads/colleges/2/building.jpg', 'مبنى كلية الهندسة', 1),
(3, 'uploads/colleges/3/building.jpg', 'مبنى كلية الحاسوب', 1);