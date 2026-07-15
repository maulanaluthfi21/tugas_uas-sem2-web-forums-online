<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config.php");

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data admin
$result = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$admin = mysqli_fetch_assoc($result);

// Cek role
if (!$admin || $admin['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Statistik
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM users"))['total'];

$totalTopics = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM topics"))['total'];

$totalComments = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM comments"))['total'];

// Jika tabel topic_likes belum ada, ubah sesuai tabel like Anda
$likeQuery = mysqli_query($conn,
"SELECT COUNT(*) AS total FROM topic_likes");

if($likeQuery){
    $totalLikes = mysqli_fetch_assoc($likeQuery)['total'];
}else{
    $totalLikes = 0;
}

// Topik terbaru
$latestTopics = mysqli_query($conn,"
SELECT topics.*, users.username
FROM topics
JOIN users ON users.id=topics.user_id
ORDER BY topics.created_at DESC
LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Dashboard Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

body{
    background:#f5f7fb;
}

.sidebar{
    width:250px;
    height:100vh;
    position:fixed;
    background:#212529;
}

.sidebar h3{
    color:white;
    padding:20px;
}

.sidebar a{
    display:block;
    color:white;
    padding:15px 20px;
    text-decoration:none;
}

.sidebar a:hover{
    background:#0d6efd;
}

.content{
    margin-left:250px;
    padding:30px;
}

.card{
    border:none;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}

.table{
    background:white;
}

</style>

</head>

<body>

<div class="sidebar">

<h3>ADMIN</h3>

<a href="dashboard.php">
<i class="bi bi-speedometer2"></i>
Dashboard
</a>

<a href="users.php">
<i class="bi bi-people"></i>
Users
</a>

<a href="topics.php">
<i class="bi bi-chat-left-text"></i>
Topik
</a>

<a href="../logout.php">
<i class="bi bi-box-arrow-right"></i>
Logout
</a>

</div>

<div class="content">

<h2 class="mb-4">
Dashboard Admin
</h2>

<div class="row">

<div class="col-md-3">
<div class="card p-3">
<h5>User</h5>
<h2><?= $totalUsers ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card p-3">
<h5>Topik</h5>
<h2><?= $totalTopics ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card p-3">
<h5>Komentar</h5>
<h2><?= $totalComments ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card p-3">
<h5>Like</h5>
<h2><?= $totalLikes ?></h2>
</div>
</div>

</div>

<div class="card mt-5">

<div class="card-header">
Topik Terbaru
</div>

<div class="card-body">

<table class="table table-bordered table-hover">

<thead>

<tr>
<th>No</th>
<th>Judul</th>
<th>Penulis</th>
<th>Tanggal</th>
</tr>

</thead>

<tbody>

<?php
$no=1;

while($row=mysqli_fetch_assoc($latestTopics)){
?>

<tr>

<td><?= $no++ ?></td>

<td><?= htmlspecialchars($row['title']) ?></td>

<td><?= htmlspecialchars($row['username']) ?></td>

<td><?= $row['created_at'] ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</body>

</html>