<?php
/**
 * Manage Heritage Sites - Admin Panel (Enhanced with Map & Coordinates)
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Create upload directories if not exists
$uploadDir = '../uploads/';
$imagesDir = $uploadDir . 'images/';
$videosDir = $uploadDir . 'videos/';
$docsDir = $uploadDir . 'documents/';

if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists($imagesDir)) mkdir($imagesDir, 0777, true);
if (!file_exists($videosDir)) mkdir($videosDir, 0777, true);
if (!file_exists($docsDir)) mkdir($docsDir, 0777, true);

// First, check if coordinates columns exist and add them if not
try {
    $checkColumns = $db->query("SHOW COLUMNS FROM heritage_sites LIKE 'latitude'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE heritage_sites ADD COLUMN latitude DECIMAL(10,8) NULL AFTER image_url");
        $db->exec("ALTER TABLE heritage_sites ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude");
        $db->exec("ALTER TABLE heritage_sites ADD COLUMN documentation TEXT NULL AFTER story");
        $db->exec("ALTER TABLE heritage_sites ADD COLUMN historical_events TEXT NULL AFTER documentation");
        $db->exec("ALTER TABLE heritage_sites ADD COLUMN documents_url VARCHAR(500) NULL AFTER historical_events");
    }
} catch (PDOException $e) {
    // Columns might already exist
}

// Handle delete action
if (isset($_GET['delete'])) {
    $site_id = intval($_GET['delete']);
    
    // Get site info to delete files
    $getSite = $db->prepare("SELECT image_url, video_url, documents_url FROM heritage_sites WHERE site_id = ?");
    $getSite->execute([$site_id]);
    $site = $getSite->fetch();
    
    // Delete associated files
    if ($site) {
        if ($site['image_url'] && file_exists('../' . $site['image_url'])) {
            unlink('../' . $site['image_url']);
        }
        if ($site['video_url'] && file_exists('../' . $site['video_url'])) {
            unlink('../' . $site['video_url']);
        }
        if ($site['documents_url'] && file_exists('../' . $site['documents_url'])) {
            unlink('../' . $site['documents_url']);
        }
    }
    
    $deleteQuery = $db->prepare("DELETE FROM heritage_sites WHERE site_id = ?");
    if ($deleteQuery->execute([$site_id])) {
        $_SESSION['success'] = "تم حذف الموقع الأثري بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء الحذف";
    }
    header('Location: manage_heritage.php');
    exit();
}

// Handle file upload function
function uploadFile($file, $type = 'image') {
    $targetDir = ($type === 'image') ? '../uploads/images/' : (($type === 'video') ? '../uploads/videos/' : '../uploads/documents/');
    $allowedTypes = ($type === 'image') ? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] : 
                    (($type === 'video') ? ['video/mp4', 'video/mpeg', 'video/webm'] : 
                    ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']);
    $maxSize = ($type === 'image') ? 5 * 1024 * 1024 : (($type === 'video') ? 50 * 1024 * 1024 : 10 * 1024 * 1024);
    
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
        $relativePath = ($type === 'image') ? 'uploads/images/' . $filename : (($type === 'video') ? 'uploads/videos/' . $filename : 'uploads/documents/' . $filename);
        return ['success' => true, 'path' => $relativePath];
    }
    
    return ['success' => false, 'message' => 'فشل في حفظ الملف'];
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
    $state_id = intval($_POST['state_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $story = trim($_POST['story']);
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $documentation = trim($_POST['documentation']);
    $historical_events = trim($_POST['historical_events']);
    
    // Handle image upload
    $image_path = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['image_file'], 'image');
        if ($uploadResult['success']) {
            if ($image_path && file_exists('../' . $image_path)) {
                unlink('../' . $image_path);
            }
            $image_path = $uploadResult['path'];
        } else {
            $_SESSION['error'] = $uploadResult['message'];
            header('Location: manage_heritage.php');
            exit();
        }
    }
    
    // Handle video upload
    $video_path = $_POST['existing_video'] ?? '';
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['video_file'], 'video');
        if ($uploadResult['success']) {
            if ($video_path && file_exists('../' . $video_path)) {
                unlink('../' . $video_path);
            }
            $video_path = $uploadResult['path'];
        } else {
            $_SESSION['error'] = $uploadResult['message'];
            header('Location: manage_heritage.php');
            exit();
        }
    }
    
    // Handle document upload
    $documents_path = $_POST['existing_documents'] ?? '';
    if (isset($_FILES['documents_file']) && $_FILES['documents_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['documents_file'], 'document');
        if ($uploadResult['success']) {
            if ($documents_path && file_exists('../' . $documents_path)) {
                unlink('../' . $documents_path);
            }
            $documents_path = $uploadResult['path'];
        } else {
            $_SESSION['error'] = $uploadResult['message'];
            header('Location: manage_heritage.php');
            exit();
        }
    }
    
    if ($site_id > 0) {
        // Update existing site
        $updateQuery = $db->prepare("UPDATE heritage_sites SET state_id = ?, name = ?, description = ?, video_url = ?, image_url = ?, story = ?, latitude = ?, longitude = ?, documentation = ?, historical_events = ?, documents_url = ? WHERE site_id = ?");
        if ($updateQuery->execute([$state_id, $name, $description, $video_path, $image_path, $story, $latitude, $longitude, $documentation, $historical_events, $documents_path, $site_id])) {
            $_SESSION['success'] = "تم تحديث الموقع الأثري بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // Insert new site
        $insertQuery = $db->prepare("INSERT INTO heritage_sites (state_id, name, description, video_url, image_url, story, latitude, longitude, documentation, historical_events, documents_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insertQuery->execute([$state_id, $name, $description, $video_path, $image_path, $story, $latitude, $longitude, $documentation, $historical_events, $documents_path])) {
            $_SESSION['success'] = "تم إضافة الموقع الأثري بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة";
        }
    }
    header('Location: manage_heritage.php');
    exit();
}

// Get site for editing
$editSite = null;
if (isset($_GET['edit'])) {
    $site_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM heritage_sites WHERE site_id = ?");
    $editQuery->execute([$site_id]);
    $editSite = $editQuery->fetch();
}

// Get all states for dropdown
$states = $db->query("SELECT state_id, name FROM states ORDER BY name")->fetchAll();

// Get all heritage sites with state names
$sites = $db->query("
    SELECT hs.*, s.name as state_name 
    FROM heritage_sites hs 
    JOIN states s ON hs.state_id = s.state_id 
    ORDER BY hs.site_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المواقع الأثرية - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f4f6f9; }
        .sidebar { background: #2c3e50; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #e67e22; }
        .sidebar .nav-link i { margin-left: 10px; }
        .main-content { padding: 20px; }
        .content-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .site-image-preview { max-width: 100px; max-height: 60px; object-fit: cover; border-radius: 5px; }
        .file-preview { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .btn-file { position: relative; overflow: hidden; }
        .btn-file input[type=file] { position: absolute; top: 0; right: 0; min-width: 100%; min-height: 100%; font-size: 100px; text-align: right; filter: alpha(opacity=0); opacity: 0; outline: none; background: white; cursor: inherit; display: block; }
        .video-preview video { max-width: 150px; max-height: 80px; border-radius: 5px; }
        #map { height: 400px; border-radius: 10px; margin-top: 10px; }
        .coordinates-info { background: #e9ecef; padding: 10px; border-radius: 5px; margin-top: 10px; }
        .event-item { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 10px; border-right: 3px solid #e67e22; }
        .document-icon { font-size: 2rem; color: #dc3545; }
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
            <div class="col-md-10 main-content">
                <div class="content-card">
                    <h5><i class="fas fa-monument"></i> إدارة المواقع الأثرية</h5>
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
                        <?php if ($editSite): ?>
                            <input type="hidden" name="site_id" value="<?= $editSite['site_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الولاية <span class="text-danger">*</span></label>
                                <select name="state_id" class="form-control" required>
                                    <option value="">اختر الولاية</option>
                                    <?php foreach ($states as $state): ?>
                                        <option value="<?= $state['state_id'] ?>" <?= ($editSite && $editSite['state_id'] == $state['state_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($state['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم الموقع الأثري <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= $editSite ? htmlspecialchars($editSite['name']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"><?= $editSite ? htmlspecialchars($editSite['description']) : '' ?></textarea>
                        </div>
                        
                        <!-- Map Section for Coordinates -->
                        <div class="mb-3">
                            <label class="form-label">الموقع الجغرافي <span class="text-muted">(اختر الموقع على الخريطة)</span></label>
                            <div id="map"></div>
                            <div class="coordinates-info mt-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">خط العرض (Latitude):</label>
                                        <input type="text" id="latitude" name="latitude" class="form-control" 
                                               value="<?= $editSite && $editSite['latitude'] ? $editSite['latitude'] : '' ?>" 
                                               placeholder="مثال: 36.7538">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">خط الطول (Longitude):</label>
                                        <input type="text" id="longitude" name="longitude" class="form-control" 
                                               value="<?= $editSite && $editSite['longitude'] ? $editSite['longitude'] : '' ?>" 
                                               placeholder="مثال: 3.0588">
                                    </div>
                                </div>
                                <small class="text-muted">يمكنك النقر على الخريطة لتحديد الموقع تلقائياً</small>
                            </div>
                        </div>
                        
                        <!-- Image Upload Section -->
                        <div class="mb-3">
                            <label class="form-label">صورة الموقع</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn btn-outline-primary btn-file w-100">
                                        <i class="fas fa-cloud-upload-alt"></i> اختر صورة
                                        <input type="file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp">
                                    </div>
                                    <small class="text-muted">الصور المدعومة: JPG, PNG, GIF, WEBP (الحد الأقصى 5MB)</small>
                                </div>
                                <?php if ($editSite && $editSite['image_url']): ?>
                                    <div class="col-md-6">
                                        <div class="file-preview">
                                            <strong>الصورة الحالية:</strong>
                                            <img src="../<?= htmlspecialchars($editSite['image_url']) ?>" class="site-image-preview d-block mt-2">
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editSite['image_url']) ?>">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeImage(this)">إزالة الصورة</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="existing_image" value="">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Video Upload Section -->
                        <div class="mb-3">
                            <label class="form-label">فيديو تعريفي</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn btn-outline-primary btn-file w-100">
                                        <i class="fas fa-cloud-upload-alt"></i> اختر فيديو
                                        <input type="file" name="video_file" accept="video/mp4,video/mpeg,video/webm">
                                    </div>
                                    <small class="text-muted">الفيديو المدعوم: MP4, MPEG, WEBM (الحد الأقصى 50MB)</small>
                                </div>
                                <?php if ($editSite && $editSite['video_url']): ?>
                                    <div class="col-md-6">
                                        <div class="file-preview">
                                            <strong>الفيديو الحالي:</strong>
                                            <video controls class="video-preview mt-2">
                                                <source src="../<?= htmlspecialchars($editSite['video_url']) ?>" type="video/mp4">
                                            </video>
                                            <input type="hidden" name="existing_video" value="<?= htmlspecialchars($editSite['video_url']) ?>">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeVideo(this)">إزالة الفيديو</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="existing_video" value="">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Historical Events Section -->
                        <div class="mb-3">
                            <label class="form-label">الأحداث التاريخية في الموقع</label>
                            <textarea name="historical_events" class="form-control" rows="4" 
                                      placeholder="مثال:&#10;- 1830: حدث تاريخي مهم&#10;- 1962: حدث آخر"><?= $editSite ? htmlspecialchars($editSite['historical_events']) : '' ?></textarea>
                            <small class="text-muted">أدخل الأحداث التاريخية المهمة التي وقعت في هذا الموقع، كل حدث في سطر جديد</small>
                        </div>
                        
                        <!-- Documentation Section -->
                        <div class="mb-3">
                            <label class="form-label">توثيق الموقع الأثري</label>
                            <textarea name="documentation" class="form-control" rows="4" 
                                      placeholder="أدخل معلومات موثقة عن الموقع الأثري، المصادر، المراجع، الاكتشافات الأثرية..."><?= $editSite ? htmlspecialchars($editSite['documentation']) : '' ?></textarea>
                        </div>
                        
                        <!-- Document Upload Section -->
                        <div class="mb-3">
                            <label class="form-label">وثائق مرفقة (PDF, DOC, TXT)</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn btn-outline-primary btn-file w-100">
                                        <i class="fas fa-file-upload"></i> اختر وثيقة
                                        <input type="file" name="documents_file" accept=".pdf,.doc,.docx,.txt">
                                    </div>
                                    <small class="text-muted">الوثائق المدعومة: PDF, DOC, DOCX, TXT (الحد الأقصى 10MB)</small>
                                </div>
                                <?php if ($editSite && $editSite['documents_url']): ?>
                                    <div class="col-md-6">
                                        <div class="file-preview">
                                            <strong>الوثيقة الحالية:</strong>
                                            <div class="d-flex align-items-center mt-2">
                                                <i class="fas fa-file-pdf document-icon"></i>
                                                <a href="../<?= htmlspecialchars($editSite['documents_url']) ?>" target="_blank" class="ms-2">عرض الوثيقة</a>
                                            </div>
                                            <input type="hidden" name="existing_documents" value="<?= htmlspecialchars($editSite['documents_url']) ?>">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeDocument(this)">إزالة الوثيقة</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="existing_documents" value="">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">القصة التاريخية</label>
                            <textarea name="story" class="form-control" rows="4" placeholder="أكتب القصة التاريخية للموقع..."><?= $editSite ? htmlspecialchars($editSite['story']) : '' ?></textarea>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $editSite ? 'تحديث' : 'إضافة' ?>
                            </button>
                            <?php if ($editSite): ?>
                                <a href="manage_heritage.php" class="btn btn-secondary">إلغاء</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <!-- Heritage Sites Table -->
                    <div class="table-responsive">
                        <h6><i class="fas fa-list"></i> قائمة المواقع الأثرية</h6>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الموقع</th>
                                    <th>الولاية</th>
                                    <th>الإحداثيات</th>
                                    <th>الوثائق</th>
                                    <th>الإجراءات</th>
                                </thead>
                            <tbody>
                                <?php foreach ($sites as $index => $site): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($site['name']) ?></strong><br>
                                        <small><?= mb_substr(htmlspecialchars($site['description']), 0, 40) ?>...</small>
                                    </td>
                                    <td><?= htmlspecialchars($site['state_name']) ?></td>
                                    <td>
                                        <?php if ($site['latitude'] && $site['longitude']): ?>
                                            <i class="fas fa-map-marker-alt text-danger"></i>
                                            <?= number_format($site['latitude'], 6) ?>, <?= number_format($site['longitude'], 6) ?>
                                            <button class="btn btn-sm btn-link" onclick="showOnMap(<?= $site['latitude'] ?>, <?= $site['longitude'] ?>, '<?= addslashes($site['name']) ?>')">عرض</button>
                                        <?php else: ?>
                                            <span class="text-muted">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($site['documents_url']): ?>
                                            <i class="fas fa-file-pdf text-danger"></i>
                                            <a href="../<?= $site['documents_url'] ?>" target="_blank">فتح</a>
                                        <?php else: ?>
                                            <span class="text-muted">لا توجد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $site['site_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                        <a href="?delete=<?= $site['site_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذا الموقع الأثري؟')">
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
    
    <!-- Map Preview Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">عرض الموقع على الخريطة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="previewMap" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize main map
        var map = L.map('map').setView([28.0339, 1.6596], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        var marker;
        
        // Set initial marker if coordinates exist
        <?php if ($editSite && $editSite['latitude'] && $editSite['longitude']): ?>
            var initialLat = <?= $editSite['latitude'] ?>;
            var initialLng = <?= $editSite['longitude'] ?>;
            map.setView([initialLat, initialLng], 13);
            marker = L.marker([initialLat, initialLng]).addTo(map)
                .bindPopup('<?= addslashes($editSite['name']) ?>')
                .openPopup();
        <?php endif; ?>
        
        // Handle map click to set coordinates
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            
            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);
            
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup('الموقع المحدد: ' + lat.toFixed(6) + ', ' + lng.toFixed(6))
                .openPopup();
        });
        
        function showOnMap(lat, lng, name) {
            var previewMap = L.map('previewMap').setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(previewMap);
            L.marker([lat, lng]).addTo(previewMap)
                .bindPopup(name)
                .openPopup();
            new bootstrap.Modal(document.getElementById('mapModal')).show();
            
            // Clean up when modal is closed
            document.getElementById('mapModal').addEventListener('hidden.bs.modal', function() {
                previewMap.remove();
            });
        }
        
        function removeImage(btn) {
            if (confirm('هل أنت متأكد من إزالة هذه الصورة؟')) {
                const previewDiv = btn.closest('.col-md-6');
                previewDiv.innerHTML = '<input type="hidden" name="existing_image" value=""><span class="text-muted">تمت إزالة الصورة</span>';
            }
        }
        
        function removeVideo(btn) {
            if (confirm('هل أنت متأكد من إزالة هذا الفيديو؟')) {
                const previewDiv = btn.closest('.col-md-6');
                previewDiv.innerHTML = '<input type="hidden" name="existing_video" value=""><span class="text-muted">تمت إزالة الفيديو</span>';
            }
        }
        
        function removeDocument(btn) {
            if (confirm('هل أنت متأكد من إزالة هذه الوثيقة؟')) {
                const previewDiv = btn.closest('.col-md-6');
                previewDiv.innerHTML = '<input type="hidden" name="existing_documents" value=""><span class="text-muted">تمت إزالة الوثيقة</span>';
            }
        }
    </script>
</body>
</html>