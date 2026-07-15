<?php
include 'config.php';
include 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$current_user_avatar = null;
$current_user_is_admin = false;
if ($current_user_id > 0) {
    $current_user_result = $conn->query("SELECT avatar, is_admin, role FROM users WHERE id = $current_user_id LIMIT 1");
    if ($current_user_result && $current_user_result->num_rows > 0) {
        $current_user_data = $current_user_result->fetch_assoc();
        $current_user_avatar = $current_user_data['avatar'];
        $current_user_is_admin = ($current_user_data['is_admin'] == 1 || $current_user_data['role'] === 'admin');
    }
}

// Ambil seluruh topik yang memiliki video (baik kolom `video` maupun `video_path`)
$query = "SELECT topics.*, users.username, users.avatar AS user_avatar
          FROM topics
          JOIN users ON topics.user_id = users.id
          WHERE (topics.video IS NOT NULL AND topics.video != '')
             OR (topics.video_path IS NOT NULL AND topics.video_path != '')
          ORDER BY topics.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video - TIBSEN BETA3</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        body {
            background: radial-gradient(circle at top, #f3f3f3 0%, #f6fafc 35%, #ffffff 100%);
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }
        .watch-main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 16px 60px;
        }
        .watch-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .watch-title i { color: #1877f2; }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }
        .video-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
            border: 1px solid #eef0f2;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .video-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
            color: inherit;
        }
        .video-card video {
            width: 100%;
            height: 170px;
            object-fit: cover;
            background: #000;
            display: block;
            pointer-events: none;
        }
        .video-card-body {
            padding: 12px 14px;
        }
        .video-card-title {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .video-card-meta {
            font-size: 12.5px;
            color: #65676b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #65676b;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #eef0f2;
        }
        .empty-state div:first-child { font-size: 50px; margin-bottom: 10px; }
    </style>
</head>
<body>

    <header class="page-header">
        <div class="container">
            <div class="header-left">
                <a class="navbar-brand" href="index.php">
                    <span class="logo-mark">F</span>
                    <span class="brand-text">FORKOM UIMY</span>
                </a>
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Cari di FORKOM..." aria-label="Cari" />
                </div>
            </div>

            <div class="header-center">
                <a href="index.php" class="header-nav" title="Beranda"><i class="fas fa-home"></i></a>
                <a href="videos.php" class="header-nav active" title="Video"><i class="fas fa-video"></i></a>
                <a href="index.php" class="header-nav" title="Grup"><i class="fas fa-users"></i></a>
                <a href="index.php" class="header-nav" title="Notifikasi"><i class="fas fa-bell"></i></a>
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

    <main class="watch-main">
        <div class="watch-title"><i class="fas fa-video"></i> Video</div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="video-grid">
                <?php while ($row = $result->fetch_assoc()):
                    $video_src = '';
                    if (!empty($row['video'])) {
                        $video_src = 'uploads/videos/' . $row['video'];
                    } elseif (!empty($row['video_path'])) {
                        $video_src = $row['video_path'];
                    }
                    if ($video_src === '') continue;
                ?>
                    <a href="view_topic.php?id=<?php echo $row['id']; ?>" class="video-card">
                        <video src="<?php echo htmlspecialchars($video_src); ?>" preload="metadata" muted></video>
                        <div class="video-card-body">
                            <div class="video-card-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="video-card-meta">
                                <?php echo getAvatarHTML($row['username'], $row['user_avatar'] ?? null, '20'); ?>
                                <span><?php echo htmlspecialchars($row['username']); ?></span>
                                <span>•</span>
                                <span><?php echo getRelativeTime($row['created_at']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div>🎬</div>
                <div class="fw-bold">Belum ada video yang diunggah.</div>
                <div class="mt-1">Video yang ditambahkan lewat "Buat Diskusi Baru" akan muncul di sini.</div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
