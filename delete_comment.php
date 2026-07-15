<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User belum login']);
    exit;
}

if (!isset($_POST['comment_id']) && !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID Komentar tidak valid']);
    exit;
}

$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

// Cek apakah komentar ada dan pemiliknya adalah user yang login
$check_query = "SELECT * FROM comments WHERE id = $comment_id";
$check_result = $conn->query($check_query);

if (!$check_result || $check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Komentar tidak ditemukan']);
    exit;
}

$comment = $check_result->fetch_assoc();
$topic_id = $comment['topic_id'];

// Cek apakah user adalah pemilik komentar atau admin
$check_owner = "SELECT is_admin, role FROM users WHERE id = $user_id";
$owner_result = $conn->query($check_owner);
$user_data = $owner_result->fetch_assoc();
$is_admin = ($user_data['is_admin'] == 1 || $user_data['role'] === 'admin');

if ($comment['user_id'] != $user_id && !$is_admin) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus komentar ini']);
    exit;
}

// Hapus komentar
$delete_query = "DELETE FROM comments WHERE id = $comment_id";
if ($conn->query($delete_query)) {
    // Jika request dari AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['id'])) {
        echo json_encode(['success' => true, 'message' => 'Komentar berhasil dihapus']);
    } else {
        // Redirect ke halaman topik
        header('Location: view_topic.php?id=' . $topic_id);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus komentar: ' . $conn->error]);
}
exit;
?>
