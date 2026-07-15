<?php
require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $query = "SELECT * FROM users WHERE username = '$username'";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Jika kolom is_admin di database bernilai 1 ATAU Anda login dengan nama 'admin' atau 'Habibi',
            // maka sistem akan meloloskan login secara otomatis tanpa memeriksa kecocokan hash password.
            $isAdminUser = ($user['is_admin'] == 1 || $user['role'] === 'admin');

            if ($isAdminUser || $username === 'admin' || $username === 'Habibi') {
                $password_valid = true;
            } else {
                // Untuk pengguna umum/biasa, tetap diverifikasi menggunakan bcrypt yang aman
                $password_valid = password_verify($password, $user['password']);
            }

            // Jika verifikasi password lolos (atau berhasil dibypass oleh aturan admin)
            if ($password_valid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // WAJIB: Menyimpan status hak akses ke session agar tombol admin di beranda (index.php) menyala
                $_SESSION['is_admin'] = $isAdminUser ? 1 : 0; 
                
                header('Location: index.php');
                exit;
            } else {
                $error_msg = 'Kata sandi yang Anda masukkan salah!';
            }
        } else {
            // Pengaman tambahan: Jika username belum terdaftar namun diketik 'admin' atau 'Habibi',
            // sistem akan otomatis membuatkannya akun admin darurat agar Anda tidak terkunci di luar sistem.
            if ($username === 'admin' || $username === 'Habibi') {
                $default_hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO users (username, password, is_admin) VALUES ('$username', '$default_hashed_password', 1)";
                if ($conn->query($insert_query)) {
                    // Login otomatis setelah akun darurat berhasil dibuat
                    $new_user_id = $conn->insert_id;
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['is_admin'] = 1;
                    
                    header('Location: index.php');
                    exit;
                }
            }
            $error_msg = 'Username tidak ditemukan!';
        }
    } else {
        $error_msg = 'Username dan Password wajib diisi!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - TIPSEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f8;
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #eef1f4;
            padding: 32px;
            max-width: 420px;
            width: 100%;
        }
        .form-control-modern {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .form-control-modern:focus {
            background-color: #fff;
            border-color: #0A959C;
            box-shadow: 0 0 0 3px rgba(10, 149, 156, 0.15);
            outline: none;
        }
        .btn-modern {
            background: linear-gradient(135deg, #0A959C 0%, #087d83 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.2s ease;
        }
        .btn-modern:hover {
            background: linear-gradient(135deg, #087d83 0%, #066468 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(10, 149, 156, 0.2);
        }
        .brand-logo {
            color: #0A959C;
            font-weight: 800;
            font-size: 24px;
            letter-spacing: -1px;
            margin-bottom: 24px;
            text-align: center;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .form-control-modern {
            padding-right: 48px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }
        .password-toggle:hover {
            color: #0A959C;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 d-flex justify-content-center">
            <div class="login-card">
                <div class="brand-logo">TIPSEN </div>
                
                <h5 class="fw-bold text-center mb-1">Selamat Datang Kembali</h5>
                <p class="text-muted text-center small mb-4">Silakan masuk ke akun Anda</p>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger p-2 border-0 small rounded-3 mb-3 d-flex align-items-center gap-2" style="background-color: #fff5f5; color: #e53e3e;">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="fw-semibold"><?php echo htmlspecialchars($error_msg); ?></div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Username</label>
                        <input type="text" name="username" class="form-control-modern w-100" placeholder="Masukkan username Anda" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Kata Sandi</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-control-modern w-100" placeholder="••••••••" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password', 'password-toggle-icon')" aria-label="Tampilkan kata sandi">
                                <i id="password-toggle-icon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-modern w-100 mb-3">
                        Masuk Sekarang <i class=\"fas fa-sign-in-alt ms-1\"></i>
                    </button>

                    <div class="text-center pt-2">
                        <span class="text-muted small">Belum punya akun?</span> 
                        <a href="register2.php" class="text-decoration-none small fw-bold" style="color: #0A959C;">Daftar di sini</a>
                    </div>
                </form>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>