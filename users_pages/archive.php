<?php
/**
 * Historical Archives Page - Professional with Sidebar
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$period = isset($_GET['period']) ? $_GET['period'] : '';
$century = isset($_GET['century']) ? intval($_GET['century']) : 0;

// Build query with search
$sql = "SELECT * FROM historical_archives WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (period_name LIKE ? OR description LIKE ? OR sources LIKE ? OR key_figures LIKE ? OR cultural_achievements LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($period && in_array($period, ['ancient', 'islamic', 'ottoman', 'colonial', 'modern'])) {
    $periodMap = [
        'ancient' => '%روماني%|%بوني%|%نوميدي%|%فينيقي%|%قديم%',
        'islamic' => '%إسلامي%|%فاطمي%|%زياني%|%حمادي%|%مرابط%|%موحدي%',
        'ottoman' => '%عثماني%',
        'colonial' => '%استعمار%|%فرنسي%|%احتلال%',
        'modern' => '%حديث%|%معاصر%|%استقلال%|%جمهورية%'
    ];
    $sql .= " AND period_name LIKE ?";
    $params[] = $periodMap[$period];
}

if ($century > 0) {
    $startYear = ($century - 1) * 100;
    $endYear = $century * 100;
    $sql .= " AND ((start_year BETWEEN ? AND ?) OR (end_year BETWEEN ? AND ?) OR (start_year <= ? AND end_year >= ?))";
    $params[] = $startYear;
    $params[] = $endYear;
    $params[] = $startYear;
    $params[] = $endYear;
    $params[] = $startYear;
    $params[] = $endYear;
}

$sql .= " ORDER BY start_year ASC";
$archivesQuery = $db->prepare($sql);
$archivesQuery->execute($params);
$archives = $archivesQuery->fetchAll();

// Get statistics for archive
$totalPeriods = $db->query("SELECT COUNT(*) as total FROM historical_archives")->fetch()['total'];
$oldestPeriod = $db->query("SELECT period_name, start_year FROM historical_archives WHERE start_year IS NOT NULL ORDER BY start_year ASC LIMIT 1")->fetch();
$latestPeriod = $db->query("SELECT period_name, end_year FROM historical_archives WHERE end_year IS NOT NULL ORDER BY end_year DESC LIMIT 1")->fetch();
$totalImages = $db->query("SELECT COUNT(*) as total FROM historical_archives WHERE featured_image IS NOT NULL OR gallery_images IS NOT NULL")->fetch()['total'];

// Get timeline data for visualization
$timelineData = $db->query("SELECT period_name, start_year, end_year, featured_image FROM historical_archives WHERE start_year IS NOT NULL ORDER BY start_year ASC")->fetchAll();

// Get random featured period
$featuredPeriod = $db->query("SELECT * FROM historical_archives WHERE featured_image IS NOT NULL ORDER BY RAND() LIMIT 1")->fetch();

$periodCategories = [
    'ancient' => ['name' => 'العصور القديمة', 'icon' => 'fa-landmark', 'color' => '#8e44ad', 'bg' => 'bg-purple'],
    'islamic' => ['name' => 'العصر الإسلامي', 'icon' => 'fa-mosque', 'color' => '#27ae60', 'bg' => 'bg-success'],
    'ottoman' => ['name' => 'الحقبة العثمانية', 'icon' => 'fa-turkish-lira', 'color' => '#e67e22', 'bg' => 'bg-warning'],
    'colonial' => ['name' => 'فترة الاستعمار', 'icon' => 'fa-fist-raised', 'color' => '#c0392b', 'bg' => 'bg-danger'],
    'modern' => ['name' => 'الجزائر المعاصرة', 'icon' => 'fa-flag', 'color' => '#2980b9', 'bg' => 'bg-info']
];

$centuries = range(10, 21);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأرشيف التاريخي - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f5f0e8; direction: rtl; }
        
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
            background: linear-gradient(135deg, #2c3e50 0%, #8e44ad 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/old-paper.png');
            opacity: 0.1;
            pointer-events: none;
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
        .stat-card i { font-size: 2rem; color: #8e44ad; margin-bottom: 10px; }
        .stat-card h3 { font-size: 1.8rem; font-weight: 700; margin: 0; color: #2c3e50; }
        
        /* Search & Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .search-input {
            border-radius: 50px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .search-input:focus { border-color: #8e44ad; box-shadow: none; }
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 30px;
            background: #f8f9fa;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px;
        }
        .filter-badge:hover, .filter-badge.active {
            background: #8e44ad;
            color: white;
            transform: translateY(-2px);
        }
        .century-select {
            border-radius: 30px;
            padding: 8px 18px;
            border: 1px solid #e0e0e0;
            background: white;
        }
        
        /* Featured Period */
        .featured-period {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .featured-period img {
            height: 280px;
            object-fit: cover;
            width: 100%;
        }
        
        /* Timeline Cards */
        .timeline-cards {
            position: relative;
        }
        .timeline-card {
            display: flex;
            margin-bottom: 30px;
            position: relative;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .timeline-card:hover {
            transform: translateX(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .timeline-card:nth-child(even) { flex-direction: row-reverse; }
        .timeline-date {
            flex: 0 0 140px;
            background: linear-gradient(135deg, #8e44ad, #6c3483);
            color: white;
            padding: 20px;
            text-align: center;
            font-weight: bold;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .timeline-date .year { font-size: 1.5rem; font-weight: 800; }
        .timeline-content { flex: 1; padding: 25px; }
        .archive-period-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 25px;
            font-size: 0.75rem;
            margin-bottom: 15px;
        }
        .archive-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 15px; color: #2c3e50; }
        .archive-description { color: #666; line-height: 1.8; margin-bottom: 15px; }
        .key-figures {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            border-right: 3px solid #8e44ad;
        }
        .achievements {
            background: #fff5e8;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
        }
        .media-gallery {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        .media-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .media-thumb:hover { transform: scale(1.05); }
        .sources-box {
            background: #f0f0f0;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-top: 15px;
        }
        
        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-wrapper { flex-direction: column; }
            .sidebar-col { flex: auto; }
            .timeline-card, .timeline-card:nth-child(even) { flex-direction: column; }
            .timeline-date { flex-direction: row; justify-content: space-between; align-items: center; gap: 15px; }
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
                <li class="nav-item"><a class="nav-link" href="explore.php">استكشاف</a></li>
                <li class="nav-item"><a class="nav-link" href="lessons.php">التعليم</a></li>
                <li class="nav-item"><a class="nav-link active" href="archive.php">الأرشيف</a></li>
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
                        <h2 class="mb-2"><i class="fas fa-history"></i> الأرشيف التاريخي للجزائر</h2>
                        <p class="mb-0">استكشف أكثر من 2000 عام من التاريخ والحضارة الجزائرية</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <span class="badge bg-light text-dark p-2">
                            <i class="fas fa-archive"></i> <?= $totalPeriods ?> حقبة تاريخية
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-calendar-alt"></i>
                        <h3><?= $oldestPeriod ? $oldestPeriod['start_year'] : '?' ?></h3>
                        <p class="text-muted mb-0">أقدم حقبة</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3><?= $latestPeriod ? $latestPeriod['end_year'] : '?' ?></h3>
                        <p class="text-muted mb-0">أحدث حقبة</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-image"></i>
                        <h3><?= $totalImages ?></h3>
                        <p class="text-muted mb-0">صورة ووثيقة</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-book"></i>
                        <h3><?= $totalPeriods ?></h3>
                        <p class="text-muted mb-0">حقبة تاريخية</p>
                    </div>
                </div>
            </div>
            
            <!-- Search & Filter Section -->
            <div class="filter-section" data-aos="fade-up" data-aos-delay="200">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control search-input" 
                                       placeholder="ابحث عن حدث، شخصية، أو حقبة تاريخية..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-primary px-4" style="border-radius: 0 50px 50px 0;">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-wrap justify-content-end">
                                <?php if ($search || $period || $century): ?>
                                    <a href="archive.php" class="filter-badge">
                                        <i class="fas fa-times"></i> إلغاء الفلتر
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="text-muted"><i class="fas fa-filter"></i> فلتر حسب الحقبة:</span>
                            <?php foreach ($periodCategories as $key => $cat): ?>
                                <a href="?period=<?= $key ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="filter-badge <?= $period == $key ? 'active' : '' ?>" 
                                   style="border-right-color: <?= $cat['color'] ?>;">
                                    <i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['name'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="text-muted"><i class="fas fa-clock"></i> فلتر حسب القرن:</span>
                            <select class="century-select" onchange="window.location.href='?century='+this.value<?= $search ? '+ \'&search=' . urlencode($search) . '\'' : '' ?>">
                                <option value="0">كل القرون</option>
                                <?php foreach ($centuries as $c): ?>
                                    <option value="<?= $c ?>" <?= $century == $c ? 'selected' : '' ?>>
                                        القرن <?= $c ?> الميلادي
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <?php if ($search): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle"></i> 
                        عرض نتائج البحث عن: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                        <span class="badge bg-primary ms-2"><?= count($archives) ?> نتيجة</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Featured Period -->
            <?php if ($featuredPeriod && !$search && !$period && !$century): ?>
            <div class="featured-period mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="row g-0">
                    <div class="col-md-5">
                        <?php if ($featuredPeriod['featured_image']): ?>
                            <img src="../<?= htmlspecialchars($featuredPeriod['featured_image']) ?>" alt="<?= htmlspecialchars($featuredPeriod['period_name']) ?>">
                        <?php else: ?>
                            <div class="bg-dark h-100 d-flex align-items-center justify-content-center" style="min-height: 250px;">
                                <i class="fas fa-landmark fa-5x text-white-50"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7 p-4">
                        <span class="badge bg-warning text-dark mb-2"><i class="fas fa-star"></i> مميز</span>
                        <h3 class="mb-3"><?= htmlspecialchars($featuredPeriod['period_name']) ?></h3>
                        <p><?= mb_substr(htmlspecialchars($featuredPeriod['description']), 0, 200) ?>...</p>
                        <?php if ($featuredPeriod['start_year'] || $featuredPeriod['end_year']): ?>
                            <div class="mt-3">
                                <i class="fas fa-calendar-alt"></i> 
                                <?php if ($featuredPeriod['start_year'] && $featuredPeriod['end_year']): ?>
                                    <?= $featuredPeriod['start_year'] ?> - <?= $featuredPeriod['end_year'] ?> م
                                <?php elseif ($featuredPeriod['start_year']): ?>
                                    من <?= $featuredPeriod['start_year'] ?> م
                                <?php elseif ($featuredPeriod['end_year']): ?>
                                    حتى <?= $featuredPeriod['end_year'] ?> م
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-light mt-3" onclick="scrollToPeriod(<?= $featuredPeriod['archive_id'] ?>)">
                            <i class="fas fa-arrow-down"></i> اكتشف المزيد
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Archive Content -->
            <?php if (empty($archives)): ?>
                <div class="no-results" data-aos="fade-up">
                    <i class="fas fa-search fa-4x text-muted mb-3 d-block"></i>
                    <h4>لم نعثر على نتائج</h4>
                    <p class="text-muted">حاول البحث بكلمات مختلفة أو إلغاء الفلاتر</p>
                    <a href="archive.php" class="btn btn-primary mt-3">
                        <i class="fas fa-home"></i> العودة إلى الأرشيف
                    </a>
                </div>
            <?php else: ?>
                <!-- Timeline Cards -->
                <div class="timeline-cards" data-aos="fade-up">
                    <?php foreach ($archives as $archive): ?>
                        <?php
                        $periodType = 'ancient';
                        if (stripos($archive['period_name'], 'إسلامي') !== false || stripos($archive['period_name'], 'فاطمي') !== false) $periodType = 'islamic';
                        elseif (stripos($archive['period_name'], 'عثماني') !== false) $periodType = 'ottoman';
                        elseif (stripos($archive['period_name'], 'استعمار') !== false || stripos($archive['period_name'], 'فرنسي') !== false) $periodType = 'colonial';
                        elseif (stripos($archive['period_name'], 'حديث') !== false || stripos($archive['period_name'], 'معاصر') !== false) $periodType = 'modern';
                        $cat = $periodCategories[$periodType];
                        ?>
                        <div class="timeline-card" id="period-<?= $archive['archive_id'] ?>">
                            <div class="timeline-date" style="background: <?= $cat['color'] ?>;">
                                <div class="year">
                                    <?php if ($archive['start_year'] && $archive['end_year']): ?>
                                        <?= $archive['start_year'] ?> - <?= $archive['end_year'] ?>
                                    <?php elseif ($archive['start_year']): ?>
                                        من <?= $archive['start_year'] ?>
                                    <?php elseif ($archive['end_year']): ?>
                                        حتى <?= $archive['end_year'] ?>
                                    <?php else: ?>
                                        تاريخ غير محدد
                                    <?php endif; ?>
                                </div>
                                <small>م</small>
                            </div>
                            <div class="timeline-content">
                                <span class="archive-period-badge" style="background: <?= $cat['color'] ?>20; color: <?= $cat['color'] ?>;">
                                    <i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['name'] ?>
                                </span>
                                <h3 class="archive-title"><?= htmlspecialchars($archive['period_name']) ?></h3>
                                
                                <?php if ($archive['featured_image']): ?>
                                    <img src="../<?= htmlspecialchars($archive['featured_image']) ?>" class="img-fluid rounded mb-3" style="max-height: 250px; width: 100%; object-fit: cover;">
                                <?php endif; ?>
                                
                                <div class="archive-description">
                                    <?= nl2br(htmlspecialchars($archive['description'])) ?>
                                </div>
                                
                                <?php if ($archive['key_figures']): ?>
                                    <div class="key-figures">
                                        <strong><i class="fas fa-users"></i> أبرز الشخصيات التاريخية:</strong>
                                        <div class="mt-2"><?= nl2br(htmlspecialchars($archive['key_figures'])) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($archive['cultural_achievements']): ?>
                                    <div class="achievements">
                                        <strong><i class="fas fa-trophy"></i> الإنجازات الحضارية والثقافية:</strong>
                                        <div class="mt-2"><?= nl2br(htmlspecialchars($archive['cultural_achievements'])) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Gallery Images -->
                                <?php if ($archive['gallery_images']): 
                                    $gallery = json_decode($archive['gallery_images'], true);
                                    if (!empty($gallery)): ?>
                                        <div class="media-gallery">
                                            <?php foreach (array_slice($gallery, 0, 6) as $img): ?>
                                                <img src="../<?= $img ?>" class="media-thumb" onclick="showImage('<?= $img ?>')">
                                            <?php endforeach; ?>
                                            <?php if (count($gallery) > 6): ?>
                                                <div class="media-thumb bg-light d-flex align-items-center justify-content-center" style="cursor: pointer;" onclick="showAllImages(<?= htmlspecialchars(json_encode($gallery)) ?>)">
                                                    +<?= count($gallery) - 6 ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                <?php endif; endif; ?>
                                
                                <!-- Videos -->
                                <?php if ($archive['videos']): 
                                    $videos = json_decode($archive['videos'], true);
                                    if (!empty($videos)): ?>
                                        <div class="mt-3">
                                            <strong><i class="fas fa-video"></i> فيديوهات توثيقية:</strong>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <?php foreach ($videos as $video): ?>
                                                    <button class="btn btn-outline-primary btn-sm" onclick="showArchiveVideo('../<?= $video ?>')">
                                                        <i class="fas fa-play"></i> مشاهدة الفيديو
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                <?php endif; endif; ?>
                                
                                <!-- Documents -->
                                <?php if ($archive['documents']): 
                                    $docs = json_decode($archive['documents'], true);
                                    if (!empty($docs)): ?>
                                        <div class="mt-3">
                                            <strong><i class="fas fa-file-alt"></i> الوثائق والمصادر:</strong>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <?php foreach ($docs as $doc): ?>
                                                    <a href="../<?= $doc ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-file-pdf text-danger"></i> عرض الوثيقة
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                <?php endif; endif; ?>
                                
                                <?php if ($archive['sources']): ?>
                                    <div class="sources-box">
                                        <i class="fas fa-book"></i>
                                        <strong>المصادر والمراجع:</strong>
                                        <div class="mt-1"><?= nl2br(htmlspecialchars($archive['sources'])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Deep Archive Footer -->
            <div class="text-center mt-5 pt-4 border-top" data-aos="fade-up">
                <h5 class="text-muted"><i class="fas fa-infinity"></i> أرشيف تاريخي شامل</h5>
                <p class="small text-muted">يحتوي الأرشيف على معلومات موثقة من مصادر تاريخية معتمدة</p>
                <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-book"></i> مصادر موثقة</span>
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-image"></i> صور أثرية</span>
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-video"></i> فيديوهات توثيقية</span>
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-file-pdf"></i> وثائق نادرة</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 80vh;">
                <button type="button" class="btn btn-light mt-3" data-bs-dismiss="modal"><i class="fas fa-times"></i> إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div class="modal fade" id="archiveVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-video"></i> فيديو توثيقي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <video controls class="w-100">
                    <source id="archiveVideoSource" src="" type="video/mp4">
                </video>
            </div>
        </div>
    </div>
</div>

<!-- Gallery Modal -->
<div class="modal fade" id="galleryModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-images"></i> معرض الصور</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="galleryContent"></div>
        </div>
    </div>
</div>

<footer>
    <div class="container text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> منصة التراث والتاريخ الجزائري. جميع الحقوق محفوظة.</p>
        <p class="small mt-2">المحتوى التاريخي موثق من مصادر معتمدة ومراجع أكاديمية</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 1000, once: true });
    
    function showImage(imgPath) {
        document.getElementById('modalImage').src = '../' + imgPath;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
    
    function showArchiveVideo(videoPath) {
        document.getElementById('archiveVideoSource').src = videoPath;
        new bootstrap.Modal(document.getElementById('archiveVideoModal')).show();
    }
    
    function showAllImages(images) {
        let html = '<div class="row g-3">';
        images.forEach(img => {
            html += `
                <div class="col-md-3 col-sm-4 col-6">
                    <img src="../${img}" class="img-fluid rounded cursor-pointer" style="cursor: pointer; height: 150px; width: 100%; object-fit: cover;" onclick="showImage('${img}')">
                </div>
            `;
        });
        html += '</div>';
        document.getElementById('galleryContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('galleryModal')).show();
    }
    
    function scrollToPeriod(periodId) {
        const element = document.getElementById(`period-${periodId}`);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            element.style.transition = 'all 0.3s';
            element.style.boxShadow = '0 0 0 3px #8e44ad';
            setTimeout(() => {
                element.style.boxShadow = '';
            }, 2000);
        }
    }
</script>
</body>
</html>