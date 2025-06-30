<?php
session_start();
require 'supabase.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fungsi untuk update semua peserta berdasarkan kategori
function update_jadwal_ke_peserta($kategori, $jadwal_id, $nilai_minimal)
{
    $peserta = supabase_request("GET", "/rest/v1/pendaftaran?kategori=eq.$kategori&or=(jadwal_id.is.null,jadwal_id.eq.$jadwal_id)");

    foreach ($peserta as $p) {
        $status = 'Baru';
        if (isset($p['nilai_ujian']) && is_numeric($p['nilai_ujian'])) {
            $nilai = floatval($p['nilai_ujian']);
            if ($nilai >= $nilai_minimal) {
                $status = 'Lulus';
            } else {
                $status = 'Gagal';
            }
        }
        supabase_request("PATCH", "/rest/v1/pendaftaran?id=eq.{$p['id']}", [
            'jadwal_id' => $jadwal_id,
            'status' => $status
        ]);
    }
}

// Tambah jadwal baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_jadwal'])) {
    $nama = $_POST['nama'];
    $tanggal = $_POST['tanggal'];
    $kategori = $_POST['kategori'];
    $nilai_min_lulus = $_POST['nilai_min_lulus'];

    $dt = new DateTime($tanggal, new DateTimeZone('Asia/Jakarta'));
    $dt->setTimezone(new DateTimeZone('UTC'));
    $tanggal_utc = $dt->format('Y-m-d\TH:i:s\Z');

    $res = supabase_request("POST", "/rest/v1/jadwal_seleksi", [
        'nama' => $nama,
        'tanggal' => $tanggal_utc,
        'kategori' => $kategori,
        'nilai_min_lulus' => floatval($nilai_min_lulus)
    ]);

    $jadwal_id = $res[0]['id'] ?? null;

    if ($jadwal_id) {
        update_jadwal_ke_peserta($kategori, $jadwal_id, floatval($nilai_minimal));
    }
    header("Location: jadwal.php?success=1");
    exit;
}

// Edit jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_jadwal'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $tanggal = $_POST['tanggal'];
    $kategori = $_POST['kategori'];
    $nilai_minimal = $_POST['nilai_min_lulus'] !== '' ? floatval($_POST['nilai_min_lulus']) : null;

    $dt = new DateTime($tanggal, new DateTimeZone('Asia/Jakarta'));
    $dt->setTimezone(new DateTimeZone('UTC'));
    $tanggal_utc = $dt->format('Y-m-d\TH:i:s\Z');

    supabase_request("PATCH", "/rest/v1/jadwal_seleksi?id=eq.$id", [
        'nama' => $nama,
        'tanggal' => $tanggal_utc,
        'kategori' => $kategori,
        'nilai_min_lulus' => $nilai_min_lulus !== '' ? floatval($nilai_min_lulus) : null
    ]);

    update_jadwal_ke_peserta($kategori, $id, floatval($nilai_minimal));

    header("Location: jadwal.php?edit_success=1");
    exit;
}

// Hapus jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_jadwal'])) {
    $id = $_POST['id'];

    // Reset peserta yang punya jadwal_id ini
    supabase_request("PATCH", "/rest/v1/pendaftaran?jadwal_id=eq.$id", [
        'jadwal_id' => null,
        'status' => 'Baru'
    ]);

    supabase_request("DELETE", "/rest/v1/jadwal_seleksi?id=eq.$id");

    header("Location: jadwal.php?hapus_success=1");
    exit;
}

$semua_jadwal = supabase_request("GET", "/rest/v1/jadwal_seleksi?order=tanggal.desc");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Kelola Jadwal Seleksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <h2>Kelola Jadwal Seleksi</h2>
    <a href="dashboard.php">Kembali ke Dashboard</a>

    <h3>Tambah Jadwal Baru</h3>
<form method="post">
    <input type="text" name="nama" placeholder="Nama Jadwal" required>
    <input type="datetime-local" name="tanggal" required>
    <select name="kategori" required>
        <option value="umum">Umum</option>
        <option value="beasiswa">Beasiswa</option>
    </select>
    <input type="number" step="0.01" name="nilai_min_lulus" placeholder="Nilai Minimal" required>
    <button type="submit" name="tambah_jadwal">Tambah</button>
</form>

<h3>Daftar Jadwal Seleksi</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>Nama</th>
        <th>Tanggal</th>
        <th>Kategori</th>
        <th>Nilai Minimal</th>
        <th>Aksi</th>
    </tr>
    <?php foreach ($semua_jadwal as $j): ?>
        <tr>
            <form method="post">
                <td>
                    <input type="text" name="nama" value="<?= htmlspecialchars($j['nama']) ?>" required>
                </td>
                <td>
                    <input type="datetime-local" name="tanggal"
                        value="<?= (new DateTime($j['tanggal'], new DateTimeZone('UTC')))
                        ->setTimezone(new DateTimeZone('Asia/Jakarta'))
                        ->format('Y-m-d\TH:i') ?>" required>
                </td>
                <td>
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($j['kategori']) ?>">
                    <?= htmlspecialchars(ucfirst($j['kategori'])) ?>
                </td>
                <td>
                    <input type="number" step="0.01" name="nilai_min_lulus" value="<?= htmlspecialchars($j['nilai_min_lulus']) ?>" required>
                </td>
                <td>
                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                    <button type="submit" name="edit_jadwal">Simpan</button>
                    <button type="submit" name="hapus_jadwal" onclick="return confirm('Yakin hapus?')">Hapus</button>
                </td>
            </form>
        </tr>
    <?php endforeach; ?>
</table>

</body>

</html>
