<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// Ambil daftar kamar
$kamarList = $pdo->query("SELECT id, nama_lokasi FROM locations WHERE kategori_lokasi = 'Kamar' ORDER BY nama_lokasi ASC")->fetchAll();
// Fallback jika belum ada kamar spesifik
if (empty($kamarList)) {
    $kamarList = $pdo->query("SELECT id, nama_lokasi FROM locations ORDER BY nama_lokasi ASC")->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = $_POST['location_id'] ?? '';
    
    // Status kondisi: 'baik' jika semua radio button 'berfungsi', jika ada 'rusak' maka 'butuh_perbaikan'
    $ac = $_POST['ac'] ?? 'rusak';
    $listrik = $_POST['listrik'] ?? 'rusak';
    $air = $_POST['air'] ?? 'rusak';
    $kunci = $_POST['kunci'] ?? 'rusak';
    $tv = $_POST['tv'] ?? 'rusak';

    $kondisi_keseluruhan = ($ac == 'berfungsi' && $listrik == 'berfungsi' && $air == 'berfungsi' && $kunci == 'berfungsi' && $tv == 'berfungsi') ? 'baik' : 'butuh_perbaikan';
    
    // Asumsi durasi inspeksi didapatkan dari client, untuk contoh kita set 15 menit statis.
    $durasi_menit = 15;

    if (empty($location_id)) {
        $error = "Pilih kamar yang diinspeksi.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO room_inspections (location_id, user_inspektur_id, hasil_inspeksi, catatan) VALUES (?, ?, ?, ?)");
            // Catatan disatukan dari status
            $catatan = "AC: $ac, Listrik: $listrik, Air: $air, Kunci: $kunci, TV: $tv | Durasi: $durasi_menit menit";
            $stmt->execute([$location_id, $_SESSION['user_id'], $kondisi_keseluruhan, $catatan]);
            $inspeksi_id = $pdo->lastInsertId();
            
            // Log Audit
            $stmtLog = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, target_tabel, target_id, deskripsi_log) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([$_SESSION['user_id'], 'CREATE_INSPECTION', 'room_inspections', $inspeksi_id, "Menyelesaikan inspeksi kamar (Hasil: $kondisi_keseluruhan)."]);
            
            // AUTO-CREATE TIKET MAINTENANCE JIKA ADA YANG RUSAK
            if ($kondisi_keseluruhan === 'butuh_perbaikan') {
                $item_rusak = [];
                if ($ac == 'rusak') $item_rusak[] = "Sistem Pendingin (AC)";
                if ($listrik == 'rusak') $item_rusak[] = "Pencahayaan & Kelistrikan";
                if ($air == 'rusak') $item_rusak[] = "Saluran Air & Sanitasi";
                if ($kunci == 'rusak') $item_rusak[] = "Kunci Pintu";
                if ($tv == 'rusak') $item_rusak[] = "Televisi & Hiburan";
                
                $deskripsi_rusak = "Ditemukan kerusakan pada saat inspeksi rutin: " . implode(", ", $item_rusak) . ". Harap segera diperiksa oleh teknisi.";
                $nomor_tiket = 'RPT-INSP-' . date('Ym') . '-' . rand(1000, 9999);
                
                $stmtMaint = $pdo->prepare("INSERT INTO maintenance_reports (nomor_tiket, user_pelapor_id, location_id, judul_masalah, deskripsi, tingkat_prioritas) VALUES (?, ?, ?, ?, ?, ?)");
                // Prioritas otomatis disetel 'tinggi' karena memblokir penjualan kamar
                $stmtMaint->execute([$nomor_tiket, $_SESSION['user_id'], $location_id, "Perbaikan Hasil Inspeksi Kamar", $deskripsi_rusak, 'tinggi']);
                
                $success = "Inspeksi disimpan! Karena ada aset rusak, Tiket Maintenance ($nomor_tiket) OTOMATIS dibuat dan dikirim ke Supervisor.";
            } else {
                $success = "Sertifikat Berhasil Diterbitkan! Kamar dalam kondisi sangat baik dan siap dijual kembali.";
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
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
    <title>Inspeksi Kamar - NusaDMS</title>
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
            <a href="buat-laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-wrench text-2xl'></i> Pelaporan
            </a>
            <a href="inspeksi-kamar.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors bg-red-50 text-primary border-l-4 border-primary">
                <i class='bx bx-check-shield text-2xl text-accent'></i> Inspeksi Kamar
            </a>
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
                <input type="text" placeholder="Cari dokumen DMS..." class="bg-transparent border-none outline-none w-full text-sm text-gray-700">
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
            <div class="flex justify-between items-end mb-8 relative">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Inspeksi & Sertifikasi Kesiapan Kamar</h1>
                    <p class="text-sm text-gray-500 mt-1">Formulir pengecekan rutin aset dan fasilitas kamar paska check-out.</p>
                </div>
                <!-- SLA Badge -->
                <div class="flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-700 border border-green-200 shadow-sm">
                    <i class='bx bx-stopwatch text-xl'></i> Durasi Inspeksi: 12m 45s (Target 15m)
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <?php if ($error): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg mb-6">
                            <h4 class="text-sm font-bold text-red-800">Gagal</h4>
                            <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg mb-6">
                            <h4 class="text-sm font-bold text-green-800">Berhasil!</h4>
                            <p class="text-xs text-green-600 mt-1"><?= htmlspecialchars($success) ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="inspection-form" enctype="multipart/form-data">
                    <!-- FORM SECTION -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class='bx bx-door-open text-primary'></i> Informasi Kamar & Pemeriksa
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 block mb-1">No. Kamar</label>
                                <select name="location_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary focus:outline-none">
                                    <option value="">-- Pilih Kamar --</option>
                                    <?php foreach ($kamarList as $kamar): ?>
                                        <option value="<?= $kamar['id'] ?>"><?= htmlspecialchars($kamar['nama_lokasi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 block mb-1">Nama Pemeriksa</label>
                                <input type="text" value="<?= htmlspecialchars($_SESSION['nama']) ?>" disabled class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 block mb-1">Waktu Inspeksi</label>
                                <input type="text" id="waktu-inspeksi" disabled class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500 cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    <!-- ASSET CHECKLIST -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class='bx bx-list-check text-primary'></i> Checklist Aset & Fasilitas
                        </h3>
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-50 pb-3 gap-3">
                                <span class="text-sm font-semibold text-gray-700">Sistem Pendingin (AC)</span>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="ac" value="berfungsi" checked class="accent-primary w-4 h-4"> Berfungsi</label>
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="ac" value="rusak" class="accent-red-600 w-4 h-4"> Rusak</label>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-50 pb-3 gap-3">
                                <span class="text-sm font-semibold text-gray-700">Pencahayaan & Kelistrikan</span>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="listrik" value="berfungsi" checked class="accent-primary w-4 h-4"> Berfungsi</label>
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="listrik" value="rusak" class="accent-red-600 w-4 h-4"> Rusak</label>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-50 pb-3 gap-3">
                                <span class="text-sm font-semibold text-gray-700">Saluran Air & Sanitasi</span>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="air" value="berfungsi" checked class="accent-primary w-4 h-4"> Berfungsi</label>
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="air" value="rusak" class="accent-red-600 w-4 h-4"> Rusak</label>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-50 pb-3 gap-3">
                                <span class="text-sm font-semibold text-gray-700">Kunci Pintu (Electronic Lock)</span>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="kunci" value="berfungsi" checked class="accent-primary w-4 h-4"> Berfungsi</label>
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="kunci" value="rusak" class="accent-red-600 w-4 h-4"> Rusak</label>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-50 pb-3 gap-3">
                                <span class="text-sm font-semibold text-gray-700">Televisi & Hiburan</span>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="tv" value="berfungsi" checked class="accent-primary w-4 h-4"> Berfungsi</label>
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="radio" name="tv" value="rusak" class="accent-red-600 w-4 h-4"> Rusak</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MINIBAR -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex flex-wrap items-center justify-between gap-3">
                            <span class="flex items-center gap-2"><i class='bx bx-drink text-primary'></i> Minibar & Consumption Tracking</span>
                            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-2 py-1.5 rounded border border-indigo-200">Integrasi Front Office</span>
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="p-3 text-xs font-bold text-gray-500 uppercase">Nama Barang</th>
                                        <th class="p-3 text-xs font-bold text-gray-500 uppercase text-center">Stok Awal</th>
                                        <th class="p-3 text-xs font-bold text-gray-500 uppercase text-center">Sisa</th>
                                        <th class="p-3 text-xs font-bold text-gray-500 uppercase text-center">Jml Dikonsumsi</th>
                                        <th class="p-3 text-xs font-bold text-gray-500 uppercase">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                        <td class="p-3 text-sm text-gray-800 font-medium">Air Mineral 600ml</td>
                                        <td class="p-3 text-sm text-gray-600 text-center">2</td>
                                        <td class="p-3 text-center"><input type="number" value="1" min="0" class="w-16 px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:border-primary focus:outline-none"></td>
                                        <td class="p-3 text-center"><input type="number" value="1" min="0" class="w-16 px-2 py-1.5 border-2 text-red-600 border-red-300 bg-red-50 rounded text-sm text-center font-bold"></td>
                                        <td class="p-3 text-sm text-gray-600">Botol</td>
                                    </tr>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                        <td class="p-3 text-sm text-gray-800 font-medium">Kacang Almond</td>
                                        <td class="p-3 text-sm text-gray-600 text-center">1</td>
                                        <td class="p-3 text-center"><input type="number" value="1" min="0" class="w-16 px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:border-primary focus:outline-none"></td>
                                        <td class="p-3 text-center"><input type="number" value="0" min="0" class="w-16 px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:border-primary focus:outline-none"></td>
                                        <td class="p-3 text-sm text-gray-600">Bks</td>
                                    </tr>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                        <td class="p-3 text-sm text-gray-800 font-medium">Soft Drink 330ml</td>
                                        <td class="p-3 text-sm text-gray-600 text-center">2</td>
                                        <td class="p-3 text-center"><input type="number" value="0" min="0" class="w-16 px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:border-primary focus:outline-none"></td>
                                        <td class="p-3 text-center"><input type="number" value="2" min="0" class="w-16 px-2 py-1.5 border-2 text-red-600 border-red-300 bg-red-50 rounded text-sm text-center font-bold"></td>
                                        <td class="p-3 text-sm text-gray-600">Kaleng</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg flex items-start gap-2">
                            <i class='bx bx-info-circle text-primary mt-0.5'></i> 
                            <p class="text-xs text-gray-600 font-medium leading-relaxed">Data konsumsi akan diteruskan ke Front Office untuk billing tagihan tamu secara otomatis via DMS.</p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="space-y-6">
                    <!-- DMS CAPTURE -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-wider border-b border-gray-100 pb-3"><i class='bx bx-camera text-primary'></i> DMS Document Capture</h3>
                        <div class="space-y-5">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 block mb-2">Upload Foto "Room Ready" (QA) <span class="text-red-500">*</span></label>
                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-5 flex flex-col items-center justify-center bg-gray-50 hover:bg-red-50 hover:border-primary hover:text-primary cursor-pointer transition-all group" onclick="document.getElementById('file-qa').click()">
                                    <i class='bx bx-image-add text-3xl text-gray-400 group-hover:text-primary mb-2 transition-colors'></i>
                                    <span class="text-sm font-semibold text-gray-600 group-hover:text-primary transition-colors">Ambil/Pilih Foto</span>
                                    <input type="file" id="file-qa" name="foto_qa" class="hidden" accept="image/*">
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 block mb-2">Upload Foto "Minibar" (Bukti) <span class="text-red-500">*</span></label>
                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-5 flex flex-col items-center justify-center bg-gray-50 hover:bg-red-50 hover:border-primary hover:text-primary cursor-pointer transition-all group" onclick="document.getElementById('file-minibar').click()">
                                    <i class='bx bx-image-add text-3xl text-gray-400 group-hover:text-primary mb-2 transition-colors'></i>
                                    <span class="text-sm font-semibold text-gray-600 group-hover:text-primary transition-colors">Ambil/Pilih Foto</span>
                                    <input type="file" id="file-minibar" name="foto_minibar" class="hidden" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- APPROVAL & SIGNATURE -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-wider border-b border-gray-100 pb-3"><i class='bx bx-check-shield text-primary'></i> Sertifikasi</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 block mb-2">Tanda Tangan Inspektur (Pemeriksa) <span class="text-red-500">*</span></label>
                                <div id="sig-pad" class="h-32 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 flex items-center justify-center cursor-pointer hover:border-primary hover:bg-red-50 transition-all relative group" onclick="signDoc()">
                                    <span class="text-sm font-medium text-gray-500 group-hover:text-primary transition-colors flex items-center gap-1.5"><i class='bx bx-edit-alt text-xl'></i> Klik untuk Tanda Tangan</span>
                                </div>
                            </div>
                            <button type="button" onclick="submitForm()" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3.5 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 hover:-translate-y-0.5 transition-all shadow-md shadow-green-900/20 mt-2">
                                <i class='bx bx-check-double text-xl'></i> Terbitkan Ready-to-Sell & Arsipkan
                            </button>
                        </div>
                    </div>

                    </form>

                    <!-- AUDIT TRAIL LOG -->
                    <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-sm p-6 text-gray-100">
                        <h3 class="text-xs font-bold text-white border-b border-gray-600 pb-3 mb-4 uppercase flex justify-between items-center">
                            <span class="flex items-center gap-2"><i class='bx bx-pulse text-accent text-lg'></i> Log Audit Otomatis</span>
                            <span class="text-[9px] text-gray-400 bg-gray-900 px-1.5 py-0.5 rounded border border-gray-600">DMS-PRO-LOG</span>
                        </h3>
                        <div class="space-y-3 font-mono text-[11px] tracking-wide">
                            <div class="flex gap-2">
                                <span class="text-gray-400">[10:00:12]</span>
                                <span class="text-gray-200">Inspeksi dimulai.</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="text-gray-400">[10:04:45]</span>
                                <span class="text-gray-200">Pengecekan AC & Kelistrikan selesai.</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="text-gray-400">[10:08:20]</span>
                                <span class="text-gray-200">Validasi sistem Sanitasi Air selesai.</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="text-gray-400">[10:12:05]</span>
                                <span class="text-gray-200">Data Minibar di-update. Sinkronisasi FO tertunda.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- PAGE CONTENT END -->
        </main>
    </div>

    <script>
        // Set Waktu
        const now = new Date();
        document.getElementById('waktu-inspeksi').value = now.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}) + ' ' + now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});

        let signed = false;
        function signDoc() {
            const pad = document.getElementById('sig-pad');
            pad.innerHTML = `
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-white rounded-xl">
                    <span class="text-4xl text-primary transform -rotate-6 font-serif italic" style="font-family: 'Brush Script MT', 'Dancing Script', cursive;">
                        <?= htmlspecialchars($_SESSION['nama']) ?>
                    </span>
                    <span class="absolute bottom-2 text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded border border-green-200 flex items-center gap-1">
                        <i class='bx bx-check-shield text-xs'></i> Terverifikasi
                    </span>
                </div>
            `;
            pad.classList.remove('border-dashed', 'bg-gray-50');
            pad.classList.add('border-solid', 'border-green-500', 'ring-2', 'ring-green-100');
            signed = true;
        }

        function submitForm() {
            if(!signed) { 
                alert('PERINGATAN: Harap bubuhkan Tanda Tangan Digital sebelum menerbitkan sertifikat!'); 
                return; 
            }
            document.getElementById('inspection-form').submit();
        }
    </script>
</body>
</html>
