<?php
/**
 * Core Configuration - Facebook Engine Style
 */

$host       = "localhost";
$user       = "root";
$pass       = "";
$db         = "forum_diskusi";
$upload_dir = 'uploads/'; // Tempat penyimpanan aset gambar postingan & foto profil

// Membuat folder unggahan otomatis jika belum tersedia
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

/**
 * Pilihan Kategori / Feed Target ala Facebook Group & Page
 */
$categories = [
    'Beranda Umum',
    'Grup Teknologi',
    'Edukasi & Sekolah',
    'Acara & Kegiatan',
    'Pengumuman Komunitas'
];

// Menginisialisasi koneksi ke server database
$conn = new mysqli($host, $user, $pass, $db);

// Memeriksa status koneksi
if ($conn->connect_error) {
    die("Koneksi ke server Facebook gagal: " . $conn->connect_error);
}
?>