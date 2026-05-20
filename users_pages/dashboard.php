<?php
/**
 * User Dashboard - Complete User Control Panel
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

$user_id = Auth::getCurrentUserId();

// Get user data
$userQuery = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$userQuery->execute([$user_id]);
$user = $userQuery->fetch();

// Get user statistics
$stats = [];

// Total trips
$tripsQuery = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ?");
$tripsQuery->execute([$user_id]);
$stats['total_trips'] = $tripsQuery->fetch()['total'];

// Upcoming trips
$upcomingQuery = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ? AND departure_date > CURDATE()");
$upcomingQuery->execute([$user_id]);
$stats['upcoming_trips'] = $upcomingQuery->fetch()['total'];

// Completed trips
$completedQuery = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ? AND return_date < CURDATE()");
$completedQuery->execute([$user_id]);
$stats['completed_trips'] = $completedQuery->fetch()['total'];

// Favorite states visited
$favStatesQuery = $db->prepare("SELECT COUNT(DISTINCT state_id) as total FROM smart_trips WHERE user_id = ?");
$favStatesQuery->execute([$user_id]);
$stats['states_visited'] = $favStatesQuery->fetch()['total'];

// Recent trips (last 3)
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

// Popular states visited by user
$popularStates = $db->prepare("
    SELECT s.name, COUNT(st.trip_id) as visit_count
    FROM smart_trips st
    JOIN states s ON st.state_id = s.state_id
    WHERE st.user_id = ?
    GROUP BY st.state_id
    ORDER BY visit_count DESC
    LIMIT 5
");
$popularStates->execute([$user_id]);
$popularStates = $popularStates->fetchAll();

// User activity timeline (last 30 days)
$activityQuery = $db->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM smart_trips
    WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$activityQuery->execute([$user_id]);
$activityData = $activityQuery->fetchAll();

// Prepare chart data
$chartDates = [];
$chartCounts = [];
foreach ($activityData as $activity) {
    $chartDates[] = date('d/m', strtotime($activity['date']));
    $chartCounts[] = $activity['count'];
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f0f2f5; direction: rtl; }
        .navbar { background: #2c3e50; }
        .main-wrapper { display: flex; gap: 20px; margin-top: 80px; }
        .sidebar-col { flex: 0 0 280px; }
        .content-col { flex: 1; }
        
        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .dashboard-card .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        .dashboard-card .card-value { font-size: 2rem; font-weight: bold; margin: 10px 0; }
        .dashboard-card .card-title { color: #7f8c8d; font-size: 0.9rem; }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 25px;
        }
        
        /* Activity Chart */
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Trip Card */
        .trip-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            border-right: 3px solid #e67e22;
            transition: all 0.3s;
        }
        .trip-card:hover { background: #fef9e8; transform: translateX(-5px); }
        
        /* State Badge */
        .state-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 25px;
            margin: 5px;
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
				 <li class="nav-item"><a class="nav-link" href="smart_trips.php">الرحلات الذكية</a></li>
                <li class="nav-item"><a class="nav-link" href="archive.php">الأرشيف</a></li>
				
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> ملفي الشخصي</a></li>
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
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="mb-2"><i class="fas fa-hand-peace"></i> مرحباً، <?= htmlspecialchars($user['username']) ?>!</h2>
                        <p class="mb-0">مرحباً بعودتك إلى منصة التراث والتاريخ الجزائري. استكشف تاريخ وثقافة الجزائر العريقة.</p>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                            <span class="mx-2">|</span>
                            <i class="fas fa-calendar-alt"></i> عضو منذ: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                    <div class="text-center mt-3 mt-md-0">
                        <div class="bg-white text-dark rounded-circle p-3 d-inline-flex">
                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="dashboard-card">
                        <div class="card-icon" style="background: #e67e2220; color: #e67e22;">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="card-value"><?= $stats['total_trips'] ?></div>
                        <div class="card-title">إجمالي الرحلات</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="dashboard-card">
                        <div class="card-icon" style="background: #3498db20; color: #3498db;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="card-value"><?= $stats['upcoming_trips'] ?></div>
                        <div class="card-title">رحلات قادمة</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="dashboard-card">
                        <div class="card-icon" style="background: #2ecc7120; color: #2ecc71;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-value"><?= $stats['completed_trips'] ?></div>
                        <div class="card-title">رحلات منتهية</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="dashboard-card">
                        <div class="card-icon" style="background: #9b59b620; color: #9b59b6;">
                            <i class="fas fa-city"></i>
                        </div>
                        <div class="card-value"><?= $stats['states_visited'] ?></div>
                        <div class="card-title">ولايات زارها</div>
                    </div>
                </div>
            </div>
            
            <!-- Activity Chart -->
            <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-line"></i> نشاط الرحلات (آخر 30 يوم)</h5>
                <canvas id="activityChart" height="100"></canvas>
                <?php if (empty($activityData)): ?>
                    <p class="text-muted text-center mt-3">لا توجد نشاطات في الفترة الأخيرة</p>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <!-- Recent Trips -->
                <div class="col-lg-6 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3"><i class="fas fa-clock"></i> آخر الرحلات</h5>
                        <?php if (empty($recentTrips)): ?>
                            <p class="text-muted text-center py-4">لا توجد رحلات مسجلة بعد</p>
                            <div class="text-center">
                                <a href="explore.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-map-marked-alt"></i> اكتشف ولاية جديدة
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentTrips as $trip): ?>
                                <div class="trip-card">
                                    <div class="d-flex justify-content-between align-items-center">
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
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="my_trips.php" class="btn btn-link">عرض جميع الرحلات <i class="fas fa-arrow-left"></i></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Popular States -->
                <div class="col-lg-6 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3"><i class="fas fa-chart-bar"></i> الولايات الأكثر زيارة</h5>
                        <?php if (empty($popularStates)): ?>
                            <p class="text-muted text-center py-4">لا توجد بيانات كافية</p>
                        <?php else: ?>
                            <?php foreach ($popularStates as $state): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span><i class="fas fa-city text-primary"></i> <?= htmlspecialchars($state['name']) ?></span>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="width: 150px; height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: <?= ($state['visit_count'] / $popularStates[0]['visit_count']) * 100 ?>%"></div>
                                        </div>
                                        <span class="badge bg-primary"><?= $state['visit_count'] ?>رحلة</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <h5 class="mb-3"><i class="fas fa-bolt"></i> إجراءات سريعة</h5>
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="explore.php" class="text-decoration-none">
                            <div class="text-center p-3 bg-light rounded-3 hover-effect">
                                <i class="fas fa-map-marked-alt fa-2x text-primary mb-2 d-block"></i>
                                <span class="text-dark">استكشاف ولايات</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="lessons.php" class="text-decoration-none">
                            <div class="text-center p-3 bg-light rounded-3 hover-effect">
                                <i class="fas fa-graduation-cap fa-2x text-success mb-2 d-block"></i>
                                <span class="text-dark">الدروس التعليمية</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="archive.php" class="text-decoration-none">
                            <div class="text-center p-3 bg-light rounded-3 hover-effect">
                                <i class="fas fa-history fa-2x text-warning mb-2 d-block"></i>
                                <span class="text-dark">الأرشيف التاريخي</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="profile.php" class="text-decoration-none">
                            <div class="text-center p-3 bg-light rounded-3 hover-effect">
                                <i class="fas fa-user-edit fa-2x text-info mb-2 d-block"></i>
                                <span class="text-dark">تعديل الملف الشخصي</span>
                            </div>
                        </a>
                    </div>
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
<script>
    // Activity Chart
    <?php if (!empty($activityData)): ?>
    const ctx = document.getElementById('activityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartDates) ?>,
            datasets: [{
                label: 'عدد الرحلات',
                data: <?= json_encode($chartCounts) ?>,
                borderColor: '#e67e22',
                backgroundColor: 'rgba(230, 126, 34, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#e67e22',
                pointBorderColor: '#fff',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: { rtl: true }
            },
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false } },
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
</script>
</body>
</html>