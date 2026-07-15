<?php
/**
 * Fungsi helper untuk notifikasi
 */

function createNotification($conn, $user_id, $actor_id, $type, $message, $topic_id = null, $comment_id = null) {
    if ($user_id == $actor_id) {
        return; // Jangan buat notifikasi untuk user sendiri
    }
    
    $user_id = intval($user_id);
    $actor_id = intval($actor_id);
    $type = $conn->real_escape_string($type);
    $message = $conn->real_escape_string($message);
    $topic_id = $topic_id ? intval($topic_id) : 'NULL';
    $comment_id = $comment_id ? intval($comment_id) : 'NULL';
    
    $query = "INSERT INTO notifications (user_id, actor_id, type, message, topic_id, comment_id) 
              VALUES ($user_id, $actor_id, '$type', '$message', $topic_id, $comment_id)";
    
    return $conn->query($query);
}
?>

