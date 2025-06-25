<?php
session_start();
require 'supabase.php';
require 'mailer.php';

// KONFIGURASI SUPABASE STORAGE
define('SUPABASE_PROJECT_ID', 'mxajscgaszabmustddfq');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im14YWpzY2dhc3phYm11c3RkZGZxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk1Mjg5MTQsImV4cCI6MjA2NTEwNDkxNH0.jyueIxwoYJf3sbDra98uN3vD6MYrvX_ZWN6hwyPzD38');
define('SUPABASE_STORAGE_BUCKET', 'berkas.pendaftar');

// FUNGSI UPLOAD FILE KE SUPABASE STORAGE
function upload_to_supabase_storage($file) {
    if ($file['error'] === 0) {
        $file_name = uniqid() . '_' . preg_replace('/[^A-Za-z0-9.\-_]/', '', $file['name']);
        $url = "https://mxajscgaszabmustddfq.supabase.co/storage/v1/object/berkas.pendaftar/$file_name";

        $fp = fopen($file['tmp_name'], 'rb');
        $file_data = stream_get_contents($fp);
        fclose($fp);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im14YWpzY2dhc3phYm11c3RkZGZxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk1Mjg5MTQsImV4cCI6MjA2NTEwNDkxNH0.jyueIxwoYJf3sbDra98uN3vD6MYrvX_ZWN6hwyPzD38",
            "apikey: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im14YWpzY2dhc3phYm11c3RkZGZxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk1Mjg5MTQsImV4cCI6MjA2NTEwNDkxNH0.jyueIxwoYJf3sbDra98uN3vD6MYrvX_ZWN6hwyPzD38",
            "Content-Type: application/octet-stream",
            "x-upsert: true"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // TAMPILKAN debug (penting!)
        echo "<pre>Status: $status\nResponse: ".htmlspecialchars($response)."</pre>";

        if ($status == 200 || $status == 201) {
            return "https://mxajscgaszabmustddfq.supabase.co/storage/v1/object/public/berkas.pendaftar/$file_name";
        }
    }
    return null;
}


if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pendaftar') header('Location: index.php');
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap   = $_POST['nama_lengkap'];
    $ttl            = $_POST['ttl'];
    $jenis_kelamin  = $_POST['jenis_kelamin'];
    $alamat         = $_POST['alamat'];
    $no_hp          = $_POST['no_hp'];
    $email          = $_POST['email'];
    $pendidikan     = $_POST['pendidikan'];
    $jurusan        = $_POST['jurusan'];
    $jenjang        = $_POST['jenjang'];
    $sistem_kuliah  = $_POST['sistem_kuliah'];
    $kategori       = $_POST['kategori'];
    $nama_ortu      = $_POST['nama_ortu'];
    $pendapatan_ortu= $_POST['pendapatan_ortu'];

    $berkas_url = null;
    if (isset($_FILES['berkas']) && $_FILES['berkas']['error'] === 0) {
        $berkas_url = upload_to_supabase_storage($_FILES['berkas']);
    }

    $data = [
        'user_id'         => $user_id,
        'nama_lengkap'    => $nama_lengkap,
        'ttl'             => $ttl,
        'jenis_kelamin'   => $jenis_kelamin,
        'alamat'          => $alamat,
        'no_hp'           => $no_hp,
        'email'           => $email,
        'pendidikan'      => $pendidikan,
        'jurusan'         => $jurusan,
        'jenjang'         => $jenjang,
        'sistem_kuliah'   => $sistem_kuliah,
        'kategori'        => $kategori,
        'nama_ortu'       => $nama_ortu,
        'pendapatan_ortu' => $pendapatan_ortu,
        'berkas_url'      => $berkas_url,
        'status'          => 'Baru'
    ];
    supabase_request("POST", "/rest/v1/pendaftaran", $data);

    // Ambil email user dari session atau query lagi
    $user = supabase_request("GET", "/rest/v1/users?id=eq.$user_id");
    send_email($user[0]['email'], "Pendaftaran Berhasil", "Data pendaftaran Anda sudah tersimpan.");
    header('Location: dashboard.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulir Pendaftaran</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Formulir Pendaftaran</h2>
<form method="post" enctype="multipart/form-data">
    <label>Nama Lengkap:</label><br>
    <input type="text" name="nama_lengkap" required><br>

    <label>Tempat, Tanggal Lahir:</label><br>
    <input type="text" name="ttl" required><br>

    <label>Jenis Kelamin:</label><br>
    <select name="jenis_kelamin" required>
        <option value="">--Pilih--</option>
        <option value="L">Laki-laki</option>
        <option value="P">Perempuan</option>
    </select><br>

    <label>Alamat:</label><br>
    <input type="text" name="alamat" required><br>

    <label>No HP:</label><br>
    <input type="text" name="no_hp" required><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br>

    <label>Pendidikan Terakhir:</label><br>
    <select name="pendidikan" required>
        <option value="">--Pilih--</option>
        <option value="SMK/SMA Sederajat">SMK/SMA Sederajat</option>
        <option value="D1">D1</option>
        <option value="D2">D2</option>
        <option value="D3">D3</option>
        <option value="S1">S1</option>
        <option value="S2">S2</option>
    </select><br>

    <label>Jurusan:</label><br>
    <select name="jurusan" required>
        <option value="">--Pilih--</option>
        <option value="Informatika">Informatika</option>
        <option value="Sistem Informasi">Sistem Informasi</option>
        <option value="RPL">RPL</option>
        <option value="Manajemen">Manajemen</option>
        <option value="Kewirausahaan">Kewirausahaan</option>
    </select><br>

    <label>Jenjang:</label><br>
    <select name="jenjang" required>
        <option value="">--Pilih--</option>
        <option value="D1">D1</option>
        <option value="D2">D2</option>
        <option value="D3">D3</option>
        <option value="S1">S1</option>
        <option value="S2">S2</option>
        <option value="S2">S3</option>
    </select><br>

    <label>Sistem Kuliah:</label><br>
    <select name="sistem_kuliah" required>
        <option value="">--Pilih--</option>
        <option value="Kelas Reguler">Kelas Reguler</option>
        <option value="Kelas Karyawan Malam">Kelas Karyawan Malam</option>
        <option value="Karyawan Jumat Sabtu">Karyawan Jumat Sabtu</option>
    </select><br>

    <label>Kategori:</label><br>
    <select name="kategori" required>
        <option value="">--Pilih Kategori--</option>
        <option value="umum">Umum</option>
        <option value="beasiswa">Beasiswa</option>
    </select><br>

    <label>Nama Orang Tua / Wali:</label><br>
    <input type="text" name="nama_ortu" required><br>

    <label>Pendapatan Orang Tua:</label><br>
    <input type="text" name="pendapatan_ortu" required><br>

    <label>Upload Berkas:</label><br>
    <input type="file" name="berkas" required><br>

    <button type="submit">Kirim</button>
</form>
</body>
</html>
