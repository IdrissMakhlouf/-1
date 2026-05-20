<?php
/**
 * Home Page - Professional Heritage Landing Page
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once 'config/db.php';

// Get total counts for statistics
$stats = [];
$query = $db->query("SELECT COUNT(*) as total FROM states");
$stats['states'] = $query->fetch()['total'];

$query = $db->query("SELECT COUNT(*) as total FROM heritage_sites");
$stats['sites'] = $query->fetch()['total'];

$query = $db->query("SELECT COUNT(*) as total FROM lessons");
$stats['lessons'] = $query->fetch()['total'];

$query = $db->query("SELECT COUNT(*) as total FROM historical_archives");
$stats['archives'] = $query->fetch()['total'];

// Get featured heritage sites
$featuredSites = $db->query("
    SELECT hs.*, s.name as state_name 
    FROM heritage_sites hs 
    JOIN states s ON hs.state_id = s.state_id 
    WHERE hs.image_url IS NOT NULL 
    ORDER BY hs.site_id DESC 
    LIMIT 6
")->fetchAll();

// Get random historical periods for slider
$historicalPeriods = $db->query("
    SELECT * FROM historical_archives 
    WHERE featured_image IS NOT NULL 
    ORDER BY RAND() 
    LIMIT 3
")->fetchAll();

// Get featured states
$featuredStates = $db->query("
    SELECT s.*, COUNT(hs.site_id) as sites_count,
           (SELECT image_url FROM heritage_sites WHERE state_id = s.state_id LIMIT 1) as preview_image
    FROM states s
    LEFT JOIN heritage_sites hs ON s.state_id = hs.state_id
    GROUP BY s.state_id
    ORDER BY sites_count DESC
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | اكتشف تراث الجزائر العريق</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background: #fef9e8;
            overflow-x: hidden;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #e67e22;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #d35400;
        }
        
        /* Navigation */
        .navbar {
            background: rgba(44, 62, 80, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar-brand i {
            color: #e67e22;
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            height: 100vh;
            min-height: 700px;
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.5) 100%),
                        url('https://images.unsplash.com/photo-1547471080-7cc2caa01c7e?ixlib=rb-4.0.3');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            text-align: center;
            color: white;
            overflow: hidden;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.7) 100%);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease;
        }
        
        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
        }
        
        .hero-content .subtitle {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .hero-buttons .btn {
            padding: 12px 35px;
            margin: 10px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom {
            background: #e67e22;
            border: none;
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: #d35400;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-outline-custom {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn-outline-custom:hover {
            background: white;
            color: #e67e22;
            transform: translateY(-3px);
        }
        
        /* Hero Wave */
        .hero-wave {
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }
        
        /* Statistics Section */
        .stats-section {
            background: white;
            padding: 60px 0;
            margin-top: -50px;
            position: relative;
            z-index: 10;
            border-radius: 30px 30px 0 0;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.05);
        }
        
        .stat-card {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 20px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e67e22, #f39c12);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .stat-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        /* Section Title */
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .section-title .title-decoration {
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #e67e22, #f39c12);
            margin: 0 auto;
            border-radius: 2px;
        }
        
        .section-title p {
            color: #7f8c8d;
            margin-top: 15px;
            font-size: 1.1rem;
        }
        
        /* Heritage Cards */
        .heritage-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s;
            margin-bottom: 30px;
            position: relative;
        }
        
        .heritage-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .heritage-card-img {
            position: relative;
            overflow: hidden;
            height: 250px;
        }
        
        .heritage-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .heritage-card:hover .heritage-card-img img {
            transform: scale(1.1);
        }
        
        .heritage-card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 20px;
            color: white;
        }
        
        .heritage-card-content {
            padding: 20px;
        }
        
        .heritage-card-content h5 {
            font-weight: 700;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        /* Historical Slider */
        .historical-slider {
            background: linear-gradient(135deg, #2c3e50, #1a2634);
            padding: 80px 0;
            color: white;
            position: relative;
        }
        
        .slider-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            margin: 20px;
            transition: all 0.3s;
        }
        
        .slider-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.2);
        }
        
        .slider-card img {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        
        .slider-card-content {
            padding: 20px;
        }
        
        /* Features Section */
        .features-section {
            background: white;
            padding: 80px 0;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px;
            background: #fef9e8;
            border-radius: 20px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e67e22, #f39c12);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .feature-icon i {
            font-size: 2rem;
            color: white;
        }
        
        /* Testimonials */
        .testimonials-section {
            background: #fef9e8;
            padding: 80px 0;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .testimonial-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e67e22, #f39c12);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .testimonial-avatar i {
            font-size: 2.5rem;
            color: white;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            padding: 80px 0;
            color: white;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta-section .btn {
            background: white;
            color: #e67e22;
            padding: 12px 40px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .cta-section .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* Footer */
        footer {
            background: #1a2634;
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-logo i {
            font-size: 2rem;
            color: #e67e22;
        }
        
        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .footer-links a:hover {
            color: #e67e22;
            transform: translateX(-5px);
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: #e67e22;
            transform: translateY(-3px);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content .subtitle {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
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
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-map-marked-alt"></i> استكشاف
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-graduation-cap"></i> التعليم
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-history"></i> الأرشيف
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-robot"></i> رحلات ذكية
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (Auth::isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="">
                                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                            </a></li>
                            <li><a class="dropdown-item" href="">
                                <i class="fas fa-user"></i> ملفي الشخصي
                            </a></li>
                            <li><a class="dropdown-item" href="">
                                <i class="fas fa-route"></i> رحلاتي
                            </a></li>
                            <?php if (Auth::isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="">
                                    <i class="fas fa-tachometer-alt"></i> لوحة التحكم (مدير)
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> تسجيل خروج
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus"></i> إنشاء حساب
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div data-aos="fade-up" data-aos-duration="1000">
            <h1>اكتشف تراث الجزائر العريق</h1>
            <p class="subtitle">رحلة عبر الزمن لاستكشاف التاريخ والثقافة والمواقع الأثرية في ربوع الجزائر</p>
            <div class="hero-buttons">
			


              
				
			  <?php if (Auth::isLoggedIn()): ?>
                <a href="users_pages/index.php" class="btn btn-primary-custom">
                    <i class="fas fa-compass"></i> ابدأ الاستكشاف الآن
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary-custom">
                    <i class="fas fa-user-plus"></i> سجل الآن مجاناً
                </a>
            <?php endif; ?>


         	
				
				
				
				
				
				
				
				
                <a href="login.php" class="btn btn-outline-custom">
                    <i class="fas fa-graduation-cap"></i> تعلم التاريخ
                </a>
            </div>
        </div>
    </div>
    <svg class="hero-wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
        <path fill="#ffffff" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
    </svg>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2 data-aos="fade-up">انضم إلى آلاف المستكشفين</h2>
        <p data-aos="fade-up" data-aos-delay="100">اكتشف تراث الجزائر وتاريخها العريق من خلال منصتنا</p>
        <div data-aos="fade-up" data-aos-delay="200">
            <?php if (Auth::isLoggedIn()): ?>
                <a href="users_pages/explore.php" class="btn">
                    <i class="fas fa-map-marked-alt"></i> ابدأ الاستكشاف الآن
                </a>
            <?php else: ?>
                <a href="register.php" class="btn">
                    <i class="fas fa-user-plus"></i> سجل الآن مجاناً
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-city"></i>
                    </div>
                    <div class="stat-number"><?= $stats['states'] ?></div>
                    <div class="stat-label">ولاية جزائرية</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-monument"></i>
                    </div>
                    <div class="stat-number"><?= $stats['sites'] ?></div>
                    <div class="stat-label">موقع أثري</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-number"><?= $stats['lessons'] ?></div>
                    <div class="stat-label">درس تعليمي</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-number"><?= $stats['archives'] ?></div>
                    <div class="stat-label">حقبة تاريخية</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Heritage Sites -->
<section class="py-5">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>أبرز المعالم الأثرية</h2>
            <div class="title-decoration"></div>
            <p>استكشف أهم المواقع التاريخية في الجزائر</p>
        </div>
        <div class="row">
            <?php foreach ($featuredSites as $site): ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="heritage-card">
                    <div class="heritage-card-img">
                        <img src="<?= $site['image_url'] ?: 'https://via.placeholder.com/400x250?text=' . urlencode($site['name']) ?>" alt="<?= htmlspecialchars($site['name']) ?>">
                        <div class="heritage-card-overlay">
                            <small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($site['state_name']) ?></small>
                        </div>
                    </div>
                    <div class="heritage-card-content">
                        <h5><?= htmlspecialchars($site['name']) ?></h5>
                        <p class="text-muted small"><?= mb_substr(htmlspecialchars($site['description']), 0, 80) ?>...</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">
                            اكتشف المزيد <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4" data-aos="fade-up">
            <a href="login.php" class="btn btn-primary-custom btn-lg">
                استكشف جميع المواقع <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>
</section>

<!-- Historical Slider Section -->
<section class="historical-slider">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2 style="color: white;">رحلة عبر الزمن</h2>
            <div class="title-decoration" style="background: white;"></div>
            <p style="color: rgba(255,255,255,0.8);">اكتشف العصور التاريخية التي شكلت هوية الجزائر</p>
        </div>
        <div class="row">
            <?php foreach ($historicalPeriods as $period): ?>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="slider-card">
                    <img src="<?= $period['featured_image'] ?: 'https://via.placeholder.com/400x200?text=' . urlencode($period['period_name']) ?>" alt="<?= htmlspecialchars($period['period_name']) ?>">
                    <div class="slider-card-content">
                        <h5><?= htmlspecialchars($period['period_name']) ?></h5>
                        <p class="small"><?= mb_substr(htmlspecialchars($period['description']), 0, 100) ?>...</p>
                        <a href="#" class="btn btn-sm btn-outline-light">
                            اكتشف المزيد
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>ماذا نقدم لك؟</h2>
            <div class="title-decoration"></div>
            <p>منصة متكاملة تجمع بين الثقافة والتعليم والسياحة</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h5>استكشاف المواقع الأثرية</h5>
                    <p class="text-muted">اكتشف أهم المعالم التاريخية في كل ولاية مع صور وفيديوهات وقصص تاريخية</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h5>تعلم التاريخ والجغرافيا</h5>
                    <p class="text-muted">دروس تعليمية مصورة وخرائط ذهنية لجميع المستويات مع مصادر موثقة</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h5>رحلات ذكية متكاملة</h5>
                    <p class="text-muted">اقتراحات ذكية للرحلات تشمل المواقع الأثرية والفنادق والمطاعم التقليدية</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <h5>خدمات سياحية متكاملة</h5>
                    <p class="text-muted">عرض فنادق ومطاعم تقليدية قريبة من المواقع السياحية مع تقييمات حقيقية</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h5>أرشيف تاريخي شامل</h5>
                    <p class="text-muted">عرض العصور التاريخية التي مرت بها الجزائر مع توثيق بالمصادر</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5>ثقافة وعادات محلية</h5>
                    <p class="text-muted">التعرف على الثقافة والعادات الخاصة بكل منطقة والملابس التقليدية</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured States Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>أبرز الولايات السياحية</h2>
            <div class="title-decoration"></div>
            <p>اكتشف أجمل المناطق الجزائرية وتراثها الفريد</p>
        </div>
        <div class="row">
            <?php foreach ($featuredStates as $state): ?>
            <div class="col-md-4 col-lg-2 col-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                <a href="users_pages/state_page.php?id=<?= $state['state_id'] ?>" class="text-decoration-none">
                    <div class="text-center">
                        <div class="rounded-circle overflow-hidden mx-auto mb-3" style="width: 100px; height: 100px;">
                            <img src="<?= $state['preview_image'] ?: 'https://via.placeholder.com/100?text=' . urlencode($state['name']) ?>" alt="<?= htmlspecialchars($state['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <h6 class="mb-1"><?= htmlspecialchars($state['name']) ?></h6>
                        <small class="text-muted"><?= $state['sites_count'] ?> موقع أثري</small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>ماذا يقول زوارنا؟</h2>
            <div class="title-decoration"></div>
            <p>آراء المستخدمين عن تجربتهم مع المنصة</p>
        </div>
        <div class="row">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="testimonial-card">
                    <div class="testimonial-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h6>أحمد بن سليمان</h6>
                    <p class="small text-muted">باحث في التاريخ</p>
                    <p class="mt-3">"منصة رائعة تجمع بين الأصالة والحداثة. استفدت كثيراً من المحتوى التاريخي الموثق."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="testimonial-card">
                    <div class="testimonial-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h6>فاطمة الزهراء</h6>
                    <p class="small text-muted">مرشدة سياحية</p>
                    <p class="mt-3">"أداة ممتازة للتعريف بالتراث الجزائري. الرحلات الذكية وفرت علي الكثير من الوقت."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="testimonial-card">
                    <div class="testimonial-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h6>محمد الأمين</h6>
                    <p class="small text-muted">طالب جامعي</p>
                    <p class="mt-3">"الدروس التعليمية ساعدتني كثيراً في فهم تاريخ الجزائر بطريقة مبسطة وشيقة."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="footer-logo">
                    <i class="fas fa-landmark fa-2x mb-3 d-block"></i>
                    <h5><?= SITE_NAME ?></h5>
                    <p class="text-muted">منصة رقمية متكاملة تهدف إلى التعريف بالتراث الجزائري وتعليم التاريخ بطريقة تفاعلية.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h5>روابط سريعة</h5>
                <ul class="list-unstyled footer-links">
                    <li><a href="index.php"><i class="fas fa-chevron-left"></i> الرئيسية</a></li>
                    <li><a href="users_pages/explore.php"><i class="fas fa-chevron-left"></i> استكشاف</a></li>
                    <li><a href="users_pages/lessons.php"><i class="fas fa-chevron-left"></i> التعليم</a></li>
                    <li><a href="users_pages/archive.php"><i class="fas fa-chevron-left"></i> الأرشيف التاريخي</a></li>
                    <li><a href="users_pages/smart_trips.php"><i class="fas fa-chevron-left"></i> الرحلات الذكية</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>تواصل معنا</h5>
                <p><i class="fas fa-envelope"></i> info@heritage.dz</p>
                <p><i class="fas fa-phone"></i> +213 123 456 789</p>
                <div class="social-links mt-3">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <hr class="mt-4">
        <div class="text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> منصة التراث والتاريخ الجزائري. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialize AOS
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(44, 62, 80, 0.98)';
        } else {
            navbar.style.background = 'rgba(44, 62, 80, 0.95)';
        }
    });
</script>
</body>
</html>