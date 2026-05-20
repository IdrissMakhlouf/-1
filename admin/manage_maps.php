<?php
/**
 * Manage Interactive Maps - Admin Panel
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Handle delete action
if (isset($_GET['delete'])) {
    $map_id = intval($_GET['delete']);
    $deleteQuery = $db->prepare("DELETE FROM interactive_maps WHERE map_id = ?");
    if ($deleteQuery->execute([$map_id])) {
        $_SESSION['success'] = "تم حذف الخريطة بنجاح";
        header('Location: manage_maps.php');
        exit();
    }
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $map_id = isset($_POST['map_id']) ? intval($_POST['map_id']) : 0;
    $state_id = intval($_POST['state_id']);
    $map_title = trim($_POST['map_title']);
    $map_image_url = trim($_POST['map_image_url']);
    $map_data = trim($_POST['map_data']);
    
    if ($map_id > 0) {
        // Update existing map
        $updateQuery = $db->prepare("UPDATE interactive_maps SET state_id = ?, map_title = ?, map_image_url = ?, map_data = ? WHERE map_id = ?");
        if ($updateQuery->execute([$state_id, $map_title, $map_image_url, $map_data, $map_id])) {
            $_SESSION['success'] = "تم تحديث الخريطة بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // Insert new map
        $insertQuery = $db->prepare("INSERT INTO interactive_maps (state_id, map_title, map_image_url, map_data) VALUES (?, ?, ?, ?)");
        if ($insertQuery->execute([$state_id, $map_title, $map_image_url, $map_data])) {
            $_SESSION['success'] = "تم إضافة الخريطة بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة";
        }
    }
    header('Location: manage_maps.php');
    exit();
}

// Get map for editing
$editMap = null;
if (isset($_GET['edit'])) {
    $map_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM interactive_maps WHERE map_id = ?");
    $editQuery->execute([$map_id]);
    $editMap = $editQuery->fetch();
}

// Get all states for dropdown
$states = $db->query("SELECT state_id, name FROM states ORDER BY name")->fetchAll();

// Get all maps with state names
$maps = $db->query("
    SELECT m.*, s.name as state_name 
    FROM interactive_maps m 
    JOIN states s ON m.state_id = s.state_id 
    ORDER BY m.map_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الخرائط التفاعلية - لوحة التحكم</title>
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
        .map-preview { max-width: 150px; max-height: 80px; object-fit: cover; border-radius: 5px; }
        .json-editor { font-family: monospace; font-size: 12px; }
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
                    <a class="nav-link" href="manage_lessons.php"><i class="fas fa-book"></i> إدارة الدروس</a>
                    <a class="nav-link" href="manage_hotels.php"><i class="fas fa-hotel"></i> إدارة الفنادق</a>
                    <a class="nav-link" href="manage_restaurants.php"><i class="fas fa-utensils"></i> إدارة المطاعم</a>
                    <a class="nav-link active" href="manage_maps.php"><i class="fas fa-map"></i> إدارة الخرائط</a>
                    <a class="nav-link" href="manage_archive.php"><i class="fas fa-history"></i> إدارة الأرشيف</a>
                   
				   <hr>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="content-card">
                    <h5><i class="fas fa-map"></i> إدارة الخرائط التفاعلية</h5>
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
                    <form method="POST" class="mb-4">
                        <?php if ($editMap): ?>
                            <input type="hidden" name="map_id" value="<?= $editMap['map_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الولاية <span class="text-danger">*</span></label>
                                <select name="state_id" class="form-control" required>
                                    <option value="">اختر الولاية</option>
                                    <?php foreach ($states as $state): ?>
                                        <option value="<?= $state['state_id'] ?>" <?= ($editMap && $editMap['state_id'] == $state['state_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($state['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">عنوان الخريطة <span class="text-danger">*</span></label>
                                <input type="text" name="map_title" class="form-control" required 
                                       value="<?= $editMap ? htmlspecialchars($editMap['map_title']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">رابط صورة الخريطة</label>
                            <input type="url" name="map_image_url" class="form-control" 
                                   value="<?= $editMap ? htmlspecialchars($editMap['map_image_url']) : '' ?>"
                                   placeholder="https://example.com/map.jpg">
                            <?php if ($editMap && $editMap['map_image_url']): ?>
                                <div class="mt-2">
                                    <img src="<?= htmlspecialchars($editMap['map_image_url']) ?>" class="map-preview">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">بيانات الخريطة (JSON)</label>
                            <textarea name="map_data" class="form-control json-editor" rows="10" 
                                      placeholder='{"markers": [{"lat": 36.7538, "lng": 3.0588, "title": "موقع أثري", "type": "heritage"}]}'><?= $editMap ? htmlspecialchars($editMap['map_data']) : '' ?></textarea>
                            <small class="text-muted">أدخل بيانات JSON تحتوي على معلومات المواقع على الخريطة (الإحداثيات، العناوين، الأنواع)</small>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $editMap ? 'تحديث' : 'إضافة' ?>
                            </button>
                            <?php if ($editMap): ?>
                                <a href="manage_maps.php" class="btn btn-secondary">إلغاء</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <!-- Maps Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                 <tr>
                                    <th>#</th>
                                    <th>عنوان الخريطة</th>
                                    <th>الولاية</th>
                                    <th>صورة</th>
                                    <th>تاريخ الإضافة</th>
                                    <th>الإجراءات</th>
                                 </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maps as $index => $map): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($map['map_title']) ?></strong></td>
                                    <td><?= htmlspecialchars($map['state_name']) ?></td>
                                    <td>
                                        <?php if ($map['map_image_url']): ?>
                                            <img src="<?= htmlspecialchars($map['map_image_url']) ?>" class="map-preview">
                                        <?php else: ?>
                                            <span class="text-muted">لا توجد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $map['created_at'] ?></td>
                                    <td>
                                        <a href="?edit=<?= $map['map_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                        <a href="?delete=<?= $map['map_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذه الخريطة؟')">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validate JSON format before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            var mapData = document.querySelector('textarea[name="map_data"]').value;
            if (mapData) {
                try {
                    JSON.parse(mapData);
                } catch(e) {
                    alert('تنسيق JSON غير صحيح. يرجى التحقق من البيانات.');
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>