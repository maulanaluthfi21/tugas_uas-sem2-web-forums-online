<?php
include 'config.php';
include 'helpers.php';
include 'notification_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($categories)) {
    $categories = [];
}

$selected_category = '';
$category_filter = '';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
    $selected_category = $_GET['category'];
    $category_filter = "WHERE topics.category = '" . $conn->real_escape_string($selected_category) . "'";
}

$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$current_user_avatar = null;
$current_user_is_admin = false;
/* ==========================================
   INISIALISASI NOTIFIKASI PERTEMANAN
========================================== */

$friend_request_count = 0;

if ($current_user_id > 0) {

    $notif_sql = "
        SELECT COUNT(*) AS total
        FROM friends
        WHERE receiver_id = $current_user_id
        AND status='pending'
    ";

    $notif_result = $conn->query($notif_sql);

    if ($notif_result && $notif_result->num_rows > 0) {

        $friend_request_count = (int)$notif_result->fetch_assoc()['total'];
    }
}
if ($current_user_id > 0) {
    $current_user_result = $conn->query("SELECT avatar, is_admin, role FROM users WHERE id = $current_user_id LIMIT 1");
    if ($current_user_result && $current_user_result->num_rows > 0) {
        $current_user_data = $current_user_result->fetch_assoc();
        $current_user_avatar = $current_user_data['avatar'];
        $current_user_is_admin = ($current_user_data['is_admin'] == 1 || $current_user_data['role'] === 'admin');
        /* ===========================
   HITUNG PERMINTAAN TEMAN
=========================== */

        $friend_request_count = 0;

        if ($current_user_id > 0) {

            $notif_sql = "
        SELECT COUNT(*) AS total
        FROM friends
        WHERE receiver_id = $current_user_id
        AND status='pending'
    ";

            $notif_result = $conn->query($notif_sql);

            if ($notif_result && $notif_result->num_rows) {

                $friend_request_count =
                    $notif_result->fetch_assoc()['total'];
            }
        }
    }
}

