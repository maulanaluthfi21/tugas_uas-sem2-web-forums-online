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

$sql = "
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

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>

<head>

    <title>Permintaan Pertemanan</title>

    <style>
        body {

            background: #f5f5f5;

            font-family: Arial;

        }

        .container {

            width: 900px;

            margin: auto;

            margin-top: 40px;

        }

        .card {

            background: white;

            padding: 20px;

            margin-bottom: 15px;

            border-radius: 12px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }

        .left {

            display: flex;

            align-items: center;

        }

        .btn {

            padding: 10px 18px;

            border: none;

            border-radius: 8px;

            cursor: pointer;

            margin-left: 8px;

        }

        .accept {

            background: #16a34a;

            color: white;

        }

        .reject {

            background: #ef4444;

            color: white;

        }
    </style>

</head>

<body>

    <div class="container">

        <h2>Permintaan Pertemanan</h2>

        <?php

        while ($row = $result->fetch_assoc()) {

        ?>

            <div class="card">

                <div class="left">

                    <?php

                    echo getAvatarHTML(

                        $row['username'],

                        $row['avatar'],

                        60

                    );

                    ?>

                    <div style="margin-left:15px;">

                        <h3>

                            <?= htmlspecialchars($row['username']) ?>

                        </h3>

                        <p>

                            <?= htmlspecialchars($row['bio']) ?>

                        </p>

                    </div>

                </div>

                <div>

                    <a class="btn accept"

                        href="friend_action.php?id=<?= $row['id'] ?>&action=accept">

                        Terima

                    </a>

                    <a class="btn reject"

                        href="friend_action.php?id=<?= $row['id'] ?>&action=reject">

                        Tolak

                    </a>

                </div>

            </div>

        <?php

        }

        ?>

    </div>

</body>

</html>