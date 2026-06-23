<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// Auto-seed lokasi jika kosong (untuk testing)
$stmtCekLokasi = $pdo->query("SELECT COUNT(*) FROM locations");
if ($stmtCekLokasi->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO locations (nama_lokasi, kategori_lokasi) VALUES 
        ('Kamar 302', 'Kamar'), 
        ('Lobby Utama', 'Fasilitas Umum'), 
        ('Restoran', 'Fasilitas Umum')");
}

// Ambil data lokasi
$locations = $pdo->query("SELECT * FROM locations ORDER BY nama_lokasi ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urgensi = $_POST['urgensi'] ?? 'sedang';
    $location_id = $_POST['location_id'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($location_id) || empty($kategori) || empty($deskripsi)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        // Buat nomor tiket unik (misal: RPT-202405-001)
        $nomor_tiket = 'RPT-' . date('Ym') . '-' . rand(1000, 9999);
        $judul_masalah = "Laporan $kategori (" . strtoupper($urgensi) . ")";

        try {
            $stmt = $pdo->prepare("INSERT INTO maintenance_reports (nomor_tiket, user_pelapor_id, location_id, judul_masalah, deskripsi, tingkat_prioritas) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nomor_tiket, $_SESSION['user_id'], $location_id, $judul_masalah, $deskripsi, $urgensi]);
            $report_id = $pdo->lastInsertId();

            // Handle file upload untuk foto_bukti
            $uploadMsg = '';
            if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['foto_bukti']['name'], PATHINFO_EXTENSION));
                $fileName = time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $fileExtension;
                $targetFile = $uploadDir . $fileName;
                
                $allowedTypes = ['jpg', 'png', 'jpeg', 'gif', 'webp'];
                
                if (in_array($fileExtension, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $targetFile)) {
                        $pathPenyimpanan = 'uploads/' . $fileName;
                        $stmtDoc = $pdo->prepare("INSERT INTO documents (report_id, nama_file, tipe_file, path_penyimpanan, diunggah_oleh) VALUES (?, ?, ?, ?, ?)");
                        $stmtDoc->execute([$report_id, $_FILES['foto_bukti']['name'], 'foto_kerusakan', $pathPenyimpanan, $_SESSION['user_id']]);
                    } else {
                        $uploadMsg = " (Namun foto gagal disimpan ke server)";
                    }
                } else {
                    $uploadMsg = " (Namun format foto tidak didukung)";
                }
            } elseif (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadMsg = " (Namun terjadi error saat upload foto)";
            }

            // Log Aktivitas
            $stmtLog = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, target_tabel, target_id, deskripsi_log) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([$_SESSION['user_id'], 'CREATE_REPORT', 'maintenance_reports', $report_id, "Membuat laporan baru tiket $nomor_tiket."]);

            $success = "Laporan Kerusakan berhasil dikirim dan masuk ke antrean DMS! ID Laporan: $nomor_tiket" . $uploadMsg;
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
$page_title = "Form Pelaporan - NusaDMS";
require_once 'layout_header.php';
?>
            <!-- PAGE CONTENT START -->
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Form Pelaporan Kerusakan</h1>
                    <p class="text-sm text-gray-500 mt-1">Isi detail kerusakan untuk diinput ke dalam sistem Document Management (DMS).</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 max-w-4xl">
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg mb-6">
                        <h4 class="text-sm font-bold text-red-800">Gagal Mengirim</h4>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg mb-6">
                        <h4 class="text-sm font-bold text-green-800">Berhasil!</h4>
                        <p class="text-xs text-green-600 mt-1"><?= htmlspecialchars($success) ?></p>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-800">Nama Pelapor</label>
                            <input type="text" value="<?= htmlspecialchars($_SESSION['nama']) ?> - <?= htmlspecialchars(strtoupper($_SESSION['role'])) ?>" disabled class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-500 cursor-not-allowed">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-800">Tingkat Urgensi <span class="text-red-500">*</span></label>
                            <select name="urgensi" required class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-red-100 transition-all">
                                <option value="rendah">Rendah</option>
                                <option value="sedang" selected>Sedang (Butuh segera)</option>
                                <option value="tinggi">Darurat (Berisiko)</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-800">Lokasi / No. Kamar <span class="text-red-500">*</span></label>
                            <select name="location_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-red-100 transition-all">
                                <option value="">-- Pilih Lokasi --</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['nama_lokasi']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-800">Kategori Kerusakan <span class="text-red-500">*</span></label>
                            <select name="kategori" required class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-red-100 transition-all">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="mep">Mekanikal, Elektrikal, Plumbing (MEP)</option>
                                <option value="hk">Fasilitas & Housekeeping</option>
                            </select>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-semibold text-gray-800">Deskripsi Masalah <span class="text-red-500">*</span></label>
                            <textarea name="deskripsi" required rows="4" placeholder="Jelaskan secara detail kerusakan yang terjadi (Contoh: AC kamar 302 bocor meneteskan air)..." class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-red-100 transition-all"></textarea>
                        </div>
                        <div class="space-y-2 md:col-span-2 mt-2">
                            <label class="text-sm font-semibold text-gray-800 mb-2 block">Unggah Foto Bukti Kerusakan <span class="text-red-500">*</span></label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-10 flex flex-col items-center justify-center bg-gray-50 hover:bg-red-50 hover:border-primary hover:text-primary cursor-pointer transition-all group" onclick="document.getElementById('file-upload').click()">
                                <i class='bx bx-cloud-upload text-5xl text-gray-400 group-hover:text-primary transition-colors mb-3'></i>
                                <h4 class="text-base font-semibold text-gray-800 group-hover:text-primary transition-colors">Tarik & lepas foto di sini</h4>
                                <p class="text-sm text-gray-500 mt-1 text-center">atau klik untuk menelusuri dari perangkat Anda<br><span class="text-xs">(Maks. 5MB, format JPG/PNG)</span></p>
                                <input type="file" id="file-upload" name="foto_bukti" class="hidden" accept="image/*" required>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end gap-4">
                        <a href="index.php" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</a>
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover hover:-translate-y-0.5 transition-all shadow-md shadow-red-900/20">
                            <i class='bx bx-send text-lg'></i> Kirim Laporan
                        </button>
                    </div>
                </form>
            </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>
