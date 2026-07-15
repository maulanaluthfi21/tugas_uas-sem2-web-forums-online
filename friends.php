<?php
include 'config.php';
include 'helpers.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$keyword = trim($_GET['search'] ?? '');

$sql = "
SELECT *
FROM users
WHERE id != $user_id
";

if ($keyword != "") {

    $keyword = $conn->real_escape_string($keyword);

    $sql .= " AND username LIKE '%$keyword%' ";
}

$sql .= " ORDER BY username ASC ";

$result = $conn->query($sql);

$result = $conn->query($sql);
$request_sql = "
SELECT
friends.id,
users.id AS user_id,
users.username,
users.avatar,
users.bio

FROM friends

JOIN users
ON users.id=friends.sender_id

WHERE
friends.receiver_id=$user_id
AND friends.status='pending'

ORDER BY friends.created_at DESC
";

$request_result = $conn->query($request_sql);
$friend_sql = "

SELECT

users.id,

users.username,

users.avatar,

users.bio

FROM friends

JOIN users

ON

(

users.id=friends.sender_id

OR

users.id=friends.receiver_id

)

WHERE

friends.status='accepted'

AND

(

friends.sender_id=$user_id

OR

friends.receiver_id=$user_id

)

AND

users.id!=$user_id

ORDER BY users.username ASC

";

$friend_result = $conn->query($friend_sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Teman</title>

    <style>
        body {

            background: #f3f4f6;

            font-family: Arial;

            margin: 0;

        }

        .container {

            width: 900px;

            margin: auto;

            margin-top: 40px;

        }

        .card {

            background: white;

            padding: 18px;

            border-radius: 12px;

            margin-bottom: 15px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);

        }

        .user {

            display: flex;

            align-items: center;

        }

        .avatar {

            width: 70px;

            height: 70px;

            border-radius: 50%;

            object-fit: cover;

            margin-right: 15px;

        }

        .bio {

            color: gray;

            font-size: 14px;

            margin-top: 5px;

        }

        .btn {

            background: #0aa2a5;

            color: white;

            padding: 10px 18px;

            border-radius: 8px;

            text-decoration: none;

        }

        .btn:hover {

            background: #087d80;

        }
    </style>

</head>

<body>

    <div class="container">
        <form method="GET" style="margin-bottom:25px;">

            <input

                type="text"

                name="search"

                placeholder="Cari teman..."

                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"

                style="

width:100%;

padding:14px;

border-radius:10px;

border:1px solid #ddd;

font-size:16px;

">

        </form>

        <h2>Daftar Pengguna</h2>
        <?php
        if ($request_result->num_rows > 0) {
        ?>

            <h2 style="margin-top:40px;">📩 Permintaan Pertemanan</h2>

            <?php

            while ($req = $request_result->fetch_assoc()) {

            ?>

                <div class="card">

                    <div class="user">

                        <?php

                        echo getAvatarHTML(
                            $req['username'],
                            $req['avatar'],
                            70
                        );

                        ?>

                        <div style="margin-left:15px;">

                            <h3><?= htmlspecialchars($req['username']) ?></h3>

                            <p>

                                <?= !empty($req['bio']) ? htmlspecialchars($req['bio']) : 'Belum memiliki bio.' ?>

                            </p>

                        </div>

                    </div>

                    <div>

                        <a
                            class="btn"
                            style="background:#22c55e;"
                            href="friend_action.php?id=<?= $req['id'] ?>&action=accept">

                            Terima

                        </a>

                        <a
                            class="btn"
                            style="background:#ef4444;"
                            href="friend_action.php?id=<?= $req['id'] ?>&action=reject">

                            Tolak

                        </a>

                    </div>

                </div>

        <?php

            }
        }

        ?>

        <?php

        while ($user = $result->fetch_assoc()) {

        ?>

            <div class="card">

                <div class="user">

                    <?php

                    echo getAvatarHTML(
                        $user['username'],
                        $user['avatar'],
                        70
                    );

                    ?>

                    <div>

                        <h3>

                            <?php

                            echo htmlspecialchars($user['username']);

                            ?>

                        </h3>

                        <div class="bio">

                            <?php

                            echo !empty($user['bio'])

                                ? htmlspecialchars($user['bio'])

                                : "Belum memiliki bio.";

                            ?>

                        </div>

                    </div>

                </div>

                <?php

                $cek = $conn->query("
SELECT status
FROM friends
WHERE
(sender_id=$user_id AND receiver_id=" . $user['id'] . ")
OR
(sender_id=" . $user['id'] . " AND receiver_id=$user_id)
");

                if ($cek->num_rows > 0) {

                    $data = $cek->fetch_assoc();

                    if ($data['status'] == "pending") {

                        echo "<button class='btn' style='background:#999;' disabled>Permintaan Terkirim</button>";
                    } elseif ($data['status'] == "accepted") {

                        echo "<a class='btn' style='background:#28a745;' href='view_profile.php?id=" . $user['id'] . "'>Lihat Profil</a>";
                    } else {

                        echo "<a class='btn' href='send_friend_request.php?user=" . $user['id'] . "'>Tambah Teman</a>";
                    }
                } else {

                ?>

                    <a class="btn"
                        href="send_friend_request.php?user=<?= $user['id'] ?>">

                        Tambah Teman

                    </a>

                <?php

                }
                ?>
            </div>

        <?php

        }

        ?><h2 style="margin-top:40px;">

            👥 Teman Saya

        </h2>

        <?php

        while ($friend = $friend_result->fetch_assoc()) {

        ?>

            <div class="card">

                <div class="user">

                    <?php

                    echo getAvatarHTML(

                        $friend['username'],

                        $friend['avatar'],

                        70

                    );

                    ?>

                    <div style="margin-left:15px;">

                        <h3>

                            <?= htmlspecialchars($friend['username']) ?>

                        </h3>

                        <p>

                            <?= !empty($friend['bio']) ? htmlspecialchars($friend['bio']) : 'Belum memiliki bio.' ?>

                        </p>

                    </div>

                </div>

                <a

                    class="btn"

                    style="background:#16a34a;"

                    href="view_profile.php?username=<?= urlencode($friend['username']) ?>">

                    Lihat Profil

                </a>

            </div>

        <?php

        }

        ?>

    </div>

</body>

</html>