<?php
/**
 * User Profile Page
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

$user_id = Auth::getCurrentUserId();
$error = '';
$success = '';

// Get user data
$userQuery = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$userQuery->execute([$user_id]);
$user = $userQuery->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    if (empty($username) || empty($email)) {
        $error = "جميع الحقول مطلوبة";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "البريد الإلكتروني غير صحيح";
    } else {
        // Check if username or email already exists for other users
        $checkQuery = $db->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $checkQuery->execute([$username, $email, $user_id]);
        
        if ($checkQuery->rowCount() > 0) {
            $error = "اسم المستخدم أو البريد الإلكتروني مستخدم من قبل";
        } else {
            $updateQuery = $db->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            if ($updateQuery->execute([$username, $email, $user_id])) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $success = "تم تحديث الملف الشخصي بنجاح";
                // Refresh user data
                $userQuery->execute([$user_id]);
                $user = $userQuery->fetch();
            } else {
                $error = "حدث خطأ أثناء تحديث الملف الشخصي";
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "جميع الحقول مطلوبة";
    } elseif (strlen($new_password) < 6) {
        $error = "كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل";
    } elseif ($new_password !== $confirm_password) {
        $error = "كلمات المرور غير متطابقة";
    } else {
        // Verify current password
        $passQuery = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $passQuery->execute([$user_id]);
        $userData = $passQuery->fetch();
        
        if (password_verify($current_password, $userData['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updatePass = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            if ($updatePass->execute([$new_hash, $user_id])) {
                $success = "تم تغيير كلمة المرور بنجاح";
            } else {
                $error = "حدث خطأ أثناء تغيير كلمة المرور";
            }
        } else {
            $error = "كلمة المرور الحالية غير صحيحة";
        }
    }
}

// Get user statistics
$stats = [];

// Total trips
$tripsQuery = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ?");
$tripsQuery->execute([$user_id]);
$stats['total_trips'] = $tripsQuery->fetch()['total'];

// Total states visited
$statesQuery = $db->prepare("SELECT COUNT(DISTINCT state_id) as total FROM smart_trips WHERE user_id = ?");
$statesQuery->execute([$user_id]);
$stats['states_visited'] = $statesQuery->fetch()['total'];

// Member since
$memberSince = date('d/m/Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f0f2f5; direction: rtl; }
        .navbar { background: #2c3e50; }
        .main-wrapper { display: flex; gap: 20px; margin-top: 80px; }
        .sidebar-col { flex: 0 0 280px; }
        .content-col { flex: 1; }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .profile-avatar i { font-size: 3rem; color: #667eea; }
        
        .profile-body { padding: 25px; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-badge {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }
        
        .nav-tabs .nav-link {
            color: #2c3e50;
            border: none;
            padding: 12px 25px;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #e67e22;
            border-bottom: 3px solid #e67e22;
            background: transparent;
        }
        
        @media (max-width: 768px) {
            .main-wrapper { flex-direction: column; }
            .sidebar-col { flex: auto; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-landmark"></i> <?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="explore.php">استكشاف</a></li>
                <li class="nav-item"><a class="nav-link" href="lessons.php">التعليم</a></li>
                <li class="nav-item"><a class="nav-link" href="archive.php">الأرشيف</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                        <li><a class="dropdown-item active" href="profile.php"><i class="fas fa-user"></i> ملفي الشخصي</a></li>
                        <li><a class="dropdown-item" href="my_trips.php"><i class="fas fa-route"></i> رحلاتي</a></li>
                        <?php if (Auth::isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../admin/index.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم (مدير)</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="main-wrapper">
        <!-- Sidebar -->
        <div class="sidebar-col">
            <?php include 'menu.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="content-col">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3><?= htmlspecialchars($user['username']) ?></h3>
                    <p class="mb-0"><?= $user['role'] == 'admin' ? 'مدير النظام' : 'عضو في المنصة' ?></p>
                </div>
                <div class="profile-body">
                    <!-- Statistics -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="stat-badge">
                                <i class="fas fa-route fa-2x text-primary mb-2 d-block"></i>
                                <h5 class="mb-0"><?= $stats['total_trips'] ?></h5>
                                <small class="text-muted">رحلات قمت بها</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-badge">
                                <i class="fas fa-city fa-2x text-success mb-2 d-block"></i>
                                <h5 class="mb-0"><?= $stats['states_visited'] ?></h5>
                                <small class="text-muted">ولاية زرتها</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-badge">
                                <i class="fas fa-calendar-alt fa-2x text-warning mb-2 d-block"></i>
                                <h5 class="mb-0"><?= $memberSince ?></h5>
                                <small class="text-muted">عضو منذ</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                <i class="fas fa-info-circle"></i> معلومات الحساب
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                                <i class="fas fa-lock"></i> تغيير كلمة المرور
                            </button>
                        </li>
                    </ul>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <div class="tab-content">
                        <!-- Profile Info Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="mb-3">
                                    <label class="form-label">اسم المستخدم</label>
                                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($user['username']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">الدور</label>
                                    <input type="text" class="form-control" value="<?= $user['role'] == 'admin' ? 'مدير' : 'زائر' ?>" disabled>
                                    <small class="text-muted">لا يمكن تغيير الدور</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">تاريخ التسجيل</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>" disabled>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="change_password" value="1">
                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور الحالية</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور الجديدة</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                    <small class="text-muted">يجب أن تكون 6 أحرف على الأقل</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i> تغيير كلمة المرور
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="profile-card">
                <div class="profile-body">
                    <h5 class="mb-3"><i class="fas fa-history"></i> آخر النشاطات</h5>
                    <?php
                    $activityQuery = $db->prepare("
                        SELECT st.*, s.name as state_name 
                        FROM smart_trips st 
                        JOIN states s ON st.state_id = s.state_id 
                        WHERE st.user_id = ? 
                        ORDER BY st.created_at DESC 
                        LIMIT 5
                    ");
                    $activityQuery->execute([$user_id]);
                    $activities = $activityQuery->fetchAll();
                    ?>
                    
                    <?php if (empty($activities)): ?>
                        <p class="text-muted text-center py-3">لا توجد نشاطات حتى الآن</p>
                        <div class="text-center">
                            <a href="explore.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-map-marked-alt"></i> ابدأ استكشاف الجزائر
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <i class="fas fa-route text-primary"></i>
                                    <strong><?= htmlspecialchars($activity['trip_name']) ?></strong>
                                    <div class="small text-muted">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($activity['state_name']) ?>
                                        <?php if ($activity['departure_date']): ?>
                                            | <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($activity['departure_date'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge bg-secondary"><?= date('d/m/Y', strtotime($activity['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="my_trips.php" class="btn btn-link">عرض جميع الرحلات <i class="fas fa-arrow-left"></i></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; 2024 منصة التراث والتاريخ الجزائري. جميع الحقوق محفوظة.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>