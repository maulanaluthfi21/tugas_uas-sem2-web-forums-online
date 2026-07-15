<?php

include 'config.php';

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location:login.php");

    exit;
}

$id = intval($_GET['id']);

$action = $_GET['action'];

if ($action == "accept") {

    $conn->query("

UPDATE friends

SET status='accepted'

WHERE id=$id

");
}

if ($action == "reject") {

    $conn->query("

UPDATE friends

SET status='rejected'

WHERE id=$id

");
}

header("Location:friend_requests.php");

exit;
