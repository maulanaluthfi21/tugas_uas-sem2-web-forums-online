<?php
include 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sender = intval($_SESSION['user_id']);
$receiver = intval($_GET['user'] ?? 0);

// Tidak boleh menambah diri sendiri
if ($sender == $receiver) {
    header("Location: friends.php");
    exit;
}

// Cek apakah request sudah ada
$cek = $conn->query("
SELECT *
FROM friends
WHERE
(sender_id=$sender AND receiver_id=$receiver)
OR
(sender_id=$receiver AND receiver_id=$sender)
");

if($cek->num_rows==0){

$conn->query("
INSERT INTO friends
(sender_id,receiver_id,status)
VALUES
($sender,$receiver,'pending')
");

}

header("Location: friends.php");
exit;
?>