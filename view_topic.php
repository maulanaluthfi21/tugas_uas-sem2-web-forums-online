<?php
include 'config.php';
include 'helpers.php';
include 'notification_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($topic_id <= 0) {
    header('Location: index.php');
    exit();
}

$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$topic_query = "SELECT topics.*, users.username, users.avatar AS user_avatar, users.is_admin,
    (SELECT COUNT(*) FROM topic_likes WHERE topic_likes.topic_id = topics.id) AS like_count,
    (SELECT COUNT(*) FROM comments WHERE comments.topic_id = topics.id) AS comment_count,
    (SELECT COUNT(*) FROM topic_likes WHERE topic_likes.topic_id = topics.id AND topic_likes.user_id = $current_user_id) AS user_liked
    FROM topics
    JOIN users ON topics.user_id = users.id
    WHERE topics.id = $topic_id
    LIMIT 1";

$topic_result = $conn->query($topic_query);
if (!$topic_result || $topic_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$topic = $topic_result->fetch_assoc();

$comments_table_exists = false;
$comment_columns = [];
$comments = [];
$comment_error = '';
$comment_success = '';

$tables_check = $conn->query("SHOW TABLES LIKE 'comments'");
if ($tables_check && $tables_check->num_rows > 0) {
    $comments_table_exists = true;
    $columns_result = $conn->query("SHOW COLUMNS FROM comments");
    if ($columns_result) {
        while ($column = $columns_result->fetch_assoc()) {
            $comment_columns[] = $column['Field'];
        }
    }
}

if ($comments_table_exists && !in_array('parent_id', $comment_columns, true)) {
    $conn->query("ALTER TABLE comments ADD COLUMN parent_id INT(11) DEFAULT NULL");
    $comment_columns[] = 'parent_id';
}

$commentLikesTableCheck = $conn->query("SHOW TABLES LIKE 'comment_likes'");
if (!$commentLikesTableCheck || $commentLikesTableCheck->num_rows === 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS comment_likes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        comment_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        PRIMARY KEY (id),
        UNIQUE KEY user_comment (comment_id, user_id),
        KEY comment_id (comment_id),
        KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']) && $current_user_id > 0 && $comments_table_exists) {
    $comment_text = trim($_POST['comment'] ?? '');
    if ($comment_text === '') {
        $comment_error = 'Tulis komentar terlebih dahulu sebelum mengirim.';
    } else {
        $safe_comment = $conn->real_escape_string($comment_text);
        $comment_fields = ['topic_id', 'user_id', 'comment'];
        $comment_values = [$topic_id, $current_user_id, "'$safe_comment'"];

        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        if ($parent_id > 0 && in_array('parent_id', $comment_columns, true)) {
            $comment_fields[] = 'parent_id';
            $comment_values[] = $parent_id;
        }

        if (in_array('created_at', $comment_columns, true)) {
            $comment_fields[] = 'created_at';
            $comment_values[] = 'NOW()';
        }

        $insert_comment_sql = 'INSERT INTO comments (' . implode(', ', $comment_fields) . ') VALUES (' . implode(', ', $comment_values) . ')';
        if ($conn->query($insert_comment_sql)) {
            // Buat notifikasi untuk pemilik postingan
            $topic_owner = "SELECT user_id FROM topics WHERE id = $topic_id";
            $topic_owner_result = $conn->query($topic_owner);
            $topic_owner_data = $topic_owner_result->fetch_assoc();
            $topic_owner_id = $topic_owner_data['user_id'];
            
            $commenter_name = '';
            $commenter_query = "SELECT username FROM users WHERE id = $current_user_id";
            $commenter_result = $conn->query($commenter_query);
            if ($commenter_result) {
                $commenter_data = $commenter_result->fetch_assoc();
                $commenter_name = $commenter_data['username'];
            }
            
            createNotification($conn, $topic_owner_id, $current_user_id, 'comment', "$commenter_name mengomentari postingan Anda", $topic_id);
            
            header('Location: view_topic.php?id=' . $topic_id);
            exit();
        }

        $comment_error = 'Gagal mengirim komentar: ' . $conn->error;
    }
}

if ($comments_table_exists) {
    $comments_query = "SELECT comments.*, users.username, users.avatar AS user_avatar, users.is_admin,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id) AS like_count,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id AND comment_likes.user_id = $current_user_id) AS user_liked,
        (SELECT COUNT(*) FROM comments AS reply_count WHERE reply_count.parent_id = comments.id) AS reply_count
        FROM comments
        JOIN users ON comments.user_id = users.id
        WHERE comments.topic_id = $topic_id
        ORDER BY comments.created_at ASC";
    $comments_result = $conn->query($comments_query);
    $comments_by_id = [];
    if ($comments_result) {
        while ($comment = $comments_result->fetch_assoc()) {
            $comment['replies'] = [];
            $comments_by_id[$comment['id']] = $comment;
        }

        foreach ($comments_by_id as $comment_id => $comment) {
            if (!empty($comment['parent_id']) && isset($comments_by_id[$comment['parent_id']])) {
                $comments_by_id[$comment['parent_id']]['replies'][] = $comment;
            } else {
                $comments[] = $comment;
            }
        }
    }
}

function render_comment_item($comment, $topic_id, $current_user_id) {
    // Cek apakah user adalah pemilik komentar
    $is_owner = ($current_user_id === intval($comment['user_id']));
    $is_admin = intval($comment['is_admin']) == 1;
    $can_delete = $is_owner || $is_admin;
    
    ?>
    <article class="comment-card <?php echo !empty($comment['parent_id']) ? 'comment-reply' : ''; ?>">
        <div class="comment-header">
            <?php echo getAvatarHTML($comment['username'], $comment['user_avatar'] ?? null, '42'); ?>
            <div>
                <p class="comment-author"><?php echo htmlspecialchars($comment['username']); ?> <?php echo getVerifiedBadgeHTML($comment['is_admin'] == 1 ? 1 : 0); ?></p>
                <p class="comment-meta"><?php echo getRelativeTime($comment['created_at'] ?? ''); ?></p>
            </div>
            <?php if ($can_delete): ?>
                <a href="delete_comment.php?id=<?php echo intval($comment['id']); ?>" onclick="return confirm('Hapus komentar ini?')" class="text-danger" style="position: absolute; right: 16px; top: 16px; text-decoration: none; font-size: 20px; cursor: pointer;">×</a>
            <?php endif; ?>
        </div>
        <div class="comment-body"><?php echo nl2br(htmlspecialchars($comment['comment'] ?? '')); ?></div>
        <div class="comment-footer">
            <span class="comment-time"><?php echo getRelativeTime($comment['created_at'] ?? ''); ?></span>
            <button type="button" class="comment-action comment-like-btn" data-comment-id="<?php echo intval($comment['id']); ?>">
                <i class="<?php echo intval($comment['user_liked']) ? 'fas' : 'far'; ?> fa-thumbs-up"></i>
                Suka <span class="comment-like-count"><?php echo intval($comment['like_count'] ?? 0); ?></span>
            </button>
            <button type="button" class="comment-action reply-toggle-btn" data-comment-id="<?php echo intval($comment['id']); ?>">
                <i class="far fa-comment"></i>
                Balas
            </button>
            <?php if (!empty($comment['reply_count'])): ?>
                <span class="comment-action reply-count"><?php echo intval($comment['reply_count']); ?> balasan</span>
            <?php endif; ?>
        </div>
        <form action="view_topic.php?id=<?php echo $topic_id; ?>" method="POST" class="reply-form" id="reply-form-<?php echo intval($comment['id']); ?>" style="display:none;">
            <textarea name="comment" placeholder="Tulis balasan..." required></textarea>
            <input type="hidden" name="parent_id" value="<?php echo intval($comment['id']); ?>">
            <button type="submit" name="submit_comment" class="btn-submit-comment mt-3">Kirim Balasan</button>
        </form>
        <?php if (!empty($comment['replies'])): ?>
            <div class="comment-replies">
                <?php foreach ($comment['replies'] as $reply): render_comment_item($reply, $topic_id, $current_user_id); endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - TIPSEN</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at top, #f3f3f3 0%, #f6fafc 35%, #ffffff 100%);
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
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
            padding: 0 16px;
            margin: 0 auto;
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
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0A959C 0%, #087d83 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 18px;
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
        .content-wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 18px 14px 36px;
        }
        .topic-card, .comment-card, .comment-form-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
            border: 1px solid #eef2f7;
            padding: 18px;
            margin-bottom: 20px;
        }
        .topic-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
            margin-bottom: 16px;
        }
        .topic-meta .meta-pill {
            background: rgba(10, 149, 156, 0.08);
            color: #0a959c;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
        }
        .topic-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 14px;
            line-height: 1.2;
        }
        .topic-content {
            font-size: 0.98rem;
            line-height: 1.75;
            color: #2d3136;
            margin-bottom: 16px;
            white-space: pre-line;
        }
        .topic-image {
            width: 100%;
            border-radius: 18px;
            max-height: 460px;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .topic-video {
            width: 100%;
            border-radius: 18px;
            max-height: 520px;
            background: #000;
            margin-bottom: 20px;
            display: block;
        }
        .topic-author {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }
        .topic-author .author-name {
            font-weight: 700;
            color: #1c1e21;
            margin: 0;
        }
        .topic-author .author-subtitle {
            font-size: 0.95rem;
            color: #65676b;
            margin: 0;
        }
        .topic-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .topic-actions button,
        .topic-actions a {
            border-radius: 999px;
            border: 1px solid #d8dee7;
            background: #ffffff;
            color: #1c1e21;
            padding: 10px 16px;
            transition: all 0.18s ease;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .topic-actions button:hover,
        .topic-actions a:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }
        .topic-actions .btn-primary {
            background: #0a959c;
            color: #ffffff;
            border-color: transparent;
        }
        .topic-actions .btn-primary:hover {
            background: #0b7c82;
        }
        .topic-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 14px;
            margin-top: 20px;
        }
        .topic-stat {
            background: #f7fbfb;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            border: 1px solid #e8f1f1;
        }
        .topic-stat .stat-value {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            color: #0a959c;
        }
        .topic-stat .stat-label {
            font-size: 0.85rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-top: 6px;
        }
        .comment-form-card {
            padding: 20px;
            border-radius: 28px;
            background: #ffffff;
            border: 1px solid #e8edf2;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.05);
        }
        .comment-form-card label {
            display: block;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }
        .comment-form-card textarea {
            width: 100%;
            min-height: 120px;
            border-radius: 22px;
            padding: 18px 20px;
            border: 1px solid #d9dfe6;
            font-size: 1rem;
            resize: vertical;
            color: #1f2937;
            background: #f8fafc;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .comment-form-card textarea:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(10, 149, 156, 0.12);
            border-color: #0a959c;
            background: #ffffff;
        }
        .comment-form-card .comment-input-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 16px;
        }
        .comment-form-card .comment-input-toolbar .input-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .comment-form-card .input-actions button {
            border: 1px solid #d8dee7;
            background: #f8fafc;
            color: #475569;
            font-size: 18px;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, background 0.15s ease;
        }
        .comment-form-card .input-actions button:hover {
            transform: translateY(-1px);
            background: #e2f3f5;
            color: #0a959c;
        }
        .comment-form-card .btn-submit-comment {
            background: #0a959c;
            color: #ffffff;
            border: none;
            padding: 14px 22px;
            border-radius: 24px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .comment-form-card .btn-submit-comment:hover {
            background: #087d83;
            transform: translateY(-1px);
        }
        .comment-card {
            padding: 20px;
            position: relative;
            background: #ffffff;
            border: 1px solid #e8edf2;
            border-radius: 24px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }
        .comment-card.comment-reply {
            margin-left: 44px;
            background: #f8fbfb;
            border-color: #d8e8ea;
        }
        .comment-card + .comment-card {
            margin-top: 16px;
        }
        .comment-card.comment-reply + .comment-card.comment-reply {
            margin-top: 14px;
        }
        .comment-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }
        .comment-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }
        .comment-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .comment-author {
            font-weight: 700;
            color: #111827;
            margin: 0;
            font-size: 0.99rem;
        }
        .comment-meta {
            color: #64748b;
            font-size: 0.88rem;
            margin: 4px 0 0;
        }
        .comment-body {
            color: #334155;
            line-height: 1.75;
            font-size: 0.98rem;
            white-space: pre-line;
            margin: 0 0 16px;
        }
        .comment-footer {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-top: 1px solid #eef2f6;
            padding-top: 14px;
        }
        .comment-footer .comment-time {
            color: #64748b;
            font-size: 0.88rem;
        }
        .comment-footer .comment-action {
            background: #f8fafc;
            border: 1px solid #d8dee7;
            color: #334155;
            font-size: 0.94rem;
            font-weight: 700;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.18s ease;
        }
        .comment-footer .comment-action:hover {
            background: #e5f4f5;
            border-color: #bce3e6;
            color: #0a959c;
        }
        .comment-footer .comment-action i {
            font-size: 0.95rem;
        }
        .comment-footer .reply-count {
            color: #64748b;
            font-size: 0.92rem;
            padding: 10px 14px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #eef2f6;
        }
        .reply-form {
            margin-top: 18px;
        }
        .reply-form textarea {
            width: 100%;
            min-height: 100px;
            border-radius: 18px;
            padding: 16px 18px;
            border: 1px solid #d8dee7;
            font-size: 0.96rem;
            resize: vertical;
            color: #1f2937;
            background: #f8fafc;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .reply-form textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 149, 156, 0.12);
            border-color: #0a959c;
            background: #ffffff;
        }
        .reply-form .btn-submit-comment {
            margin-top: 12px;
            padding: 12px 20px;
        }
        .comment-replies {
            margin-top: 18px;
            border-left: 2px solid rgba(10, 149, 156, 0.14);
            padding-left: 18px;
        }
        .badge-verified {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #18f259;
            color: #ffffff;
            font-size: 0.75rem;
            margin-left: 6px;
        }
        .alert-message {
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .alert-error {
            background: #ffe6e6;
            color: #b02020;
            border: 1px solid #f5c2c7;
        }
        .alert-success {
            background: #e7f9f6;
            color: #0b6347;
            border: 1px solid #a3e6d0;
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
            .header-search {
                min-width: 200px;
            }
        }

        @media (max-width: 680px) {
            .topic-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            .topic-actions {
                width: 100%;
            }
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
                    <span class="logo-mark">F</span>
                    <span class="brand-text"></span>
                </a>

                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Cari di TIPSEN..." aria-label="Cari" onkeyup="if(this.value.length===0){return;}" disabled />
                </div>
            </div>

            <div class="header-center">
                <a href="index.php" class="header-nav active" title="Beranda"><i class="fas fa-home"></i></a>
                <a href="videos.php" class="header-nav" title="Video"><i class="fas fa-video"></i></a>
                <a href="index.php" class="header-nav" title="Grup"><i class="fas fa-users"></i></a>
                <div class="header-nav notification-btn" style="position: relative; cursor: pointer;" title="Notifikasi" onclick="toggleNotifications(event)">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="position: absolute; top: 8px; right: 8px; background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; display: none;">0</span>
                </div>
                <div id="notificationPanel" class="notification-panel" style="display: none; position: absolute; top: 60px; right: 20px; width: 360px; background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); z-index: 1000; max-height: 400px; overflow-y: auto;">
                    <div class="notification-header" style="padding: 16px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: bold;">Notifikasi</h3>
                        <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: #0a959c; cursor: pointer; font-size: 12px; font-weight: bold;">Tandai Semua</button>
                    </div>
                    <div id="notificationList" style="max-height: 350px; overflow-y: auto;"></div>
                </div>
            </div>

            <div class="header-actions">
                <?php if ($current_user_id > 0): ?>
                    <a href="edit_profile.php" class="profile-pill" title="Profil">
                        <?php echo getAvatarHTML($_SESSION['username'], $topic['user_avatar'] ?? null, '30'); ?>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
                    <a href="logout.php" class="action-pill" title="Keluar"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" class="profile-pill" title="Masuk"><i class="fas fa-sign-in-alt"></i> Masuk</a>
                    <a href="register2.php" class="action-pill" title="Daftar"><i class="fas fa-user-plus"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="content-wrap">
        <section class="topic-card">
            <div class="topic-meta">
                <div class="topic-author">
                    <?php echo getAvatarHTML($topic['username'], $topic['user_avatar'] ?? null, '48'); ?>
                    <div>
                        <p class="author-name"><?php echo htmlspecialchars($topic['username']); ?> <?php echo getVerifiedBadgeHTML($topic['is_admin'] == 1 ? 1 : 0); ?></p>
                        <p class="author-subtitle"><?php echo htmlspecialchars($topic['category'] ?? 'Umum'); ?> • <?php echo getRelativeTime($topic['created_at']); ?></p>
                    </div>
                </div>
                <span class="meta-pill"><?php echo intval($topic['like_count'] ?? 0); ?></span>
                <span class="meta-pill"><?php echo intval($topic['comment_count'] ?? 0); ?></span>
            </div>

            <h2 class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></h2>
            <p class="topic-content"><?php echo nl2br(htmlspecialchars($topic['content'])); ?></p>

            <?php if (!empty($topic['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($topic['image_path']); ?>" alt="Topik gambar" class="topic-image">
            <?php endif; ?>

            <?php
                $topic_video_src = '';
                if (!empty($topic['video'])) {
                    $topic_video_src = 'uploads/videos/' . $topic['video'];
                } elseif (!empty($topic['video_path'])) {
                    $topic_video_src = $topic['video_path'];
                }
            ?>
            <?php if ($topic_video_src !== ''): ?>
                <video src="<?php echo htmlspecialchars($topic_video_src); ?>" class="topic-video" controls preload="metadata"></video>
            <?php endif; ?>

            <div class="topic-actions">
                <button type="button" id="likeButton" class="btn-primary">
                    <i class="<?php echo $topic['user_liked'] ? 'fas' : 'far'; ?> fa-thumbs-up"></i> <span id="likeCount"><?php echo intval($topic['like_count'] ?? 0); ?></span>
                </button>
                <a href="#comments" class="btn-secondary"><i class="far fa-comment"></i></a>
                <button type="button" onclick="copyShareLink()" class="btn-secondary"><i class="far fa-share-square"></i></button>
                <?php if ($current_user_id === intval($topic['user_id'])): ?>
                    <a href="delete_topic.php?id=<?php echo intval($topic['id']); ?>" class="btn-secondary text-danger" onclick="return confirm('Hapus diskusi ini?');"><i class="fas fa-trash"></i> Hapus</a>
                <?php endif; ?>
            </div>
        <?php if ($comment_error !== ''): ?>
            <div class="alert-message alert-error"><?php echo htmlspecialchars($comment_error); ?></div>
        <?php endif; ?>

        <?php if ($comments_table_exists && $current_user_id > 0): ?>
            <section class="comment-form-card">
                <form action="view_topic.php?id=<?php echo $topic_id; ?>" method="POST">
                    <label for="comment" class="form-label fw-bold">Tulis komentar publik...</label>
                    <textarea id="comment" name="comment" placeholder="Tulis komentar publik..." required></textarea>
                    <div class="comment-input-toolbar">
                        <div class="input-actions">
                            <button type="button" title="Emoji"><i class="far fa-laugh"></i></button>
                            <button type="button" title="Foto"><i class="far fa-image"></i></button>
                            <button type="button" title="GIF"><i class="far fa-face-surprise"></i></button>
                            <button type="button" title="Sticker"><i class="far fa-sticker"></i></button>
                        </div>
                        <button type="submit" name="submit_comment" class="btn-submit-comment"><i class="fas fa-paper-plane"></i> Kirim</button>
                    </div>
                </form>
            </section>
        <?php elseif (!$comments_table_exists): ?>
            <section class="comment-form-card">
                <div class="alert-message alert-error">Fitur komentar belum tersedia di sistem ini.</div>
            </section>
        <?php elseif ($current_user_id === 0): ?>
            <section class="comment-form-card">
                <div class="alert-message alert-error">Silakan <a href="login.php">masuk</a> untuk menambahkan komentar.</div>
            </section>
        <?php endif; ?>

        <section id="comments">
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $comment): ?>
                    <?php render_comment_item($comment, $topic_id, $current_user_id); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="comment-card text-muted" style="text-align: center;">
                    <p class="mb-0">Belum ada komentar. Jadilah yang pertama memberikan tanggapan.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function copyShareLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                alert('Tautan diskusi disalin ke clipboard.');
            }, function() {
                alert('Gagal menyalin tautan. Silakan salin secara manual.');
            });
        }

        document.getElementById('likeButton').addEventListener('click', function() {
            <?php if ($current_user_id === 0): ?>
                alert('Silakan login terlebih dahulu untuk menyukai diskusi.');
                window.location.href = 'login.php';
                return;
            <?php else: ?>
                fetch('like_handler.php?id=<?php echo $topic_id; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('likeCount').innerText = data.new_likes;
                            const icon = document.querySelector('#likeButton i');
                            icon.className = icon.className.includes('fas') ? 'far fa-thumbs-up' : 'fas fa-thumbs-up';
                        }
                    })
                    .catch(() => {
                        alert('Terjadi kesalahan saat memproses like.');
                    });
            <?php endif; ?>
        });

        const commentLikeButtons = document.querySelectorAll('.comment-like-btn');
        const replyToggleButtons = document.querySelectorAll('.reply-toggle-btn');
        const userLoggedIn = <?php echo $current_user_id > 0 ? 'true' : 'false'; ?>;

        commentLikeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                if (!userLoggedIn) {
                    alert('Silakan login terlebih dahulu untuk menyukai komentar.');
                    window.location.href = 'login.php';
                    return;
                }

                fetch('comment_like_handler.php?id=' + commentId, { credentials: 'same-origin' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const countSpan = this.querySelector('.comment-like-count');
                            const icon = this.querySelector('i');
                            countSpan.innerText = data.total_likes;
                            icon.className = icon.className.includes('fas') ? 'far fa-thumbs-up' : 'fas fa-thumbs-up';
                        }
                    })
                    .catch(() => {
                        alert('Gagal memproses like komentar.');
                    });
            });
        });

        replyToggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const form = document.getElementById('reply-form-' + commentId);
                if (form) {
                    form.style.display = form.style.display === 'none' ? 'block' : 'none';
                }
            });
        });

        // Notification System
        function loadNotifications() {
            <?php if ($current_user_id > 0): ?>
            fetch('notification_handler.php?action=get_unread')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        document.getElementById('notificationBadge').textContent = data.count;
                        document.getElementById('notificationBadge').style.display = 'flex';
                    } else {
                        document.getElementById('notificationBadge').style.display = 'none';
                    }
                });
            <?php endif; ?>
        }

        function toggleNotifications(event) {
            event.stopPropagation();
            const panel = document.getElementById('notificationPanel');
            const isHidden = panel.style.display === 'none';
            
            if (isHidden) {
                loadNotificationList();
            }
            
            panel.style.display = isHidden ? 'block' : 'none';
        }

        function loadNotificationList() {
            <?php if ($current_user_id > 0): ?>
            fetch('notification_handler.php?action=get_notifications&limit=15')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notificationList');
                    if (data.success && data.notifications.length > 0) {
                        let html = '';
                        data.notifications.forEach(notif => {
                            const icon = notif.type === 'like' ? '👍' : notif.type === 'comment' ? '💬' : '⭐';
                            const link = notif.topic_id ? `view_topic.php?id=${notif.topic_id}` : '#';
                            const readClass = notif.is_read ? '' : 'style="background-color: #f0f8ff;"';
                            
                            html += `<a href="${link}" onclick="markNotificationRead(${notif.id})" style="display: block; padding: 12px 16px; border-bottom: 1px solid #eee; text-decoration: none; color: #333; ${notif.is_read ? '' : 'background-color: #f0f8ff;'} transition: background-color 0.2s;">
                                <div style="display: flex; gap: 8px;">
                                    <span style="font-size: 18px;">${icon}</span>
                                    <div style="flex: 1;">
                                        <div style="font-size: 14px; font-weight: 500;">${notif.message}</div>
                                        <div style="font-size: 12px; color: #999; margin-top: 4px;">${getTimeAgo(notif.created_at)}</div>
                                    </div>
                                </div>
                            </a>`;
                        });
                        list.innerHTML = html;
                    } else {
                        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Tidak ada notifikasi</div>';
                    }
                });
            <?php endif; ?>
        }

        function markNotificationRead(notifId) {
            fetch(`notification_handler.php?action=mark_read&id=${notifId}`)
                .then(response => response.json())
                .catch(() => {});
        }

        function markAllNotificationsRead() {
            fetch('notification_handler.php?action=mark_all_read')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('notificationBadge').style.display = 'none';
                        loadNotificationList();
                    }
                });
        }

        function getTimeAgo(dateString) {
            const time = new Date(dateString).getTime();
            const now = new Date().getTime();
            const diff = now - time;
            
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (seconds < 60) return 'baru saja';
            if (minutes < 60) return minutes + ' menit lalu';
            if (hours < 24) return hours + ' jam lalu';
            if (days < 7) return days + ' hari lalu';
            
            return new Date(dateString).toLocaleDateString('id-ID');
        }

        // Load notifications on page load
        loadNotifications();
        setInterval(loadNotifications, 30000); // Refresh setiap 30 detik

        // Close notification panel when clicking outside
        document.addEventListener('click', function(event) {
            const panel = document.getElementById('notificationPanel');
            const btn = document.querySelector('.notification-btn');
            if (panel && btn && !panel.contains(event.target) && !btn.contains(event.target)) {
                panel.style.display = 'none';
            }
        });

        // Toast Notification System
        function showToast(message, type = 'success', duration = 3000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3';
            
            toast.style.cssText = `
                background-color: ${bgColor};
                color: white;
                padding: 16px 20px;
                margin-bottom: 10px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-size: 14px;
                font-weight: 500;
                animation: slideIn 0.3s ease;
                max-width: 400px;
            `;
            
            toast.textContent = message;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Check for session messages
        <?php if (isset($_SESSION['success_message'])): ?>
            showToast('<?= addslashes($_SESSION['success_message']) ?>', 'success');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showToast('<?= addslashes($_SESSION['error_message']) ?>', 'error');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
