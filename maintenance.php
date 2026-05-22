<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// Handle aksi persetujuan / penyelesaian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $report_id = $_POST['report_id'] ?? '';

    if ($action === 'approve') {
        $teknisi_id = $_POST['teknisi_id'] ?? '';
        if (empty($teknisi_id)) {
            $error = "Pilih teknisi terlebih dahulu.";
        } else {
            $pdo->beginTransaction();
            try {
                // Update status laporan
                $stmt = $pdo->prepare("UPDATE maintenance_reports SET status = 'dikerjakan' WHERE id_laporan = ?");
                $stmt->execute([$report_id]);
                
                // Tambah penugasan
                $stmt2 = $pdo->prepare("INSERT INTO ticket_assignments (report_id, teknisi_id, ditugaskan_oleh) VALUES (?, ?, ?)");
                $stmt2->execute([$report_id, $teknisi_id, $_SESSION['user_id']]);
                
                // Log Audit
                $stmt3 = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, target_tabel, target_id, deskripsi_log) VALUES (?, ?, ?, ?, ?)");
                $stmt3->execute([$_SESSION['user_id'], 'APPROVE_REPORT', 'maintenance_reports', $report_id, "Menyetujui tiket dan menugaskan teknisi ID: $teknisi_id"]);
                
                $pdo->commit();
                $success = "Tiket disetujui dan ditugaskan!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    } elseif ($action === 'reject') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE maintenance_reports SET status = 'ditolak' WHERE id_laporan = ?");
            $stmt->execute([$report_id]);
            
            $stmt3 = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, target_tabel, target_id, deskripsi_log) VALUES (?, ?, ?, ?, ?)");
            $stmt3->execute([$_SESSION['user_id'], 'REJECT_REPORT', 'maintenance_reports', $report_id, "Menolak tiket."]);
            
            $pdo->commit();
            $success = "Tiket berhasil ditolak.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } elseif ($action === 'complete') {
        $catatan = $_POST['catatan'] ?? '';
        $pdo->beginTransaction();
        try {
            // Update laporan
            $stmt = $pdo->prepare("UPDATE maintenance_reports SET status = 'selesai', waktu_selesai = CURRENT_TIMESTAMP WHERE id_laporan = ?");
            $stmt->execute([$report_id]);
            
            // Update penugasan (catatan)
            $stmt2 = $pdo->prepare("UPDATE ticket_assignments SET waktu_selesai = CURRENT_TIMESTAMP, catatan_teknisi = ? WHERE report_id = ?");
            $stmt2->execute([$catatan, $report_id]);
            
            // Cek apakah ada upload foto
            if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['bukti_foto']['name']);
                $targetFile = $uploadDir . $fileName;
                $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                
                // Cek ekstensi file gambar
                if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($_FILES['bukti_foto']['tmp_name'], $targetFile)) {
                        $hashFile = hash_file('sha256', $targetFile);
                        // Masukkan ke tabel documents
                        $stmtDoc = $pdo->prepare("INSERT INTO documents (report_id, nama_file, tipe_file, path_penyimpanan, hash_dokumen, diunggah_oleh) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtDoc->execute([$report_id, $fileName, 'foto_penyelesaian', $targetFile, $hashFile, $_SESSION['user_id']]);
                    }
                }
            }
            
            // Log Audit
            $stmt3 = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, target_tabel, target_id, deskripsi_log) VALUES (?, ?, ?, ?, ?)");
            $stmt3->execute([$_SESSION['user_id'], 'COMPLETE_REPORT', 'maintenance_reports', $report_id, "Menyelesaikan perbaikan tiket."]);
            
            $pdo->commit();
            $success = "Tugas perbaikan berhasil diselesaikan!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Ambil daftar tiket aktif
