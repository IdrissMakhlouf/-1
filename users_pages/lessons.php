<?php
/**
 * Educational Lessons Page - Professional with Sidebar
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

// Get filter parameters
$level = isset($_GET['level']) ? $_GET['level'] : '';
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Build query
$sql = "SELECT * FROM lessons WHERE 1=1";
$params = [];

if ($level && in_array($level, ['primary', 'middle', 'secondary'])) {
    $sql .= " AND level = ?";
    $params[] = $level;
}

if ($subject && in_array($subject, ['history', 'geography', 'civic_education'])) {
    $sql .= " AND subject = ?";
    $params[] = $subject;
}

$sql .= " ORDER BY lesson_id DESC";

$lessonsQuery = $db->prepare($sql);
$lessonsQuery->execute($params);
$lessons = $lessonsQuery->fetchAll();

// Get statistics
$totalLessons = $db->query("SELECT COUNT(*) as total FROM lessons")->fetch()['total'];
$totalVideos = $db->query("SELECT COUNT(*) as total FROM lessons WHERE video_url IS NOT NULL AND video_url != ''")->fetch()['total'];
$totalMindmaps = $db->query("SELECT COUNT(*) as total FROM lessons WHERE mindmap_url IS NOT NULL AND mindmap_url != ''")->fetch()['total'];

$levels = [
    'primary' => 'الابتدائي',
    'middle' => 'المتوسط',
    'secondary' => 'الثانوي'
];

$subjects = [
    'history' => 'التاريخ',
    'geography' => 'الجغرافيا',
    'civic_education' => 'التربية المدنية'
];

$levelColors = [
    'primary' => 'success',
    'middle' => 'primary',
    'secondary' => 'warning'
];

$subjectColors = [
    'history' => 'info',
    'geography' => 'success',
    'civic_education' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الدروس التعليمية - <?= SITE_NAME ?></title>
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
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .filter-btn {
            border-radius: 30px;
            padding: 8px 20px;
            margin: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .filter-btn.active { background: #e67e22; color: white; border-color: #e67e22; }
        .filter-btn:not(.active):hover { background: #f8f9fa; color: #e67e22; }
        
        /* Lesson Cards */
        .lesson-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            margin-bottom: 25px;
            height: 100%;
        }
        .lesson-card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
        .lesson-card .card-body { padding: 20px; }
        .lesson-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 8px;
            margin-bottom: 10px;
        }
        .lesson-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 10px; color: #2c3e50; }
        .lesson-summary { color: #666; font-size: 0.9rem; margin-bottom: 15px; line-height: 1.6; }
        .lesson-meta { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        
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
                <li class="nav-item"><a class="nav-link" href="explore.php">استكشاف</a></li>
                <li class="nav-item"><a class="nav-link active" href="lessons.php">التعليم</a></li>
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
                        <h2 class="mb-2"><i class="fas fa-graduation-cap"></i> الدروس التعليمية</h2>
                        <p class="mb-0">تعلم التاريخ والجغرافيا والتربية المدنية بطريقة مبسطة وممتعة</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <span class="badge bg-light text-dark p-2">
                            <i class="fas fa-book"></i> <?= $totalLessons ?> درس
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-video"></i>
                        <h3><?= $totalVideos ?></h3>
                        <p class="text-muted mb-0">فيديو تعليمي</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-map"></i>
                        <h3><?= $totalMindmaps ?></h3>
                        <p class="text-muted mb-0">خريطة ذهنية</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-chalkboard-user"></i>
                        <h3>3</h3>
                        <p class="text-muted mb-0">مواد تعليمية</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar" data-aos="fade-up" data-aos-delay="200">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label fw-bold mb-2"><i class="fas fa-layer-group"></i> المستوى التعليمي:</label>
                        <div>
                            <a href="?<?= $subject ? 'subject=' . $subject . '&' : '' ?>" class="btn btn-outline-secondary filter-btn <?= !$level ? 'active' : '' ?>">
                                <i class="fas fa-list"></i> الكل
                            </a>
                            <?php foreach ($levels as $key => $value): ?>
                                <a href="?level=<?= $key ?><?= $subject ? '&subject=' . $subject : '' ?>" 
                                   class="btn btn-outline-secondary filter-btn <?= $level == $key ? 'active' : '' ?>">
                                    <i class="fas fa-<?= $key == 'primary' ? 'child' : ($key == 'middle' ? 'user-graduate' : 'university') ?>"></i>
                                    <?= $value ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-2"><i class="fas fa-book-open"></i> المادة:</label>
                        <div>
                            <a href="?<?= $level ? 'level=' . $level . '&' : '' ?>" class="btn btn-outline-secondary filter-btn <?= !$subject ? 'active' : '' ?>">
                                <i class="fas fa-list"></i> الكل
                            </a>
                            <?php foreach ($subjects as $key => $value): ?>
                                <a href="?subject=<?= $key ?><?= $level ? '&level=' . $level : '' ?>" 
                                   class="btn btn-outline-secondary filter-btn <?= $subject == $key ? 'active' : '' ?>">
                                    <i class="fas fa-<?= $key == 'history' ? 'history' : ($key == 'geography' ? 'globe' : 'balance-scale') ?>"></i>
                                    <?= $value ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lessons Grid -->
            <?php if (empty($lessons)): ?>
                <div class="text-center py-5 bg-white rounded-3" data-aos="fade-up">
                    <i class="fas fa-book-open fa-4x text-muted mb-3 d-block"></i>
                    <h5>لا توجد دروس متاحة حالياً</h5>
                    <p class="text-muted">سيتم إضافة المزيد من الدروس قريباً</p>
                    <a href="lessons.php" class="btn btn-primary">تحديث الصفحة</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($lessons as $lesson): ?>
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="lesson-card">
                            <div class="card-body">
                                <div>
                                    <span class="lesson-badge bg-<?= $levelColors[$lesson['level']] ?> text-white">
                                        <i class="fas fa-<?= $lesson['level'] == 'primary' ? 'child' : ($lesson['level'] == 'middle' ? 'user-graduate' : 'university') ?>"></i>
                                        <?= $levels[$lesson['level']] ?>
                                    </span>
                                    <span class="lesson-badge bg-<?= $subjectColors[$lesson['subject']] ?> text-white">
                                        <i class="fas fa-<?= $lesson['subject'] == 'history' ? 'history' : ($lesson['subject'] == 'geography' ? 'globe' : 'balance-scale') ?>"></i>
                                        <?= $subjects[$lesson['subject']] ?>
                                    </span>
                                </div>
                                <h5 class="lesson-title mt-2"><?= htmlspecialchars($lesson['title']) ?></h5>
                                <p class="lesson-summary"><?= mb_substr(htmlspecialchars($lesson['summary']), 0, 100) ?>...</p>
                                
                                <div class="lesson-meta">
                                    <?php if ($lesson['video_url']): ?>
                                        <a href="#" onclick="showVideo('<?= $lesson['video_url'] ?>')" class="text-decoration-none">
                                            <i class="fas fa-video text-primary"></i> فيديو
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($lesson['mindmap_url']): ?>
                                        <a href="../<?= $lesson['mindmap_url'] ?>" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-map text-success"></i> خريطة ذهنية
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none" onclick="showLessonDetails(<?= htmlspecialchars(json_encode($lesson)) ?>)">
                                        <i class="fas fa-eye"></i> تفاصيل
                                    </button>
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

<!-- Lesson Details Modal -->
<div class="modal fade" id="lessonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الدرس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="lessonContent"></div>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">فيديو تعليمي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="videoContainer"></div>
            </div>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    AOS.init({ duration: 1000, once: true });
    
    function showLessonDetails(lesson) {
        const content = `
            <div class="row">
                <div class="col-12">
                    <h4>${lesson.title}</h4>
                    <p class="text-muted">
                        <span class="badge bg-${getLevelColor(lesson.level)}">${getLevelText(lesson.level)}</span>
                        <span class="badge bg-secondary">${getSubjectText(lesson.subject)}</span>
                    </p>
                    <hr>
                    <h6>ملخص الدرس:</h6>
                    <p>${lesson.summary || 'لا يوجد ملخص'}</p>
                    ${lesson.sources ? `
                    <h6>المصادر والمراجع:</h6>
                    <p>${lesson.sources}</p>
                    ` : ''}
                    ${lesson.video_url ? `
                    <div class="mt-3">
                        <button class="btn btn-primary" onclick="showVideo('${lesson.video_url}')">
                            <i class="fas fa-play"></i> مشاهدة الفيديو التعليمي
                        </button>
                    </div>
                    ` : ''}
                    ${lesson.mindmap_url ? `
                    <div class="mt-2">
                        <a href="../${lesson.mindmap_url}" target="_blank" class="btn btn-success">
                            <i class="fas fa-map"></i> عرض الخريطة الذهنية
                        </a>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
        document.getElementById('lessonContent').innerHTML = content;
        new bootstrap.Modal(document.getElementById('lessonModal')).show();
    }
    
    function showVideo(videoUrl) {
        let embedUrl = videoUrl;
        if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
            let videoId = videoUrl.split('v=')[1] || videoUrl.split('/').pop();
            embedUrl = 'https://www.youtube.com/embed/' + videoId;
        } else if (videoUrl.includes('uploads/')) {
            embedUrl = '../' + videoUrl;
            $('#videoContainer').html('<video controls class="w-100"><source src="' + embedUrl + '" type="video/mp4"></video>');
            new bootstrap.Modal(document.getElementById('videoModal')).show();
            return;
        }
        $('#videoContainer').html('<iframe width="100%" height="400" src="' + embedUrl + '" frameborder="0" allowfullscreen></iframe>');
        new bootstrap.Modal(document.getElementById('videoModal')).show();
    }
    
    function getLevelColor(level) {
        const colors = { 'primary': 'success', 'middle': 'primary', 'secondary': 'warning' };
        return colors[level] || 'secondary';
    }
    
    function getLevelText(level) {
        const levels = { 'primary': 'الابتدائي', 'middle': 'المتوسط', 'secondary': 'الثانوي' };
        return levels[level] || level;
    }
    
    function getSubjectText(subject) {
        const subjects = { 'history': 'التاريخ', 'geography': 'الجغرافيا', 'civic_education': 'التربية المدنية' };
        return subjects[subject] || subject;
    }
</script>
</body>
</html>