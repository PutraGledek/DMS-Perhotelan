<?php
require_once 'config.php';
requireLogin();

$allowedRoles = ['manager_hr', 'manager_ops', 'manager_maintenance', 'gm', 'director'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    header("Location: index.php");
    exit;
}

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
    }
}

// Ambil daftar tiket aktif
$stmt = $pdo->query("
    SELECT m.*, u.nama_lengkap as pelapor, l.nama_lokasi, t.teknisi_id, ut.nama_lengkap as nama_teknisi,
           (SELECT path_penyimpanan FROM documents WHERE report_id = m.id_laporan AND tipe_file = 'foto_kerusakan' ORDER BY id_dokumen DESC LIMIT 1) as foto_kerusakan
    FROM maintenance_reports m 
    JOIN users u ON m.user_pelapor_id = u.id 
    JOIN locations l ON m.location_id = l.id 
    LEFT JOIN ticket_assignments t ON m.id_laporan = t.report_id
    LEFT JOIN users ut ON t.teknisi_id = ut.id
    WHERE m.status IN ('menunggu_approval', 'dikerjakan')
    ORDER BY m.waktu_lapor DESC
");
$tickets = $stmt->fetchAll();

// Ambil daftar teknisi
$teknisiList = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role = 'teknisi'")->fetchAll();


$page_title = "Penugasan - NusaDMS";
require_once 'layout_header.php';
?>
            <!-- PAGE CONTENT START -->
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Penugasan & SPK</h1>
                    <p class="text-sm text-gray-500 mt-1">Daftar tiket pending untuk ditugaskan, dan cetak Surat Perintah Kerja (SPK).</p>
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
                                    <div class="flex flex-col md:flex-row gap-4 mb-4 mt-2">
                                        <?php if (!empty($ticket['foto_kerusakan'])): ?>
                                            <div class="w-full md:w-48 shrink-0 relative rounded-xl overflow-hidden shadow-sm border border-gray-200">
                                                <a href="<?= htmlspecialchars($ticket['foto_kerusakan']) ?>" target="_blank" title="Klik untuk memperbesar">
                                                    <img src="<?= htmlspecialchars($ticket['foto_kerusakan']) ?>" alt="Foto Bukti" class="w-full h-32 object-cover hover:scale-105 transition-transform duration-300">
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-700 bg-gray-50 p-4 rounded-xl border border-gray-200 flex-1">
                                            "<?= htmlspecialchars($ticket['deskripsi']) ?>"
                                        </p>
                                    </div>
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
                                    <!-- Print SPK Action -->
                                    <div class="mt-6 pt-5 border-t border-gray-100 bg-white">
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm text-gray-600">
                                                Ditugaskan kepada: <span class="font-bold text-gray-800"><?= htmlspecialchars($ticket['nama_teknisi']) ?></span>
                                            </div>
                                            <a href="surat-tugas.php?id=<?= $ticket['id_laporan'] ?>" target="_blank" class="px-5 py-2.5 bg-gray-800 text-white font-bold rounded-lg hover:bg-gray-900 transition-all text-sm shadow-sm flex items-center gap-2">
                                                <i class='bx bxs-printer text-lg'></i> Cetak Surat Tugas
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>