$query = "SELECT topics.*, users.username, users.avatar AS user_avatar,
          (SELECT COUNT(*) FROM topic_likes WHERE topic_likes.topic_id = topics.id AND topic_likes.user_id = $current_user_id) AS user_liked,
          (SELECT COUNT(*) FROM comments WHERE comments.topic_id = topics.id) AS total_comments
          FROM topics 
          JOIN users ON topics.user_id = users.id 
          $category_filter 
          ORDER BY topics.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIBSEN BETA</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: radial-gradient(circle at top, #f3f3f3 0%, #f6fafc 35%, #ffffff 100%);
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .page-header {
            background: #ffffff;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
            padding: 18px 0;
            position: sticky;
            top: 0;
            z-index: 700;
        }

        .page-header .container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            max-width: 1360px;
        }

        .header-left,
        .header-center,
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left {
            flex: 1 1 380px;
            min-width: 320px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-center {
            justify-content: center;
            flex: 0 1 auto;
            gap: 14px;
            flex-wrap: wrap;
        }

        .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #0f172a !important;
            font-weight: 800;
            font-size: 1.45rem;
            letter-spacing: -1px;
            text-decoration: none;
        }

        .logo-mark {
            width: auto;
            height: 48px;
            border-radius: 0;
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .logo-image {
            width: auto;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .logo-image {
            width: auto;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-text {
            font-size: 1.05rem;
            letter-spacing: -0.05em;
            color: #0f172a;
        }

        .header-search {
            display: flex;
            align-items: center;
            background: #f0f2f5;
            border-radius: 999px;
            padding: 10px 16px;
            min-width: 320px;
            max-width: 480px;
            width: 100%;
            color: #334155;
            border: 1px solid #1d7974;
        }

        .header-search i {
            margin-right: 12px;
            font-size: 1rem;
            color: #64748b;
        }

        .header-search input {
            border: none;
            background: transparent;
            color: #0f172a;
            width: 100%;
            font-size: 0.98rem;
            outline: none;
        }

        .header-nav,
        .action-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            transition: all 0.18s ease;
        }

        .header-nav:hover,
        .header-nav.active,
        .action-pill:hover {
            background: #e8f4f6;
            color: #0b525b;
            transform: translateY(-1px);
            border-color: #b9d9e2;
        }

        .header-nav.active {
            background: #d9f4f0;
            color: #0a7c74;
            border-color: #0a959c;
        }

        .profile-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #27968c;
            color: #0f172a;
            text-decoration: none;
            transition: all 0.18s ease;
        }

        .profile-pill:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }

        .profile-pill img,
        .profile-pill .avatar-placeholder {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
        }

        .header-actions {
            flex: 0 1 auto;
            justify-content: flex-end;
        }

        .header-secondary {
            display: none;
        }

        @media (max-width: 900px) {
            .header-center {
                display: none;
            }

            .header-actions {
                justify-content: flex-start;
            }

            .header-left {
                width: 100%;
            }
        }

        .nav-btn-modern {
            font-size: 14px;
            font-weight: 800;
            padding: 8px 18px;
            border-radius: 50px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-btn-light {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .nav-btn-light:hover {
            background-color: #ffffff;
            color: #0A959C !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-btn-white {
            background-color: #ffffff;
            color: #0A959C !important;
            border: 1px solid #ffffff;
        }

        .nav-btn-white:hover {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .nav-btn-danger {
            background-color: #ffeef0;
            color: #dc3545 !important;
            border: 1px solid #ffeef0;
        }

        .nav-btn-danger:hover {
            background-color: #dc3545;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
        }

        .feed-container {
            max-width: 840px;
            margin: 0 auto;
            padding: 30px 0 50px;
        }

        .create-box {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(247, 252, 255, 0.96) 100%);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            padding: 22px 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .create-box-top {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .create-trigger {
            background-color: #f1f7f9;
            border-radius: 999px;
            padding: 14px 20px;
            flex-grow: 1;
            color: #334155;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s, transform 0.2s;
        }

        .create-trigger:hover {
            background-color: #e2f1f4;
            color: #0a7279;
            transform: translateY(-1px);
        }

        .fb-card {
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            margin-bottom: 24px;
            padding: 22px;
            display: block;
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .fb-card:hover {
            color: inherit;
            transform: translateY(-1px);
        }

        .fb-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            position: relative;
            gap: 12px;
        }

        .fb-author-info {
            display: flex;
            flex-direction: column;
            margin-left: 10px;
        }

        .fb-title {
            font-weight: 800;
            font-size: 18px;
            color: #111827;
            margin-bottom: 10px;
            letter-spacing: -0.4px;
        }

        .fb-text {
            font-size: 15px;
            line-height: 1.75;
            color: #334155;
            margin-bottom: 18px;
            word-wrap: break-word;
        }

        .fb-category {
            display: inline-block;
            background: #f0fcf9;
            color: #0f766e;
            font-size: 12px;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 14px;
            font-weight: 700;
        }

        .fb-image {
            width: 100%;
            border-radius: 24px;
            max-height: 520px;
            object-fit: cover;
            margin: 18px 0;
            border: 1px solid #e2e8f0;
        }

        .fb-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 0 0;
            border-top: 1px solid #eef2f7;
            margin-top: 16px;
        }

        .fb-action-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.18s ease;
        }

        .fb-action-btn:hover {
            background-color: #eef4f7;
            color: #0f172a;
        }

        .fb-action-btn.liked {
            color: #0a959c !important;
            border-color: rgba(10, 149, 156, 0.2);
        }

        .action-count-span {
            font-size: 14px;
            font-weight: 700;
        }

        .filter-scroll {
            display: flex;
            overflow-x: auto;
            padding: 8px 0 18px 0;
            margin-bottom: 12px;
            scrollbar-width: none;
        }

        .filter-scroll::-webkit-scrollbar {
            display: none;
        }

        .filter-btn {
            white-space: nowrap;
            border-radius: 999px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 700;
            margin-right: 10px;
            border: none;
            background-color: #f5f7fa;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            background-color: #e4eff7;
        }

        .filter-btn.active {
            background-color: #dbf2f0;
            color: #0a796f;
        }

        .create-trigger:hover {
            background-color: #e4e6eb;
            color: #5c636a;
        }

        .fb-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
            padding: 16px 18px 8px 18px;
            display: block;
            text-decoration: none;
            color: inherit;
            border: 1px solid #eef1f4;
        }

        .fb-card:hover {
            color: inherit;
        }

        .color-inherit {
            color: #1c1e21 !important;
        }

        .fb-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 14px;
            position: relative;
        }

        .fb-author-info {
            display: flex;
            flex-direction: column;
            margin-left: 10px;
        }

        .fb-profile-link {
            text-decoration: none;
            color: #1c1e21;
            transition: color 0.15s ease;
        }

        .fb-profile-link:hover .fb-author-name {
            color: #0A959C;
            text-decoration: underline;
        }

        .fb-author-name {
            font-weight: 700;
            font-size: 15px;
            color: #1c1e21;
        }

        .fb-meta {
            font-size: 12px;
            color: #65676b;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 1px;
        }

        .fb-title {
            font-weight: 700;
            font-size: 17px;
            color: #1c1e21;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .fb-text {
            font-size: 15px;
            line-height: 1.5;
            color: #2d3136;
            margin-bottom: 14px;
            word-wrap: break-word;
        }

        .fb-category {
            display: inline-block;
            background: rgba(10, 149, 156, 0.08);
            color: #0A959C;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 50px;
            margin-bottom: 14px;
            font-weight: 700;
        }

        .fb-image {
            width: calc(100% + 36px);
            margin-left: -18px;
            margin-right: -18px;
            max-height: 450px;
            object-fit: cover;
            border-top: 1px solid #f0f2f5;
            border-bottom: 1px solid #f0f2f5;
            margin-bottom: 12px;
        }

        .fb-video {
            width: calc(100% + 36px);
            margin-left: -18px;
            margin-right: -18px;
            max-height: 480px;
            background: #000;
            border-top: 1px solid #f0f2f5;
            border-bottom: 1px solid #f0f2f5;
            margin-bottom: 12px;
            display: block;
        }

        .fb-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            padding: 6px 0;
            border-top: 1px solid #f0f2f5;
            margin-top: 8px;
        }

        .fb-action-btn {
            background: none;
            border: none;
            color: #5c636a;
            font-weight: 600;
            font-size: 14px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s;
        }

        .fb-action-btn:hover {
            background-color: #f4f6f8;
            color: #1c1e21;
        }

        .fb-action-btn.liked {
            color: #0A959C !important;
        }

        .action-count-span {
            font-size: 14px;
            font-weight: 700;
        }

        .filter-scroll {
            display: flex;
            overflow-x: auto;
            padding: 4px 0 14px 0;
            margin-bottom: 10px;
            scrollbar-width: none;
        }

        .filter-scroll::-webkit-scrollbar {
            display: none;
        }

        .filter-btn {
            white-space: nowrap;
            border-radius: 50px;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 600;
            margin-right: 8px;
            border: none;
            background-color: #e4e6eb;
            color: #4b4f56;
            text-decoration: none;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background-color: #dcdfe3;
        }

        .filter-btn.active {
            background-color: rgba(10, 149, 156, 0.1);
            color: #0A959C;
        }

        .page-layout {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            width: 100%;
            max-width: 1400px;
            margin: 30px auto 40px;
            padding: 0 16px;
        }

        .sidebar {
            position: sticky;
            top: 110px;
            width: 280px;
            align-self: flex-start;
        }

        .sidebar-card {
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            padding: 18px;
            min-height: calc(100vh - 150px);
        }

        .right-sidebar {
            width: 780px;
            align-self: flex-start;
            margin-left: 0;
        }

        .right-card {
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.06);
            padding: 22px;
            margin-bottom: 22px;
        }

        .right-card-title {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
            font-size: 18px;
        }

        .sponsor-box {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 14px;
            border-radius: 18px;
            background: #f3fbfb;
        }

        .sponsor-image {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 18px;
            background: #e9f4f3;
        }

        main {
            flex: 1;
            margin-left: 0;
        }

        .sidebar-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 22px;
        }

        .sidebar-profile {
            display: flex;
            gap: 12px;
            align-items: center;
            text-decoration: none;
            color: #111827;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.12s;
            margin-bottom: 12px;
        }

        .sidebar-profile:hover {
            background: #f4f6f8;
        }

        .sidebar-profile .avatar-placeholder,
        .sidebar-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sidebar-name {
            font-weight: 700;
            font-size: 16px;
            color: #111827;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            color: #111827;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 10px;
            transition: background 0.12s, color 0.12s;
            margin-bottom: 8px;
        }

        .sidebar-item:last-child {
            margin-bottom: 0;
        }

        .sidebar-item:hover {
            background: #f4f6f8;
            color: #0A959C;
        }

        .sidebar-item .icon-wrap {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e6f7f6, #f0fcfb);
            color: #0A959C;
            flex-shrink: 0;
            font-size: 18px;
        }

        .right-sidebar {
            width: 800px;
            align-self: flex-start;
            margin-left: 0;
        }

        .right-card {
            background: #ffffff;
            border: 1px solid #eef1f4;
            border-radius: 22px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            padding: 18px;
            margin-bottom: 20px;
        }

        .right-card-title {
            font-weight: 800;
            color: #2b3940;
            margin-bottom: 12px;
        }

        .sponsor-box {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 10px;
            border-radius: 12px;
            background: #f7fbfb;
        }

        .sponsor-image {
            width: 78px;
            height: 78px;
            object-fit: cover;
            border-radius: 12px;
            background: #e9f4f3;
        }

        .sidebar-footer {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
            margin-top: 4px;
        }

        .sidebar .view-more {
            color: #0A959C;
            font-weight: 700;
        }

        @media (max-width: 1060px) {
            .page-layout {
                display: block;
            }

            .sidebar {
                position: relative;
                top: 0;
                width: 100%;
                margin-bottom: 24px;
            }

            .sidebar-card {
                min-height: auto;
            }

            main {
                margin-left: 0;
            }
        }

        .notification-icon {

            position: relative;

        }

        .notification-badge {

            position: absolute;

            top: -5px;

            right: -5px;

            background: red;

            color: white;

            width: 22px;

            height: 22px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: bold;

        }
    </style>
