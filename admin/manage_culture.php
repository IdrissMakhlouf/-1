<?php
/**
 * Manage Local Culture - Admin Panel
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Create upload directories
$uploadDir = '../uploads/culture/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

// Handle delete
if (isset($_GET['delete'])) {
    $culture_id = intval($_GET['delete']);
    $getCulture = $db->prepare("SELECT image_url FROM local_culture WHERE culture_id = ?");
    $getCulture->execute([$culture_id]);
    $culture = $getCulture->fetch();
    if ($culture && $culture['image_url'] && file_exists('../' . $culture['image_url'])) {
        unlink('../' . $culture['image_url']);
    }
    $deleteQuery = $db->prepare("DELETE FROM local_culture WHERE culture_id = ?");
    $deleteQuery->execute([$culture_id]);
    $_SESSION['success'] = "تم الحذف بنجاح";
    header('Location: manage_culture.php');
    exit();
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $culture_id = isset($_POST['culture_id']) ? intval($_POST['culture_id']) : 0;
    $state_id = intval($_POST['state_id']);
    $category = $_POST['category'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    // Handle image upload
    $image_path = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['image_file']['type'], $allowedTypes) && $_FILES['image_file']['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $targetPath = '../uploads/culture/' . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                if ($image_path && file_exists('../' . $image_path)) unlink('../' . $image_path);
                $image_path = 'uploads/culture/' . $filename;
            }
        }
    }
    
    if ($culture_id > 0) {
        $updateQuery = $db->prepare("UPDATE local_culture SET state_id = ?, category = ?, title = ?, description = ?, image_url = ? WHERE culture_id = ?");
        $updateQuery->execute([$state_id, $category, $title, $description, $image_path, $culture_id]);
        $_SESSION['success'] = "تم التحديث بنجاح";
    } else {
        $insertQuery = $db->prepare("INSERT INTO local_culture (state_id, category, title, description, image_url) VALUES (?, ?, ?, ?, ?)");
        $insertQuery->execute([$state_id, $category, $title, $description, $image_path]);
        $_SESSION['success'] = "تم الإضافة بنجاح";
    }
    header('Location: manage_culture.php');
    exit();
}

$editCulture = null;
if (isset($_GET['edit'])) {
    $editQuery = $db->prepare("SELECT * FROM local_culture WHERE culture_id = ?");
    $editQuery->execute([intval($_GET['edit'])]);
    $editCulture = $editQuery->fetch();
}

$states = $db->query("SELECT state_id, name FROM states ORDER BY name")->fetchAll();
$cultures = $db->query("SELECT lc.*, s.name as state_name FROM local_culture lc JOIN states s ON lc.state_id = s.state_id ORDER BY lc.culture_id DESC")->fetchAll();

$categories = [
    'nature' => 'السياحة الطبيعية',
    'traditional_clothes' => 'الملابس التقليدية',
    'traditions' => 'العادات والتقاليد'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الثقافة المحلية - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f4f6f9; }
        .wrapper { display: flex; }
        .main-content { flex: 1; padding: 20px; }
        .content-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-file { position: relative; overflow: hidden; }
        .btn-file input[type=file] { position: absolute; top: 0; right: 0; min-width: 100%; min-height: 100%; font-size: 100px; text-align: right; opacity: 0; cursor: pointer; }
        .preview-image { max-width: 100px; max-height: 60px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'menu.php'; ?>
    <div class="main-content">
        <div class="content-card">
            <h5><i class="fas fa-theater-masks"></i> إدارة الثقافة المحلية</h5>
            <hr>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="mb-4">
                <?php if ($editCulture): ?>
                    <input type="hidden" name="culture_id" value="<?= $editCulture['culture_id'] ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الولاية</label>
                        <select name="state_id" class="form-control" required>
                            <option value="">اختر الولاية</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?= $state['state_id'] ?>" <?= ($editCulture && $editCulture['state_id'] == $state['state_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($state['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">التصنيف</label>
                        <select name="category" class="form-control" required>
                            <option value="">اختر التصنيف</option>
                            <?php foreach ($categories as $key => $value): ?>
                                <option value="<?= $key ?>" <?= ($editCulture && $editCulture['category'] == $key) ? 'selected' : '' ?>>
                                    <?= $value ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">العنوان</label>
                        <input type="text" name="title" class="form-control" required 
                               value="<?= $editCulture ? htmlspecialchars($editCulture['title']) : '' ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3"><?= $editCulture ? htmlspecialchars($editCulture['description']) : '' ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">صورة</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn btn-outline-primary btn-file w-100">
                                <i class="fas fa-cloud-upload-alt"></i> اختر صورة
                                <input type="file" name="image_file" accept="image/*">
                            </div>
                        </div>
                        <?php if ($editCulture && $editCulture['image_url']): ?>
                            <div class="col-md-6">
                                <img src="../<?= $editCulture['image_url'] ?>" class="preview-image">
                                <input type="hidden" name="existing_image" value="<?= $editCulture['image_url'] ?>">
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="existing_image" value="">
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $editCulture ? 'تحديث' : 'إضافة' ?>
                </button>
                <?php if ($editCulture): ?>
                    <a href="manage_culture.php" class="btn btn-secondary">إلغاء</a>
                <?php endif; ?>
            </form>
            
            <hr>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>#</th><th>العنوان</th><th>الولاية</th><th>التصنيف</th><th>الصورة</th><th>الإجراءات</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cultures as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($item['title']) ?></td>
                            <td><?= htmlspecialchars($item['state_name']) ?></td>
                            <td><?= $categories[$item['category']] ?></td>
                            <td><?= $item['image_url'] ? '<i class="fas fa-image text-success"></i>' : '<i class="fas fa-image text-muted"></i>' ?></td>
                            <td>
                                <a href="?edit=<?= $item['culture_id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?= $item['culture_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('تأكيد الحذف؟')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>