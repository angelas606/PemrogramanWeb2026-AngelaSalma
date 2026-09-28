<?php
session_start();

$nama = trim($_POST['nama'] ?? '');
$noAnggota = trim($_POST['no_anggota'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$noHp = trim($_POST['no_hp'] ?? '');

$errors = [];
if ($nama === '') {
    $errors[] = "Nama wajib diisi.";
} elseif (strlen($nama) < 2) {
    $errors[] = "Nama minimal 2 karakter.";
}

if ($noAnggota === '') {
    $errors[] = "No. Anggota wajib diisi.";
} elseif (!preg_match('/^[A-Za-z0-9\-\/\s]+$/', $noAnggota)) {
    $errors[] = "No. Anggota hanya boleh berisi huruf, angka, spasi, tanda hubung, dan garis miring.";
}

if ($alamat !== '' && strlen($alamat) < 5) {
    $errors[] = "Alamat minimal 5 karakter jika diisi.";
}

if ($noHp !== '' && !preg_match('/^[0-9+\-\s]+$/', $noHp)) {
    $errors[] = "No. HP hanya boleh berisi angka, tanda plus, dan tanda hubung.";
}

if ($noHp !== '' && strlen(preg_replace('/\D/', '', $noHp)) < 10) {
    $errors[] = "No. HP minimal 10 digit angka.";
}

if (!empty($errors)) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => implode(' ', $errors)];
    header('Location: tambah.php');
    exit;
}

if (!isset($_SESSION['anggota'])) {
    $_SESSION['anggota'] = [];
}

$_SESSION['anggota'][] = [
    'nama' => $nama,
    'no_anggota' => $noAnggota,
    'alamat' => $alamat,
    'no_hp' => $noHp,
];

$_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Anggota berhasil ditambahkan.'];
header('Location: list.php');
exit;
