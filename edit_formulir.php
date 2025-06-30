<?php
session_start();
require 'supabase.php'; // Pastikan file ini ada dan berfungsi dengan baik

// Pastikan user sudah login, jika belum redirect ke halaman login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit; // Penting: selalu keluar setelah header redirect
}
$user_id = $_SESSION['user_id'];

// --- Ambil Data Profil Pengguna ---
// Inisialisasi $profile sebagai array kosong untuk menghindari "Undefined array key 0"
$profile = []; 

$data_pendaftaran = supabase_request("GET", "/rest/v1/pendaftaran?user_id=eq.$user_id");

// Periksa apakah data ditemukan dan merupakan array yang tidak kosong
if (!empty($data_pendaftaran) && is_array($data_pendaftaran)) {
    $profile = $data_pendaftaran[0];
}

// --- Handle Update/Simpan Data Profil ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $update_data = [
        'nama_lengkap'    => $_POST['nama_lengkap'] ?? null,
        'kategori'        => $_POST['kategori'] ?? null,
        'ttl'             => $_POST['ttl'] ?? null,
        'jenis_kelamin'   => $_POST['jenis_kelamin'] ?? null,
        'alamat'          => $_POST['alamat'] ?? null,
        'no_hp'           => $_POST['no_hp'] ?? null,
        'email'           => $_POST['email'] ?? null,
        'pendidikan'      => $_POST['pendidikan'] ?? null,
        'jurusan'         => $_POST['jurusan'] ?? null,
        'jenjang'         => $_POST['jenjang'] ?? null,
        'sistem_kuliah'   => $_POST['sistem_kuliah'] ?? null,
        'nama_ortu'       => $_POST['nama_ortu'] ?? null,
        'pendapatan_ortu' => $_POST['pendapatan_ortu'] ?? null
    ];

    // Tambahkan URL berkas jika ada dari JavaScript (input hidden)
    if (isset($_POST['berkas_url_hidden']) && !empty($_POST['berkas_url_hidden'])) {
        $update_data['berkas_url'] = $_POST['berkas_url_hidden'];
    }

    // Tentukan apakah ini INSERT (pendaftaran baru) atau UPDATE (edit profil)
    if (empty($profile)) {
        // Jika $profile kosong, berarti user belum punya data pendaftaran
        // Lakukan INSERT (POST request)
        $update_data['user_id'] = $user_id; // Penting: tambahkan user_id untuk pendaftaran baru
        $response = supabase_request("POST", "/rest/v1/pendaftaran", $update_data);
    } else {
        // Jika $profile tidak kosong, berarti user sudah punya data, lakukan UPDATE (PATCH request)
        $response = supabase_request("PATCH", "/rest/v1/pendaftaran?user_id=eq.$user_id", $update_data);
    }
    
    // Anda mungkin ingin memeriksa $response untuk keberhasilan atau kegagalan
    // Namun untuk saat ini, kita anggap sukses dan redirect
    header("Location: edit_formulir.php?sukses=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Formulir</title>
    <link rel="stylesheet" href="assets/css/style.css">
    </head>
<body>
<h2>Edit Formulir</h2>
<?php if(isset($_GET['sukses'])) echo "<b>Data berhasil diupdate!</b>"; ?>
<form method="post">
    <label>Nama Lengkap</label><br>
    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($profile['nama_lengkap'] ?? '') ?>" required><br><br>

    <label>Tempat, Tanggal Lahir</label><br>
    <input type="text" name="ttl" value="<?= htmlspecialchars($profile['ttl'] ?? '') ?>"><br><br>

    <label>Jenis Kelamin</label><br>
    <select name="jenis_kelamin">
        <option value="Laki-laki" <?= (($profile['jenis_kelamin'] ?? '') == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
        <option value="Perempuan" <?= (($profile['jenis_kelamin'] ?? '') == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
    </select><br><br>

    <label>Alamat</label><br>
    <textarea name="alamat"><?= htmlspecialchars($profile['alamat'] ?? '') ?></textarea><br><br>

    <label>No HP</label><br>
    <input type="text" name="no_hp" value="<?= htmlspecialchars($profile['no_hp'] ?? '') ?>"><br><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>"><br><br>

    <label>Pendidikan Terakhir:</label><br>
    <select name="pendidikan" required>
        <option value="">--Pilih--</option>
        <option value="SMK/SMA Sederajat" <?= (($profile['pendidikan'] ?? '') == 'SMK/SMA Sederajat') ? 'selected' : '' ?>>SMK/SMA Sederajat</option>
        <option value="D1" <?= (($profile['pendidikan'] ?? '') == 'D1') ? 'selected' : '' ?>>D1</option>
        <option value="D2" <?= (($profile['pendidikan'] ?? '') == 'D2') ? 'selected' : '' ?>>D2</option>
        <option value="D3" <?= (($profile['pendidikan'] ?? '') == 'D3') ? 'selected' : '' ?>>D3</option>
        <option value="S1" <?= (($profile['pendidikan'] ?? '') == 'S1') ? 'selected' : '' ?>>S1</option>
        <option value="S2" <?= (($profile['pendidikan'] ?? '') == 'S2') ? 'selected' : '' ?>>S2</option>
    </select><br>

    <label>Jurusan:</label><br>
    <select name="jurusan" required>
        <option value="">--Pilih--</option>
        <option value="Informatika" <?= (($profile['jurusan'] ?? '') == 'Informatika') ? 'selected' : '' ?>>Informatika</option>
        <option value="Sistem Informasi" <?= (($profile['jurusan'] ?? '') == 'Sistem Informasi') ? 'selected' : '' ?>>Sistem Informasi</option>
        <option value="RPL" <?= (($profile['jurusan'] ?? '') == 'RPL') ? 'selected' : '' ?>>RPL</option>
        <option value="Manajemen" <?= (($profile['jurusan'] ?? '') == 'Manajemen') ? 'selected' : '' ?>>Manajemen</option>
        <option value="Kewirausahaan" <?= (($profile['jurusan'] ?? '') == 'Kewirausahaan') ? 'selected' : '' ?>>Kewirausahaan</option>
    </select><br>

    <label>Jenjang:</label><br>
    <select name="jenjang" required>
        <option value="">--Pilih--</option>
        <option value="D1" <?= (($profile['jenjang'] ?? '') == 'D1') ? 'selected' : '' ?>>D1</option>
        <option value="D2" <?= (($profile['jenjang'] ?? '') == 'D2') ? 'selected' : '' ?>>D2</option>
        <option value="D3" <?= (($profile['jenjang'] ?? '') == 'D3') ? 'selected' : '' ?>>D3</option>
        <option value="S1" <?= (($profile['jenjang'] ?? '') == 'S1') ? 'selected' : '' ?>>S1</option>
        <option value="S2" <?= (($profile['jenjang'] ?? '') == 'S2') ? 'selected' : '' ?>>S2</option>
        <option value="S3" <?= (($profile['jenjang'] ?? '') == 'S3') ? 'selected' : '' ?>>S3</option>
    </select><br>

    <label>Sistem Kuliah:</label><br>
    <select name="sistem_kuliah" required>
        <option value="">--Pilih--</option>
        <option value="Kelas Reguler" <?= (($profile['sistem_kuliah'] ?? '') == 'Kelas Reguler') ? 'selected' : '' ?>>Kelas Reguler</option>
        <option value="Kelas Karyawan Malam" <?= (($profile['sistem_kuliah'] ?? '') == 'Kelas Karyawan Malam') ? 'selected' : '' ?>>Kelas Karyawan Malam</option>
        <option value="Karyawan Jumat Sabtu" <?= (($profile['sistem_kuliah'] ?? '') == 'Karyawan Jumat Sabtu') ? 'selected' : '' ?>>Karyawan Jumat Sabtu</option>
    </select><br>

    <label>Kategori:</label><br>
    <select name="kategori" required>
        <option value="">--Pilih Kategori--</option>
        <option value="umum" <?= (($profile['kategori'] ?? '') == 'umum') ? 'selected' : '' ?>>Umum</option>
        <option value="beasiswa" <?= (($profile['kategori'] ?? '') == 'beasiswa') ? 'selected' : '' ?>>Beasiswa</option>
    </select><br>

    <label>Nama Orang Tua</label><br>
    <input type="text" name="nama_ortu" value="<?= htmlspecialchars($profile['nama_ortu'] ?? '') ?>"><br><br>

    <label>Pendapatan Orang Tua</label><br>
    <input type="text" name="pendapatan_ortu" value="<?= htmlspecialchars($profile['pendapatan_ortu'] ?? '') ?>"><br><br>

    <label>URL Berkas (Otomatis dari Upload)</label><br>
    <input type="text" name="berkas_url_hidden" id="berkas_url_input" value="<?= htmlspecialchars($profile['berkas_url'] ?? '') ?>" readonly><br><br>

    <h3>Upload Berkas (Gambar) ke Supabase Storage</h3>
    <input type="file" id="uploadFile" accept="image/*" />
    <button type="button" id="uploadBtn">Upload Berkas</button> <div id="result" style="margin-top: 20px;"></div>

    <script type="module">
        // Pastikan Anda sudah menginstal @supabase/supabase-js, atau gunakan CDN yang sesuai
        import { createClient } from 'https://esm.sh/@supabase/supabase-js';

        // Ganti ini dengan Supabase URL dan Anon Key Anda yang sebenarnya
        const supabaseUrl = 'https://oedivlvixbbovizhavwa.supabase.co';
        const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9lZGl2bHZpeGJib3ZpemhhdndhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTEyNTgzMDcsImV4cCI6MjA2NjgzNDMwN30.XpV1qEVjwqfepbC6AcBVkO-u-9Mf1ttszf4pOYZ11Ic';
        const supabase = createClient(supabaseUrl, supabaseKey);

        document.getElementById('uploadBtn').addEventListener('click', async () => {
            const fileInput = document.getElementById('uploadFile');
            const resultDiv = document.getElementById('result');
            const berkasUrlInput = document.getElementById('berkas_url_input'); // Ambil elemen input untuk URL berkas
            const file = fileInput.files[0];

            if (!file) {
                resultDiv.textContent = "⚠️ Silakan pilih file gambar terlebih dahulu.";
                return;
            }

            // Buat nama file unik untuk menghindari konflik
            const fileName = `${Date.now()}_${file.name}`;

            // --- PERHATIAN PENTING: GANTI 'berkas_pendaftar' dengan NAMA BUCKET ANDA YANG SEBENARNYA DI SUPABASE ---
            // Contoh: Jika nama bucket Anda di Supabase adalah 'pendaftaran-berkas' atau 'uploads'
            const bucketName = 'berkas.pendaftar'; // <-- INI YANG PALING KEMUNGKINAN BESAR PERLU ANDA SESUAIKAN

            const { data, error } = await supabase
                .storage
                .from(bucketName)
                .upload(fileName, file, {
                    cacheControl: '3600', // Cache selama 1 jam
                    upsert: false // Set true jika ingin menimpa file dengan nama yang sama
                });

            if (error) {
                console.error("Upload gagal:", error.message);
                resultDiv.textContent = "❌ Upload gagal: " + error.message;
            } else {
                // Setelah upload sukses, ambil URL publik dari file yang diunggah
                const { data: publicUrlData } = supabase
                    .storage
                    .from(bucketName) // Gunakan nama bucket yang sama
                    .getPublicUrl(fileName);

                if (publicUrlData && publicUrlData.publicUrl) {
                    resultDiv.innerHTML = `
                        ✅ Upload berhasil!<br>
                        <a href="${publicUrlData.publicUrl}" target="_blank">${publicUrlData.publicUrl}</a><br>
                        <img src="${publicUrlData.publicUrl}" alt="Uploaded Image" style="max-width: 300px; margin-top: 10px;" />
                    `;
                    // Setel nilai URL ke input hidden agar bisa dikirim ke PHP
                    berkasUrlInput.value = publicUrlData.publicUrl;
                } else {
                    resultDiv.textContent = "❌ Gagal mendapatkan URL publik setelah upload.";
                }
            }
        });
    </script>
    <button type="submit">Simpan Perubahan</button>
</form>
<a href="dashboard.php">Kembali ke Dashboard</a>
</body>
</html>