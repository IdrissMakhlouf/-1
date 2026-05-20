<?php
/**
 * State Page - Display heritage sites, hotels, restaurants for a specific state
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

// Get state ID from URL
$state_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($state_id <= 0) {
    header('Location: explore.php');
    exit();
}

// Get state information
$stateQuery = $db->prepare("SELECT * FROM states WHERE state_id = ?");
$stateQuery->execute([$state_id]);
$state = $stateQuery->fetch();

if (!$state) {
    header('Location: explore.php');
    exit();
}

// Get heritage sites for this state
$sitesQuery = $db->prepare("SELECT * FROM heritage_sites WHERE state_id = ?");
$sitesQuery->execute([$state_id]);
$heritageSites = $sitesQuery->fetchAll();

// Get local culture (nature, traditional clothes, traditions)
$cultureQuery = $db->prepare("SELECT * FROM local_culture WHERE state_id = ?");
$cultureQuery->execute([$state_id]);
$localCulture = $cultureQuery->fetchAll();

// Get hotels
$hotelsQuery = $db->prepare("SELECT * FROM hotels WHERE state_id = ? ORDER BY rating DESC");
$hotelsQuery->execute([$state_id]);
$hotels = $hotelsQuery->fetchAll();

// Get restaurants
$restaurantsQuery = $db->prepare("SELECT * FROM restaurants WHERE state_id = ? ORDER BY rating DESC");
$restaurantsQuery->execute([$state_id]);
$restaurants = $restaurantsQuery->fetchAll();

// Get interactive map data
$mapQuery = $db->prepare("SELECT * FROM interactive_maps WHERE state_id = ? ORDER BY created_at DESC LIMIT 1");
$mapQuery->execute([$state_id]);
$map = $mapQuery->fetch();

$categories = [
    'nature' => ['name' => 'السياحة الطبيعية', 'icon' => 'fa-tree'],
    'traditional_clothes' => ['name' => 'الملابس التقليدية', 'icon' => 'fa-tshirt'],
    'traditions' => ['name' => 'العادات والتقاليد', 'icon' => 'fa-users']
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($state['name']) ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f8f9fa; direction: rtl; }
        .navbar { background: #2c3e50; }
        .state-header {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('<?= $heritageSites[0]['image_url'] ?? 'https://images.unsplash.com/photo-1547471080-7cc2caa01c7e' ?>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0 60px;
            text-align: center;
            margin-top: 56px;
        }
        .state-header h1 { font-size: 3rem; font-weight: 700; }
        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            border-right: 5px solid #e67e22;
            padding-right: 15px;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        .heritage-card, .hotel-card, .restaurant-card, .culture-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
            height: 100%;
        }
        .heritage-card:hover, .hotel-card:hover, .restaurant-card:hover, .culture-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .heritage-card img, .culture-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .hotel-card img, .restaurant-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .card-content { padding: 20px; }
        .rating { color: #f39c12; }
        .badge-culture {
            background: #e67e22;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
            margin-bottom: 10px;
        }
        .btn-smart-trip {
            background: #27ae60;
            color: white;
            border-radius: 50px;
            padding: 10px 25px;
            margin-top: 20px;
        }
        .btn-smart-trip:hover { background: #229954; color: white; }
        .coordinates-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
        }
        #map { height: 300px; border-radius: 10px; margin-top: 10px; }
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
                    <a class="nav-link" href="index.php">الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="explore.php">استكشاف</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="lessons.php">التعليم</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="archive.php">الأرشيف</a>
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

<div class="state-header">
    <div class="container">
        <h1><?= htmlspecialchars($state['name']) ?></h1>
        <p class="lead"><?= htmlspecialchars($state['description']) ?></p>
        
        <button class="btn btn-smart-trip" onclick="generateSmartTrip(<?= $state_id ?>)">
            <i class="fas fa-robot"></i> اقتراح رحلة ذكية
        </button>
    </div>
</div>

<div class="container mb-5">
    <!-- Heritage Sites Section -->
    <div class="section-card">
        <h3 class="section-title"><i class="fas fa-monument"></i> المواقع الأثرية</h3>
        <div class="row">
            <?php if (count($heritageSites) > 0): ?>
                <?php foreach ($heritageSites as $site): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="heritage-card">
                        <?php if ($site['image_url']): ?>
                            <img src="../<?= htmlspecialchars($site['image_url']) ?>" alt="<?= htmlspecialchars($site['name']) ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x200?text=<?= urlencode($site['name']) ?>" alt="<?= htmlspecialchars($site['name']) ?>">
                        <?php endif; ?>
                        <div class="card-content">
                            <h5><?= htmlspecialchars($site['name']) ?></h5>
                            <p class="small"><?= htmlspecialchars($site['description']) ?></p>
                            
                            <?php if ($site['latitude'] && $site['longitude']): ?>
                                <div class="coordinates-info small">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                    <?= number_format($site['latitude'], 6) ?>, <?= number_format($site['longitude'], 6) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-2">
                                <?php if ($site['story']): ?>
                                    <button class="btn btn-sm btn-outline-info" onclick="showStory('<?= addslashes($site['story']) ?>')">
                                        <i class="fas fa-book-open"></i> القصة
                                    </button>
                                <?php endif; ?>
                                <?php if ($site['video_url']): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showVideo('<?= $site['video_url'] ?>')">
                                        <i class="fas fa-play"></i> فيديو
                                    </button>
                                <?php endif; ?>
                                <?php if ($site['documents_url']): ?>
                                    <a href="../<?= $site['documents_url'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-file-pdf"></i> وثيقة
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted text-center">لا توجد مواقع أثرية مسجلة في هذه الولاية بعد.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Local Culture Section -->
    <?php if (count($localCulture) > 0): ?>
    <div class="section-card">
        <h3 class="section-title"><i class="fas fa-users"></i> الثقافة المحلية</h3>
        <div class="row">
            <?php foreach ($localCulture as $culture): ?>
            <div class="col-md-4 mb-3">
                <div class="culture-card">
                    <?php if ($culture['image_url']): ?>
                        <img src="../<?= htmlspecialchars($culture['image_url']) ?>" style="height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-content">
                        <span class="badge-culture">
                            <i class="fas <?= $categories[$culture['category']]['icon'] ?? 'fa-tag' ?>"></i>
                            <?= $categories[$culture['category']]['name'] ?? $culture['category'] ?>
                        </span>
                        <h5 class="mt-2"><?= htmlspecialchars($culture['title']) ?></h5>
                        <p class="small"><?= htmlspecialchars($culture['description']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Hotels Section -->
    <div class="section-card">
        <h3 class="section-title"><i class="fas fa-hotel"></i> الفنادق القريبة</h3>
        <div class="row">
            <?php if (count($hotels) > 0): ?>
                <?php foreach ($hotels as $hotel): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="hotel-card">
                        <?php if ($hotel['image_url']): ?>
                            <img src="../<?= htmlspecialchars($hotel['image_url']) ?>">
                        <?php endif; ?>
                        <div class="card-content">
                            <h5><?= htmlspecialchars($hotel['name']) ?></h5>
                            <p class="small"><?= htmlspecialchars($hotel['description']) ?></p>
                            <p class="small"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($hotel['address']) ?></p>
                            <p class="small"><i class="fas fa-phone"></i> <?= htmlspecialchars($hotel['phone']) ?></p>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($hotel['rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $hotel['rating']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="text-muted">(<?= $hotel['rating'] ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted text-center">لا توجد فنادق مسجلة في هذه الولاية بعد.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Restaurants Section -->
    <div class="section-card">
        <h3 class="section-title"><i class="fas fa-utensils"></i> المطاعم التقليدية</h3>
        <div class="row">
            <?php if (count($restaurants) > 0): ?>
                <?php foreach ($restaurants as $restaurant): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="restaurant-card">
                        <?php if ($restaurant['image_url']): ?>
                            <img src="../<?= htmlspecialchars($restaurant['image_url']) ?>">
                        <?php endif; ?>
                        <div class="card-content">
                            <h5><?= htmlspecialchars($restaurant['name']) ?></h5>
                            <p class="small"><?= htmlspecialchars($restaurant['description']) ?></p>
                            <p class="small"><i class="fas fa-utensil-spoon"></i> <?= htmlspecialchars($restaurant['cuisine_type']) ?></p>
                            <p class="small"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($restaurant['address']) ?></p>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($restaurant['rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $restaurant['rating']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="text-muted">(<?= $restaurant['rating'] ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted text-center">لا توجد مطاعم مسجلة في هذه الولاية بعد.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Interactive Map Section -->
    <?php if ($map && ($map['map_image_url'] || $map['map_data'])): ?>
    <div class="section-card">
        <h3 class="section-title"><i class="fas fa-map"></i> الخريطة التفاعلية</h3>
        <?php if ($map['map_image_url']): ?>
            <img src="../<?= htmlspecialchars($map['map_image_url']) ?>" class="img-fluid rounded" alt="خريطة <?= htmlspecialchars($state['name']) ?>">
        <?php endif; ?>
        <?php if ($map['map_data']): ?>
            <div class="mt-3">
                <button class="btn btn-outline-primary" onclick="showInteractiveMap(<?= htmlspecialchars($map['map_data']) ?>)">
                    <i class="fas fa-map-marker-alt"></i> عرض المواقع على الخريطة
                </button>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modals -->
<div class="modal fade" id="storyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">القصة التاريخية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="storyContent"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">فيديو تعريفي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="videoContainer"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function showStory(story) {
    $('#storyContent').html('<p>' + story + '</p>');
    new bootstrap.Modal(document.getElementById('storyModal')).show();
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

function generateSmartTrip(stateId) {
    $.ajax({
        url: '../admin/smart_trip_api.php',
        type: 'POST',
        data: { state_id: stateId, action: 'generate' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.location.href = 'trip_details.php?id=' + response.trip_id;
            } else {
                alert('حدث خطأ: ' + response.message);
            }
        },
        error: function() {
            alert('حدث خطأ في الاتصال بالخادم');
        }
    });
}

function showInteractiveMap(mapData) {
    // Handle interactive map display
    console.log('Map data:', mapData);
    alert('سيتم عرض الخريطة التفاعلية قريباً');
}
</script>
</body>
</html>