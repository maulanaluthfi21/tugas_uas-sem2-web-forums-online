<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, langsung lempar ke index.php
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Logika khusus registrasi akun baru
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Enkripsi password demi keamanan
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Gunakan Prepared Statement untuk mengecek apakah username sudah ada
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            echo "<script>alert('Username sudah terdaftar! Silakan cari nama lain.');</script>";
        } else {
            // Jika username aman, masukkan ke database
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='login.php';</script>";
                exit();
            } else {
                echo "<script>alert('Terjadi kesalahan sistem.');</script>";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pengguna</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .form-control {
            padding-right: 45px;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #6c757d;
            cursor: pointer;
        }
        .password-toggle:hover {
            color: #198754;
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">Daftar Akun Baru</h3>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Username Baru</label>
                                <input type="text" name="username" class="form-control" placeholder="Buat username" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Buat password" required>
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password', 'password-toggle-icon')" aria-label="Tampilkan kata sandi">
                                        <i id="password-toggle-icon" class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Perhatikan name="register" di bawah ini -->
                            <button type="submit" name="register" class="btn btn-success w-100 py-2">Daftar Sekarang</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">Sudah punya akun? <a href="login.php" class="text-decoration-none">Login di sini</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (!input || !icon) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
    }
    </script>
</body>
</html>
