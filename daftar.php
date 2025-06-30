<?php
session_start();
require 'supabase.php'; // Pastikan file ini ada dan berfungsi untuk interaksi DB Supabase
require 'mailer.php';   // Pastikan file ini ada dan berfungsi untuk pengiriman email

// KONFIGURASI SUPABASE STORAGE (digunakan di fungsi upload_to_supabase_storage)
// Ganti dengan Project ID dan Anon Key Supabase Anda
define('SUPABASE_PROJECT_ID', 'oedivlvixbbovizhavwa');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9lZGl2bHZpeGJib3ZpemhhdndhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTEyNTgzMDcsImV4cCI6MjA2NjgzNDMwN30.XpV1qEVjwqfepbC6AcBVkO-u-9Mf1ttszf4pOYZ11Ic');
define('SUPABASE_STORAGE_BUCKET', 'berkas.pendaftar'); // Pastikan ini nama bucket yang BENAR di Supabase Storage

/**
 * FUNGSI: Mengunggah satu file ke Supabase Storage menggunakan cURL.
 * @param array $file Array informasi file tunggal dari $_FILES (misal: $_FILES['berkas']['tmp_name'][0])
 * @return string|null URL publik file yang diunggah jika berhasil, null jika gagal.
 */
function upload_to_supabase_storage($file) {
    if ($file['error'] === UPLOAD_ERR_OK) { // Pastikan tidak ada error upload dari sisi PHP
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        // Buat nama file unik untuk mencegah konflik, sertakan nama asli untuk readability
        $file_name = uniqid() . '_' . preg_replace('/[^A-Za-z0-9.\-_]/', '', basename($file['name'], '.' . $file_extension)) . '.' . $file_extension;
        
        // URL endpoint untuk upload ke Supabase Storage
        $upload_url = "https://" . SUPABASE_PROJECT_ID . ".supabase.co/storage/v1/object/" . SUPABASE_STORAGE_BUCKET . "/" . $file_name;

        // Membaca konten file dari lokasi sementara
        $fp = fopen($file['tmp_name'], 'rb');
        if (!$fp) {
            error_log("Failed to open temporary file: " . $file['tmp_name']);
            return null;
        }
        $file_data = stream_get_contents($fp);
        fclose($fp);

        // Inisialisasi cURL
        $ch = curl_init($upload_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Metode HTTP PUT untuk upload
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . SUPABASE_ANON_KEY, // Gunakan ANON KEY
            "apikey: " . SUPABASE_ANON_KEY,               // Gunakan ANON KEY
            "Content-Type: " . $file['type'],             // Tipe MIME file dari $_FILES
            "x-upsert: true"                              // Overwrite jika ada file dengan nama yang sama
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data); // Data file
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // Mengembalikan response sebagai string

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // --- Debugging Penting ---
        error_log("[UPLOAD] Status for " . $file['name'] . ": " . $status);
        error_log("[UPLOAD] Response for " . $file['name'] . ": " . $response);
        if ($curl_error) {
            error_log("[UPLOAD] cURL Error for " . $file['name'] . ": " . $curl_error);
        }
        // --- End Debugging ---

        // Cek status HTTP dari response Supabase
        if ($status == 200 || $status == 201) {
            // Jika berhasil, kembalikan URL publik
            // Catatan: Supabase Storage URL publik memiliki format '.../storage/v1/object/public/bucket_name/file_name'
            return "https://" . SUPABASE_PROJECT_ID . ".supabase.co/storage/v1/object/public/" . SUPABASE_STORAGE_BUCKET . "/" . $file_name;
        } else {
            error_log("[UPLOAD] Failed to upload file to Supabase. Status: " . $status . ", Response: " . $response);
        }
    } else {
        error_log("[UPLOAD] PHP File Upload Error for " . $file['name'] . " (Code: " . $file['error'] . ")");
        // Tambahkan detail error PHP untuk debugging lebih lanjut
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE: error_log("The uploaded file exceeds the upload_max_filesize directive in php.ini."); break;
            case UPLOAD_ERR_FORM_SIZE: error_log("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form."); break;
            case UPLOAD_ERR_PARTIAL: error_log("The uploaded file was only partially uploaded."); break;
            case UPLOAD_ERR_NO_FILE: error_log("No file was uploaded."); break;
            case UPLOAD_ERR_NO_TMP_DIR: error_log("Missing a temporary folder."); break;
            case UPLOAD_ERR_CANT_WRITE: error_log("Failed to write file to disk."); break;
            case UPLOAD_ERR_EXTENSION: error_log("A PHP extension stopped the file upload."); break;
        }
    }
    return null; // Mengembalikan null jika upload gagal
}


