<?php
/**
 * Manage Historical Archives - Admin Panel (Enhanced with Media Upload)
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Create upload directories if not exists
$uploadDir = '../uploads/';
$archiveImagesDir = $uploadDir . 'archive/images/';
$archiveVideosDir = $uploadDir . 'archive/videos/';
$archiveDocsDir = $uploadDir . 'archive/documents/';

if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists($archiveImagesDir)) mkdir($archiveImagesDir, 0777, true);
if (!file_exists($archiveVideosDir)) mkdir($archiveVideosDir, 0777, true);
if (!file_exists($archiveDocsDir)) mkdir($archiveDocsDir, 0777, true);

// First, check if media columns exist and add them if not
try {
    $checkColumns = $db->query("SHOW COLUMNS FROM historical_archives LIKE 'featured_image'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE historical_archives ADD COLUMN featured_image VARCHAR(500) NULL AFTER sources");
        $db->exec("ALTER TABLE historical_archives ADD COLUMN gallery_images TEXT NULL AFTER featured_image");
        $db->exec("ALTER TABLE historical_archives ADD COLUMN videos TEXT NULL AFTER gallery_images");
        $db->exec("ALTER TABLE historical_archives ADD COLUMN documents TEXT NULL AFTER videos");
        $db->exec("ALTER TABLE historical_archives ADD COLUMN key_figures TEXT NULL AFTER documents");
        $db->exec("ALTER TABLE historical_archives ADD COLUMN cultural_achievements TEXT NULL AFTER key_figures");
    }
} catch (PDOException $e) {
    // Columns might already exist
}

// Handle delete action
if (isset($_GET['delete'])) {
    $archive_id = intval($_GET['delete']);
    
    // Get archive info to delete files
    $getArchive = $db->prepare("SELECT featured_image, gallery_images, videos, documents FROM historical_archives WHERE archive_id = ?");
    $getArchive->execute([$archive_id]);
    $archive = $getArchive->fetch();
    
    // Delete associated files
    if ($archive) {
        if ($archive['featured_image'] && file_exists('../' . $archive['featured_image'])) {
            unlink('../' . $archive['featured_image']);
        }
        
        // Delete gallery images
        if ($archive['gallery_images']) {
            $gallery = json_decode($archive['gallery_images'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $img) {
                    if (file_exists('../' . $img)) unlink('../' . $img);
                }
            }
        }
        
        // Delete videos
        if ($archive['videos']) {
            $videos = json_decode($archive['videos'], true);
            if (is_array($videos)) {
                foreach ($videos as $video) {
                    if (file_exists('../' . $video)) unlink('../' . $video);
                }
            }
        }
        
        // Delete documents
        if ($archive['documents']) {
            $docs = json_decode($archive['documents'], true);
            if (is_array($docs)) {
                foreach ($docs as $doc) {
                    if (file_exists('../' . $doc)) unlink('../' . $doc);
                }
            }
        }
    }
    
    $deleteQuery = $db->prepare("DELETE FROM historical_archives WHERE archive_id = ?");
    if ($deleteQuery->execute([$archive_id])) {
        $_SESSION['success'] = "تم حذف الحقبة التاريخية بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء الحذف";
    }
    header('Location: manage_archive.php');
    exit();
}

// Handle file upload function
function uploadArchiveFile($file, $type = 'image') {
    $targetDir = ($type === 'image') ? '../uploads/archive/images/' : (($type === 'video') ? '../uploads/archive/videos/' : '../uploads/archive/documents/');
    $allowedTypes = ($type === 'image') ? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] : 
                    (($type === 'video') ? ['video/mp4', 'video/mpeg', 'video/webm'] : 
                    ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'image/jpeg', 'image/png']);
    $maxSize = ($type === 'image') ? 10 * 1024 * 1024 : (($type === 'video') ? 100 * 1024 * 1024 : 20 * 1024 * 1024);
    
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
        $relativePath = ($type === 'image') ? 'uploads/archive/images/' . $filename : (($type === 'video') ? 'uploads/archive/videos/' . $filename : 'uploads/archive/documents/' . $filename);
        return ['success' => true, 'path' => $relativePath];
    }
    
    return ['success' => false, 'message' => 'فشل في حفظ الملف'];
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archive_id = isset($_POST['archive_id']) ? intval($_POST['archive_id']) : 0;
    $period_name = trim($_POST['period_name']);
    $start_year = !empty($_POST['start_year']) ? intval($_POST['start_year']) : null;
    $end_year = !empty($_POST['end_year']) ? intval($_POST['end_year']) : null;
    $description = trim($_POST['description']);
    $sources = trim($_POST['sources']);
    $key_figures = trim($_POST['key_figures']);
    $cultural_achievements = trim($_POST['cultural_achievements']);
    
    // Handle featured image upload
    $featured_image = $_POST['existing_featured_image'] ?? '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadArchiveFile($_FILES['featured_image'], 'image');
        if ($uploadResult['success']) {
            if ($featured_image && file_exists('../' . $featured_image)) {
                unlink('../' . $featured_image);
            }
            $featured_image = $uploadResult['path'];
        } else {
            $_SESSION['error'] = $uploadResult['message'];
            header('Location: manage_archive.php');
            exit();
        }
    }
    
    // Handle gallery images (multiple)
    $gallery_images = isset($_POST['existing_gallery']) ? json_decode($_POST['existing_gallery'], true) : [];
    if (isset($_FILES['gallery_images'])) {
        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$key],
                    'type' => $_FILES['gallery_images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['gallery_images']['error'][$key],
                    'size' => $_FILES['gallery_images']['size'][$key]
                ];
                $uploadResult = uploadArchiveFile($file, 'image');
                if ($uploadResult['success']) {
                    $gallery_images[] = $uploadResult['path'];
                }
            }
        }
    }
    
    // Handle videos (multiple)
    $videos = isset($_POST['existing_videos']) ? json_decode($_POST['existing_videos'], true) : [];
    if (isset($_FILES['videos'])) {
        foreach ($_FILES['videos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['videos']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['videos']['name'][$key],
                    'type' => $_FILES['videos']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['videos']['error'][$key],
                    'size' => $_FILES['videos']['size'][$key]
                ];
                $uploadResult = uploadArchiveFile($file, 'video');
                if ($uploadResult['success']) {
                    $videos[] = $uploadResult['path'];
                }
            }
        }
    }
    
    // Handle documents (multiple)
    $documents = isset($_POST['existing_documents']) ? json_decode($_POST['existing_documents'], true) : [];
    if (isset($_FILES['documents'])) {
        foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['documents']['name'][$key],
                    'type' => $_FILES['documents']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['documents']['error'][$key],
                    'size' => $_FILES['documents']['size'][$key]
                ];
                $uploadResult = uploadArchiveFile($file, 'document');
                if ($uploadResult['success']) {
                    $documents[] = $uploadResult['path'];
                }
            }
        }
    }
    
    $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
    $videos_json = !empty($videos) ? json_encode($videos) : null;
    $documents_json = !empty($documents) ? json_encode($documents) : null;
    
    if ($archive_id > 0) {
        // Update existing archive
        $updateQuery = $db->prepare("UPDATE historical_archives SET period_name = ?, start_year = ?, end_year = ?, description = ?, sources = ?, featured_image = ?, gallery_images = ?, videos = ?, documents = ?, key_figures = ?, cultural_achievements = ? WHERE archive_id = ?");
        if ($updateQuery->execute([$period_name, $start_year, $end_year, $description, $sources, $featured_image, $gallery_json, $videos_json, $documents_json, $key_figures, $cultural_achievements, $archive_id])) {
            $_SESSION['success'] = "تم تحديث الحقبة التاريخية بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // Insert new archive
        $insertQuery = $db->prepare("INSERT INTO historical_archives (period_name, start_year, end_year, description, sources, featured_image, gallery_images, videos, documents, key_figures, cultural_achievements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insertQuery->execute([$period_name, $start_year, $end_year, $description, $sources, $featured_image, $gallery_json, $videos_json, $documents_json, $key_figures, $cultural_achievements])) {
            $_SESSION['success'] = "تم إضافة الحقبة التاريخية بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة";
        }
    }
    header('Location: manage_archive.php');
    exit();
}

// Get archive for editing
$editArchive = null;
if (isset($_GET['edit'])) {
    $archive_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM historical_archives WHERE archive_id = ?");
    $editQuery->execute([$archive_id]);
    $editArchive = $editQuery->fetch();
    
    // Decode JSON data for editing
    if ($editArchive) {
        $editArchive['gallery_images_array'] = $editArchive['gallery_images'] ? json_decode($editArchive['gallery_images'], true) : [];
        $editArchive['videos_array'] = $editArchive['videos'] ? json_decode($editArchive['videos'], true) : [];
        $editArchive['documents_array'] = $editArchive['documents'] ? json_decode($editArchive['documents'], true) : [];
    }
}

// Get all archives ordered by start year
$archives = $db->query("SELECT * FROM historical_archives ORDER BY start_year ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأرشيف التاريخي - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { display: flex; }
        .main-content { flex: 1; padding: 20px; }
        .content-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-file { position: relative; overflow: hidden; }
        .btn-file input[type=file] { position: absolute; top: 0; right: 0; min-width: 100%; min-height: 100%; font-size: 100px; text-align: right; filter: alpha(opacity=0); opacity: 0; outline: none; background: white; cursor: inherit; display: block; }
        .file-preview { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .preview-image { max-width: 100px; max-height: 80px; object-fit: cover; border-radius: 5px; margin: 5px; cursor: pointer; }
        .media-item { position: relative; display: inline-block; margin: 5px; }
        .media-item .remove-btn { position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; font-size: 12px; cursor: pointer; }
        .archive-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; transition: transform 0.3s; border-right: 3px solid #e67e22; }
        .archive-card:hover { transform: translateX(-5px); box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        .archive-year { font-weight: bold; color: #e67e22; font-size: 1rem; }
        .gallery-thumb { display: inline-block; margin: 5px; }
        .gallery-thumb img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
		* { font-family: 'Cairo', sans-serif; }
        body { background: #f4f6f9; }
        .sidebar { background: #2c3e50; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #e67e22; }
        .sidebar .nav-link i { margin-left: 10px; }
        .main-content { padding: 20px; }
        .content-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .rating-stars { color: #f39c12; }
        .hotel-image { max-width: 80px; max-height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer; }
        .btn-file { position: relative; overflow: hidden; }
        .btn-file input[type=file] { position: absolute; top: 0; right: 0; min-width: 100%; min-height: 100%; font-size: 100px; text-align: right; filter: alpha(opacity=0); opacity: 0; outline: none; background: white; cursor: inherit; display: block; }
        .file-preview { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .hotel-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
            height: 100%;
        }
        .hotel-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .hotel-card img { width: 100%; height: 180px; object-fit: cover; }
        .hotel-card .card-content { padding: 15px; }
        .state-section { margin-bottom: 40px; }
        .state-section h4 { 
            border-right: 5px solid #e67e22; 
            padding-right: 15px; 
            margin-bottom: 20px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
<div class="wrapper">
  <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-center py-4">
                    <h5><i class="fas fa-landmark"></i> لوحة التحكم</h5>
                    <small>مرحباً <?= htmlspecialchars($_SESSION['username']) ?></small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> الرئيسية</a>
                    <a class="nav-link" href="manage_states.php"><i class="fas fa-city"></i> إدارة الولايات</a>
                    <a class="nav-link active" href="manage_heritage.php"><i class="fas fa-monument"></i> إدارة المواقع الأثرية</a>
                    <a class="nav-link" href="manage_lessons.php"><i class="fas fa-book"></i> إدارة الدروس</a>
						<a class="nav-link" href="manage_trips.php">
                        <i class="fas fa-book"></i> إدارة الرحلات الذكية
                    </a>
					
					<a class="nav-link" href="manage_archive.php">
                        <i class="fas fa-book"></i> إدارة الأرشيف التاريخي
                    </a>
                    <a class="nav-link" href="manage_hotels.php"><i class="fas fa-hotel"></i> إدارة الفنادق</a>
                    <a class="nav-link" href="manage_restaurants.php"><i class="fas fa-utensils"></i> إدارة المطاعم</a>
                    <a class="nav-link" href="manage_maps.php"><i class="fas fa-map"></i> إدارة الخرائط</a>
                    <a class="nav-link" href="manage_archive.php"><i class="fas fa-history"></i> إدارة الأرشيف</a>

				   <hr>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
                </nav>
            </div>    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-card">
            <h5><i class="fas fa-history"></i> إدارة الأرشيف التاريخي</h5>
            <p class="text-muted">إدارة العصور والحقب التاريخية التي مرت بها الجزائر مع إمكانية إضافة الصور والفيديوهات والوثائق</p>
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
                <?php if ($editArchive): ?>
                    <input type="hidden" name="archive_id" value="<?= $editArchive['archive_id'] ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم الحقبة التاريخية <span class="text-danger">*</span></label>
                        <input type="text" name="period_name" class="form-control" required 
                               value="<?= $editArchive ? htmlspecialchars($editArchive['period_name']) : '' ?>"
                               placeholder="مثال: العصر الروماني، العصر الإسلامي، الحقبة العثمانية...">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">بداية الحقبة (السنة)</label>
                        <input type="number" name="start_year" class="form-control" 
                               value="<?= $editArchive ? $editArchive['start_year'] : '' ?>"
                               placeholder="مثال: 202">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">نهاية الحقبة (السنة)</label>
                        <input type="number" name="end_year" class="form-control" 
                               value="<?= $editArchive ? $editArchive['end_year'] : '' ?>"
                               placeholder="مثال: 429">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">الوصف التفصيلي</label>
                    <textarea name="description" class="form-control" rows="5" 
                              placeholder="أدخل وصفاً مفصلاً عن هذه الحقبة التاريخية، الأحداث المهمة، الشخصيات البارزة، الإنجازات الحضارية..."><?= $editArchive ? htmlspecialchars($editArchive['description']) : '' ?></textarea>
                </div>
                
                <!-- Featured Image Upload -->
                <div class="mb-3">
                    <label class="form-label">الصورة الرئيسية</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn btn-outline-primary btn-file w-100">
                                <i class="fas fa-cloud-upload-alt"></i> اختر صورة رئيسية
                                <input type="file" name="featured_image" accept="image/jpeg,image/png,image/gif,image/webp">
                            </div>
                            <small class="text-muted">الصور المدعومة: JPG, PNG, GIF, WEBP (الحد الأقصى 10MB)</small>
                        </div>
                        <?php if ($editArchive && $editArchive['featured_image']): ?>
                            <div class="col-md-6">
                                <div class="file-preview">
                                    <strong>الصورة الحالية:</strong>
                                    <img src="../<?= htmlspecialchars($editArchive['featured_image']) ?>" class="preview-image d-block mt-2">
                                    <input type="hidden" name="existing_featured_image" value="<?= htmlspecialchars($editArchive['featured_image']) ?>">
                                    <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeFeaturedImage(this)">إزالة الصورة</button>
                                </div>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="existing_featured_image" value="">
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Gallery Images Upload (Multiple) -->
                <div class="mb-3">
                    <label class="form-label">معرض الصور</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn btn-outline-primary btn-file w-100">
                                <i class="fas fa-images"></i> اختر صور متعددة
                                <input type="file" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/gif,image/webm">
                            </div>
                            <small class="text-muted">يمكنك اختيار عدة صور في وقت واحد</small>
                        </div>
                        <div class="col-md-6" id="galleryPreview">
                            <?php if ($editArchive && !empty($editArchive['gallery_images_array'])): ?>
                                <strong>الصور الحالية:</strong>
                                <div class="mt-2">
                                    <?php foreach ($editArchive['gallery_images_array'] as $img): ?>
                                        <div class="media-item">
                                            <img src="../<?= $img ?>" class="preview-image">
                                            <span class="remove-btn" onclick="removeGalleryImage('<?= $img ?>')">×</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="existing_gallery" value='<?= json_encode($editArchive['gallery_images_array']) ?>'>
                            <?php else: ?>
                                <input type="hidden" name="existing_gallery" value='[]'>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Videos Upload (Multiple) -->
                <div class="mb-3">
                    <label class="form-label">فيديوهات توثيقية</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn btn-outline-primary btn-file w-100">
                                <i class="fas fa-video"></i> اختر فيديوهات متعددة
                                <input type="file" name="videos[]" multiple accept="video/mp4,video/mpeg,video/webm">
                            </div>
                            <small class="text-muted">الفيديو المدعوم: MP4, MPEG, WEBM (الحد الأقصى 100MB لكل فيديو)</small>
                        </div>
                        <div class="col-md-6" id="videosPreview">
                            <?php if ($editArchive && !empty($editArchive['videos_array'])): ?>
                                <strong>الفيديوهات الحالية:</strong>
                                <div class="mt-2">
                                    <?php foreach ($editArchive['videos_array'] as $video): ?>
                                        <div class="media-item">
                                            <video width="100" height="60" controls>
                                                <source src="../<?= $video ?>" type="video/mp4">
                                            </video>
                                            <span class="remove-btn" onclick="removeVideo('<?= $video ?>')">×</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="existing_videos" value='<?= json_encode($editArchive['videos_array']) ?>'>
                            <?php else: ?>
                                <input type="hidden" name="existing_videos" value='[]'>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Documents Upload (Multiple) -->
                <div class="mb-3">
                    <label class="form-label">وثائق ومصادر</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn btn-outline-primary btn-file w-100">
                                <i class="fas fa-file-pdf"></i> اختر وثائق متعددة
                                <input type="file" name="documents[]" multiple accept=".pdf,.doc,.docx,.txt">
                            </div>
                            <small class="text-muted">الوثائق المدعومة: PDF, DOC, DOCX, TXT (الحد الأقصى 20MB لكل ملف)</small>
                        </div>
                        <div class="col-md-6" id="documentsPreview">
                            <?php if ($editArchive && !empty($editArchive['documents_array'])): ?>
                                <strong>الوثائق الحالية:</strong>
                                <div class="mt-2">
                                    <?php foreach ($editArchive['documents_array'] as $doc): ?>
                                        <div class="media-item">
                                            <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                            <a href="../<?= $doc ?>" target="_blank">عرض الوثيقة</a>
                                            <span class="remove-btn" onclick="removeDocument('<?= $doc ?>')">×</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="existing_documents" value='<?= json_encode($editArchive['documents_array']) ?>'>
                            <?php else: ?>
                                <input type="hidden" name="existing_documents" value='[]'>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">أبرز الشخصيات في هذه الحقبة</label>
                        <textarea name="key_figures" class="form-control" rows="3" 
                                  placeholder="أبرز الشخصيات التاريخية في هذه الفترة..."><?= $editArchive ? htmlspecialchars($editArchive['key_figures']) : '' ?></textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الإنجازات الحضارية والثقافية</label>
                        <textarea name="cultural_achievements" class="form-control" rows="3" 
                                  placeholder="الإنجازات العلمية، الثقافية، العمرانية..."><?= $editArchive ? htmlspecialchars($editArchive['cultural_achievements']) : '' ?></textarea>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">المصادر والمراجع</label>
                    <textarea name="sources" class="form-control" rows="2" 
                              placeholder="المصادر التاريخية المعتمدة، الكتب، الأبحاث، المراجع..."><?= $editArchive ? htmlspecialchars($editArchive['sources']) : '' ?></textarea>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $editArchive ? 'تحديث' : 'إضافة' ?>
                    </button>
                    <?php if ($editArchive): ?>
                        <a href="manage_archive.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <hr>
            
            <!-- Archives Display -->
            <h6><i class="fas fa-timeline"></i> قائمة الحقب التاريخية</h6>
            
            <?php if (empty($archives)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> لا توجد حقب تاريخية مسجلة. أضف حقبة جديدة من النموذج أعلاه.
                </div>
            <?php else: ?>
                <?php foreach ($archives as $archive): ?>
                    <div class="archive-card">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div class="flex-grow-1">
                                <div class="archive-year">
                                    <?php if ($archive['start_year'] && $archive['end_year']): ?>
                                        <i class="fas fa-calendar-alt"></i> <?= $archive['start_year'] ?> - <?= $archive['end_year'] ?> م
                                    <?php elseif ($archive['start_year']): ?>
                                        <i class="fas fa-calendar-alt"></i> من <?= $archive['start_year'] ?> م
                                    <?php elseif ($archive['end_year']): ?>
                                        <i class="fas fa-calendar-alt"></i> حتى <?= $archive['end_year'] ?> م
                                    <?php endif; ?>
                                </div>
                                <h5 class="mt-2"><?= htmlspecialchars($archive['period_name']) ?></h5>
                                <p class="text-muted"><?= mb_substr(htmlspecialchars($archive['description']), 0, 150) ?>...</p>
                                
                                <!-- Display Media Thumbnails -->
                                <?php if ($archive['featured_image']): ?>
                                    <div class="mt-2">
                                        <img src="../<?= $archive['featured_image'] ?>" class="gallery-thumb" style="width: 50px; height: 50px;">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($archive['gallery_images']): 
                                    $gallery = json_decode($archive['gallery_images'], true);
                                    if (!empty($gallery)): ?>
                                        <div class="mt-2">
                                            <?php foreach (array_slice($gallery, 0, 3) as $img): ?>
                                                <img src="../<?= $img ?>" class="gallery-thumb" style="width: 40px; height: 40px;">
                                            <?php endforeach; ?>
                                            <?php if (count($gallery) > 3): ?>
                                                <span class="badge bg-secondary">+<?= count($gallery) - 3 ?> صور</span>
                                            <?php endif; ?>
                                        </div>
                                <?php endif; endif; ?>
                                
                                <?php if ($archive['videos']): 
                                    $videos = json_decode($archive['videos'], true);
                                    if (!empty($videos)): ?>
                                        <div class="mt-2">
                                            <i class="fas fa-video text-primary"></i> <?= count($videos) ?> فيديو
                                        </div>
                                <?php endif; endif; ?>
                                
                                <?php if ($archive['documents']): 
                                    $docs = json_decode($archive['documents'], true);
                                    if (!empty($docs)): ?>
                                        <div class="mt-2">
                                            <i class="fas fa-file-pdf text-danger"></i> <?= count($docs) ?> وثيقة
                                        </div>
                                <?php endif; endif; ?>
                            </div>
                            <div class="btn-group">
                                <a href="?edit=<?= $archive['archive_id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                                <a href="?delete=<?= $archive['archive_id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذه الحقبة التاريخية؟')">
                                    <i class="fas fa-trash"></i> حذف
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function removeFeaturedImage(btn) {
        if (confirm('هل أنت متأكد من إزالة هذه الصورة؟')) {
            const previewDiv = btn.closest('.col-md-6');
            previewDiv.innerHTML = '<input type="hidden" name="existing_featured_image" value=""><span class="text-muted">تمت إزالة الصورة</span>';
        }
    }
    
    function removeGalleryImage(imgPath) {
        if (confirm('هل أنت متأكد من إزالة هذه الصورة؟')) {
            let existing = document.querySelector('input[name="existing_gallery"]');
            let images = JSON.parse(existing.value);
            images = images.filter(img => img !== imgPath);
            existing.value = JSON.stringify(images);
            
            // Remove from display
            const mediaItems = document.querySelectorAll('#galleryPreview .media-item');
            mediaItems.forEach(item => {
                if (item.querySelector('img')?.src.includes(encodeURIComponent(imgPath))) {
                    item.remove();
                }
            });
        }
    }
    
    function removeVideo(videoPath) {
        if (confirm('هل أنت متأكد من إزالة هذا الفيديو؟')) {
            let existing = document.querySelector('input[name="existing_videos"]');
            let videos = JSON.parse(existing.value);
            videos = videos.filter(v => v !== videoPath);
            existing.value = JSON.stringify(videos);
            
            const mediaItems = document.querySelectorAll('#videosPreview .media-item');
            mediaItems.forEach(item => {
                if (item.querySelector('video')?.src.includes(encodeURIComponent(videoPath))) {
                    item.remove();
                }
            });
        }
    }
    
    function removeDocument(docPath) {
        if (confirm('هل أنت متأكد من إزالة هذه الوثيقة؟')) {
            let existing = document.querySelector('input[name="existing_documents"]');
            let docs = JSON.parse(existing.value);
            docs = docs.filter(d => d !== docPath);
            existing.value = JSON.stringify(docs);
            
            const mediaItems = document.querySelectorAll('#documentsPreview .media-item');
            mediaItems.forEach(item => {
                if (item.querySelector('a')?.href.includes(encodeURIComponent(docPath))) {
                    item.remove();
                }
            });
        }
    }
</script>
</body>
</html>