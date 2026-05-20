<?php
/**
 * Manage Restaurants - Admin Panel
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Handle delete action
if (isset($_GET['delete'])) {
    $restaurant_id = intval($_GET['delete']);
    $deleteQuery = $db->prepare("DELETE FROM restaurants WHERE restaurant_id = ?");
    if ($deleteQuery->execute([$restaurant_id])) {
        $_SESSION['success'] = "تم حذف المطعم بنجاح";
        header('Location: manage_restaurants.php');
        exit();
    }
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
    $state_id = intval($_POST['state_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $cuisine_type = trim($_POST['cuisine_type']);
    $rating = floatval($_POST['rating']);
    $image_url = trim($_POST['image_url']);
    
    if ($restaurant_id > 0) {
        // Update existing restaurant
        $updateQuery = $db->prepare("UPDATE restaurants SET state_id = ?, name = ?, description = ?, address = ?, phone = ?, cuisine_type = ?, rating = ?, image_url = ? WHERE restaurant_id = ?");
        if ($updateQuery->execute([$state_id, $name, $description, $address, $phone, $cuisine_type, $rating, $image_url, $restaurant_id])) {
            $_SESSION['success'] = "تم تحديث المطعم بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // Insert new restaurant
        $insertQuery = $db->prepare("INSERT INTO restaurants (state_id, name, description, address, phone, cuisine_type, rating, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insertQuery->execute([$state_id, $name, $description, $address, $phone, $cuisine_type, $rating, $image_url])) {
            $_SESSION['success'] = "تم إضافة المطعم بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة";
        }
    }
    header('Location: manage_restaurants.php');
    exit();
}

// Get restaurant for editing
$editRestaurant = null;
if (isset($_GET['edit'])) {
    $restaurant_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM restaurants WHERE restaurant_id = ?");
    $editQuery->execute([$restaurant_id]);
    $editRestaurant = $editQuery->fetch();
}

// Get all states for dropdown
$states = $db->query("SELECT state_id, name FROM states ORDER BY name")->fetchAll();

// Get all restaurants with state names
$restaurants = $db->query("
    SELECT r.*, s.name as state_name 
    FROM restaurants r 
    JOIN states s ON r.state_id = s.state_id 
    ORDER BY r.rating DESC, r.restaurant_id DESC
")->fetchAll();

$cuisineTypes = [
    'الجزائرية التقليدية' => 'الجزائرية التقليدية',
    'القسنطينية' => 'القسنطينية',
    'الوهرانية' => 'الوهرانية',
    'الصحراوية' => 'الصحراوية',
    'البحر المتوسط' => 'البحر المتوسط',
    'مشاوي' => 'مشاوي',
    'مأكولات بحرية' => 'مأكولات بحرية'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المطاعم - لوحة التحكم</title>
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
        .restaurant-image { max-width: 80px; max-height: 50px; object-fit: cover; border-radius: 5px; }
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
                    <a class="nav-link active" href="manage_restaurants.php"><i class="fas fa-utensils"></i> إدارة المطاعم</a>
                    <a class="nav-link" href="manage_maps.php"><i class="fas fa-map"></i> إدارة الخرائط</a>
                    <a class="nav-link" href="manage_archive.php"><i class="fas fa-history"></i> إدارة الأرشيف</a>

					<hr>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="content-card">
                    <h5><i class="fas fa-utensils"></i> إدارة المطاعم التقليدية</h5>
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
                        <?php if ($editRestaurant): ?>
                            <input type="hidden" name="restaurant_id" value="<?= $editRestaurant['restaurant_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الولاية <span class="text-danger">*</span></label>
                                <select name="state_id" class="form-control" required>
                                    <option value="">اختر الولاية</option>
                                    <?php foreach ($states as $state): ?>
                                        <option value="<?= $state['state_id'] ?>" <?= ($editRestaurant && $editRestaurant['state_id'] == $state['state_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($state['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم المطعم <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= $editRestaurant ? htmlspecialchars($editRestaurant['name']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="2"><?= $editRestaurant ? htmlspecialchars($editRestaurant['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">العنوان</label>
                                <input type="text" name="address" class="form-control" 
                                       value="<?= $editRestaurant ? htmlspecialchars($editRestaurant['address']) : '' ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= $editRestaurant ? htmlspecialchars($editRestaurant['phone']) : '' ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع المأكولات</label>
                                <select name="cuisine_type" class="form-control">
                                    <option value="">اختر نوع المأكولات</option>
                                    <?php foreach ($cuisineTypes as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= ($editRestaurant && $editRestaurant['cuisine_type'] == $key) ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">التقييم (1-5)</label>
                                <input type="number" name="rating" class="form-control" step="0.1" min="0" max="5" 
                                       value="<?= $editRestaurant ? $editRestaurant['rating'] : '0' ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رابط الصورة</label>
                                <input type="url" name="image_url" class="form-control" 
                                       value="<?= $editRestaurant ? htmlspecialchars($editRestaurant['image_url']) : '' ?>"
                                       placeholder="https://example.com/restaurant.jpg">
                                <?php if ($editRestaurant && $editRestaurant['image_url']): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($editRestaurant['image_url']) ?>" class="restaurant-image">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $editRestaurant ? 'تحديث' : 'إضافة' ?>
                            </button>
                            <?php if ($editRestaurant): ?>
                                <a href="manage_restaurants.php" class="btn btn-secondary">إلغاء</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <!-- Restaurants Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم المطعم</th>
                                    <th>الولاية</th>
                                    <th>نوع المأكولات</th>
                                    <th>التقييم</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restaurants as $index => $restaurant): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($restaurant['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($restaurant['state_name']) ?></td>
                                    <td><?= htmlspecialchars($restaurant['cuisine_type']) ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= floor($restaurant['rating'])): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i - 0.5 <= $restaurant['rating']): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            (<?= $restaurant['rating'] ?>)
                                        </div>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $restaurant['restaurant_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                        <a href="?delete=<?= $restaurant['restaurant_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذا المطعم؟')">
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
</body>
</html>