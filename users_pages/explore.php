<?php
/**
 * Explore States Page - Professional with Sidebar
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

// Get all states with heritage sites count
$states = $db->query("
    SELECT s.*, COUNT(hs.site_id) as sites_count,
           (SELECT image_url FROM heritage_sites WHERE state_id = s.state_id LIMIT 1) as preview_image
    FROM states s
    LEFT JOIN heritage_sites hs ON s.state_id = hs.state_id
    GROUP BY s.state_id
    ORDER BY s.name
")->fetchAll();

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $states = array_filter($states, function($state) use ($search) {
        return stripos($state['name'], $search) !== false || 
               stripos($state['description'], $search) !== false;
    });
}

// Get total statistics
$totalStates = count($states);
$totalSites = $db->query("SELECT COUNT(*) as total FROM heritage_sites")->fetch()['total'];
$totalHotels = $db->query("SELECT COUNT(*) as total FROM hotels")->fetch()['total'];
$totalRestaurants = $db->query("SELECT COUNT(*) as total FROM restaurants")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استكشاف الجزائر - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f8f9fa; direction: rtl; }
        
        /* Navbar */
        .navbar { background: #2c3e50; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; }
        .navbar-brand i { color: #e67e22; }
        
        /* Main Layout */
        .main-wrapper {
            display: flex;
            gap: 25px;
            margin-top: 90px;
            margin-bottom: 50px;
        }
        .sidebar-col { flex: 0 0 300px; }
        .content-col { flex: 1; }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
        }
        
        /* Statistics Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-card i { font-size: 2rem; color: #e67e22; margin-bottom: 10px; }
        .stat-card h3 { font-size: 1.8rem; font-weight: 700; margin: 0; color: #2c3e50; }
        
        /* Search Box */
        .search-box { background: white; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .search-input { border-radius: 50px; padding: 12px 20px; border: 2px solid #e0e0e0; transition: all 0.3s; }
        .search-input:focus { border-color: #e67e22; box-shadow: none; }
        
        /* State Cards */
        .state-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            margin-bottom: 25px;
            height: 100%;
        }
        .state-card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
        .state-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .state-card:hover img { transform: scale(1.05); }
        .state-card .card-body { padding: 20px; }
        .state-card .sites-count { color: #e67e22; font-size: 0.85rem; font-weight: 500; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-wrapper { flex-direction: column; }
            .sidebar-col { flex: auto; }
        }
        
        footer { background: #1a2634; color: white; padding: 40px 0 20px; margin-top: 50px; }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-landmark"></i> <?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="../index.php">الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link active" href="explore.php">استكشاف</a></li>
                <li class="nav-item"><a class="nav-link" href="lessons.php">التعليم</a></li>
                <li class="nav-item"><a class="nav-link" href="archive.php">الأرشيف</a></li>
                <li class="nav-item"><a class="nav-link" href="smart_trips.php">رحلات ذكية</a></li>
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
            <div class="page-header" data-aos="fade-up">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="mb-2"><i class="fas fa-map-marked-alt"></i> استكشف الجزائر</h2>
                        <p class="mb-0">اكتشف الولايات الجزائرية ومواقعها الأثرية وتاريخها العريق</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <span class="badge bg-light text-dark p-2">
                            <i class="fas fa-city"></i> <?= $totalStates ?> ولاية
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-monument"></i>
                        <h3><?= $totalSites ?></h3>
                        <p class="text-muted mb-0">موقع أثري</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-hotel"></i>
                        <h3><?= $totalHotels ?></h3>
                        <p class="text-muted mb-0">فندق</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-utensils"></i>
                        <h3><?= $totalRestaurants ?></h3>
                        <p class="text-muted mb-0">مطعم تقليدي</p>
                    </div>
                </div>
            </div>
            
            <!-- Search Box -->
            <div class="search-box" data-aos="fade-up" data-aos-delay="200">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control search-input" 
                               placeholder="ابحث عن ولاية..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary px-4" style="border-radius: 50px; margin-right: -10px;" type="submit">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <?php if ($search): ?>
                            <a href="explore.php" class="btn btn-secondary px-4" style="border-radius: 50px;">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- States Grid -->
            <?php if (empty($states)): ?>
                <div class="text-center py-5 bg-white rounded-3" data-aos="fade-up">
                    <i class="fas fa-search fa-4x text-muted mb-3 d-block"></i>
                    <h5>لا توجد نتائج مطابقة لبحثك</h5>
                    <p class="text-muted">حاول البحث بكلمة مختلفة</p>
                    <a href="explore.php" class="btn btn-primary">عرض جميع الولايات</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($states as $state): ?>
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="state-card">
                            <img src="<?= htmlspecialchars($state['preview_image'] ?? 'https://via.placeholder.com/400x200?text=' . urlencode($state['name'])) ?>" 
                                 alt="<?= htmlspecialchars($state['name']) ?>">
                            <div class="card-body">
                                <h5><?= htmlspecialchars($state['name']) ?></h5>
                                <p class="text-muted small"><?= mb_substr(htmlspecialchars($state['description']), 0, 80) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="sites-count">
                                        <i class="fas fa-monument"></i> <?= $state['sites_count'] ?> موقع أثري
                                    </span>
                                    <a href="state_page.php?id=<?= $state['state_id'] ?>" class="btn btn-sm btn-primary">
                                        استكشف <i class="fas fa-arrow-left"></i>
                                    </a>
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

<footer>
    <div class="container text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> منصة التراث والتاريخ الجزائري. جميع الحقوق محفوظة.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 1000, once: true });
</script>
</body>
</html>