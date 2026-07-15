<?php
include 'config.php';
include 'notification_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User belum login']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID Topik tidak valid']);
    exit;
}

$topic_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

// Ambil data topik untuk mendapatkan pemilik postingan
$topic_query = "SELECT user_id FROM topics WHERE id = $topic_id";
$topic_result = $conn->query($topic_query);
$topic_data = $topic_result->fetch_assoc();
$topic_owner_id = $topic_data['user_id'];

$check_query = "SELECT * FROM topic_likes WHERE topic_id = $topic_id AND user_id = $user_id";
$check_result = $conn->query($check_query);

if ($check_result && $check_result->num_rows > 0) {
    $delete_like = "DELETE FROM topic_likes WHERE topic_id = $topic_id AND user_id = $user_id";
    $conn->query($delete_like);
    
    $update_topic = "UPDATE topics SET likes = GREATEST(0, likes - 1) WHERE id = $topic_id";
    $conn->query($update_topic);
} else {
    $insert_like = "INSERT INTO topic_likes (topic_id, user_id) VALUES ($topic_id, $user_id)";
    $conn->query($insert_like);
    
    $update_topic = "UPDATE topics SET likes = likes + 1 WHERE id = $topic_id";
    $conn->query($update_topic);
    
    // Buat notifikasi ketika user menyukai postingan
    $actor_username = '';
    $actor_query = "SELECT username FROM users WHERE id = $user_id";
    $actor_result = $conn->query($actor_query);
    if ($actor_result) {
        $actor_data = $actor_result->fetch_assoc();
        $actor_username = $actor_data['username'];
    }
    
    createNotification($conn, $topic_owner_id, $user_id, 'like', "$actor_username menyukai postingan Anda", $topic_id);
}

$count_query = "SELECT likes FROM topics WHERE id = $topic_id";
$count_result = $conn->query($count_query);
$row = $count_result->fetch_assoc();
$new_likes = isset($row['likes']) ? intval($row['likes']) : 0;

echo json_encode([
    'success' => true,
    'new_likes' => $new_likes
]);
exit;