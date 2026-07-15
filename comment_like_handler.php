<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

// Ensure comment_likes table exists for storing comment likes.
$checkLikesTable = $conn->query("SHOW TABLES LIKE 'comment_likes'");
if (!$checkLikesTable || $checkLikesTable->num_rows === 0) {
    $createLikesTable = "CREATE TABLE IF NOT EXISTS comment_likes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        comment_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        PRIMARY KEY (id),
        UNIQUE KEY user_comment (comment_id, user_id),
        KEY comment_id (comment_id),
        KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->query($createLikesTable);
}

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$comment_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

// Cek apakah user sudah me-like komentar ini sebelumnya
$check = $conn->query("SELECT * FROM comment_likes WHERE comment_id = $comment_id AND user_id = $user_id");

if ($check && $check->num_rows > 0) {
    // Unlike
    $conn->query("DELETE FROM comment_likes WHERE comment_id = $comment_id AND user_id = $user_id");
    $status = 'unliked';
} else {
    // Like
    $conn->query("INSERT INTO comment_likes (comment_id, user_id) VALUES ($comment_id, $user_id)");
    $status = 'liked';
}

// Hitung total like terbaru untuk komentar ini
$count_res = $conn->query("SELECT COUNT(*) as total FROM comment_likes WHERE comment_id = $comment_id");
$count_row = $count_res->fetch_assoc();
$total_likes = intval($count_row['total']);

echo json_encode([
    'success' => true,
    'status' => $status,
    'total_likes' => $total_likes
]);
exit;
?>