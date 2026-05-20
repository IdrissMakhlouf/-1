<?php
/**
 * Admin Dashboard
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// Get statistics
$stats = [];

// Total users
$query = $db->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $query->fetch()['total'];

// Total states
$query = $db->query("SELECT COUNT(*) as total FROM states");
$stats['states'] = $query->fetch()['total'];

// Total heritage sites
$query = $db->query("SELECT COUNT(*) as total FROM heritage_sites");
$stats['sites'] = $query->fetch()['total'];

// Total lessons
$query = $db->query("SELECT COUNT(*) as total FROM lessons");
$stats['lessons'] = $query->fetch()['total'];

// Total hotels
$query = $db->query("SELECT COUNT(*) as total FROM hotels");
$stats['hotels'] = $query->fetch()['total'];

// Total restaurants
$query = $db->query("SELECT COUNT(*) as total FROM restaurants");
$stats['restaurants'] = $query->fetch()['total'];

// Recent users
$recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent heritage sites
$recentSites = $db->query("
    SELECT hs.*, s.name as state_name 
    FROM heritage_sites hs 
    JOIN states s ON hs.state_id = s.state_id 
    ORDER BY hs.site_id DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body {
            background: #f4f6f9;
            direction: rtl;
        }
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #e67e22;
            color: white;
        }
        .sidebar .nav-link i {
            margin-left: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
            color: #e67e22;
        }
        .stat-card p {
            color: #7f8c8d;
            margin: 0;
        }
        .recent-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 10px 20px;
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
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> الرئيسية
                    </a>
                   
                    <a class="nav-link" href="manage_states.php">
                        <i class="fas fa-city"></i> إدارة الولايات
                    </a>
                    <a class="nav-link" href="manage_heritage.php">
                        <i class="fas fa-monument"></i> إدارة المواقع الأثرية
                    </a>
                    <a class="nav-link" href="manage_lessons.php">
                        <i class="fas fa-book"></i> إدارة الدروس
                    </a>
					<a class="nav-link" href="manage_trips.php">
                        <i class="fas fa-book"></i> إدارة الرحلات الذكية
                    </a>
					
					<a class="nav-link" href="manage_archive.php">
                        <i class="fas fa-book"></i> إدارة الأرشيف التاريخي
                    </a>
					
					
					
					
                    <a class="nav-link" href="manage_hotels.php">
                        <i class="fas fa-hotel"></i> إدارة الفنادق
                    </a>
                    <a class="nav-link" href="manage_restaurants.php">
                        <i class="fas fa-utensils"></i> إدارة المطاعم
                    </a>
					
                    <a class="nav-link" href="manage_maps.php">
                        <i class="fas fa-map"></i> إدارة الخرائط
                    </a>
					
					 <a class="nav-link" href="manage_maps.php">
                        <i class="fas fa-map"></i> إدارة الخرائط
                    </a>
					
					
					
					
                    <hr class="my-3">
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> تسجيل خروج
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="navbar-admin mb-4">
                    <h5><i class="fas fa-chart-line"></i> نظرة عامة</h5>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-users fa-2x mb-2" style="color: #3498db;"></i>
                            <h3><?= $stats['users'] ?></h3>
                            <p>المستخدمين</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-city fa-2x mb-2" style="color: #2ecc71;"></i>
                            <h3><?= $stats['states'] ?></h3>
                            <p>الولايات</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-monument fa-2x mb-2" style="color: #e67e22;"></i>
                            <h3><?= $stats['sites'] ?></h3>
                            <p>المواقع الأثرية</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-book fa-2x mb-2" style="color: #9b59b6;"></i>
                            <h3><?= $stats['lessons'] ?></h3>
                            <p>الدروس</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-hotel fa-2x mb-2" style="color: #1abc9c;"></i>
                            <h3><?= $stats['hotels'] ?></h3>
                            <p>الفنادق</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-utensils fa-2x mb-2" style="color: #f39c12;"></i>
                            <h3><?= $stats['restaurants'] ?></h3>
                            <p>المطاعم</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="recent-table">
                    <h5><i class="fas fa-users"></i> آخر المستخدمين</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>اسم المستخدم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الدور</th>
                                    <th>تاريخ التسجيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>">
                                            <?= $user['role'] === 'admin' ? 'مدير' : 'زائر' ?>
                                        </span>
                                    </td>
                                    <td><?= $user['created_at'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Heritage Sites -->
                <div class="recent-table">
                    <h5><i class="fas fa-monument"></i> آخر المواقع الأثرية</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الولاية</th>
                                    <th>الوصف</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSites as $site): ?>
                                <tr>
                                    <td><?= htmlspecialchars($site['name']) ?></td>
                                    <td><?= htmlspecialchars($site['state_name']) ?></td>
                                    <td><?= mb_substr(htmlspecialchars($site['description']), 0, 50) ?>...</td>
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