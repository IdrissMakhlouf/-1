<?php
/**
 * Manage Hotels - Admin Panel (Enhanced with File Upload & Display Below)
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Create upload directories if not exists
$uploadDir = '../uploads/';
$hotelsDir = $uploadDir . 'hotels/';

if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists($hotelsDir)) mkdir($hotelsDir, 0777, true);

// Handle delete action
if (isset($_GET['delete'])) {
    $hotel_id = intval($_GET['delete']);
    
    // Get hotel info to delete image
    $getHotel = $db->prepare("SELECT image_url FROM hotels WHERE hotel_id = ?");
    $getHotel->execute([$hotel_id]);
    $hotel = $getHotel->fetch();
    
    // Delete associated image
    if ($hotel && $hotel['image_url'] && file_exists('../' . $hotel['image_url'])) {
        unlink('../' . $hotel['image_url']);
    }
    
    $deleteQuery = $db->prepare("DELETE FROM hotels WHERE hotel_id = ?");
    if ($deleteQuery->execute([$hotel_id])) {
        $_SESSION['success'] = "تم حذف الفندق بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء الحذف";
    }
    header('Location: manage_hotels.php');
    exit();
}

// Handle file upload function
function uploadHotelImage($file) {
    $targetDir = '../uploads/hotels/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الصورة'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الصورة غير مسموح به. الصور المدعومة: JPG, PNG, GIF, WEBP'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'حجم الصورة كبير جداً. الحد الأقصى 5MB'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'uploads/hotels/' . $filename];
    }
    
    return ['success' => false, 'message' => 'فشل في حفظ الصورة'];
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
    $state_id = intval($_POST['state_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $rating = floatval($_POST['rating']);
    
    // Handle image upload
    $image_path = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadHotelImage($_FILES['image_file']);
        if ($uploadResult['success']) {
            // Delete old image if exists
            if ($image_path && file_exists('../' . $image_path)) {
                unlink('../' . $image_path);
            }
            $image_path = $uploadResult['path'];
        } else {
            $_SESSION['error'] = $uploadResult['message'];
            header('Location: manage_hotels.php');
            exit();
        }
    }
    
    if ($hotel_id > 0) {
        // Update existing hotel
        $updateQuery = $db->prepare("UPDATE hotels SET state_id = ?, name = ?, description = ?, address = ?, phone = ?, rating = ?, image_url = ? WHERE hotel_id = ?");
        if ($updateQuery->execute([$state_id, $name, $description, $address, $phone, $rating, $image_path, $hotel_id])) {
            $_SESSION['success'] = "تم تحديث الفندق بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // Insert new hotel
        $insertQuery = $db->prepare("INSERT INTO hotels (state_id, name, description, address, phone, rating, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($insertQuery->execute([$state_id, $name, $description, $address, $phone, $rating, $image_path])) {
            $_SESSION['success'] = "تم إضافة الفندق بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة";
        }
    }
    header('Location: manage_hotels.php');
    exit();
}

// Get hotel for editing
$editHotel = null;
if (isset($_GET['edit'])) {
    $hotel_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM hotels WHERE hotel_id = ?");
    $editQuery->execute([$hotel_id]);
    $editHotel = $editQuery->fetch();
}

// Get all states for dropdown
$states = $db->query("SELECT state_id, name FROM states ORDER BY name")->fetchAll();

// Get all hotels with state names
$hotels = $db->query("
    SELECT h.*, s.name as state_name 
    FROM hotels h 
    JOIN states s ON h.state_id = s.state_id 
    ORDER BY h.rating DESC, h.hotel_id DESC
")->fetchAll();

// Group hotels by state for better display
$hotelsByState = [];
foreach ($hotels as $hotel) {
    $hotelsByState[$hotel['state_name']][] = $hotel;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفنادق - لوحة التحكم</title>
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
					
						<a class="nav-link" href="manage_trips.php">
                        <i class="fas fa-book"></i> إدارة الرحلات الذكية
                    </a>
					
					<a class="nav-link" href="manage_archive.php">
                        <i class="fas fa-book"></i> إدارة الأرشيف التاريخي
                    </a>
					
                    <a class="nav-link active" href="manage_hotels.php"><i class="fas fa-hotel"></i> إدارة الفنادق</a>
                    <a class="nav-link" href="manage_restaurants.php"><i class="fas fa-utensils"></i> إدارة المطاعم</a>
                    <a class="nav-link" href="manage_maps.php"><i class="fas fa-map"></i> إدارة الخرائط</a>
					<a class="nav-link" href="manage_archive.php"><i class="fas fa-history"></i> إدارة الأرشيف</a>

                    <hr>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Add/Edit Form Section -->
                <div class="content-card">
                    <h5><i class="fas fa-hotel"></i> <?= $editHotel ? 'تعديل الفندق' : 'إضافة فندق جديد' ?></h5>
                    <hr>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($editHotel): ?>
                            <input type="hidden" name="hotel_id" value="<?= $editHotel['hotel_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الولاية <span class="text-danger">*</span></label>
                                <select name="state_id" class="form-control" required>
                                    <option value="">اختر الولاية</option>
                                    <?php foreach ($states as $state): ?>
                                        <option value="<?= $state['state_id'] ?>" <?= ($editHotel && $editHotel['state_id'] == $state['state_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($state['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم الفندق <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= $editHotel ? htmlspecialchars($editHotel['name']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"><?= $editHotel ? htmlspecialchars($editHotel['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">العنوان</label>
                                <input type="text" name="address" class="form-control" 
                                       value="<?= $editHotel ? htmlspecialchars($editHotel['address']) : '' ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= $editHotel ? htmlspecialchars($editHotel['phone']) : '' ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">التقييم (1-5)</label>
                                <input type="number" name="rating" class="form-control" step="0.1" min="0" max="5" 
                                       value="<?= $editHotel ? $editHotel['rating'] : '0' ?>">
                            </div>
                        </div>
                        
                        <!-- Image Upload Section -->
                        <div class="mb-3">
                            <label class="form-label">صورة الفندق</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn btn-outline-primary btn-file w-100">
                                        <i class="fas fa-cloud-upload-alt"></i> اختر صورة
                                        <input type="file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp">
                                    </div>
                                    <small class="text-muted">الصور المدعومة: JPG, PNG, GIF, WEBP (الحد الأقصى 5MB)</small>
                                </div>
                                <?php if ($editHotel && $editHotel['image_url']): ?>
                                    <div class="col-md-6">
                                        <div class="file-preview">
                                            <strong>الصورة الحالية:</strong>
                                            <img src="../<?= htmlspecialchars($editHotel['image_url']) ?>" class="hotel-image d-block mt-2" style="max-width: 150px; max-height: 100px;">
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editHotel['image_url']) ?>">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeImage(this)">إزالة الصورة</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="existing_image" value="">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $editHotel ? 'تحديث' : 'إضافة' ?>
                            </button>
                            <?php if ($editHotel): ?>
                                <a href="manage_hotels.php" class="btn btn-secondary">إلغاء</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Hotels Display Section (Grouped by State) -->
                <div class="content-card">
                    <h5><i class="fas fa-list"></i> قائمة الفنادق المسجلة</h5>
                    <hr>
                    
                    <?php if (empty($hotels)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i> لا توجد فنادق مسجلة حتى الآن. أضف فندقاً جديداً من النموذج أعلاه.
                        </div>
                    <?php else: ?>
                        <?php foreach ($hotelsByState as $stateName => $stateHotels): ?>
                            <div class="state-section">
                                <h4><i class="fas fa-city"></i> <?= htmlspecialchars($stateName) ?></h4>
                                <div class="row">
                                    <?php foreach ($stateHotels as $hotel): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="hotel-card">
                                            <?php if ($hotel['image_url']): ?>
                                                <img src="../<?= htmlspecialchars($hotel['image_url']) ?>" alt="<?= htmlspecialchars($hotel['name']) ?>" onclick="window.open('../<?= $hotel['image_url'] ?>')" style="cursor: pointer;">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/400x200?text=<?= urlencode($hotel['name']) ?>" alt="<?= htmlspecialchars($hotel['name']) ?>">
                                            <?php endif; ?>
                                            <div class="card-content">
                                                <h5><?= htmlspecialchars($hotel['name']) ?></h5>
                                                <p class="text-muted small"><?= mb_substr(htmlspecialchars($hotel['description']), 0, 80) ?>...</p>
                                                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($hotel['address']) ?></p>
                                                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($hotel['phone']) ?></p>
                                                <div class="rating-stars mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= floor($hotel['rating'])): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php elseif ($i - 0.5 <= $hotel['rating']): ?>
                                                            <i class="fas fa-star-half-alt"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    <span class="text-muted">(<?= $hotel['rating'] ?>)</span>
                                                </div>
                                                <div class="btn-group w-100">
                                                    <a href="?edit=<?= $hotel['hotel_id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> تعديل
                                                    </a>
                                                    <a href="?delete=<?= $hotel['hotel_id'] ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('هل أنت متأكد من حذف هذا الفندق؟')">
                                                        <i class="fas fa-trash"></i> حذف
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function removeImage(btn) {
            if (confirm('هل أنت متأكد من إزالة هذه الصورة؟')) {
                const previewDiv = btn.closest('.col-md-6');
                previewDiv.innerHTML = '<input type="hidden" name="existing_image" value=""><span class="text-muted">تمت إزالة الصورة</span>';
            }
        }
    </script>
</body>
</html>