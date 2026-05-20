<?php
/**
 * My Trips Page - User's Trip Management
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

$user_id = Auth::getCurrentUserId();

// Handle trip deletion
if (isset($_POST['delete_trip'])) {
    $trip_id = intval($_POST['trip_id']);
    $deleteQuery = $db->prepare("DELETE FROM smart_trips WHERE trip_id = ? AND user_id = ?");
    if ($deleteQuery->execute([$trip_id, $user_id])) {
        $_SESSION['success'] = "تم حذف الرحلة بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء الحذف";
    }
    header('Location: my_trips.php');
    exit();
}

// Handle trip cancellation
if (isset($_POST['cancel_trip'])) {
    $trip_id = intval($_POST['trip_id']);
    $cancelQuery = $db->prepare("UPDATE smart_trips SET status = 'cancelled' WHERE trip_id = ? AND user_id = ?");
    if ($cancelQuery->execute([$trip_id, $user_id])) {
        $_SESSION['success'] = "تم إلغاء الرحلة بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء الإلغاء";
    }
    header('Location: my_trips.php');
    exit();
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT st.*, s.name as state_name 
        FROM smart_trips st 
        JOIN states s ON st.state_id = s.state_id 
        WHERE st.user_id = ?";
$params = [$user_id];

if ($filter == 'upcoming') {
    $sql .= " AND st.departure_date > CURDATE()";
} elseif ($filter == 'active') {
    $sql .= " AND st.departure_date <= CURDATE() AND st.return_date >= CURDATE()";
} elseif ($filter == 'completed') {
    $sql .= " AND st.return_date < CURDATE()";
} elseif ($filter == 'cancelled') {
    $sql .= " AND st.status = 'cancelled'";
}

if ($search) {
    $sql .= " AND (st.trip_name LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY st.departure_date DESC, st.created_at DESC";
$tripsQuery = $db->prepare($sql);
$tripsQuery->execute($params);
$trips = $tripsQuery->fetchAll();

// Get statistics
$totalTrips = count($trips);
$upcomingTrips = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ? AND departure_date > CURDATE()");
$upcomingTrips->execute([$user_id]);
$upcomingTrips = $upcomingTrips->fetch()['total'];

$activeTrips = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ? AND departure_date <= CURDATE() AND return_date >= CURDATE()");
$activeTrips->execute([$user_id]);
$activeTrips = $activeTrips->fetch()['total'];

$completedTrips = $db->prepare("SELECT COUNT(*) as total FROM smart_trips WHERE user_id = ? AND return_date < CURDATE()");
$completedTrips->execute([$user_id]);
$completedTrips = $completedTrips->fetch()['total'];

$tripTypes = [
    'cultural' => ['name' => 'سياحة ثقافية', 'icon' => 'fa-landmark', 'color' => 'primary'],
    'adventure' => ['name' => 'سياحة مغامرات', 'icon' => 'fa-hiking', 'color' => 'success'],
    'religious' => ['name' => 'سياحة دينية', 'icon' => 'fa-mosque', 'color' => 'info'],
    'family' => ['name' => 'سياحة عائلية', 'icon' => 'fa-users', 'color' => 'warning'],
    'luxury' => ['name' => 'سياحة فاخرة', 'icon' => 'fa-crown', 'color' => 'danger']
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رحلاتي - <?= SITE_NAME ?></title>
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
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card.active { border-bottom: 3px solid #e67e22; background: #fef9e8; }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: #2c3e50; }
        
        .trip-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .trip-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        .trip-status {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .trip-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .trip-body { padding: 20px; }
        
        .trip-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-btn {
            border-radius: 30px;
            padding: 8px 20px;
            margin: 5px;
        }
        .filter-btn.active { background: #e67e22; color: white; border-color: #e67e22; }
        
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
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> ملفي الشخصي</a></li>
                        <li><a class="dropdown-item active" href="my_trips.php"><i class="fas fa-route"></i> رحلاتي</a></li>
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
            <!-- Page Title -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-route"></i> رحلاتي</h4>
                <a href="explore.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> رحلة جديدة
                </a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <a href="?filter=all" class="text-decoration-none">
                        <div class="stat-card <?= $filter == 'all' ? 'active' : '' ?>">
                            <div class="stat-number"><?= $totalTrips ?></div>
                            <div class="text-muted">جميع الرحلات</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="?filter=upcoming" class="text-decoration-none">
                        <div class="stat-card <?= $filter == 'upcoming' ? 'active' : '' ?>">
                            <div class="stat-number"><?= $upcomingTrips ?></div>
                            <div class="text-muted">قادمة</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="?filter=active" class="text-decoration-none">
                        <div class="stat-card <?= $filter == 'active' ? 'active' : '' ?>">
                            <div class="stat-number"><?= $activeTrips ?></div>
                            <div class="text-muted">نشطة</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="?filter=completed" class="text-decoration-none">
                        <div class="stat-card <?= $filter == 'completed' ? 'active' : '' ?>">
                            <div class="stat-number"><?= $completedTrips ?></div>
                            <div class="text-muted">منتهية</div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="mb-4">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="hidden" name="filter" value="<?= $filter ?>">
                        <input type="text" name="search" class="form-control" placeholder="ابحث عن رحلة..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <?php if ($search): ?>
                            <a href="?filter=<?= $filter ?>" class="btn btn-secondary">إلغاء</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Trips List -->
            <?php if (empty($trips)): ?>
                <div class="text-center py-5 bg-white rounded-3">
                    <i class="fas fa-suitcase-rolling fa-4x text-muted mb-3"></i>
                    <h5>لا توجد رحلات</h5>
                    <p class="text-muted">قم بإنشاء رحلة جديدة لاكتشاف التراث الجزائري</p>
                    <a href="explore.php" class="btn btn-primary mt-2">
                        <i class="fas fa-map-marked-alt"></i> استكشف الآن
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($trips as $trip): 
                    $isUpcoming = $trip['departure_date'] && strtotime($trip['departure_date']) > time();
                    $isActive = $trip['departure_date'] && strtotime($trip['departure_date']) <= time() && 
                                (!$trip['return_date'] || strtotime($trip['return_date']) >= time());
                    $isCompleted = $trip['return_date'] && strtotime($trip['return_date']) < time();
                    $type = $tripTypes[$trip['trip_type']] ?? $tripTypes['cultural'];
                ?>
                <div class="trip-card">
                    <div class="trip-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($trip['trip_name']) ?></h5>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas <?= $type['icon'] ?>"></i> <?= $type['name'] ?>
                                    </span>
                                    <span class="badge bg-light text-dark ms-2">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($trip['state_name']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                            if ($isUpcoming) $statusClass = 'bg-info';
                            elseif ($isActive) $statusClass = 'bg-success';
                            elseif ($isCompleted) $statusClass = 'bg-secondary';
                            else $statusClass = 'bg-danger';
                            ?>
                            <span class="trip-status <?= $statusClass ?>">
                                <?php if ($isUpcoming): ?>
                                    <i class="fas fa-clock"></i> قادمة
                                <?php elseif ($isActive): ?>
                                    <i class="fas fa-play"></i> نشطة
                                <?php elseif ($isCompleted): ?>
                                    <i class="fas fa-check-double"></i> منتهية
                                <?php else: ?>
                                    <i class="fas fa-ban"></i> ملغية
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <div class="trip-body">
                        <div class="trip-meta">
                            <?php if ($trip['departure_date']): ?>
                                <div><i class="fas fa-plane-departure text-primary"></i> الانطلاق: <?= date('d/m/Y', strtotime($trip['departure_date'])) ?></div>
                            <?php endif; ?>
                            <?php if ($trip['return_date']): ?>
                                <div><i class="fas fa-plane-arrival text-success"></i> العودة: <?= date('d/m/Y', strtotime($trip['return_date'])) ?></div>
                            <?php endif; ?>
                            <?php if ($trip['duration_days']): ?>
                                <div><i class="fas fa-calendar-day"></i> <?= $trip['duration_days'] ?> أيام</div>
                            <?php endif; ?>
                            <?php if ($trip['estimated_cost']): ?>
                                <div><i class="fas fa-money-bill-wave"></i> <?= number_format($trip['estimated_cost']) ?> دج</div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($trip['sites']): ?>
                            <div class="mb-2">
                                <i class="fas fa-monument text-primary"></i>
                                <strong>المواقع:</strong> <?= htmlspecialchars($trip['sites']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($trip['hotels']): ?>
                            <div class="mb-2">
                                <i class="fas fa-hotel text-success"></i>
                                <strong>الفنادق:</strong> <?= htmlspecialchars($trip['hotels']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($trip['description']): ?>
                            <div class="mb-3">
                                <i class="fas fa-align-left"></i>
                                <small class="text-muted"><?= nl2br(htmlspecialchars($trip['description'])) ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewTripDetails(<?= htmlspecialchars(json_encode($trip)) ?>)">
                                <i class="fas fa-eye"></i> تفاصيل
                            </button>
                            <?php if ($isUpcoming): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من إلغاء هذه الرحلة؟')">
                                    <input type="hidden" name="trip_id" value="<?= $trip['trip_id'] ?>">
                                    <button type="submit" name="cancel_trip" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times"></i> إلغاء الرحلة
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الرحلة؟')">
                                <input type="hidden" name="trip_id" value="<?= $trip['trip_id'] ?>">
                                <button type="submit" name="delete_trip" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Trip Details Modal -->
<div class="modal fade" id="tripDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الرحلة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tripDetailsContent"></div>
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
function viewTripDetails(trip) {
    const tripTypes = <?= json_encode($tripTypes) ?>;
    const type = tripTypes[trip.trip_type] || tripTypes.cultural;
    
    const content = `
        <div class="row">
            <div class="col-12 mb-3">
                <h4>${trip.trip_name}</h4>
                <p><i class="fas fa-map-marker-alt text-danger"></i> ${trip.state_name}</p>
                <span class="badge bg-${type.color}">
                    <i class="fas ${type.icon}"></i> ${type.name}
                </span>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-calendar-alt"></i> مواعيد الرحلة</h6>
                        <p><strong>الانطلاق:</strong> ${trip.departure_date ? new Date(trip.departure_date).toLocaleDateString('ar') : 'غير محدد'}</p>
                        <p><strong>العودة:</strong> ${trip.return_date ? new Date(trip.return_date).toLocaleDateString('ar') : 'غير محدد'}</p>
                        <p><strong>المدة:</strong> ${trip.duration_days || 'غير محدد'} أيام</p>
                        <p><strong>التكلفة:</strong> ${trip.estimated_cost ? Number(trip.estimated_cost).toLocaleString() + ' دج' : 'غير محدد'}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle"></i> معلومات إضافية</h6>
                        <p><strong>الحد الأقصى للمشاركين:</strong> ${trip.max_participants || 'غير محدد'}</p>
                        <p><strong>نقطة التجمع:</strong> ${trip.meeting_point || 'غير محدد'}</p>
                        ${trip.guide_name ? `<p><strong>المرشد:</strong> ${trip.guide_name} ${trip.guide_phone ? '(' + trip.guide_phone + ')' : ''}</p>` : ''}
                    </div>
                </div>
            </div>
            
            ${trip.sites ? `
            <div class="col-12 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-monument text-primary"></i> المواقع الأثرية</h6>
                        <p>${trip.sites}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${trip.hotels ? `
            <div class="col-12 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-hotel text-success"></i> الفنادق</h6>
                        <p>${trip.hotels}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${trip.restaurants ? `
            <div class="col-12 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-utensils text-warning"></i> المطاعم</h6>
                        <p>${trip.restaurants}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${trip.included_services ? `
            <div class="col-12 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-check-circle text-success"></i> الخدمات المشمولة</h6>
                        <p style="white-space: pre-line;">${trip.included_services}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${trip.description ? `
            <div class="col-12 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-align-left"></i> وصف الرحلة</h6>
                        <p style="white-space: pre-line;">${trip.description}</p>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('tripDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('tripDetailsModal')).show();
}
</script>
</body>
</html>