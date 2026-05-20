<?php
/**
 * Smart Trips Page - View All Available Smart Trips Created by Admin
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

$user_id = Auth::getCurrentUserId();

// Get all smart trips (admin created) - trips created by admin (user_id is null or 0)
$tripsQuery = $db->query("
    SELECT st.*, s.name as state_name 
    FROM smart_trips st 
    JOIN states s ON st.state_id = s.state_id 
    WHERE (st.user_id IS NULL OR st.user_id = 0) AND (st.departure_date >= CURDATE() OR st.departure_date IS NULL)
    ORDER BY st.departure_date ASC, st.created_at DESC
");
$availableTrips = $tripsQuery->fetchAll();

// Get user's booked trips
$userTripsQuery = $db->prepare("
    SELECT st.*, s.name as state_name 
    FROM smart_trips st 
    JOIN states s ON st.state_id = s.state_id 
    WHERE st.user_id = ? 
    ORDER BY st.departure_date DESC
");
$userTripsQuery->execute([$user_id]);
$userTrips = $userTripsQuery->fetchAll();

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Filter trips
$filteredTrips = $availableTrips;
if ($search) {
    $filteredTrips = array_filter($filteredTrips, function($trip) use ($search) {
        return stripos($trip['trip_name'], $search) !== false || 
               stripos($trip['state_name'], $search) !== false ||
               stripos($trip['description'], $search) !== false;
    });
}

if ($filter == 'upcoming') {
    $filteredTrips = array_filter($filteredTrips, function($trip) {
        return $trip['departure_date'] && strtotime($trip['departure_date']) > time();
    });
} elseif ($filter == 'available') {
    $filteredTrips = array_filter($filteredTrips, function($trip) {
        return !$trip['departure_date'] || strtotime($trip['departure_date']) > time();
    });
}

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
    <title>الرحلات الذكية - <?= SITE_NAME ?></title>
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
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 25px;
        }
        
        .trip-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
        }
        .trip-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        
        .trip-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 10;
        }
        
        .trip-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
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
            text-decoration: none;
            display: inline-block;
        }
        .filter-btn.active { background: #e67e22; color: white; border-color: #e67e22; }
        
        .btn-book {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 25px;
            border-radius: 30px;
            transition: all 0.3s;
        }
        .btn-book:hover { background: #229954; color: white; transform: translateY(-2px); }
        
        .btn-booked {
            background: #95a5a6;
            color: white;
            cursor: default;
        }
        
        .participants-info {
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
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
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> ملفي الشخصي</a></li>
                        <li><a class="dropdown-item" href="my_trips.php"><i class="fas fa-route"></i> رحلاتي</a></li>
                        <li><a class="dropdown-item" href="smart_trips.php"><i class="fas fa-robot"></i> رحلات ذكية</a></li>
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
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="mb-2"><i class="fas fa-robot"></i> الرحلات الذكية</h2>
                        <p class="mb-0">اكتشف الرحلات السياحية المنظمة التي أعدها خبراء السياحة خصيصاً لك</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <span class="badge bg-light text-dark p-2">
                            <i class="fas fa-calendar"></i> <?= count($availableTrips) ?> رحلة متاحة
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter -->
            <div class="bg-white rounded-3 p-3 mb-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <form method="GET" action="">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="ابحث عن رحلة..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                                <?php if ($search): ?>
                                    <a href="smart_trips.php" class="btn btn-secondary">إلغاء</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap justify-content-end">
                            <a href="?filter=all" class="filter-btn <?= $filter == 'all' ? 'active btn-primary' : 'btn-outline-secondary' ?>">
                                <i class="fas fa-list"></i> الكل
                            </a>
                            <a href="?filter=upcoming" class="filter-btn <?= $filter == 'upcoming' ? 'active btn-primary' : 'btn-outline-secondary' ?>">
                                <i class="fas fa-calendar-alt"></i> القادمة
                            </a>
                            <a href="?filter=available" class="filter-btn <?= $filter == 'available' ? 'active btn-primary' : 'btn-outline-secondary' ?>">
                                <i class="fas fa-clock"></i> المتاحة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- My Booked Trips Section -->
            <?php if (!empty($userTrips)): ?>
            <div class="mb-4">
                <h5 class="mb-3"><i class="fas fa-check-circle text-success"></i> رحلاتي المحجوزة</h5>
                <div class="row">
                    <?php foreach (array_slice($userTrips, 0, 2) as $trip): 
                        $type = $tripTypes[$trip['trip_type']] ?? $tripTypes['cultural'];
                        $isUpcoming = $trip['departure_date'] && strtotime($trip['departure_date']) > time();
                    ?>
                    <div class="col-md-6">
                        <div class="trip-card">
                            <div class="trip-header" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($trip['trip_name']) ?></h6>
                                        <small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($trip['state_name']) ?></small>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-check"></i> محجوزة
                                    </span>
                                </div>
                            </div>
                            <div class="trip-body">
                                <div class="trip-meta">
                                    <?php if ($trip['departure_date']): ?>
                                        <div><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($trip['departure_date'])) ?></div>
                                    <?php endif; ?>
                                    <div><i class="fas <?= $type['icon'] ?>"></i> <?= $type['name'] ?></div>
                                </div>
                                <a href="my_trips.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> عرض في رحلاتي
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($userTrips) > 2): ?>
                    <div class="text-center mt-2">
                        <a href="my_trips.php" class="btn btn-link">عرض جميع رحلاتي (<?= count($userTrips) ?>) <i class="fas fa-arrow-left"></i></a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Available Trips Section -->
            <h5 class="mb-3"><i class="fas fa-gem text-warning"></i> رحلات ذكية متاحة للحجز</h5>
            
            <?php if (empty($filteredTrips)): ?>
                <div class="text-center py-5 bg-white rounded-3">
                    <i class="fas fa-suitcase-rolling fa-4x text-muted mb-3"></i>
                    <h5>لا توجد رحلات متاحة حالياً</h5>
                    <p class="text-muted">سيتم إضافة رحلات جديدة قريباً</p>
                    <a href="explore.php" class="btn btn-primary mt-2">
                        <i class="fas fa-map-marked-alt"></i> استكشف الولايات
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($filteredTrips as $trip): 
                        $type = $tripTypes[$trip['trip_type']] ?? $tripTypes['cultural'];
                        $isUpcoming = $trip['departure_date'] && strtotime($trip['departure_date']) > time();
                        $isAvailable = !$trip['departure_date'] || strtotime($trip['departure_date']) > time();
                        $spotsLeft = ($trip['max_participants'] - ($trip['current_bookings'] ?? 0));
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="trip-card">
                            <span class="trip-badge bg-<?= $type['color'] ?>">
                                <i class="fas <?= $type['icon'] ?>"></i> <?= $type['name'] ?>
                            </span>
                            <div class="trip-header">
                                <h6 class="mb-1"><?= htmlspecialchars($trip['trip_name']) ?></h6>
                                <small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($trip['state_name']) ?></small>
                            </div>
                            <div class="trip-body">
                                <div class="trip-meta">
                                    <?php if ($trip['departure_date']): ?>
                                        <div><i class="fas fa-calendar-alt text-primary"></i> <?= date('d/m/Y', strtotime($trip['departure_date'])) ?></div>
                                    <?php endif; ?>
                                    <?php if ($trip['duration_days']): ?>
                                        <div><i class="fas fa-clock"></i> <?= $trip['duration_days'] ?> أيام</div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($trip['estimated_cost']): ?>
                                    <div class="mb-2">
                                        <strong class="text-success"><?= number_format($trip['estimated_cost']) ?> دج</strong>
                                        <small class="text-muted">/ للشخص</small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($trip['sites']): ?>
                                    <div class="mb-2 small">
                                        <i class="fas fa-monument text-primary"></i>
                                        <?= mb_substr(htmlspecialchars($trip['sites']), 0, 50) ?>...
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($trip['max_participants']): ?>
                                    <div class="participants-info mb-3">
                                        <i class="fas fa-users"></i>
                                        <?= $spotsLeft > 0 ? $spotsLeft . ' مقعد متاح' : 'اكتمل العدد' ?>
                                        / <?= $trip['max_participants'] ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="viewTripDetails(<?= htmlspecialchars(json_encode($trip)) ?>)">
                                        <i class="fas fa-eye"></i> تفاصيل
                                    </button>
                                    <?php
                                    // Check if user already booked this trip
                                    $bookedQuery = $db->prepare("SELECT trip_id FROM smart_trips WHERE trip_id = ? AND user_id = ?");
                                    $bookedQuery->execute([$trip['trip_id'], $user_id]);
                                    $isBooked = $bookedQuery->rowCount() > 0;
                                    ?>
                                    <?php if ($isBooked): ?>
                                        <button class="btn btn-sm btn-booked" disabled>
                                            <i class="fas fa-check"></i> محجوزة
                                        </button>
                                    <?php elseif ($spotsLeft <= 0): ?>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-times"></i> اكتمل العدد
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="book_trip.php" style="display: inline;">
                                            <input type="hidden" name="trip_id" value="<?= $trip['trip_id'] ?>">
                                            <button type="submit" name="book_trip" class="btn btn-sm btn-book" onclick="return confirm('هل أنت متأكد من حجز هذه الرحلة؟')">
                                                <i class="fas fa-calendar-plus"></i> احجز الآن
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Trip Details Modal -->
<div class="modal fade" id="tripDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الرحلة الذكية</h5>
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
                        <h6><i class="fas fa-calendar-alt"></i> معلومات الرحلة</h6>
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