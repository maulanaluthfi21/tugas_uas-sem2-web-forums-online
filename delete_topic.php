<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    header('Location: index.php');
    exit();
}

$topic_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
$user_id = intval($_SESSION['user_id']);

// Ambil data topik untuk memverifikasi kepemilikan
$topic_query = "SELECT * FROM topics WHERE id = $topic_id";
$topic_result = $conn->query($topic_query);

if (!$topic_result || $topic_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$topic = $topic_result->fetch_assoc();

// Cek apakah user adalah pemilik topik atau admin
$check_admin = "SELECT is_admin, role FROM users WHERE id = $user_id";
$admin_result = $conn->query($check_admin);
$user_data = $admin_result->fetch_assoc();
$is_admin = ($user_data['is_admin'] == 1 || $user_data['role'] === 'admin');

if ($topic['user_id'] != $user_id && !$is_admin) {
    // User tidak memiliki izin untuk menghapus
    $_SESSION['error_message'] = 'Anda tidak memiliki izin untuk menghapus topik ini';
    header('Location: index.php');
    exit();
}

// Hapus gambar jika ada
if (!empty($topic['image_path']) && file_exists($topic['image_path'])) {
    unlink($topic['image_path']);
}

// Hapus video jika ada
if (!empty($topic['video'])) {
    $video_path = 'uploads/videos/' . $topic['video'];
    if (file_exists($video_path)) {
        unlink($video_path);
    }
}

// Hapus semua komentar terkait
$conn->query("DELETE FROM comments WHERE topic_id = $topic_id");

// Hapus semua likes terkait
$conn->query("DELETE FROM topic_likes WHERE topic_id = $topic_id");

// Hapus topik
$delete_query = "DELETE FROM topics WHERE id = $topic_id";
if ($conn->query($delete_query)) {
    $_SESSION['success_message'] = 'Postingan berhasil dihapus';
    header('Location: index.php');
    exit();
} else {
    $_SESSION['error_message'] = 'Gagal menghapus postingan: ' . $conn->error;
    header('Location: index.php');
    exit();
}
?>
