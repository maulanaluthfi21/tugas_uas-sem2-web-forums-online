<?php
require_once 'includes/config.php';

// Kita siapkan struktur HTML dasar agar SweetAlert2 bisa dirender dengan cantik
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pendaftaran</title>
    <!-- Memanggil Google Fonts & SweetAlert2 CDN terupdate -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f0f2f5;
        }
        /* Mengubah font bawaan SweetAlert agar serasi dengan tema modern */
        .swal2-popup {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            border-radius: 20px !important;
        }
    </style>
</head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 1. Validasi Input Kosong
    if (empty($username) || empty($password)) {
        echo "
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Opps...',
                text: 'Username dan Password tidak boleh kosong!',
                confirmButtonColor: '#0A959C'
            }).then(() => {
                window.history.back();
            });
        </script>";
        exit;
    }

    $username = $conn->real_escape_string($username);

    // 2. Cek Apakah Username Sudah Terdaftar
    $check_query = "SELECT id FROM users WHERE username = '$username'";
    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        echo "
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Username Duplikat',
                text: 'Username sudah digunakan! Silakan pilih username lain.',
                confirmButtonColor: '#0A959C'
            }).then(() => {
                window.history.back();
            });
        </script>";
        exit;
    }

    // Hash Password Keamanan Tinggi
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // 3. Query Insert User Baru
    $insert_query = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";

    if ($conn->query($insert_query)) {
        $new_user_id = $conn->insert_id;
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;

        // NOTIFIKASI SUKSES MUNCUL DI TENGAH LAYAR
        echo "
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Sukses Membuat Akun',
                text: 'Selamat bergabung di FORKOM UIMY!',
                confirmButtonColor: '#0A959C',
                timer: 2500,
                timerProgressBar: true
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>";
    } else {
        echo "
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan: " . $conn->error . "',
                confirmButtonColor: '#0A959C'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }
} else {
    header('Location: register2.php');
    exit;
}
?>

</body>
</html>