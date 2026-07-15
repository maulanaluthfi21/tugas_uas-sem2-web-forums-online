<?php
include 'config.php'; // Memanggil koneksi database bawaan proyekmu

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Menangkap input username dan password dari form login
    $username = $conn->real_escape_string($_POST['username']);
    $password_input = $_POST['password'];

    // =======================================================================
    // KONDISI LOGIN AKUN SAMA & PASSWORD SAMA
    // =======================================================================
    if ($username === $password_input) {
        
        $query_khusus = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
        $result_khusus = $conn->query($query_khusus);
        
        if ($result_khusus && $result_khusus->num_rows > 0) {
            $user = $result_khusus->fetch_assoc();
            $_SESSION['user_id'] = $user['id']; // Menyimpan session ID user asli
        } else {
            $_SESSION['user_id'] = 999; 
        }
        
        $_SESSION['username'] = $username; // Menyimpan nama user ke session
        
        // TAMPILAN NOTIFIKASI SUKSES MENARIK DI TENGAH LAYAR (PASSWORD DISEMBUNYIKAN)
        echo "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Login Berhasil</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
            <style>
                body {
                    background-color: #f0f2f5;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Segoe UI', Arial, sans-serif;
                }
                .blur-bg {
                    position: fixed;
                    top:0; left:0; right:0; bottom:0;
                    background: rgba(240, 242, 245, 0.8);
                    backdrop-filter: blur(6px);
                    z-index: 1;
                }
                .custom-popup {
                    background: #ffffff;
                    border-radius: 16px;
                    padding: 35px;
                    max-width: 420px;
                    width: 90%;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                    text-align: center;
                    z-index: 2;
                    opacity: 0;
                    transform: scale(0.8);
                    animation: popupMasuk 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
                }
                @keyframes popupMasuk {
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
                .icon-container {
                    width: 75px;
                    height: 75px;
                    background: #e6f4f5;
                    color: #0A959C;
                    font-size: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    margin: 0 auto 15px auto;
                }
                .welcome-msg {
                    font-size: 15px;
                    color: #4b4f56;
                    margin-bottom: 25px;
                }
                .user-badge {
                    background-color: #f1f2f6;
                    padding: 8px 16px;
                    border-radius: 20px;
                    display: inline-block;
                    font-weight: 600;
                    color: #1c1e21;
                    margin-bottom: 10px;
                    border: 1px solid #e4e6eb;
                }
                .btn-lanjut {
                    background-color: #0a9c2a;
                    color: white;
                    border: none;
                    font-weight: 600;
                    padding: 12px 24px;
                    border-radius: 8px;
                    transition: all 0.2s;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    text-decoration: none;
                    width: 100%;
                }
                .btn-lanjut:hover {
                    background-color: #087d83;
                    color: white;
                    box-shadow: 0 4px 12px rgba(10, 149, 156, 0.2);
                }
            </style>
        </head>
        <body>

            <div class='blur-bg'></div>

            <div class='custom-popup'>
                <div class='icon-container'>
                    <i class='fas fa-check-circle'></i>
                </div>
                <h4 class='fw-bold text-dark mb-2'>Kamu Berhasil Login!</h4>
                
                <div class='mb-2'>
                    <span class='user-badge'>
                        <i class='fas fa-user text-muted me-2'></i>" . htmlspecialchars($username) . "
                    </span>
                </div>
                
                <p class='welcome-msg'>Selamat datang kembali di TIPSEN.</p>

                <a href='index.php' class='btn btn-lanjut'>Masuk ke Beranda <i class='fas fa-arrow-right'></i></a>
                <small class='text-muted d-block mt-3' style='font-size: 12px;'>Mengalihkan otomatis dalam beberapa detik...</small>
            </div>

            <script>
                // Mengalihkan otomatis ke halaman utama setelah 3 detik
                setTimeout(function(){
                    window.location.href = 'index.php'; //
                }, 3000);
            </script>
        </body>
        </html>
        ";
        exit();
    } else {
        echo "<script>
                alert('Gagal: Untuk bypass, pastikan Username dan Password yang diketik SAMA!'); 
                window.location.href = 'login.php';
              </script>";
    }
} else {
    header("Location: login.php");
    exit();
}
?>