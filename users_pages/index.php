<?php
/**
 * User Dashboard / Home Page
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

// Get user statistics
$user_id = Auth::getCurrentUserId();

// Get user's trips count
$tripsQuery = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ?");
$tripsQuery->execute([$user_id]);
$userTrips = $tripsQuery->fetch()['total'];

// Get total states
$statesCount = $db->query("SELECT COUNT(*) as total FROM states")->fetch()['total'];

// Get total heritage sites
$sitesCount = $db->query("SELECT COUNT(*) as total FROM heritage_sites")->fetch()['total'];

// Get recent trips
$recentTrips = $db->prepare("
    SELECT st.*, s.name as state_name 
    FROM smart_trips st 
    JOIN states s ON st.state_id = s.state_id 
    WHERE st.user_id = ? 
    ORDER BY st.created_at DESC 
    LIMIT 3
");
$recentTrips->execute([$user_id]);
$recentTrips = $recentTrips->fetchAll();

// Get featured states
$featuredStates = $db->query("
    SELECT s.*, COUNT(hs.site_id) as sites_count
    FROM states s
    LEFT JOIN heritage_sites hs ON s.state_id = hs.state_id
    GROUP BY s.state_id
    ORDER BY sites_count DESC
    LIMIT 4
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المستخدم - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body {
            background: #f8f9fa;
            direction: rtl;
        }
        .navbar {
            background: #2c3e50;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .main-wrapper {
            display: flex;
            gap: 20px;
            margin-top: 80px;
        }
        .sidebar-col {
            flex: 0 0 280px;
        }
        .content-col {
            flex: 1;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2.5rem;
            color: #e67e22;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
            color: #2c3e50;
        }
        .feature-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .feature-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .feature-card .card-body {
            padding: 15px;
        }
        .trip-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-right: 3px solid #e67e22;
            transition: all 0.3s;
        }
        .trip-card:hover {
            background: #fef9e8;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e67e22;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }
            .sidebar-col {
                flex: auto;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
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
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="explore.php">استكشاف</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="lessons.php">التعليم</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="archive.php">الأرشيف</a>
                </li>
				 <li class="nav-item">
                    <a class="nav-link" href="smart_trips.php">الرحلات الذكية</a>
                </li>
				 <li class="nav-item">
                    <a class="nav-link" href="archive.php">الأرشيف التاريخي</a>
                </li>
			 <li class="nav-item">
                    <a class="nav-link" href="my_trips.php">رحلاتي</a>
                </li>
			
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> ملفي الشخصي</a></li>
                        <li><a class="dropdown-item" href="my_trips.php"><i class="fas fa-route"></i> رحلاتي</a></li>
                        <?php if (Auth::isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../admin/index.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
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
            <!-- Welcome Section -->
            <div class="welcome-card">
                <h2><i class="fas fa-hand-peace"></i> مرحباً، <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
                <p class="mb-0">مرحباً بك في منصة التراث والتاريخ الجزائري. استكشف تاريخ وثقافة الجزائر العريقة.</p>
            </div>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <i class="fas fa-city"></i>
                        <h3><?= $statesCount ?></h3>
                        <p class="text-muted">ولاية جزائرية</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <i class="fas fa-monument"></i>
                        <h3><?= $sitesCount ?></h3>
                        <p class="text-muted">موقع أثري</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <i class="fas fa-route"></i>
                        <h3><?= $userTrips ?></h3>
                        <p class="text-muted">رحلة قمت بها</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Trips -->
            <?php if (!empty($recentTrips)): ?>
            <div class="mb-4">
                <h5 class="section-title"><i class="fas fa-clock"></i> آخر رحلاتي</h5>
                <?php foreach ($recentTrips as $trip): ?>
                <div class="trip-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($trip['trip_name']) ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($trip['state_name']) ?>
                                <?php if ($trip['departure_date']): ?>
                                    | <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($trip['departure_date'])) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <a href="trip_details.php?id=<?= $trip['trip_id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> تفاصيل
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="text-center mt-2">
                    <a href="my_trips.php" class="btn btn-link">عرض جميع الرحلات <i class="fas fa-arrow-left"></i></a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Featured States -->
            <div>
                <h5 class="section-title"><i class="fas fa-star"></i> ولايات مميزة للاستكشاف</h5>
                <div class="row">
                    <?php foreach ($featuredStates as $state): ?>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="feature-card">
                            <?php 
                            $imgQuery = $db->prepare("SELECT image_url FROM heritage_sites WHERE state_id = ? LIMIT 1");
                            $imgQuery->execute([$state['state_id']]);
                            $img = $imgQuery->fetch();
                            $imageUrl = $img ? $img['image_url'] : 'https://via.placeholder.com/400x200?text=' . urlencode($state['name']);
                            ?>
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($state['name']) ?>">
                            <div class="card-body">
                                <h6><?= htmlspecialchars($state['name']) ?></h6>
                                <p class="small text-muted"><?= mb_substr(htmlspecialchars($state['description']), 0, 60) ?>...</p>
                                <a href="state_page.php?id=<?= $state['state_id'] ?>" class="btn btn-sm btn-primary w-100">
                                    استكشف <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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