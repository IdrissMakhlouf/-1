<?php
/**
 * Manage States - Admin Panel
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Handle delete action
if (isset($_GET['delete'])) {
    $state_id = intval($_GET['delete']);
    $deleteQuery = $db->prepare("DELETE FROM states WHERE state_id = ?");
    if ($deleteQuery->execute([$state_id])) {
        $_SESSION['success'] = "تم حذف الولاية بنجاح";
        header('Location: manage_states.php');
        exit();
    }
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state_id = isset($_POST['state_id']) ? intval($_POST['state_id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if ($state_id > 0) {
        // Update existing state
        $updateQuery = $db->prepare("UPDATE states SET name = ?, description = ? WHERE state_id = ?");
        if ($updateQuery->execute([$name, $description, $state_id])) {
            $_SESSION['success'] = "تم تحديث الولاية بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // Insert new state
        $insertQuery = $db->prepare("INSERT INTO states (name, description) VALUES (?, ?)");
        if ($insertQuery->execute([$name, $description])) {
            $_SESSION['success'] = "تم إضافة الولاية بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة";
        }
    }
    header('Location: manage_states.php');
    exit();
}

// Get state for editing
$editState = null;
if (isset($_GET['edit'])) {
    $state_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM states WHERE state_id = ?");
    $editQuery->execute([$state_id]);
    $editState = $editQuery->fetch();
}

// Get all states
$states = $db->query("SELECT * FROM states ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الولايات - لوحة التحكم</title>
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
                    <a class="nav-link active" href="manage_states.php"><i class="fas fa-city"></i> إدارة الولايات</a>
                    <a class="nav-link" href="manage_heritage.php"><i class="fas fa-monument"></i> إدارة المواقع الأثرية</a>
                    <a class="nav-link" href="manage_lessons.php"><i class="fas fa-book"></i> إدارة الدروس</a>
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
                    <h5><i class="fas fa-city"></i> إدارة الولايات</h5>
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
                        <?php if ($editState): ?>
                            <input type="hidden" name="state_id" value="<?= $editState['state_id'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">اسم الولاية</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= $editState ? htmlspecialchars($editState['name']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" class="form-control" rows="2"><?= $editState ? htmlspecialchars($editState['description']) : '' ?></textarea>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="fas fa-save"></i> <?= $editState ? 'تحديث' : 'إضافة' ?>
                                </button>
                                <?php if ($editState): ?>
                                    <a href="manage_states.php" class="btn btn-secondary mt-2">إلغاء</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                    
                    <!-- States Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم الولاية</th>
                                    <th>الوصف</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($states as $index => $state): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($state['name']) ?></td>
                                    <td><?= mb_substr(htmlspecialchars($state['description']), 0, 100) ?></td>
                                    <td>
                                        <a href="?edit=<?= $state['state_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                        <a href="?delete=<?= $state['state_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذه الولاية؟')">
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