$stmt = $pdo->query("
    SELECT m.*, u.nama_lengkap as pelapor, l.nama_lokasi 
    FROM maintenance_reports m 
    JOIN users u ON m.user_pelapor_id = u.id 
    JOIN locations l ON m.location_id = l.id 
    WHERE m.status IN ('menunggu_approval', 'dikerjakan')
    ORDER BY m.waktu_lapor DESC
");
$tickets = $stmt->fetchAll();

// Ambil daftar teknisi
$teknisiList = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role = 'teknisi'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - NusaDMS</title>
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
            <a href="inspeksi-kamar.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent"><i class='bx bx-check-shield text-2xl'></i> Inspeksi Kamar</a>
            <a href="maintenance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors bg-red-50 text-primary border-l-4 border-primary">
                <i class='bx bx-calendar-event text-2xl text-accent'></i> Maintenance
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
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Antrean Maintenance</h1>
                    <p class="text-sm text-gray-500 mt-1">Daftar tiket pending untuk persetujuan Supervisor dan tugas perbaikan Teknisi.</p>
                </div>
            </div>

            <div class="space-y-6 max-w-5xl">
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

                <?php if (count($tickets) === 0): ?>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center">
                        <i class='bx bx-check-shield text-5xl text-gray-300 mb-3'></i>
                        <h3 class="text-lg font-bold text-gray-700">Tidak ada antrean tiket</h3>
                        <p class="text-gray-500 mt-1">Semua tiket telah diselesaikan atau belum ada laporan baru.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col md:flex-row relative">
                            <div class="w-full md:w-64 bg-gray-50 p-6 flex flex-col justify-center border-b md:border-b-0 md:border-r border-gray-200 shrink-0 relative">
                                <?php if ($ticket['status'] === 'menunggu_approval'): ?>
                                    <span class="absolute top-4 left-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200 shadow-sm">
                                        <i class='bx bx-time'></i> Menunggu Approval
                                    </span>
                                <?php else: ?>
                                    <span class="absolute top-4 left-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200 shadow-sm">
                                        <i class='bx bx-loader-circle'></i> Perbaikan Berjalan
                                    </span>
                                <?php endif; ?>
                                
                                <div class="mt-8">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">ID Dokumen</h4>
                                    <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($ticket['nomor_tiket']) ?></p>
                                    <div class="mt-4">
                                        <span class="text-xs font-semibold text-gray-500">Pelapor:</span>
                                        <p class="text-sm text-gray-700 font-medium"><?= htmlspecialchars($ticket['pelapor']) ?></p>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-xs font-semibold text-gray-500">Tanggal Lapor:</span>
                                        <p class="text-sm text-gray-700 font-medium"><?= htmlspecialchars(date('d M Y H:i', strtotime($ticket['waktu_lapor']))) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between relative">
                                <div class="mt-4 md:mt-0">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($ticket['nama_lokasi']) ?> - <?= htmlspecialchars($ticket['judul_masalah']) ?></h3>
                                            <?php 
                                            $urgensiColor = $ticket['tingkat_prioritas'] == 'tinggi' ? 'bg-red-100 text-red-700' : ($ticket['tingkat_prioritas'] == 'sedang' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700');
                                            ?>
                                            <span class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded <?= $urgensiColor ?> uppercase">Urgensi: <?= htmlspecialchars($ticket['tingkat_prioritas']) ?></span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-200 mb-4">
                                        "<?= htmlspecialchars($ticket['deskripsi']) ?>"
                                    </p>
                                </div>
                                
                                <?php if ($ticket['status'] === 'menunggu_approval'): ?>
                                    <!-- Supervisor Actions -->
                                    <form action="" method="POST" class="mt-6 pt-5 border-t border-gray-100 flex flex-wrap gap-4 items-center bg-white">
                                        <input type="hidden" name="report_id" value="<?= $ticket['id_laporan'] ?>">
                                        <div class="flex-1 min-w-[200px]">
                                            <select name="teknisi_id" class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                                                <option value="">-- Pilih Teknisi Bertugas --</option>
                                                <?php foreach ($teknisiList as $tek): ?>
                                                    <option value="<?= $tek['id'] ?>"><?= htmlspecialchars($tek['nama_lengkap']) ?></option>
                                                <?php endforeach; ?>
                                                <?php if (empty($teknisiList)): ?>
                                                    <!-- Fallback jika belum ada teknisi di DB -->
                                                    <option value="<?= $_SESSION['user_id'] ?>">Tugaskan ke Saya (<?= htmlspecialchars($_SESSION['nama']) ?>)</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="submit" name="action" value="reject" class="px-5 py-2 border border-red-200 text-red-600 font-semibold rounded-lg hover:bg-red-50 transition-all text-sm">
                                                <i class='bx bx-x'></i> Tolak
                                            </button>
                                            <button type="submit" name="action" value="approve" class="px-5 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-all text-sm shadow-sm">
                                                <i class='bx bx-check-double'></i> Setujui & Tugaskan
                                            </button>
                                        </div>
                                    </form>
                                <?php elseif ($ticket['status'] === 'dikerjakan'): ?>
                                    <!-- Technician Form -->
                                    <form action="" method="POST" enctype="multipart/form-data" class="mt-2 pt-4 border-t border-gray-100">
                                        <input type="hidden" name="report_id" value="<?= $ticket['id_laporan'] ?>">
                                        <h4 class="text-sm font-bold text-gray-800 mb-3">Form Penyelesaian Tugas</h4>
                                        <div class="space-y-4">
                                            <textarea name="catatan" required rows="2" placeholder="Catatan Teknisi (Misal: Bohlam telah diganti)..." class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary transition-all"></textarea>
                                            
                                            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                                                <div class="flex-1 w-full relative">
                                                    <input type="file" name="bukti_foto" id="foto_<?= $ticket['id_laporan'] ?>" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="this.nextElementSibling.innerHTML = '<i class=\'bx bx-check-circle text-xl text-green-500\'></i> Foto Dipilih'">
                                                    <label for="foto_<?= $ticket['id_laporan'] ?>" class="w-full px-4 py-2.5 border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2 relative z-0">
                                                        <i class='bx bx-camera text-xl'></i> Opsional: Bukti Foto
                                                    </label>
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <button type="submit" name="action" value="complete" class="w-full px-5 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary-hover transition-all text-sm shadow-md shadow-red-900/20 flex items-center justify-center gap-2">
                                                        <i class='bx bx-task text-lg'></i> Selesaikan Tugas
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- PAGE CONTENT END -->
        </main>
    </div>
</body>
</html>

