<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIPSEN - Pendaftaran Akun Baru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgba(10, 149, 156, 0.05) 0%, rgba(240, 242, 245, 1) 90.1%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .main-wrapper {
            max-width: 1050px;
            width: 100%;
            padding: 20px;
        }
        .brand-section {
            padding-right: 40px;
        }
        .brand-logo-title {
            color: #0A959C; /* Warna khas FORKOM UIMY */
            font-size: 3.8rem;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1;
            margin-bottom: 15px;
        }
        .brand-badge {
            background-color: rgba(10, 149, 156, 0.1);
            color: #0A959C;
            font-weight: 700;
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .brand-slogan {
            font-size: 1.35rem;
            line-height: 1.45;
            color: #4a5568;
            font-weight: 500;
        }
        .glass-card {
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            padding: 40px;
            transition: transform 0.3s ease;
        }
        .form-title {
            font-size: 1.85rem;
            font-weight: 700;
            color: #1a202c;
            letter-spacing: -0.5px;
        }
        .form-subtitle {
            font-size: 14px;
            color: #718096;
        }
        .custom-label {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .input-group-custom {
            position: relative;
            background-color: #f7fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        .input-group-custom:focus-within {
            border-color: #0A959C;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(10, 149, 156, 0.15);
        }
        .input-icon {
            padding-left: 16px;
            color: #a0aec0;
            font-size: 16px;
        }
        .input-group-custom .form-control-modern {
            background: transparent;
            border: none;
            font-size: 15px;
            font-weight: 500;
            padding: 13px 16px 13px 12px;
            color: #2d3748;
            width: 100%;
        }
        .input-group-custom .form-control-modern:focus {
            outline: none;
            box-shadow: none;
        }
        .eye-toggle {
            padding-right: 16px;
            color: #a0aec0;
            cursor: pointer;
            transition: color 0.2s;
        }
        .eye-toggle:hover {
            color: #0A959C;
        }
        .btn-modern-submit {
            background: linear-gradient(135deg, #0A959C 0%, #087d83 100%);
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 700;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            box-shadow: 0 4px 12px rgba(10, 149, 156, 0.25);
            transition: all 0.2s ease;
        }
        .btn-modern-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(10, 149, 156, 0.35);
            color: white;
        }
        .divider-container {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #a0aec0;
            font-size: 13px;
            font-weight: 500;
        }
        .divider-container::before, .divider-container::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider-container:not(:empty)::before { margin-right: .5em; }
        .divider-container:not(:empty)::after { margin-left: .5em; }
        
        .btn-social-oauth {
            background-color: #ffffff;
            border: 1.5px solid #e2e8f0;
            color: #4a5568;
            font-size: 14px;
            font-weight: 600;
            padding: 11px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-social-oauth:hover {
            background-color: #f7fafc;
            border-color: #cbd5e0;
            color: #2d3748;
        }
        .footer-redirect {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f0f2f5;
            font-size: 14px;
            color: #718096;
        }
        .redirect-link {
            color: #0A959C;
            text-decoration: none;
            font-weight: 700;
        }
        .redirect-link:hover {
            text-decoration: underline;
        }
        .copyright-text {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<div class="container main-wrapper">
    <div class="row align-items-center justify-content-center g-4">
        
        <div class="col-lg-6 text-center text-lg-start brand-section">
            <span class="brand-badge"><i class="fas fa-users me-1"></i> Komunitas TIPSEN</span>
            <h1 class="brand-logo-title">TIPSEN</h1>
            <p class="brand-slogan">
                Ruang interaktif modern untuk saling terhubung, bertukar pikiran, dan berdiskusi bersama seluruh anggota akademik.
            </p>
        </div>

        <div class="col-lg-5 col-md-8">
            <div class="glass-card">
                <form action="proses_register.php" method="POST">
                    
                    <div class="mb-4">
                        <h2 class="form-title">Buat Akun</h2>
                        <p class="form-subtitle">Gabung sekarang, gratis tanpa biaya apa pun.</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="custom-label">Username Baru</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control-modern" placeholder="Contoh: hbdjufri" required autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="custom-label">Kata Sandi</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" id="field-password" name="password" class="form-control-modern" placeholder="Min. 6 karakter kombinasi" required>
                            <span class="eye-toggle" onclick="toggleSecretPassword()">
                                <i id="icon-eye-toggle" class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-modern-submit">Daftar Sekarang <i class="fas fa-arrow-right ms-2"></i></button>

                    <div class="footer-redirect">
                        Sudah memiliki akun? <a href="login.php" class="redirect-link">Masuk disini</a>
                    </div>

                </form>
            </div>
            
            <div class="copyright-text text-center text-lg-start ps-2">
                &copy; 2026 TIPSEN
            </div>
        </div>

    </div>
</div>

<script>
    function toggleSecretPassword() {
        const passwordField = document.getElementById('field-password');
        const iconEye = document.getElementById('icon-eye-toggle');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            iconEye.classList.remove('fa-eye');
            iconEye.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            iconEye.classList.remove('fa-eye-slash');
            iconEye.classList.add('fa-eye');
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>