// Cek sesi pengguna dan peran (pastikan user sudah login sebagai pendaftar)
// Asumsi $_SESSION['role'] diatur saat login
if (!isset($_SESSION['user_id']) /* || $_SESSION['role'] != 'pendaftar' */) { // Hapus bagian role jika belum diterapkan
    header('Location: index.php');
    exit(); // Penting: keluar setelah redirect
}
$user_id = $_SESSION['user_id'];

// Proses form jika di-submit dengan metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- DEBUGGING: Tampilkan semua data POST ---
    error_log("[FORM SUBMIT] Received POST data: " . print_r($_POST, true));
    error_log("[FORM SUBMIT] Received FILES data: " . print_r($_FILES, true));
    // --- END DEBUGGING ---

    // Ambil data dari form menggunakan null coalescing operator untuk keamanan
    $nama_lengkap     = $_POST['nama_lengkap'] ?? '';
    $ttl              = $_POST['ttl'] ?? '';
    $jenis_kelamin    = $_POST['jenis_kelamin'] ?? '';
    $alamat           = $_POST['alamat'] ?? '';
    $no_hp            = $_POST['no_hp'] ?? '';
    $email            = $_POST['email'] ?? '';
    $pendidikan       = $_POST['pendidikan'] ?? '';
    $jurusan          = $_POST['jurusan'] ?? '';
    $jenjang          = $_POST['jenjang'] ?? '';
    $sistem_kuliah    = $_POST['sistem_kuliah'] ?? '';
    $kategori         = $_POST['kategori'] ?? '';
    $nama_ortu        = $_POST['nama_ortu'] ?? '';
    $pendapatan_ortu  = $_POST['pendapatan_ortu'] ?? '';

    $berkas_urls = []; // Array untuk menyimpan semua URL berkas yang berhasil diupload
    
    // Cek apakah ada file yang diunggah melalui input 'berkas[]'
    if (isset($_FILES['berkas']) && is_array($_FILES['berkas']['name'])) {
        $file_count = count($_FILES['berkas']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            // Pastikan file yang diupload tidak kosong dan tidak ada error
            if ($_FILES['berkas']['error'][$i] === UPLOAD_ERR_OK && !empty($_FILES['berkas']['tmp_name'][$i])) {
                $single_file_info = [
                    'name'     => $_FILES['berkas']['name'][$i],
                    'type'     => $_FILES['berkas']['type'][$i],
                    'tmp_name' => $_FILES['berkas']['tmp_name'][$i],
                    'error'    => $_FILES['berkas']['error'][$i],
                    'size'     => $_FILES['berkas']['size'][$i],
                ];

                $url = upload_to_supabase_storage($single_file_info);
                if ($url) {
                    $berkas_urls[] = $url; // Tambahkan URL ke array jika upload berhasil
                }
            } else {
                error_log("[FORM SUBMIT] File upload skipped or had error: " . $_FILES['berkas']['name'][$i] . " Error Code: " . $_FILES['berkas']['error'][$i]);
            }
        }
    } else {
        error_log("[FORM SUBMIT] No files uploaded for 'berkas' field or an unknown error occurred with FILES array structure.");
    }
    
    // Gabungkan semua URL yang berhasil diupload menjadi satu string yang dipisahkan koma
    // Jika tidak ada berkas yang diupload/berhasil, $berkas_url_string akan kosong
    $berkas_url_string = implode(',', $berkas_urls);

    // Data untuk diinsert ke tabel 'pendaftaran'
    $data_to_insert = [ // Ganti nama variabel agar tidak ambigu dengan $_POST['data']
        'user_id'          => $user_id, // Penting: user_id dari sesi
        'nama_lengkap'     => $nama_lengkap,
        'ttl'              => $ttl,
        'jenis_kelamin'    => $jenis_kelamin,
        'alamat'           => $alamat,
        'no_hp'            => $no_hp,
        'email'            => $email,
        'pendidikan'       => $pendidikan,
        'jurusan'          => $jurusan,
        'jenjang'          => $jenjang,
        'sistem_kuliah'    => $sistem_kuliah,
        'kategori'         => $kategori,
        'nama_ortu'        => $nama_ortu,
        'pendapatan_ortu'  => $pendapatan_ortu,
        'berkas_url'       => $berkas_url_string, // URL berkas dari Supabase Storage (bisa banyak)
        'status'           => 'Calon Mahasiswa'              // Status awal pendaftaran
    ];

    // --- DEBUGGING: Tampilkan data yang akan diinsert ke DB ---
    error_log("[DB INSERT] Data to be inserted: " . print_r($data_to_insert, true));
    // --- END DEBUGGING ---

    // Lakukan request POST ke API Supabase untuk menyimpan data
    // Pastikan fungsi supabase_request di 'supabase.php' sudah benar dan memiliki logging
    $response_db = supabase_request("POST", "/rest/v1/pendaftaran", $data_to_insert);

    // Cek respon dari Supabase API
    // Supabase biasanya mengembalikan array kosong [] jika insert sukses dan return=representation tidak mengembalikan apa-apa
    // atau mengembalikan array dengan objek data yang baru dibuat jika return=representation aktif
    // Jika ada error, akan ada kunci 'code' atau 'message' di respons
    if ($response_db && !isset($response_db['code']) && !isset($response_db['message'])) { // Periksa apakah tidak ada indikasi error
        // Ambil email user untuk pengiriman notifikasi
        $user_data_from_db = supabase_request("GET", "/rest/v1/users?id=eq.$user_id");
        if ($user_data_from_db && isset($user_data_from_db[0]['email'])) {
            send_email($user_data_from_db[0]['email'], "Pendaftaran Berhasil", "Selamat, data pendaftaran Anda sudah tersimpan. Silakan cek dashboard untuk informasi lebih lanjut.");
        } else {
            error_log("[EMAIL] Failed to retrieve user email for notification: " . json_encode($user_data_from_db));
        }
        
        $_SESSION['success_message'] = "Pendaftaran berhasil disimpan!";
        header('Location: dashboard.php'); // Redirect ke dashboard setelah berhasil
        exit();
    } else {
        $error_message = "Terjadi kesalahan saat menyimpan data pendaftaran.";
        // Coba ambil pesan error lebih detail dari respons Supabase
        if (isset($response_db['message'])) {
            $error_message .= " Pesan Supabase: " . $response_db['message'];
        } elseif (isset($response_db['details'])) { // Kadang detail ada di 'details'
            $error_message .= " Detail Supabase: " . $response_db['details'];
        }
        error_log("[DB INSERT ERROR] Supabase DB Insert Error Response: " . json_encode($response_db));
        $_SESSION['error_message'] = $error_message;
        header('Location: daftar.php'); // Redirect kembali ke form dengan pesan error
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulir Pendaftaran</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Beberapa style dasar untuk label dan input agar lebih rapi */
        body { font-family: sans-serif; margin: 20px; background-color: #f4f7f6; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        form { max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e0e0e0; border-radius: 10px; background-color: #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        input[type="text"],
        input[type="email"],
        select,
        textarea { /* Tambahkan textarea di sini */
            width: calc(100% - 22px); /* Adjust for padding and border */
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            font-size: 1em;
        }
        input[type="file"] {
            margin-bottom: 20px;
        }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<h2>Formulir Pendaftaran</h2>

<?php
// Tampilkan pesan sukses atau error dari session
if (isset($_SESSION['success_message'])) {
    echo '<div class="message success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="message error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<form method="post" enctype="multipart/form-data" action="daftar.php">
    <label>Nama Lengkap:</label><br>
    <input type="text" name="nama_lengkap" required><br>

    <label>Tempat, Tanggal Lahir:</label><br>
    <input type="text" name="ttl" required><br>

    <label>Jenis Kelamin:</label><br>
    <select name="jenis_kelamin" required>
        <option value="">--Pilih--</option>
        <option value="Laki-laki">Laki-laki</option> <!-- Sesuaikan dengan nilai di DB Anda -->
        <option value="Perempuan">Perempuan</option> <!-- Sesuaikan dengan nilai di DB Anda -->
    </select><br>

    <label>Alamat:</label><br>
    <textarea name="alamat" required></textarea><br> <!-- Menggunakan textarea untuk alamat -->

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
        <option value="S3">S3</option>
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

    <label>Upload Berkas (Bisa Pilih Lebih dari 1 Gambar atau PDF):</label><br>
    <!-- Tambahkan 'application/pdf' ke atribut accept -->
    <input type="file" name="berkas[]" accept="image/*,application/pdf" multiple required><br>

    <button type="submit">Kirim Pendaftaran</button>
</form>
</body>
</html>