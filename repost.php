<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Anda harus login terlebih dahulu.']);
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}

$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : ''; // add atau remove
$user_id = $_SESSION['user_id'];

if ($topic_id <= 0) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ID Topik tidak valid.']);
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

$topicCheck = $conn->query("SELECT user_id FROM topics WHERE id = $topic_id");
if (!$topicCheck || $topicCheck->num_rows === 0) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Topik diskusi tidak ditemukan.']);
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

$topic = $topicCheck->fetch_assoc();
$original_user_id = $topic['user_id'];

$success = false;

if ($action === 'add') {
    // Eksekusi operasi penambahan data Repost
    try {
        $stmt = $conn->prepare("INSERT INTO reposts (user_id, topic_id, original_user_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
        if ($stmt) {
            $stmt->bind_param('iii', $user_id, $topic_id, $original_user_id);
            $success = $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        $success = false;
    }
} elseif ($action === 'remove') {
    // Eksekusi operasi pembatalan/penghapusan data Repost
    try {
        $stmt = $conn->prepare("DELETE FROM reposts WHERE user_id = ? AND topic_id = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $user_id, $topic_id);
            $success = $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        $success = false;
    }
}

// Ambil hitungan total data repost terbaru pasca eksekusi untuk diumpankan ke frontend
$count_query = $conn->query("SELECT COUNT(*) as total FROM reposts WHERE topic_id = $topic_id");
$count_row = $count_query ? $count_query->fetch_assoc() : ['total' => 0];
$new_repost_count = intval($count_row['total']);

// Berikan feedback berdasarkan cara pemanggilan file
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'action' => $action,
        'new_count' => $new_repost_count
    ]);
    exit();
} else {
    // Fallback: Jika diakses manual via tautan browser normal, alihkan kembali ke halaman sebelumnya
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "view_topic.php?id=$topic_id";
    header("Location: $referer");
    exit();
}
?>