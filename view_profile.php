<?php
include 'config.php';
include 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengambil username dari URL (contoh: view_profile.php?username=habibi)
if (isset($_GET['username'])) {
    $profile_username = $conn->real_escape_string(trim($_GET['username']));
    
    // Query untuk mengambil data user berdasarkan username yang diklik
    $user_query = "SELECT * FROM users WHERE username = '$profile_username'";
    $user_result = $conn->query($user_query);

    if ($user_result && $user_result->num_rows > 0) {
        $profile_user = $user_result->fetch_assoc();
        $profile_user_id = $profile_user['id'];
    } else {
        echo "<script>alert('User tidak ditemukan!'); window.location.href='index.php';</script>";
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

// Mengambil diskusi/topik yang pernah dibuat oleh user ini saja
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$topics_query = "SELECT topics.*, users.username, users.avatar AS user_avatar,
                (SELECT COUNT(*) FROM topic_likes WHERE topic_likes.topic_id = topics.id AND topic_likes.user_id = $current_user_id) AS user_liked,
                (SELECT COUNT(*) FROM comments WHERE comments.topic_id = topics.id) AS total_comments
                FROM topics 
                JOIN users ON topics.user_id = users.id 
                WHERE topics.user_id = $profile_user_id
                ORDER BY topics.created_at DESC";
$topics_result = $conn->query($topics_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?php echo htmlspecialchars($profile_username); ?> - FORKOM UIMY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f8;
            color: #1c1e21;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #0A959C 0%, #087d83 100%) !important;
            box-shadow: 0 4px 12px rgba(10, 149, 156, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 16px;
        }
        .navbar-brand {
            color: #ffffff !important;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -1px;
        }
        .profile-container {
            max-width: 620px;
            margin: 30px auto;
            padding: 0 8px;
        }
        /* Card Header Profil Modern */
        .profile-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 30px;
            text-align: center;
            border: 1px solid #eef1f4;
            margin-bottom: 25px;
        }
        .profile-avatar-wrapper {
            margin-bottom: 15px;
            display: inline-block;
        }
        .profile-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 5px;
        }
        .profile-bio {
            font-size: 14px;
            color: #718096;
            max-width: 400px;
            margin: 0 auto 15px auto;
        }
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding-top: 15px;
            border-top: 1px solid #f0f2f5;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0A959C;
        }
        .stat-label {
            font-size: 12px;
            color: #a0aec0;
            font-weight: 600;
            text-transform: uppercase;
        }
        /* Styling Feed Diskusi User */
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #4a5568;
            margin-bottom: 15px;
            padding-left: 5px;
        }
        .fb-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            margin-bottom: 20px;
            padding: 16px 18px 8px 18px;
            display: block;
            text-decoration: none;
            color: inherit;
            border: 1px solid #eef1f4;
        }
        .fb-card-header { display: flex; align-items: center; margin-bottom: 14px; }
        .fb-author-info { display: flex; flex-direction: column; margin-left: 10px; }
        .fb-author-name { font-weight: 700; font-size: 15px; color: #1c1e21; }
        .fb-meta { font-size: 12px; color: #65676b; display: flex; align-items: center; gap: 4px; }
        .fb-title { font-weight: 700; font-size: 17px; color: #1c1e21; margin-bottom: 6px; }
        .fb-text { font-size: 15px; line-height: 1.5; color: #2d3136; margin-bottom: 14px; word-wrap: break-word; }
        .fb-category { display: inline-block; background: rgba(10, 149, 156, 0.08); color: #0A959C; font-size: 12px; padding: 4px 12px; border-radius: 50px; margin-bottom: 14px; font-weight: 700; }
        .fb-image { width: calc(100% + 36px); margin-left: -18px; margin-right: -18px; max-height: 400px; object-fit: cover; border-top: 1px solid #f0f2f5; border-bottom: 1px solid #f0f2f5; margin-bottom: 12px; }
        .fb-actions { display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; padding: 6px 0; border-top: 1px solid #f0f2f5; margin-top: 8px; }
        .fb-action-btn { background: none; border: none; color: #5c636a; font-weight: 600; font-size: 14px; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .fb-action-btn.liked { color: #0A959C !important; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark mb-0">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda</a>
        </div>
    </nav>

    <div class="profile-container">
        
        <div class="profile-card">
            <div class="profile-avatar-wrapper">
                <?php echo getAvatarHTML($profile_username, $profile_user['avatar'] ?? null, '80'); ?>
            </div>
            <h2 class="profile-name">
                <?php echo htmlspecialchars($profile_username); ?>
                <?php echo getVerifiedBadgeHTML($profile_user_id == 1 ? 1 : 0); ?>
            </h2>
            <p class="profile-bio">Anggota aktif Komunitas Akademik FORKOM UIMY.</p>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $topics_result->num_rows; ?></div>
                    <div class="stat-label">Diskusi</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><i class="fas fa-circle-check text-success"></i></div>
                    <div class="stat-label">Status</div>
                </div>
            </div>
        </div>

        <h3 class="section-title">Diskusi oleh <?php echo htmlspecialchars($profile_username); ?></h3>

        <div>
            <?php if ($topics_result->num_rows > 0): ?>
                <?php while($row = $topics_result->fetch_assoc()): ?>
                    <div class="fb-card">
                        
                        <div class="fb-card-header">
                            <?php echo getAvatarHTML($row['username'], $row['user_avatar'] ?? null, '42'); ?>
                            <div class="fb-author-info">
                                <span class="fb-author-name"><?php echo htmlspecialchars($row['username']); ?></span>
                                <div class="fb-meta">
                                    <span><?php echo getRelativeTime($row['created_at']); ?></span> • <i class="fas fa-globe-asia"></i>
                                </div>
                            </div>
                        </div>

                        <a href="view_topic.php?id=<?php echo $row['id']; ?>" class="text-decoration-none" style="color: inherit;">
                            <div class="fb-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="fb-text"><?php echo htmlspecialchars(truncateText($row['content'], 250)); ?></div>
                            
                            <?php if (!empty($row['category'])): ?>
                                <span class="fb-category"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($row['category']); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Media" class="fb-image">
                            <?php endif; ?>
                        </a>

                        <div class="fb-actions">
                            <?php $is_liked = (isset($row['user_liked']) && $row['user_liked'] > 0); ?>
                            <span class="fb-action-btn <?php echo $is_liked ? 'liked' : ''; ?>">
                                <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-thumbs-up"></i> <?php echo intval($row['likes'] ?? 0); ?>
                            </span>
                            <a href="view_topic.php?id=<?php echo $row['id']; ?>" class="fb-action-btn">
                                <i class="far fa-comment-alt"></i> <?php echo intval($row['total_comments'] ?? 0); ?>
                            </a>
                            <span class="fb-action-btn" style="cursor: default;">
                                <i class="far fa-share-square"></i>
                            </span>
                        </div>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="fb-card text-center text-muted py-5">
                    <div class="fw-bold">User ini belum pernah membuat kiriman diskusi.</div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>