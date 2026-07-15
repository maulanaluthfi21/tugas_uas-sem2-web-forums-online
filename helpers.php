<?php
/**
 * Helper Functions - Facebook Engine Style
 */

/**
 * Konversi timestamp menjadi format relatif lengkap dan natural ala Facebook
 * @param string $datetime - Format: YYYY-MM-DD HH:MM:SS
 * @return string - Contoh: "Baru saja", "10 menit yang lalu", "4 jam yang lalu", "2 hari yang lalu"
 */
function getRelativeTime($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit yang lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam yang lalu';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' hari yang lalu';
    } elseif ($diff < 2419200) {
        return floor($diff / 604800) . ' minggu yang lalu';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' bulan yang lalu';
    } else {
        return floor($diff / 31536000) . ' tahun yang lalu';
    }
}

/**
 * Dapatkan inisial untuk avatar placeholder (Maksimal 2 Karakter)
 * @param string $username
 * @return string - Inisial kapital
 */
function getInitials($username) {
    $parts = explode(' ', trim($username));
    $initials = '';
    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper($part[0]);
        }
    }
    return substr($initials, 0, 2);
}

/**
 * Generate warna background avatar berbasis tema cerah/biru Facebook yang bersahabat
 * @param string $username
 * @return string - Kode warna Hex
 */
function getAvatarColor($username) {
    $colors = [
        '#1877f2', '#3b5998', '#4267b2', '#244280', '#1070e0',
        '#0084ff', '#4b7bec', '#204abf', '#0fb96a', '#a55eed'
    ];
    $index = (ord($username[0] ?? 'A') + strlen($username)) % count($colors);
    return $colors[$index];
}

/**
 * Generate HTML komponen avatar bulat dengan sentuhan border putih/abu tipis ala Facebook
 * @param string $username
 * @param string|null $avatar_path
 * @param string $size - Ukuran dalam pixel
 * @return string - Elemen HTML img atau div placeholder
 */
function getAvatarHTML($username, $avatar_path = null, $size = '40') {
    if ($avatar_path && file_exists($avatar_path)) {
        return '<img src="' . htmlspecialchars($avatar_path) . '" class="avatar-img" style="width:' . $size . 'px; height:' . $size . 'px; border-radius: 50%; object-fit: cover; border: 1px solid #ced0d4; display: inline-block; vertical-align: middle;" alt="' . htmlspecialchars($username) . '">';
    } else {
        $initials = getInitials($username);
        $color = getAvatarColor($username);
        $fontSize = max(11, floor($size / 2.3)); // Skala font dinamis yang proporsional
        return '<div class="avatar-placeholder" style="width:' . $size . 'px; height:' . $size . 'px; background-color:' . $color . '; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 700; font-size: ' . $fontSize . 'px; letter-spacing: -0.3px; border: 1px solid rgba(0,0,0,0.05); vertical-align: middle;">' . $initials . '</div>';
    }
}

/**
 * Otomatis memunculkan lencana centang biru verifikasi versi lingkaran penuh Facebook jika user adalah admin
 * @param int|string $is_admin - Status admin dari database (1 atau 0)
 * @return string - Elemen SVG lencana verifikasi biru khas Facebook
 */
function getVerifiedBadgeHTML($is_admin) {
    if ($is_admin == 1) {
        return '<svg class="verified-badge-facebook" viewBox="0 0 24 24" aria-label=\"Terverifikasi\" style="width: 16px; height: 16px; fill: #18f259; display: inline-block; vertical-align: middle; margin-left: 4px; margin-top: -2px;"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
    }
    return '';
}

/**
 * Memotong teks deskripsi panjang secara rapi tanpa merusak kata terakhir
 * @param string $text
 * @param int $length
 * @return string
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $truncated = substr($text, 0, $length);
    $last_space = strrpos($truncated, ' ');
    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }
    
    return $truncated . '...';
}
?>