</head>

<body>
    <!-- Toast Notification -->
    <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <header class="page-header">
        <div class="container">
            <div class="header-left">
                <a class="navbar-brand" href="index.php">
                    <span class="logo-mark">
                        <img src="assets/img/logo.png" alt="Logo TIPSEN" class="logo-image">
                    </span>
                    <span class="brand-text">TIPSEN</span>
                </a>
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Cari di DIPSEN..." aria-label="Cari" />
                </div>
            </div>

            <div class="header-center">
                <a href="index.php" class="header-nav active" title="Beranda"><i class="fas fa-home"></i></a>
                <a href="videos.php" class="header-nav" title="Video"><i class="fas fa-video"></i></a>
                <a href="index.php" class="header-nav" title="Grup"><i class="fas fa-users"></i></a>
                <a href="friend_requests.php" class="top-icon notification-icon">

                    <i class="fas fa-bell"></i>

                    <?php if ($friend_request_count > 0) { ?>

                        <span class="notification-badge">

                            <?= $friend_request_count ?>

                        </span>

                    <?php } ?>

                </a>
            </div>

            <div class="header-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="edit_profile.php" class="profile-pill" title="Profil">
                        <?php echo getAvatarHTML($_SESSION['username'], $current_user_avatar ?? null, '30'); ?>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
                    <?php if ($current_user_is_admin): ?>
                        <a href="admin/dashboard.php" class="action-pill" title="Admin Dashboard"><i class="fas fa-user-shield"></i></a>
                    <?php endif; ?>
                    <a href="logout.php" class="action-pill" title="Keluar"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" class="profile-pill" title="Masuk"><i class="fas fa-sign-in-alt"></i> Masuk</a>
                    <a href="register2.php" class="action-pill" title="Daftar"><i class="fas fa-user-plus"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-layout">
        <aside class="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-title">Pintasan Anda</div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="view_profile.php?username=<?php echo urlencode($_SESSION['username']); ?>" class="sidebar-profile">
                        <?php echo getAvatarHTML($_SESSION['username'], $current_user_avatar ?? null, '44'); ?>
                        <div>
                            <div class="sidebar-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            <div class="sidebar-footer">Lihat profil Anda</div>
                        </div>
                    </a>
                <?php endif; ?>
                <a href="index.php" class="sidebar-item"><span class="icon-wrap"><i class="fas fa-house-chimney"></i></span>Beranda</a>
                <a href="friends.php" class="sidebar-item">
                    <span class="icon-wrap">
                        <i class="fas fa-user-friends"></i>
                    </span>
                    Teman
                </a>
                <a href="index.php" class="sidebar-item"><span class="icon-wrap"><i class="fas fa-newspaper"></i></span>Kenangan</a>
                <a href="index.php" class="sidebar-item"><span class="icon-wrap"><i class="fas fa-bookmark"></i></span>Tersimpan</a>
                <a href="index.php" class="sidebar-item"><span class="icon-wrap"><i class="fas fa-layer-group"></i></span>Grup</a>
                <a href="index.php" class="sidebar-item view-more"><span class="icon-wrap"><i class="fas fa-chevron-down"></i></span>Lihat selengkapnya</a>
            </div>
        </aside>

        <main>
            <div class="feed-container">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="create-box">
                        <div class="create-box-top">
                            <?php echo getAvatarHTML($_SESSION['username'], $current_user_avatar ?? null, '42'); ?>
                            <a href="create_topic.php" class="create-trigger">Apa yang Anda pikirkan saat ini? Buat Diskusi Baru...</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="filter-scroll">
                    <a href="index.php" class="filter-btn <?php echo $selected_category === '' ? 'active' : ''; ?>">Beranda Utama</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?php echo urlencode($cat); ?>" class="filter-btn <?php echo $selected_category === $cat ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="fb-card">

                                <div class="fb-card-header">
                                    <a href="view_profile.php?username=<?php echo urlencode($row['username']); ?>" class="fb-profile-link d-flex align-items-center">
                                        <?php echo getAvatarHTML($row['username'], $row['user_avatar'] ?? null, '42'); ?>
                                        <div class="fb-author-info">
                                            <span class="fb-author-name">
                                                <?php echo htmlspecialchars($row['username']); ?>
                                                <?php echo getVerifiedBadgeHTML($row['user_id'] == 1 ? 1 : 0); ?>
                                            </span>
                                            <div class="fb-meta">
                                                <span><?php echo getRelativeTime($row['created_at']); ?></span> • <i class="fas fa-globe-asia" title="Publik"></i>
                                            </div>
                                        </div>
                                    </a>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']): ?>
                                        <a href="delete_topic.php?id=<?php echo $row['id']; ?>" class="text-secondary text-decoration-none ms-auto fw-bold" style="font-size: 20px; position: absolute; right: 0; top: 0;" onclick="return confirm('Hapus kiriman ini?')" title="Hapus">×</a>
                                    <?php endif; ?>
                                </div>

                                <a href="view_topic.php?id=<?php echo $row['id']; ?>" class="text-decoration-none color-inherit">
                                    <div class="fb-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="fb-text"><?php echo htmlspecialchars(truncateText($row['content'], 250)); ?></div>

                                    <?php if (!empty($row['category'])): ?>
                                        <span class="fb-category"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($row['category']); ?></span>
                                    <?php endif; ?>

                                    <?php if (!empty($row['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Media" class="fb-image">
                                    <?php endif; ?>
                                </a>

                                <?php
                                $video_src = '';
                                if (!empty($row['video'])) {
                                    $video_src = 'uploads/videos/' . $row['video'];
                                } elseif (!empty($row['video_path'])) {
                                    $video_src = $row['video_path'];
                                }
                                ?>
                                <?php if ($video_src !== ''): ?>
                                    <video src="<?php echo htmlspecialchars($video_src); ?>" class="fb-video" controls preload="metadata"></video>
                                <?php endif; ?>

                                <div class="fb-actions">
                                    <?php $is_liked = (isset($row['user_liked']) && $row['user_liked'] > 0); ?>

                                    <button class="fb-action-btn <?php echo $is_liked ? 'liked' : ''; ?>" onclick="likePost(<?php echo $row['id']; ?>, this)">
                                        <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-thumbs-up"></i>
                                        <span id="like-count-<?php echo $row['id']; ?>" class="action-count-span">
                                            <?php echo intval($row['likes'] ?? 0); ?>
                                        </span>
                                    </button>

                                    <a href="view_topic.php?id=<?php echo $row['id']; ?>" class="fb-action-btn text-decoration-none">
                                        <i class="far fa-comment-alt"></i>
                                        <span class="action-count-span">
                                            <?php echo intval($row['total_comments'] ?? 0); ?>
                                        </span>
                                    </a>

                                    <button class="fb-action-btn" onclick="alert('Tautan berhasil disalin!')">
                                        <i class="far fa-share-square"></i> <span></span>
                                    </button>
                                </div>

                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="fb-card text-center text-muted py-5">
                            <div style="font-size: 50px; margin-bottom: 10px;">👥</div>
                            <div class="fw-bold">Belum ada kiriman di beranda ini.</div>
                        </div>
                    <?php endif; ?>
                </div>
        </main>
    </div>

    </aside>
    </div>

    <script>
        function likePost(topicId, buttonElement) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Silakan login terlebih dahulu!');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            fetch('like_handler.php?id=' + topicId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const countSpan = document.getElementById('like-count-' + topicId);
                        countSpan.innerText = data.new_likes;
                        buttonElement.classList.toggle('liked');
                        const icon = buttonElement.querySelector('i');
                        icon.className = buttonElement.classList.contains('liked') ? "fas fa-thumbs-up" : "far fa-thumbs-up";
                    }
                }).catch(err => console.error(err));
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>