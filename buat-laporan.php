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

            // Log Aktivitas
            $stmtLog = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, target_tabel, target_id, deskripsi_log) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([$_SESSION['user_id'], 'CREATE_REPORT', 'maintenance_reports', $report_id, "Membuat laporan baru tiket $nomor_tiket."]);

            $success = "Laporan Kerusakan berhasil dikirim dan masuk ke antrean DMS! ID Laporan: $nomor_tiket";
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pelaporan - NusaDMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: #8B2323;
            --color-primary-hover: #6E1C1C;
            --color-accent: #D4AF37;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
        <div class="h-18 flex items-center px-6 border-b border-gray-200 gap-3 shrink-0">
            <i class='bx bxs-buildings text-primary text-3xl'></i>
            <h2 class="text-xl font-bold text-primary tracking-tight">NusaDMS</h2>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-grid-alt text-2xl'></i> Dasbor
            </a>
            <a href="buat-laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors bg-red-50 text-primary border-l-4 border-primary">
                <i class='bx bx-wrench text-2xl text-accent'></i> Pelaporan
            </a>
            <a href="inspeksi-kamar.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent"><i class='bx bx-check-shield text-2xl'></i> Inspeksi Kamar</a>
            <a href="maintenance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-calendar-event text-2xl'></i> Maintenance
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-history text-2xl'></i> Riwayat & Laporan
            </a>
            <a href="pengaturan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-user-circle text-2xl'></i> Pengaturan User
            </a>
        </nav>
    </aside>

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="h-18 bg-white border-b border-gray-200 flex items-center justify-between px-8 shrink-0">
            <div class="flex items-center bg-gray-50 rounded-full px-5 py-2.5 w-80 border border-transparent focus-within:border-primary focus-within:ring-2 focus-within:ring-red-100 transition-all">
                <i class='bx bx-search text-gray-400 text-xl mr-3'></i>
                <input type="text" placeholder="Cari ID laporan, lokasi..." class="bg-transparent border-none outline-none w-full text-sm text-gray-700">
            </div>
            <div class="flex items-center gap-6">
                <button class="relative text-gray-500 hover:scale-110 transition-transform">
                    <i class='bx bx-bell text-2xl'></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white">3</span>
                </button>
                <div class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-1.5 rounded-lg transition-colors">
                    <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-semibold text-lg">A</div>
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                        <span class="text-xs text-gray-500 uppercase"><?= htmlspecialchars($_SESSION['role']) ?></span>
                    </div>
                    <i class='bx bx-chevron-down text-gray-400 text-xl'></i>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 p-8 overflow-y-auto">
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
        </main>
    </div>
</body>
</html>

