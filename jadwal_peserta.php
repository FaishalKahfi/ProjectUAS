<?php
session_start();
require 'supabase.php';
date_default_timezone_set('Asia/Jakarta');

// Pastikan user sudah login saja
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua jadwal seleksi
$jadwal = supabase_request("GET", "/rest/v1/jadwal_seleksi?order=tanggal.asc");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Lihat Jadwal Seleksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h2>Daftar Jadwal Seleksi</h2>
    <a href="dashboard.php">Kembali ke Dashboard</a>

    <?php if ($jadwal && count($jadwal) > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Nama Jadwal</th>
                <th>Kategori</th>
                <th>Tanggal</th>
                <th>Nilai Minimal</th>
            </tr>
            <?php foreach ($jadwal as $j): ?>
                <tr>
                    <td><?= htmlspecialchars($j['nama']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($j['kategori'])) ?></td>
                    <td>
                        <?= (new DateTime($j['tanggal'], new DateTimeZone('UTC')))
                                ->setTimezone(new DateTimeZone('Asia/Jakarta'))
                                ->format('Y-m-d H:i') ?>
                    </td>
                    <td><?= htmlspecialchars($j['nilai_min_lulus'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Tidak ada jadwal seleksi yang tersedia.</p>
    <?php endif; ?>
</body>
</html>
