<?php
/**
 * Manage Lessons - Admin Panel (Enhanced with File Upload)
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Create upload directories if not exists
$uploadDir = '../uploads/';
$videosDir = $uploadDir . 'videos/';
$mindmapsDir = $uploadDir . 'mindmaps/';

if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists($videosDir)) mkdir($videosDir, 0777, true);
if (!file_exists($mindmapsDir)) mkdir($mindmapsDir, 0777, true);

// Handle delete action
if (isset($_GET['delete'])) {
    $lesson_id = intval($_GET['delete']);
    
    // Get lesson info to delete files
    $getLesson = $db->prepare("SELECT video_url, mindmap_url FROM lessons WHERE lesson_id = ?");
    $getLesson->execute([$lesson_id]);
    $lesson = $getLesson->fetch();
    
    // Delete associated files
    if ($lesson) {
        if ($lesson['video_url'] && file_exists('../' . $lesson['video_url'])) {
            unlink('../' . $lesson['video_url']);
        }
        if ($lesson['mindmap_url'] && file_exists('../' . $lesson['mindmap_url'])) {
            unlink('../' . $lesson['mindmap_url']);
        }
    }
    
    $deleteQuery = $db->prepare("DELETE FROM lessons WHERE lesson_id = ?");
    if ($deleteQuery->execute([$lesson_id])) {
        $_SESSION['success'] = "تم حذف الدرس بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء الحذف";
    }
    header('Location: manage_lessons.php');
    exit();
}

// Handle file upload function
function uploadLessonFile($file, $type = 'video') {
    $targetDir = ($type === 'video') ? '../uploads/videos/' : '../uploads/mindmaps/';
    $allowedTypes = ($type === 'video') ? ['video/mp4', 'video/mpeg', 'video/webm'] : ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    $maxSize = ($type === 'video') ? 100 * 1024 * 1024 : 10 * 1024 * 1024; // 100MB for videos, 10MB for mindmaps
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح به'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = ($type === 'video') ? 'uploads/videos/' . $filename : 'uploads/mindmaps/' . $filename;
        return ['success' => true, 'path' => $relativePath];
    }
    
    return ['success' => false, 'message' => 'فشل في حفظ الملف'];
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
    $level = $_POST['level'];
    $subject = $_POST['subject'];
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $sources = trim($_POST['sources']);
    
    // Handle video upload
    $video_path = $_POST['existing_video'] ?? '';
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadLessonFile($_FILES['video_file'], 'video');
        if ($uploadResult['success']) {
            if ($video_path && file_exists('../' . $video_path)) {
                unlink('../' . $video_path);
            }
            $video_path = $uploadResult['path'];
        } else {
            $_SESSION['error'] = $uploadResult['message'];
            header('Location: manage_lessons.php');
            exit();
        }
    }
    
    // Handle mindmap upload
    $mindmap_path = $_POST['existing_mindmap'] ?? '';
    if (isset($_FILES['mindmap_file']) && $_FILES['mindmap_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadLessonFile($_FILES['mindmap_file'], 'mindmap');
        if ($uploadResult['success']) {
            if ($mindmap_path && file_exists('../' . $mindmap_path)) {
                unlink('../' . $mindmap_path);
            }
            $mindmap_path = $uploadResult['path'];
        } else {
            $_SESSION['error'] = $uploadResult['message'];
            header('Location: manage_lessons.php');
            exit();
        }
    }
    
    if ($lesson_id > 0) {
        // Update existing lesson
        $updateQuery = $db->prepare("UPDATE lessons SET level = ?, subject = ?, title = ?, summary = ?, video_url = ?, mindmap_url = ?, sources = ? WHERE lesson_id = ?");
        if ($updateQuery->execute([$level, $subject, $title, $summary, $video_path, $mindmap_path, $sources, $lesson_id])) {
            $_SESSION['success'] = "تم تحديث الدرس بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // Insert new lesson
        $insertQuery = $db->prepare("INSERT INTO lessons (level, subject, title, summary, video_url, mindmap_url, sources) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($insertQuery->execute([$level, $subject, $title, $summary, $video_path, $mindmap_path, $sources])) {
            $_SESSION['success'] = "تم إضافة الدرس بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة";
        }
    }
    header('Location: manage_lessons.php');
    exit();
}

// Get lesson for editing
$editLesson = null;
if (isset($_GET['edit'])) {
    $lesson_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
    $editQuery->execute([$lesson_id]);
    $editLesson = $editQuery->fetch();
}

// Get all lessons
$lessons = $db->query("SELECT * FROM lessons ORDER BY lesson_id DESC")->fetchAll();

$levels = [
    'primary' => 'الابتدائي',
    'middle' => 'المتوسط',
    'secondary' => 'الثانوي'
];

$subjects = [
    'history' => 'التاريخ',
    'geography' => 'الجغرافيا',
    'civic_education' => 'التربية المدنية'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الدروس التعليمية - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f4f6f9; }
        .sidebar { background: #2c3e50; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #e67e22; }
        .sidebar .nav-link i { margin-left: 10px; }
        .main-content { padding: 20px; }
        .content-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .badge-level { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
        .badge-primary { background: #3498db; color: white; }
        .badge-middle { background: #2ecc71; color: white; }
        .badge-secondary { background: #e67e22; color: white; }
        .btn-file { position: relative; overflow: hidden; }
        .btn-file input[type=file] { position: absolute; top: 0; right: 0; min-width: 100%; min-height: 100%; font-size: 100px; text-align: right; filter: alpha(opacity=0); opacity: 0; outline: none; background: white; cursor: inherit; display: block; }
        .file-preview { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .video-preview video { max-width: 150px; max-height: 80px; border-radius: 5px; }
        .mindmap-preview { max-width: 100px; max-height: 60px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-center py-4">
                    <h5><i class="fas fa-landmark"></i> لوحة التحكم</h5>
                    <small>مرحباً <?= htmlspecialchars($_SESSION['username']) ?></small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> الرئيسية</a>
                    <a class="nav-link" href="manage_states.php"><i class="fas fa-city"></i> إدارة الولايات</a>
                    <a class="nav-link" href="manage_heritage.php"><i class="fas fa-monument"></i> إدارة المواقع الأثرية</a>
                    <a class="nav-link active" href="manage_lessons.php"><i class="fas fa-book"></i> إدارة الدروس</a>
                    <a class="nav-link" href="manage_hotels.php"><i class="fas fa-hotel"></i> إدارة الفنادق</a>
                    <a class="nav-link" href="manage_restaurants.php"><i class="fas fa-utensils"></i> إدارة المطاعم</a>
                    <a class="nav-link" href="manage_maps.php"><i class="fas fa-map"></i> إدارة الخرائط</a>
					<a class="nav-link" href="manage_archive.php"><i class="fas fa-history"></i> إدارة الأرشيف</a>

                    <hr>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="content-card">
                    <h5><i class="fas fa-book"></i> إدارة الدروس التعليمية</h5>
                    <hr>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <!-- Add/Edit Form -->
                    <form method="POST" class="mb-4" enctype="multipart/form-data">
                        <?php if ($editLesson): ?>
                            <input type="hidden" name="lesson_id" value="<?= $editLesson['lesson_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">المستوى التعليمي <span class="text-danger">*</span></label>
                                <select name="level" class="form-control" required>
                                    <option value="">اختر المستوى</option>
                                    <?php foreach ($levels as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= ($editLesson && $editLesson['level'] == $key) ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">المادة <span class="text-danger">*</span></label>
                                <select name="subject" class="form-control" required>
                                    <option value="">اختر المادة</option>
                                    <?php foreach ($subjects as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= ($editLesson && $editLesson['subject'] == $key) ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">عنوان الدرس <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required 
                                   value="<?= $editLesson ? htmlspecialchars($editLesson['title']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ملخص الدرس</label>
                            <textarea name="summary" class="form-control" rows="4"><?= $editLesson ? htmlspecialchars($editLesson['summary']) : '' ?></textarea>
                        </div>
                        
                        <!-- Video Upload Section -->
                        <div class="mb-3">
                            <label class="form-label">فيديو تعليمي</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn btn-outline-primary btn-file w-100">
                                        <i class="fas fa-cloud-upload-alt"></i> اختر فيديو تعليمي
                                        <input type="file" name="video_file" accept="video/mp4,video/mpeg,video/webm">
                                    </div>
                                    <small class="text-muted">الفيديو المدعوم: MP4, MPEG, WEBM (الحد الأقصى 100MB)</small>
                                </div>
                                <?php if ($editLesson && $editLesson['video_url']): ?>
                                    <div class="col-md-6">
                                        <div class="file-preview">
                                            <strong>الفيديو الحالي:</strong>
                                            <video controls class="video-preview mt-2">
                                                <source src="../<?= htmlspecialchars($editLesson['video_url']) ?>" type="video/mp4">
                                            </video>
                                            <input type="hidden" name="existing_video" value="<?= htmlspecialchars($editLesson['video_url']) ?>">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeVideo(this)">إزالة الفيديو</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="existing_video" value="">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Mindmap Upload Section -->
                        <div class="mb-3">
                            <label class="form-label">خريطة ذهنية</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn btn-outline-primary btn-file w-100">
                                        <i class="fas fa-cloud-upload-alt"></i> اختر خريطة ذهنية
                                        <input type="file" name="mindmap_file" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf">
                                    </div>
                                    <small class="text-muted">الصور المدعومة: JPG, PNG, GIF, WEBP, PDF (الحد الأقصى 10MB)</small>
                                </div>
                                <?php if ($editLesson && $editLesson['mindmap_url']): ?>
                                    <div class="col-md-6">
                                        <div class="file-preview">
                                            <strong>الخريطة الحالية:</strong>
                                            <?php if (pathinfo($editLesson['mindmap_url'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                                <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                                <a href="../<?= htmlspecialchars($editLesson['mindmap_url']) ?>" target="_blank" class="d-block">عرض PDF</a>
                                            <?php else: ?>
                                                <img src="../<?= htmlspecialchars($editLesson['mindmap_url']) ?>" class="mindmap-preview d-block mt-2">
                                            <?php endif; ?>
                                            <input type="hidden" name="existing_mindmap" value="<?= htmlspecialchars($editLesson['mindmap_url']) ?>">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeMindmap(this)">إزالة الخريطة</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="existing_mindmap" value="">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">المصادر والمراجع</label>
                            <textarea name="sources" class="form-control" rows="2" placeholder="المصادر والمراجع المستخدمة..."><?= $editLesson ? htmlspecialchars($editLesson['sources']) : '' ?></textarea>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $editLesson ? 'تحديث' : 'إضافة' ?>
                            </button>
                            <?php if ($editLesson): ?>
                                <a href="manage_lessons.php" class="btn btn-secondary">إلغاء</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <!-- Lessons Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العنوان</th>
                                    <th>المستوى</th>
                                    <th>المادة</th>
                                    <th>فيديو</th>
                                    <th>خريطة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $index => $lesson): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($lesson['title']) ?></strong><br>
                                        <small><?= mb_substr(htmlspecialchars($lesson['summary']), 0, 50) ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge-level badge-<?= $lesson['level'] ?>">
                                            <?= $levels[$lesson['level']] ?>
                                        </span>
                                    </td>
                                    <td><?= $subjects[$lesson['subject']] ?></td>
                                    <td>
                                        <?php if ($lesson['video_url']): ?>
                                            <i class="fas fa-video text-success"></i> متوفر
                                            <button class="btn btn-sm btn-link" onclick="previewVideo('../<?= $lesson['video_url'] ?>')">معاينة</button>
                                        <?php else: ?>
                                            <span class="text-muted">لا يوجد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($lesson['mindmap_url']): ?>
                                            <i class="fas fa-map text-success"></i> متوفر
                                            <a href="../<?= $lesson['mindmap_url'] ?>" target="_blank" class="btn btn-sm btn-link">عرض</a>
                                        <?php else: ?>
                                            <span class="text-muted">لا توجد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $lesson['lesson_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                        <a href="?delete=<?= $lesson['lesson_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذا الدرس؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Video Preview Modal -->
    <div class="modal fade" id="videoPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">معاينة الفيديو التعليمي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <video controls class="w-100">
                        <source id="videoSource" src="" type="video/mp4">
                    </video>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewVideo(url) {
            document.getElementById('videoSource').src = url;
            new bootstrap.Modal(document.getElementById('videoPreviewModal')).show();
        }
        
        function removeVideo(btn) {
            if (confirm('هل أنت متأكد من إزالة هذا الفيديو؟')) {
                const previewDiv = btn.closest('.col-md-6');
                previewDiv.innerHTML = '<input type="hidden" name="existing_video" value=""><span class="text-muted">تمت إزالة الفيديو</span>';
            }
        }
        
        function removeMindmap(btn) {
            if (confirm('هل أنت متأكد من إزالة هذه الخريطة الذهنية؟')) {
                const previewDiv = btn.closest('.col-md-6');
                previewDiv.innerHTML = '<input type="hidden" name="existing_mindmap" value=""><span class="text-muted">تمت إزالة الخريطة</span>';
            }
        }
    </script>
</body>
</html>