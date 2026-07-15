<?php
include 'config.php';
include 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$query = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($query);
$user = $result->fetch_assoc();
$requiredColumns = [
    'avatar' => "VARCHAR(255) DEFAULT NULL",
    'bio' => "TEXT DEFAULT NULL"
];
foreach ($requiredColumns as $column => $definition) {
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($checkColumn && $checkColumn->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN $column $definition");
    }
}

$success_message = '';
$error_message = '';

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update bio
    if (isset($_POST['update_bio'])) {
        $bio = $conn->real_escape_string($_POST['bio']);
        $update_query = "UPDATE users SET bio = '$bio' WHERE id = $user_id";
        if ($conn->query($update_query)) {
            $success_message = 'Bio berhasil diperbarui!';
            $user['bio'] = $bio;
        } else {
            $error_message = 'Gagal memperbarui bio.';
        }
    }

    // Upload avatar
    if (isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $file['tmp_name']);
            finfo_close($file_info);

            if (!in_array($mime_type, $allowed_mimes)) {
                $error_message = 'Format file harus JPG, PNG, atau GIF.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error_message = 'Ukuran file maksimal 2MB.';
            } else {
                // Hapus avatar lama jika ada
                if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                    unlink($user['avatar']);
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $upload_path = $conn->real_escape_string($upload_path);
                    $update_query = "UPDATE users SET avatar = '$upload_path' WHERE id = $user_id";
                    if ($conn->query($update_query)) {
                        $success_message = 'Avatar berhasil diperbarui!';
                        $user['avatar'] = $upload_path;
                    } else {
                        $error_message = 'Gagal menyimpan avatar ke database.';
                        unlink($upload_path);
                    }
                } else {
                    $error_message = 'Gagal mengunggah file.';
                }
            }
        } else {
            $error_message = 'Silakan pilih file gambar.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - FORKOM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            color: #1c1e21;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
        }
        .app-shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 20px 16px 36px;
        }
        .app-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
            padding: 16px 22px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #e9edf2;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }
        .app-bar-actions {
            display: flex;
            gap: 10px;
        }
        .app-action {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f8;
            color: #0a959c;
            border: 1px solid #e5eff0;
            text-decoration: none;
            transition: all 0.18s ease;
        }
        .app-action:hover {
            transform: translateY(-1px);
            background: #e8f4f5;
        }
        .profile-card {
            position: relative;
            overflow: hidden;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
            border: 1px solid #e9edf2;
        }
        .profile-cover {
            height: 190px;
            background: linear-gradient(135deg, #0a959c 0%, #087d83 100%);
        }
        .profile-avatar-wrap {
            position: absolute;
            left: 50%;
            top: 150px;
            transform: translateX(-50%);
            width: 132px;
            height: 132px;
            border-radius: 50%;
            padding: 6px;
            background: #ffffff;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
        }
        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        .avatar-placeholder-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 800;
            color: #ffffff;
        }
        .profile-body {
            padding: 100px 30px 28px;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 26px;
        }
        .profile-header h2 {
            font-size: 1.9rem;
            margin: 0;
            font-weight: 800;
        }
        .profile-header p {
            margin: 8px auto 0;
            max-width: 620px;
            color: #556274;
            line-height: 1.6;
        }
        .profile-form {
            display: grid;
            gap: 24px;
        }
        .section-card {
            background: #f7fbfb;
            border: 1px solid #e5eff0;
            border-radius: 18px;
            padding: 22px 24px;
        }
        .section-title {
            display: block;
            font-size: 15px;
            font-weight: 700;
            color: #112d39;
            margin-bottom: 14px;
        }
        .fb-textarea {
            width: 100%;
            min-height: 120px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid #d7dde3;
            background: #ffffff;
            color: #1c2533;
            font-size: 0.98rem;
            resize: vertical;
        }
        .fb-textarea:focus {
            outline: none;
            border-color: #0a959c;
            box-shadow: 0 0 0 3px rgba(10, 149, 156, 0.14);
        }
        .file-upload-wrapper {
            display: grid;
            gap: 14px;
        }
        .file-selector-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 16px;
            border: 1px solid transparent;
            background: #e9f6f7;
            color: #0a959c;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .file-selector-btn:hover {
            background: #d1eef0;
            border-color: #b2e3e6;
        }
        input[type="file"] {
            display: none;
        }
        .btn-fb-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background: #0a959c;
            border: none;
            border-radius: 999px;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.18s ease;
        }
        .btn-fb-primary:hover {
            background: #087d83;
        }
        .btn-fb-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 999px;
            background: #eef2f4;
            color: #0a959c;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #d8e3e8;
        }
        .btn-fb-secondary:hover {
            background: #ddebed;
        }
        .btn-fb-link {
            display: block;
            width: 100%;
            padding: 18px 20px;
            text-align: center;
            color: #0a959c;
            text-decoration: none;
            font-weight: 700;
            border-top: 1px solid #e9edf2;
            transition: background-color 0.18s ease;
        }
        .btn-fb-link:hover {
            background: #f1f7f8;
        }
        .alert-fb {
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 0;
            font-size: 0.98rem;
        }
        .alert-success {
            background: #e7f8f2;
            color: #0f6d52;
            border: 1px solid #b2e6cb;
        }
        .alert-danger {
            background: #ffe7e7;
            color: #8f1c1c;
            border: 1px solid #f3c2c2;
        }
        @media (max-width: 780px) {
            .app-shell {
                padding: 16px 12px 28px;
            }
            .app-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .app-bar-actions {
                justify-content: flex-start;
            }
            .profile-avatar-wrap {
                top: 138px;
            }
            .profile-body {
                padding: 88px 18px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="app-shell">
        <div class="app-bar">
            <div>
                <a href="index.php" class="btn-fb-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            <div class="app-bar-actions">
                <a href="edit_profile.php" class="app-action" title="Edit Profil"><i class="fas fa-user-edit"></i></a>
                <a href="index.php" class="app-action" title="Beranda"><i class="fas fa-home"></i></a>
                <a href="logout.php" class="app-action" title="Keluar"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-avatar-wrap">
                <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="profile-avatar" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-placeholder-circle" style="background-color: <?php echo getAvatarColor($username); ?>;">
                        <?php echo getInitials($username); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-body">
                <div class="profile-header">
                    <h2>Edit Profil</h2>
                    <p>Perbarui bio dan foto profil Anda dengan gaya modern yang bersih seperti Facebook.</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-fb" role="alert">
                        <strong>Berhasil!</strong> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-fb" role="alert">
                        <strong>Gagal!</strong> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-form">
                    <div class="section-card">
                        <span class="section-title">Foto Profil</span>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="file-upload-wrapper">
                                <label class="file-selector-btn" for="fb-avatar-input">
                                    <i class="fas fa-image"></i>
                                    <span id="file-chosen-text">Pilih Foto Profil</span>
                                </label>
                                <input type="file" name="avatar" id="fb-avatar-input" accept="image/*" required onchange="displaySelectedFile()">
                                <small class="text-muted">Mendukung JPG, PNG, GIF. Maksimal 2MB.</small>
                                <button type="submit" name="upload_avatar" class="btn-fb-primary">Perbarui Foto</button>
                            </div>
                        </form>
                    </div>

                    <div class="section-card">
                        <span class="section-title">Bio</span>
                        <form method="POST">
                            <textarea name="bio" class="fb-textarea" rows="4" placeholder="Ceritakan sedikit tentang diri Anda..." maxlength="160"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" name="update_bio" class="btn-fb-primary">Simpan Bio</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <a href="index.php" class="btn-fb-link">Kembali ke Beranda</a>
        </div>
    </div>

    <script>
    // Memperbarui teks label tombol ketika file dipilih
    function displaySelectedFile() {
        const fileInput = document.getElementById('fb-avatar-input');
        const textLabel = document.getElementById('file-chosen-text');
        if (fileInput.files && fileInput.files.length > 0) {
            textLabel.innerText = 'Siap: ' + fileInput.files[0].name;
        } else {
            textLabel.innerText = 'Pilih Foto Profil';
        }
    }
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>