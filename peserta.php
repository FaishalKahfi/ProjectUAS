<?php
session_start();
require 'supabase.php';
require 'mailer.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// FILTER
$filter = $_GET['filter'] ?? '';
$status = $_GET['status'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$endpoint = "/rest/v1/pendaftaran";
$params = [];
if($filter) $params[] = "nama_lengkap=ilike.*$filter*";
if($status) $params[] = "status=eq.$status";
if($kategori) $params[] = "kategori=eq.$kategori";
if($params) $endpoint .= "?" . implode("&", $params);

$pendaftar = supabase_request("GET", $endpoint);

// Update semua nilai sekaligus
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_semua'])) {
    foreach ($_POST['nilai'] as $id => $nilai) {
        $nilai = $nilai === '' ? null : floatval($nilai);

        // Ambil data pendaftar
        $pendaftaran = supabase_request("GET", "/rest/v1/pendaftaran?id=eq.$id");
        $p = $pendaftaran[0];

        $jadwal = null;
        if (!empty($p['jadwal_id'])) {
            $jadwal_data = supabase_request("GET", "/rest/v1/jadwal_seleksi?id=eq." . $p['jadwal_id']);
            if ($jadwal_data) {
                $jadwal = $jadwal_data[0];
            }
        }

        $status = $p['status'];
        if ($jadwal && isset($jadwal['nilai_min_lulus']) && $nilai !== null) {
            $status = $nilai >= floatval($jadwal['nilai_min_lulus']) ? 'Lulus' : 'Gagal';
        }

        $update_data = ['nilai' => $nilai];
        if ($nilai !== null && isset($status)) {
            $update_data['status'] = $status;
        }

        supabase_request("PATCH", "/rest/v1/pendaftaran?id=eq.$id", $update_data);

        $user = supabase_request("GET", "/rest/v1/users?id=eq." . $p['user_id']);
        send_email($user[0]['email'], "Status Pendaftaran", "Nilai Anda: $nilai. Status Anda: $status");
    }

    header('Location: peserta.php');
    exit;
}

// Hapus peserta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_peserta']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    supabase_request("DELETE", "/rest/v1/pendaftaran?id=eq.$id");
    header("Location: peserta.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Peserta (Admin)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Data Peserta</h2>
<a href="export.php">Export Excel</a> | <a href="jadwal.php">Kelola Jadwal</a> | <a href="logout.php">Logout</a>
<form method="get" style="margin-bottom:10px;">
    <input type="text" name="filter" placeholder="Cari nama peserta..." value="<?= htmlspecialchars($filter) ?>" />
    <select name="kategori">
        <option value="">Semua Kategori</option>
        <option value="umum" <?= ($kategori=='umum')?'selected':'' ?>>Umum</option>
        <option value="beasiswa" <?= ($kategori=='beasiswa')?'selected':'' ?>>Beasiswa</option>
    </select>
    <select name="status">
        <option value="">Semua Status</option>
        <option <?= $status=='Baru'?'selected':'' ?>>Baru</option>
        <option <?= $status=='Diverifikasi'?'selected':'' ?>>Diverifikasi</option>
        <option <?= $status=='Lulus'?'selected':'' ?>>Lulus</option>
        <option <?= $status=='Gagal'?'selected':'' ?>>Gagal</option>
    </select>
    <button type="submit">Filter</button>
</form>
<form method="post">
<table border="1" cellpadding="5">
    <tr>
        <th>Nama</th>
        <th>Kategori</th>
        <th>Berkas</th>
        <th>Nilai</th>
        <th>Status</th>
        <th>Jadwal</th>
        <th>Aksi</th>
    </tr>
    <?php foreach($pendaftar as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
        <td><?= htmlspecialchars($p['kategori']) ?></td>
        <td>
            <?php if (!empty($p['berkas_url'])): ?>
                <a href="<?= htmlspecialchars($p['berkas_url']) ?>" target="_blank">Lihat</a>
            <?php else: ?>-<?php endif; ?>
        </td>
        <td>
            <input type="number" step="0.01" name="nilai[<?= $p['id'] ?>]" value="<?= isset($p['nilai']) ? htmlspecialchars($p['nilai']) : '' ?>" placeholder="Nilai Seleksi" style="width:80px">
        </td>
        <td><?= htmlspecialchars($p['status']) ?></td>
        <td>
            <?php
            if (!empty($p['jadwal_id'])) {
                $jadwal_data = supabase_request("GET", "/rest/v1/jadwal_seleksi?id=eq.{$p['jadwal_id']}");
                if ($jadwal_data) {
                    $j = $jadwal_data[0];
                    $tanggal = (new DateTime($j['tanggal'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d H:i');
                    echo htmlspecialchars($j['nama']) . " (" . $tanggal . ")";
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            ?>
        </td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" name="hapus_peserta" onclick="return confirm('Yakin ingin menghapus peserta ini?');" style="background:red;color:white;">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<div style="text-align:right;margin-top:10px;">
    <button type="submit" name="simpan_semua">Simpan Semua Perubahan</button>
</div>
</form>
</body>
</html>