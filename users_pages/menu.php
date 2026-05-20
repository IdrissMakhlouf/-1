<?php
/**
 * User Sidebar Menu - Complete with All Pages
 * Heritage Platform - Algeria Cultural Heritage
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    return;
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="user-sidebar">
    <div class="user-info text-center py-4">
        <div class="user-avatar mb-3">
            <i class="fas fa-user-circle fa-4x text-primary"></i>
        </div>
        <h6><?= htmlspecialchars($_SESSION['username']) ?></h6>
        <small class="text-muted"><?= $_SESSION['role'] == 'admin' ? 'مدير' : 'زائر' ?></small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i> لوحة التحكم
        </a>
		<!-- في منتصف القائمة أضف هذا الرابط -->
<a class="nav-link <?= $current_page == 'ai_assistant.php' ? 'active' : '' ?>" href="ai_assistant.php">
    <i class="fas fa-robot"></i> المساعد الذكي
</a>
        <a class="nav-link <?= $current_page == 'explore.php' ? 'active' : '' ?>" href="explore.php">
            <i class="fas fa-map-marked-alt"></i> استكشاف الجزائر
        </a>
        <a class="nav-link <?= $current_page == 'lessons.php' ? 'active' : '' ?>" href="lessons.php">
            <i class="fas fa-graduation-cap"></i> الدروس التعليمية
        </a>
        <a class="nav-link <?= $current_page == 'archive.php' ? 'active' : '' ?>" href="archive.php">
            <i class="fas fa-history"></i> الأرشيف التاريخي
        </a>
        <a class="nav-link <?= $current_page == 'smart_trips.php' ? 'active' : '' ?>" href="smart_trips.php">
            <i class="fas fa-robot"></i> الرحلات الذكية
        </a>
        <a class="nav-link <?= $current_page == 'my_trips.php' ? 'active' : '' ?>" href="my_trips.php">
            <i class="fas fa-route"></i> رحلاتي
        </a>
        <a class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>" href="profile.php">
            <i class="fas fa-user"></i> ملفي الشخصي
        </a>
        <hr class="my-3">
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> تسجيل خروج
        </a>
    </nav>
</div>

<style>
    .user-sidebar {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .user-sidebar .user-info {
        border-bottom: 1px solid #eee;
    }
    .user-sidebar .user-avatar i {
        font-size: 4rem;
        color: #e67e22;
    }
    .user-sidebar .nav-link {
        color: #2c3e50;
        padding: 12px 20px;
        transition: all 0.3s;
        border-radius: 0;
    }
    .user-sidebar .nav-link:hover {
        background: #f8f9fa;
        color: #e67e22;
    }
    .user-sidebar .nav-link.active {
        background: #e67e22;
        color: white;
    }
    .user-sidebar .nav-link i {
        margin-left: 10px;
        width: 20px;
        text-align: center;
    }
    .user-sidebar hr {
        border-color: #eee;
        margin: 15px 0;
    }
    @media (max-width: 768px) {
        .user-sidebar {
            margin-bottom: 20px;
        }
        .user-sidebar .nav {
            display: flex;
            flex-wrap: wrap;
        }
        .user-sidebar .nav-link {
            flex: 1;
            text-align: center;
            font-size: 0.85rem;
            padding: 10px;
        }
        .user-sidebar .user-info {
            display: none;
        }
    }
</style>