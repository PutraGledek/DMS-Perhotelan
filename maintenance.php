<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// Handle aksi persetujuan / penyelesaian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $report_id = $_POST['report_id'] ?? '';

    if ($action === 'complete') {
        $catatan = $_POST['catatan'] ?? '';
        $qty_array = $_POST['qty'] ?? [];
        
        $pdo->beginTransaction();
        try {
            // Kumpulkan daftar barang yang digunakan (qty > 0)
            $itemsToUse = [];
            foreach ($qty_array as $id => $qty) {
                $qty = (int)$qty;
                if ($qty > 0) {
                    $itemsToUse[$id] = $qty;
                }
            }

            // Cek stok untuk semua barang sebelum memproses
            if (!empty($itemsToUse)) {
                $checkStmt = $pdo->prepare("SELECT nama_barang, stok_tersedia FROM inventory_items WHERE id = ?");
                foreach ($itemsToUse as $id => $qty) {
                    $checkStmt->execute([$id]);
                    $itemData = $checkStmt->fetch();
                    if (!$itemData) {
                        throw new Exception("Barang tidak ditemukan.");
                    }
                    if ($itemData['stok_tersedia'] < $qty) {
                        throw new Exception("Stok barang '{$itemData['nama_barang']}' tidak mencukupi! Diminta: {$qty}, Sisa: {$itemData['stok_tersedia']}");
                    }
                }
                
                // Proses pengurangan stok dan pencatatan
                $updateStokStmt = $pdo->prepare("UPDATE inventory_items SET stok_tersedia = stok_tersedia - ? WHERE id = ?");
                $insertUsageStmt = $pdo->prepare("INSERT INTO inventory_usage (report_id, item_id, jumlah_digunakan) VALUES (?, ?, ?)");
                
                foreach ($itemsToUse as $id => $qty) {
                    $updateStokStmt->execute([$qty, $id]);
                    $insertUsageStmt->execute([$report_id, $id, $qty]);
                }
            }
        
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

// Ambil daftar tiket aktif (hanya yang sedang dikerjakan)
$whereClause = "m.status = 'dikerjakan'";
$params = [];

if ($_SESSION['role'] === 'teknisi') {
    // Teknisi hanya melihat tiket yang ditugaskan ke mereka
    $whereClause .= " AND t.teknisi_id = ?";
    $params[] = $_SESSION['user_id'];
}

$stmt = $pdo->prepare("
    SELECT m.*, u.nama_lengkap as pelapor, l.nama_lokasi 
    FROM maintenance_reports m 
    JOIN users u ON m.user_pelapor_id = u.id 
    JOIN locations l ON m.location_id = l.id 
    LEFT JOIN ticket_assignments t ON m.id_laporan = t.report_id
    WHERE $whereClause
    ORDER BY m.waktu_lapor DESC
");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Ambil daftar teknisi
$teknisiList = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role = 'teknisi'")->fetchAll();

// Ambil daftar barang untuk dropdown teknisi
$inventoryItems = $pdo->query("SELECT id, nama_barang, stok_tersedia, satuan FROM inventory_items ORDER BY nama_barang ASC")->fetchAll();
$page_title = "Maintenance - NusaDMS";
require_once 'layout_header.php';
?>
            <!-- PAGE CONTENT START -->
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Tugas Maintenance Saya</h1>
                    <p class="text-sm text-gray-500 mt-1">Daftar tugas perbaikan yang sedang dikerjakan. Selesaikan tugas dan catat penggunaan barang di sini.</p>
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
                                
                                    <!-- Technician Form -->
                                    <form action="" method="POST" enctype="multipart/form-data" class="mt-2 pt-4 border-t border-gray-100">
                                        <input type="hidden" name="report_id" value="<?= $ticket['id_laporan'] ?>">
                                        <h4 class="text-sm font-bold text-gray-800 mb-3">Form Penyelesaian Tugas</h4>
                                        <div class="space-y-4">
                                            <textarea name="catatan" required rows="2" placeholder="Catatan Teknisi (Misal: Bohlam telah diganti)..." class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary transition-all"></textarea>
                                            
                                            <!-- Pilihan Barang / Stok (Multiple) -->
                                            <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100">
                                                <label class="text-xs font-semibold text-gray-600 block mb-3 border-b border-blue-200 pb-2">Barang Digunakan (Isi jumlah pada barang yang dipakai)</label>
                                                <div class="max-h-48 overflow-y-auto pr-2 space-y-2">
                                                    <?php foreach ($inventoryItems as $inv): ?>
                                                        <div class="flex items-center justify-between bg-white p-2 border border-gray-200 rounded shadow-sm">
                                                            <div class="flex-1">
                                                                <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($inv['nama_barang']) ?></div>
                                                                <div class="text-xs text-gray-500">Sisa stok: <span class="font-bold <?= $inv['stok_tersedia'] > 0 ? 'text-green-600' : 'text-red-500' ?>"><?= $inv['stok_tersedia'] ?></span> <?= htmlspecialchars($inv['satuan']) ?></div>
                                                            </div>
                                                            <div class="w-20 shrink-0 ml-3">
                                                                <input type="number" name="qty[<?= $inv['id'] ?>]" min="0" max="<?= $inv['stok_tersedia'] ?>" placeholder="Qty" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:border-primary focus:outline-none <?= $inv['stok_tersedia'] == 0 ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' ?>" <?= $inv['stok_tersedia'] == 0 ? 'disabled' : '' ?>>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>
