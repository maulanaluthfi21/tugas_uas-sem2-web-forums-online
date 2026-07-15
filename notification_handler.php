<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Pastikan tabel notifications ada
$check_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if (!$check_table || $check_table->num_rows === 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS notifications (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        actor_id INT(11) NOT NULL,
        type VARCHAR(50) NOT NULL,
        topic_id INT(11),
        comment_id INT(11),
        message VARCHAR(255),
        is_read INT(1) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY actor_id (actor_id),
        KEY created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->query($create_table);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_unread') {
    // Ambil jumlah notifikasi yang belum dibaca
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'count' => 0]);
        exit;
    }
    
    $user_id = intval($_SESSION['user_id']);
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    $row = $result->fetch_assoc();
    
    echo json_encode(['success' => true, 'count' => intval($row['count'])]);
    exit;
}

if ($action === 'get_notifications') {
    // Ambil daftar notifikasi
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'notifications' => []]);
        exit;
    }
    
    $user_id = intval($_SESSION['user_id']);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    $query = "SELECT n.*, u.username, u.avatar 
              FROM notifications n
              LEFT JOIN users u ON n.actor_id = u.id
              WHERE n.user_id = $user_id
              ORDER BY n.created_at DESC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $notifications = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
    exit;
}

if ($action === 'mark_read') {
    // Tandai notifikasi sebagai dibaca
    if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $notif_id = intval($_GET['id']);
    $user_id = intval($_SESSION['user_id']);
    
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id");
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_all_read') {
    // Tandai semua notifikasi sebagai dibaca
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $user_id = intval($_SESSION['user_id']);
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action not found']);
?>
