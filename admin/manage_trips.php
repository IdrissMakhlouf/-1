<?php
/**
 * Manage Smart Trips - Admin Panel (Fixed Version)
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check admin access
Auth::requireAdmin();

// First, check and add all required columns if they don't exist
try {
    // Check if duration_days column exists
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'duration_days'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN duration_days INT NULL AFTER restaurants");
        echo "<script>console.log('Added duration_days column');</script>";
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'estimated_cost'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN estimated_cost DECIMAL(10,2) NULL AFTER duration_days");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'description'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN description TEXT NULL AFTER estimated_cost");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'departure_date'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN departure_date DATE NULL AFTER description");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'return_date'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN return_date DATE NULL AFTER departure_date");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'trip_type'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN trip_type ENUM('cultural', 'adventure', 'religious', 'family', 'luxury') DEFAULT 'cultural' AFTER return_date");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'included_services'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN included_services TEXT NULL AFTER trip_type");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'excluded_services'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN excluded_services TEXT NULL AFTER included_services");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'meeting_point'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN meeting_point VARCHAR(255) NULL AFTER excluded_services");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'guide_name'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN guide_name VARCHAR(100) NULL AFTER meeting_point");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'guide_phone'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN guide_phone VARCHAR(50) NULL AFTER guide_name");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'max_participants'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN max_participants INT DEFAULT 20 AFTER guide_phone");
    }
    
    $checkColumns = $db->query("SHOW COLUMNS FROM smart_trips LIKE 'current_bookings'");
    if ($checkColumns->rowCount() == 0) {
        $db->exec("ALTER TABLE smart_trips ADD COLUMN current_bookings INT DEFAULT 0 AFTER max_participants");
    }
    
} catch (PDOException $e) {
    // Log error but continue
    error_log("Database alter error: " . $e->getMessage());
}

// Handle delete action
if (isset($_GET['delete'])) {
    $trip_id = intval($_GET['delete']);
    $deleteQuery = $db->prepare("DELETE FROM smart_trips WHERE trip_id = ?");
    if ($deleteQuery->execute([$trip_id])) {
        $_SESSION['success'] = "تم حذف الرحلة الذكية بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء الحذف";
    }
    header('Location: manage_trips.php');
    exit();
}

// Handle manual trip creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_trip'])) {
    $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $state_id = intval($_POST['state_id']);
    $trip_name = trim($_POST['trip_name']);
    $sites = trim($_POST['sites']);
    $hotels = trim($_POST['hotels']);
    $restaurants = trim($_POST['restaurants']);
    $duration_days = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : null;
    $estimated_cost = !empty($_POST['estimated_cost']) ? floatval($_POST['estimated_cost']) : null;
    $description = trim($_POST['description']);
    $departure_date = !empty($_POST['departure_date']) ? $_POST['departure_date'] : null;
    $return_date = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
    $trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : 'cultural';
    $included_services = trim($_POST['included_services']);
    $excluded_services = trim($_POST['excluded_services']);
    $meeting_point = trim($_POST['meeting_point']);
    $guide_name = trim($_POST['guide_name']);
    $guide_phone = trim($_POST['guide_phone']);
    $max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : 20;
    
    // Validate dates
    if ($departure_date && $return_date && $return_date < $departure_date) {
        $_SESSION['error'] = "تاريخ العودة يجب أن يكون بعد تاريخ الانطلاق";
        header('Location: manage_trips.php');
        exit();
    }
    
    // Calculate duration days if dates are provided
    if ($departure_date && $return_date && !$duration_days) {
        $start = new DateTime($departure_date);
        $end = new DateTime($return_date);
        $interval = $start->diff($end);
        $duration_days = $interval->days + 1;
    }
    
    // Check if we need to auto-generate suggestions
    $auto_generate = isset($_POST['auto_generate']);
    
    if ($auto_generate) {
        // Auto-generate trip based on state
        $sitesList = [];
        $hotelsList = [];
        $restaurantsList = [];
        
        // Get top heritage sites
        $sitesQuery = $db->prepare("SELECT name FROM heritage_sites WHERE state_id = ? ORDER BY site_id LIMIT 3");
        $sitesQuery->execute([$state_id]);
        $sitesList = $sitesQuery->fetchAll();
        $sites = implode('، ', array_column($sitesList, 'name'));
        
        // Get top hotels
        $hotelsQuery = $db->prepare("SELECT name FROM hotels WHERE state_id = ? ORDER BY rating DESC LIMIT 2");
        $hotelsQuery->execute([$state_id]);
        $hotelsList = $hotelsQuery->fetchAll();
        $hotels = implode('، ', array_column($hotelsList, 'name'));
        
        // Get top restaurants
        $restQuery = $db->prepare("SELECT name FROM restaurants WHERE state_id = ? ORDER BY rating DESC LIMIT 2");
        $restQuery->execute([$state_id]);
        $restList = $restQuery->fetchAll();
        $restaurants = implode('، ', array_column($restList, 'name'));
        
        // Generate trip name
        $stateQuery = $db->prepare("SELECT name FROM states WHERE state_id = ?");
        $stateQuery->execute([$state_id]);
        $stateName = $stateQuery->fetchColumn();
        $trip_name = "رحلة سياحية في ولاية " . $stateName;
    }
    
    // Build the insert query dynamically based on available columns
    $columns = ['user_id', 'state_id', 'trip_name', 'sites', 'hotels', 'restaurants', 'created_at'];
    $values = [$user_id, $state_id, $trip_name, $sites, $hotels, $restaurants, date('Y-m-d H:i:s')];
    
    // Add optional fields if they have values
    if ($duration_days !== null) {
        $columns[] = 'duration_days';
        $values[] = $duration_days;
    }
    if ($estimated_cost !== null) {
        $columns[] = 'estimated_cost';
        $values[] = $estimated_cost;
    }
    if ($description !== null && $description !== '') {
        $columns[] = 'description';
        $values[] = $description;
    }
    if ($departure_date !== null) {
        $columns[] = 'departure_date';
        $values[] = $departure_date;
    }
    if ($return_date !== null) {
        $columns[] = 'return_date';
        $values[] = $return_date;
    }
    if ($trip_type !== null) {
        $columns[] = 'trip_type';
        $values[] = $trip_type;
    }
    if ($included_services !== null && $included_services !== '') {
        $columns[] = 'included_services';
        $values[] = $included_services;
    }
    if ($excluded_services !== null && $excluded_services !== '') {
        $columns[] = 'excluded_services';
        $values[] = $excluded_services;
    }
    if ($meeting_point !== null && $meeting_point !== '') {
        $columns[] = 'meeting_point';
        $values[] = $meeting_point;
    }
    if ($guide_name !== null && $guide_name !== '') {
        $columns[] = 'guide_name';
        $values[] = $guide_name;
    }
    if ($guide_phone !== null && $guide_phone !== '') {
        $columns[] = 'guide_phone';
        $values[] = $guide_phone;
    }
    if ($max_participants !== null) {
        $columns[] = 'max_participants';
        $values[] = $max_participants;
    }
    
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $columnsList = implode(', ', $columns);
    
    $sql = "INSERT INTO smart_trips ($columnsList) VALUES ($placeholders)";
    $insertQuery = $db->prepare($sql);
    
    if ($insertQuery->execute($values)) {
        $_SESSION['success'] = "تم إنشاء الرحلة الذكية بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء إنشاء الرحلة: " . implode(', ', $insertQuery->errorInfo());
    }
    header('Location: manage_trips.php');
    exit();
}

// Handle edit trip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_trip'])) {
    $trip_id = intval($_POST['trip_id']);
    $trip_name = trim($_POST['trip_name']);
    $sites = trim($_POST['sites']);
    $hotels = trim($_POST['hotels']);
    $restaurants = trim($_POST['restaurants']);
    $duration_days = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : null;
    $estimated_cost = !empty($_POST['estimated_cost']) ? floatval($_POST['estimated_cost']) : null;
    $description = trim($_POST['description']);
    $departure_date = !empty($_POST['departure_date']) ? $_POST['departure_date'] : null;
    $return_date = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
    $trip_type = isset($_POST['trip_type']) ? $_POST['trip_type'] : 'cultural';
    $included_services = trim($_POST['included_services']);
    $excluded_services = trim($_POST['excluded_services']);
    $meeting_point = trim($_POST['meeting_point']);
    $guide_name = trim($_POST['guide_name']);
    $guide_phone = trim($_POST['guide_phone']);
    $max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : 20;
    
    // Validate dates
    if ($departure_date && $return_date && $return_date < $departure_date) {
        $_SESSION['error'] = "تاريخ العودة يجب أن يكون بعد تاريخ الانطلاق";
        header('Location: manage_trips.php');
        exit();
    }
    
    // Calculate duration days if dates are provided
    if ($departure_date && $return_date && !$duration_days) {
        $start = new DateTime($departure_date);
        $end = new DateTime($return_date);
        $interval = $start->diff($end);
        $duration_days = $interval->days + 1;
    }
    
    // Build the update query dynamically
    $updateFields = [];
    $values = [];
    
    $updateFields[] = "trip_name = ?";
    $values[] = $trip_name;
    
    $updateFields[] = "sites = ?";
    $values[] = $sites;
    
    $updateFields[] = "hotels = ?";
    $values[] = $hotels;
    
    $updateFields[] = "restaurants = ?";
    $values[] = $restaurants;
    
    if ($duration_days !== null) {
        $updateFields[] = "duration_days = ?";
        $values[] = $duration_days;
    }
    
    if ($estimated_cost !== null) {
        $updateFields[] = "estimated_cost = ?";
        $values[] = $estimated_cost;
    }
    
    if ($description !== null && $description !== '') {
        $updateFields[] = "description = ?";
        $values[] = $description;
    }
    
    if ($departure_date !== null) {
        $updateFields[] = "departure_date = ?";
        $values[] = $departure_date;
    }
    
    if ($return_date !== null) {
        $updateFields[] = "return_date = ?";
        $values[] = $return_date;
    }
    
    if ($trip_type !== null) {
        $updateFields[] = "trip_type = ?";
        $values[] = $trip_type;
    }
    
    if ($included_services !== null && $included_services !== '') {
        $updateFields[] = "included_services = ?";
        $values[] = $included_services;
    }
    
    if ($excluded_services !== null && $excluded_services !== '') {
        $updateFields[] = "excluded_services = ?";
        $values[] = $excluded_services;
    }
    
    if ($meeting_point !== null && $meeting_point !== '') {
        $updateFields[] = "meeting_point = ?";
        $values[] = $meeting_point;
    }
    
    if ($guide_name !== null && $guide_name !== '') {
        $updateFields[] = "guide_name = ?";
        $values[] = $guide_name;
    }
    
    if ($guide_phone !== null && $guide_phone !== '') {
        $updateFields[] = "guide_phone = ?";
        $values[] = $guide_phone;
    }
    
    if ($max_participants !== null) {
        $updateFields[] = "max_participants = ?";
        $values[] = $max_participants;
    }
    
    $values[] = $trip_id;
    $sql = "UPDATE smart_trips SET " . implode(', ', $updateFields) . " WHERE trip_id = ?";
    $updateQuery = $db->prepare($sql);
    
    if ($updateQuery->execute($values)) {
        $_SESSION['success'] = "تم تحديث الرحلة بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء التحديث";
    }
    header('Location: manage_trips.php');
    exit();
}

// Get trip for editing
$editTrip = null;
if (isset($_GET['edit'])) {
    $trip_id = intval($_GET['edit']);
    $editQuery = $db->prepare("SELECT * FROM smart_trips WHERE trip_id = ?");
    $editQuery->execute([$trip_id]);
    $editTrip = $editQuery->fetch();
}

// Get all states for dropdown
$states = $db->query("SELECT state_id, name FROM states ORDER BY name")->fetchAll();

// Get all users for dropdown
$users = $db->query("SELECT user_id, username, email FROM users ORDER BY username")->fetchAll();

// Get all trips with user and state info
$trips = $db->query("
    SELECT st.*, 
           u.username, u.email,
           s.name as state_name
    FROM smart_trips st
    LEFT JOIN users u ON st.user_id = u.user_id
    LEFT JOIN states s ON st.state_id = s.state_id
    ORDER BY st.created_at DESC
")->fetchAll();

// Get statistics
$totalTrips = count($trips);
$upcomingTrips = count(array_filter($trips, function($t) { 
    return $t['departure_date'] && strtotime($t['departure_date']) > time(); 
}));
$activeTrips = count(array_filter($trips, function($t) { 
    return $t['departure_date'] && strtotime($t['departure_date']) <= time() && 
           (!$t['return_date'] || strtotime($t['return_date']) >= time()); 
}));

$tripTypes = [
    'cultural' => 'سياحة ثقافية',
    'adventure' => 'سياحة مغامرات',
    'religious' => 'سياحة دينية',
    'family' => 'سياحة عائلية',
    'luxury' => 'سياحة فاخرة'
];

$tripTypeColors = [
    'cultural' => 'primary',
    'adventure' => 'success',
    'religious' => 'info',
    'family' => 'warning',
    'luxury' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الرحلات الذكية - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { display: flex; }
        .main-content { flex: 1; padding: 20px; }
        .content-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 2rem; margin: 0; }
        .stat-card p { margin: 0; opacity: 0.9; }
        .trip-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-right: 4px solid #e67e22;
        }
        .trip-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .trip-title { font-size: 1.2rem; font-weight: bold; color: #2c3e50; }
        .trip-state { color: #e67e22; font-size: 0.9rem; }
        .trip-details { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .badge-type { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .trip-meta { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px; }
        .trip-meta span { font-size: 0.85rem; color: #7f8c8d; }
        .trip-meta i { margin-left: 5px; }
        .auto-generate-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .auto-generate-btn:hover { background: #229954; }
        .date-badge {
            background: #e8f0fe;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .participants-info {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
<div class="wrapper">


    <?php include 'menu.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="fas fa-route fa-2x mb-2"></i>
                    <h3><?= $totalTrips ?></h3>
                    <p>إجمالي الرحلات</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                    <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                    <h3><?= $upcomingTrips ?></h3>
                    <p>رحلات قادمة</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
                    <i class="fas fa-play-circle fa-2x mb-2"></i>
                    <h3><?= $activeTrips ?></h3>
                    <p>رحلات نشطة</p>
                </div>
            </div>
        </div>
        
        <!-- Create New Trip Form -->
        <div class="content-card">
            <h5><i class="fas fa-plus-circle"></i> <?= $editTrip ? 'تعديل الرحلة' : 'إنشاء رحلة ذكية جديدة' ?></h5>
            <hr>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php if ($editTrip): ?>
                    <input type="hidden" name="edit_trip" value="1">
                    <input type="hidden" name="trip_id" value="<?= $editTrip['trip_id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="create_trip" value="1">
                <?php endif; ?>
                
                <!-- Basic Information -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الولاية <span class="text-danger">*</span></label>
                        <select name="state_id" class="form-control" required id="stateSelect">
                            <option value="">اختر الولاية</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?= $state['state_id'] ?>" <?= ($editTrip && $editTrip['state_id'] == $state['state_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($state['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم الرحلة <span class="text-danger">*</span></label>
                        <input type="text" name="trip_name" class="form-control" required 
                               value="<?= $editTrip ? htmlspecialchars($editTrip['trip_name']) : '' ?>">
                    </div>
                </div>
                
                <!-- Trip Dates -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">موعد الانطلاق <span class="text-danger">*</span></label>
                        <input type="date" name="departure_date" class="form-control" required
                               value="<?= $editTrip && $editTrip['departure_date'] ? $editTrip['departure_date'] : '' ?>"
                               min="<?= date('Y-m-d') ?>">
                        <small class="text-muted">تاريخ بداية الرحلة</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">موعد العودة <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control" required
                               value="<?= $editTrip && $editTrip['return_date'] ? $editTrip['return_date'] : '' ?>"
                               min="<?= date('Y-m-d') ?>">
                        <small class="text-muted">تاريخ نهاية الرحلة</small>
                    </div>
                </div>
                
                <!-- Trip Type & Duration -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">نوع الرحلة</label>
                        <select name="trip_type" class="form-control">
                            <?php foreach ($tripTypes as $key => $value): ?>
                                <option value="<?= $key ?>" <?= ($editTrip && $editTrip['trip_type'] == $key) ? 'selected' : ($key == 'cultural' && !$editTrip ? 'selected' : '') ?>>
                                    <?= $value ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">عدد أيام الرحلة</label>
                        <input type="number" name="duration_days" class="form-control" min="1" max="30" readonly
                               value="<?= $editTrip && $editTrip['duration_days'] ? $editTrip['duration_days'] : '' ?>" id="durationDays">
                        <small class="text-muted">يتم حسابها تلقائياً من تاريخ الانطلاق والعودة</small>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">التكلفة التقديرية (دينار جزائري)</label>
                        <input type="number" name="estimated_cost" class="form-control" step="1000" min="0"
                               value="<?= $editTrip && $editTrip['estimated_cost'] ? $editTrip['estimated_cost'] : '' ?>">
                    </div>
                </div>
                
                <!-- Participant Information -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الحد الأقصى للمشاركين</label>
                        <input type="number" name="max_participants" class="form-control" min="1" max="100"
                               value="<?= $editTrip && $editTrip['max_participants'] ? $editTrip['max_participants'] : '20' ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">المستخدم (اختياري)</label>
                        <select name="user_id" class="form-control">
                            <option value="">بدون مستخدم</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['user_id'] ?>" <?= ($editTrip && $editTrip['user_id'] == $user['user_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">نقطة التجمع</label>
                        <input type="text" name="meeting_point" class="form-control" 
                               value="<?= $editTrip ? htmlspecialchars($editTrip['meeting_point']) : '' ?>"
                               placeholder="مثال: فندق الأوراسي - الجزائر العاصمة">
                    </div>
                </div>
                
                <!-- Guide Information -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم المرشد السياحي</label>
                        <input type="text" name="guide_name" class="form-control" 
                               value="<?= $editTrip ? htmlspecialchars($editTrip['guide_name']) : '' ?>"
                               placeholder="اسم المرشد">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">رقم هاتف المرشد</label>
                        <input type="text" name="guide_phone" class="form-control" 
                               value="<?= $editTrip ? htmlspecialchars($editTrip['guide_phone']) : '' ?>"
                               placeholder="رقم الهاتف">
                    </div>
                </div>
                
                <!-- Trip Components -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">المواقع الأثرية المقترحة</label>
                        <textarea name="sites" class="form-control" rows="3" 
                                  placeholder="قائمة بالمواقع الأثرية..."><?= $editTrip ? htmlspecialchars($editTrip['sites']) : '' ?></textarea>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الفنادق المقترحة</label>
                        <textarea name="hotels" class="form-control" rows="3" 
                                  placeholder="قائمة بالفنادق..."><?= $editTrip ? htmlspecialchars($editTrip['hotels']) : '' ?></textarea>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">المطاعم المقترحة</label>
                        <textarea name="restaurants" class="form-control" rows="3" 
                                  placeholder="قائمة بالمطاعم..."><?= $editTrip ? htmlspecialchars($editTrip['restaurants']) : '' ?></textarea>
                    </div>
                </div>
                
                <!-- Services -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الخدمات المشمولة</label>
                        <textarea name="included_services" class="form-control" rows="3" 
                                  placeholder="• النقل ذهاباً وإياباً&#10;• الإقامة في فنادق 4 نجوم&#10;• وجبات إفطار يومية&#10;• تذاكر دخول المواقع الأثرية"><?= $editTrip ? htmlspecialchars($editTrip['included_services']) : '' ?></textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الخدمات غير المشمولة</label>
                        <textarea name="excluded_services" class="form-control" rows="3" 
                                  placeholder="• التذاكر الدولية&#10;• التأمين الصحي&#10;• المشتريات الشخصية"><?= $editTrip ? htmlspecialchars($editTrip['excluded_services']) : '' ?></textarea>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">وصف الرحلة</label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="وصف تفصيلي للرحلة..."><?= $editTrip ? htmlspecialchars($editTrip['description']) : '' ?></textarea>
                </div>
                
                <?php if (!$editTrip): ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="auto_generate" class="form-check-input" id="autoGenerate">
                            <label class="form-check-label" for="autoGenerate">
                                <i class="fas fa-magic text-success"></i> توليد تلقائي للمقترحات (بناءً على الولاية)
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save"></i> <?= $editTrip ? 'تحديث الرحلة' : 'إنشاء الرحلة' ?>
                    </button>
                    <?php if ($editTrip): ?>
                        <a href="manage_trips.php" class="btn btn-secondary btn-lg px-4">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Trips List -->
        <div class="content-card">
            <h5><i class="fas fa-list"></i> قائمة الرحلات الذكية</h5>
            <hr>
            
            <?php if (empty($trips)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                    <h5>لا توجد رحلات ذكية مسجلة</h5>
                    <p>قم بإنشاء رحلة جديدة من النموذج أعلاه</p>
                </div>
            <?php else: ?>
                <?php foreach ($trips as $trip): ?>
                    <?php
                    $isUpcoming = $trip['departure_date'] && strtotime($trip['departure_date']) > time();
                    $isActive = $trip['departure_date'] && strtotime($trip['departure_date']) <= time() && 
                                (!$trip['return_date'] || strtotime($trip['return_date']) >= time());
                    $isCompleted = $trip['return_date'] && strtotime($trip['return_date']) < time();
                    ?>
                    <div class="trip-card">
                        <div class="trip-header">
                            <div>
                                <div class="trip-title"><?= htmlspecialchars($trip['trip_name']) ?></div>
                                <div class="trip-state">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($trip['state_name']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge-type bg-<?= $tripTypeColors[$trip['trip_type']] ?>">
                                    <i class="fas fa-<?= $trip['trip_type'] == 'cultural' ? 'landmark' : ($trip['trip_type'] == 'adventure' ? 'hiking' : ($trip['trip_type'] == 'religious' ? 'mosque' : ($trip['trip_type'] == 'family' ? 'users' : 'crown'))) ?>"></i>
                                    <?= $tripTypes[$trip['trip_type']] ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Dates -->
                        <div class="trip-meta">
                            <?php if ($trip['departure_date']): ?>
                                <span class="date-badge">
                                    <i class="fas fa-plane-departure text-primary"></i>
                                    الانطلاق: <?= date('d/m/Y', strtotime($trip['departure_date'])) ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($trip['return_date']): ?>
                                <span class="date-badge">
                                    <i class="fas fa-plane-arrival text-success"></i>
                                    العودة: <?= date('d/m/Y', strtotime($trip['return_date'])) ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($isUpcoming): ?>
                                <span class="badge bg-info"><i class="fas fa-clock"></i> قادمة</span>
                            <?php elseif ($isActive): ?>
                                <span class="badge bg-success"><i class="fas fa-play"></i> نشطة</span>
                            <?php elseif ($isCompleted): ?>
                                <span class="badge bg-secondary"><i class="fas fa-check-double"></i> منتهية</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="trip-meta">
                            <?php if ($trip['duration_days']): ?>
                                <span><i class="fas fa-calendar-day"></i> <?= $trip['duration_days'] ?> أيام</span>
                            <?php endif; ?>
                            
                            <?php if ($trip['estimated_cost']): ?>
                                <span><i class="fas fa-money-bill-wave"></i> <?= number_format($trip['estimated_cost']) ?> دج</span>
                            <?php endif; ?>
                            
                            <?php if ($trip['max_participants']): ?>
                                <span class="participants-info">
                                    <i class="fas fa-users"></i>
                                    <?= $trip['current_bookings'] ?? 0 ?> / <?= $trip['max_participants'] ?> مشارك
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($trip['username']): ?>
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($trip['username']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($trip['meeting_point']): ?>
                            <div class="mt-2">
                                <i class="fas fa-map-pin text-danger"></i>
                                <strong>نقطة التجمع:</strong> <?= htmlspecialchars($trip['meeting_point']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($trip['guide_name']): ?>
                            <div class="mt-1">
                                <i class="fas fa-user-tie text-info"></i>
                                <strong>المرشد:</strong> <?= htmlspecialchars($trip['guide_name']) ?>
                                <?php if ($trip['guide_phone']): ?>
                                    <i class="fas fa-phone ms-2"></i> <?= htmlspecialchars($trip['guide_phone']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($trip['description']): ?>
                            <div class="mt-2">
                                <small class="text-muted"><?= nl2br(htmlspecialchars($trip['description'])) ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="trip-details">
                            <div class="row">
                                <?php if ($trip['sites']): ?>
                                    <div class="col-md-4">
                                        <i class="fas fa-monument text-primary"></i>
                                        <strong>المواقع الأثرية:</strong>
                                        <p class="small mb-0"><?= htmlspecialchars($trip['sites']) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($trip['hotels']): ?>
                                    <div class="col-md-4">
                                        <i class="fas fa-hotel text-success"></i>
                                        <strong>الفنادق:</strong>
                                        <p class="small mb-0"><?= htmlspecialchars($trip['hotels']) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($trip['restaurants']): ?>
                                    <div class="col-md-4">
                                        <i class="fas fa-utensils text-warning"></i>
                                        <strong>المطاعم:</strong>
                                        <p class="small mb-0"><?= htmlspecialchars($trip['restaurants']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($trip['included_services'] || $trip['excluded_services']): ?>
                                <div class="row mt-2">
                                    <?php if ($trip['included_services']): ?>
                                        <div class="col-md-6">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <strong>الخدمات المشمولة:</strong>
                                            <p class="small mb-0"><?= nl2br(htmlspecialchars($trip['included_services'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($trip['excluded_services']): ?>
                                        <div class="col-md-6">
                                            <i class="fas fa-times-circle text-danger"></i>
                                            <strong>الخدمات غير المشمولة:</strong>
                                            <p class="small mb-0"><?= nl2br(htmlspecialchars($trip['excluded_services'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3 text-end">
                            <a href="?edit=<?= $trip['trip_id'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <a href="?delete=<?= $trip['trip_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه الرحلة؟')">
                                <i class="fas fa-trash"></i> حذف
                            </a>
                            <button class="btn btn-sm btn-info" onclick="viewTripDetails(<?= htmlspecialchars(json_encode($trip)) ?>)">
                                <i class="fas fa-eye"></i> تفاصيل
                            </button>
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
            <div class="modal-body" id="tripDetailsContent">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Calculate duration days based on departure and return dates
    function calculateDuration() {
        const departure = document.querySelector('input[name="departure_date"]').value;
        const returnDate = document.querySelector('input[name="return_date"]').value;
        
        if (departure && returnDate) {
            const start = new Date(departure);
            const end = new Date(returnDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('durationDays').value = diffDays;
        }
    }
    
    // Add event listeners for date inputs
    const departureInput = document.querySelector('input[name="departure_date"]');
    const returnInput = document.querySelector('input[name="return_date"]');
    
    if (departureInput && returnInput) {
        departureInput.addEventListener('change', calculateDuration);
        returnInput.addEventListener('change', calculateDuration);
    }
    
    function viewTripDetails(trip) {
        const tripTypes = <?= json_encode($tripTypes) ?>;
        const content = `
            <div class="row">
                <div class="col-12 mb-3">
                    <h4>${trip.trip_name}</h4>
                    <p><i class="fas fa-map-marker-alt text-danger"></i> ${trip.state_name}</p>
                    <span class="badge bg-${getTypeColor(trip.trip_type)}">${tripTypes[trip.trip_type] || trip.trip_type}</span>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-calendar-alt"></i> مواعيد الرحلة</h6>
                            <p><strong>الانطلاق:</strong> ${trip.departure_date ? new Date(trip.departure_date).toLocaleDateString('ar') : 'غير محدد'}</p>
                            <p><strong>العودة:</strong> ${trip.return_date ? new Date(trip.return_date).toLocaleDateString('ar') : 'غير محدد'}</p>
                            <p><strong>المدة:</strong> ${trip.duration_days || 'غير محدد'} أيام</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-info-circle"></i> معلومات إضافية</h6>
                            <p><strong>التكلفة التقديرية:</strong> ${trip.estimated_cost ? Number(trip.estimated_cost).toLocaleString() + ' دج' : 'غير محدد'}</p>
                            <p><strong>الحد الأقصى للمشاركين:</strong> ${trip.max_participants || 'غير محدد'}</p>
                            <p><strong>نقطة التجمع:</strong> ${trip.meeting_point || 'غير محدد'}</p>
                        </div>
                    </div>
                </div>
                
                ${trip.guide_name ? `
                <div class="col-12 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-user-tie"></i> المرشد السياحي</h6>
                            <p><strong>الاسم:</strong> ${trip.guide_name}</p>
                            ${trip.guide_phone ? `<p><strong>الهاتف:</strong> ${trip.guide_phone}</p>` : ''}
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <div class="col-md-4 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-monument text-primary"></i> المواقع الأثرية</h6>
                            <p>${trip.sites || 'لا توجد معلومات'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-hotel text-success"></i> الفنادق</h6>
                            <p>${trip.hotels || 'لا توجد معلومات'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-utensils text-warning"></i> المطاعم</h6>
                            <p>${trip.restaurants || 'لا توجد معلومات'}</p>
                        </div>
                    </div>
                </div>
                
                ${trip.included_services ? `
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-check-circle text-success"></i> الخدمات المشمولة</h6>
                            <p style="white-space: pre-line;">${trip.included_services}</p>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${trip.excluded_services ? `
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-times-circle text-danger"></i> الخدمات غير المشمولة</h6>
                            <p style="white-space: pre-line;">${trip.excluded_services}</p>
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
    
    function getTypeColor(type) {
        const colors = {
            'cultural': 'primary',
            'adventure': 'success',
            'religious': 'info',
            'family': 'warning',
            'luxury': 'danger'
        };
        return colors[type] || 'secondary';
    }
    
    // Auto-generate trip name when state is selected
    const stateSelect = document.getElementById('stateSelect');
    if (stateSelect) {
        stateSelect.addEventListener('change', function() {
            const autoGenerate = document.getElementById('autoGenerate');
            if (autoGenerate && autoGenerate.checked) {
                const selectedOption = this.options[this.selectedIndex];
                const stateName = selectedOption.text;
                if (stateName && stateName !== 'اختر الولاية') {
                    document.querySelector('input[name="trip_name"]').value = 'رحلة سياحية في ولاية ' + stateName;
                }
            }
        });
    }
    
    // Trigger auto-generate on checkbox change
    const autoGenerateCheckbox = document.getElementById('autoGenerate');
    if (autoGenerateCheckbox) {
        autoGenerateCheckbox.addEventListener('change', function() {
            if (this.checked) {
                const stateSelect = document.getElementById('stateSelect');
                const selectedOption = stateSelect.options[stateSelect.selectedIndex];
                const stateName = selectedOption.text;
                if (stateName && stateName !== 'اختر الولاية') {
                    document.querySelector('input[name="trip_name"]').value = 'رحلة سياحية في ولاية ' + stateName;
                }
            } else {
                document.querySelector('input[name="trip_name"]').value = '';
            }
        });
    }
    
    // Set minimum return date based on departure date
    if (departureInput && returnInput) {
        departureInput.addEventListener('change', function() {
            returnInput.min = this.value;
            if (returnInput.value && returnInput.value < this.value) {
                returnInput.value = this.value;
            }
            calculateDuration();
        });
    }
</script>
</body>
</html>