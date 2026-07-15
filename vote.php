<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$target = $_GET['target'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$vote = $_GET['vote'] ?? '';

$allowedTargets = ['topic', 'reply'];
$allowedVotes = ['like', 'dislike'];

if (!in_array($target, $allowedTargets, true) || !in_array($vote, $allowedVotes, true) || $id <= 0) {
    header('Location: index.php');
    exit();
}

$table = $target === 'topic' ? 'topics' : 'replies';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, vote_type FROM votes WHERE user_id = ? AND target_type = ? AND target_id = ?");
$stmt->bind_param('isi', $user_id, $target, $id);
$stmt->execute();
$result = $stmt->get_result();
$existingVote = $result->fetch_assoc();
$stmt->close();

$incrementColumn = $vote === 'like' ? 'likes' : 'dislikes';
$decrementColumn = $vote === 'like' ? 'dislikes' : 'likes';

if ($existingVote) {
    if ($existingVote['vote_type'] !== $vote) {
        $updateVote = $conn->prepare("UPDATE votes SET vote_type = ?, updated_at = NOW() WHERE id = ?");
        $updateVote->bind_param('si', $vote, $existingVote['id']);
        $updateVote->execute();
        $updateVote->close();

        $conn->query("UPDATE $table SET $decrementColumn = GREATEST($decrementColumn - 1, 0), $incrementColumn = $incrementColumn + 1 WHERE id = $id");
    }
} else {
    $insertVote = $conn->prepare("INSERT INTO votes (user_id, target_type, target_id, vote_type) VALUES (?, ?, ?, ?)");
    $insertVote->bind_param('isis', $user_id, $target, $id, $vote);
    $insertVote->execute();
    $insertVote->close();

    $conn->query("UPDATE $table SET $incrementColumn = $incrementColumn + 1 WHERE id = $id");
}

$redirectUrl = 'index.php';
if ($target === 'topic') {
    $redirectUrl = 'view_topic.php?id=' . $id;
} else {
    $result = $conn->query("SELECT topic_id FROM replies WHERE id = $id");
    if ($result && $row = $result->fetch_assoc()) {
        $redirectUrl = 'view_topic.php?id=' . intval($row['topic_id']);
    }
}

header('Location: ' . $redirectUrl);
exit();
