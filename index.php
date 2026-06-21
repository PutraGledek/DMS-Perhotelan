<?php
require_once 'config.php';
requireLogin();

// Hitung Statistik
$stmtAktif = $pdo->query("SELECT COUNT(*) FROM maintenance_reports WHERE status IN ('menunggu_approval', 'disetujui', 'dikerjakan')");
$laporanAktif = $stmtAktif->fetchColumn();

$stmtMenunggu = $pdo->query("SELECT COUNT(*) FROM maintenance_reports WHERE status = 'menunggu_approval'");
$menungguApproval = $stmtMenunggu->fetchColumn();

$stmtSelesai = $pdo->query("SELECT COUNT(*) FROM maintenance_reports WHERE status = 'selesai' AND DATE(waktu_selesai) = DATE('now')");
$selesaiHariIni = $stmtSelesai->fetchColumn();

// Ambil Feed Aktivitas (5 terbaru)
$stmtAktivitas = $pdo->query("
    SELECT a.*, u.nama_lengkap, u.role 
    FROM audit_logs a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.waktu_kejadian DESC 
    LIMIT 5
");
$aktivitas = $stmtAktivitas->fetchAll();

$page_title = "Dasbor - GrandVault Nusantara";
require_once 'layout_header.php';
?>
            <!-- PAGE CONTENT START -->
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Ringkasan Sistem</h1>
                    <p class="text-sm text-gray-500 mt-1">Pantau status laporan dan operasional maintenance hari ini.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <?php if (in_array($_SESSION['role'], ['manager_hr', 'manager_ops', 'manager_maintenance', 'gm', 'director'])): ?>
                    <a href="penugasan.php"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 hover:text-primary hover:border-primary transition-all shadow-sm">
                        <i class='bx bx-clipboard text-lg'></i> Penugasan SPK
                    </a>
                    <?php endif; ?>
                    <a href="buat-laporan.php"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover hover:-translate-y-0.5 transition-all shadow-md shadow-red-900/20">
                        <i class='bx bx-plus text-lg'></i> Laporan Baru
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col items-center justify-center text-center gap-3 hover:-translate-y-1 hover:shadow-md transition-all">
                    <div
                        class="w-14 h-14 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-3xl">
                        <i class='bx bx-file'></i>
                    </div>
                    <div>
                        <h3 class="text-3xl font-bold text-gray-800"><?= $laporanAktif ?></h3>
                        <p class="text-sm text-gray-500 font-medium mt-1">Laporan Aktif</p>
                    </div>
                </div>
                <div
                    class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col items-center justify-center text-center gap-3 hover:-translate-y-1 hover:shadow-md transition-all">
                    <div
                        class="w-14 h-14 rounded-xl bg-yellow-100 text-yellow-600 flex items-center justify-center text-3xl">
                        <i class='bx bx-time-five'></i>
                    </div>
                    <div>
                        <h3 class="text-3xl font-bold text-gray-800"><?= $menungguApproval ?></h3>
                        <p class="text-sm text-gray-500 font-medium mt-1">Menunggu Approval</p>
                    </div>
                </div>
                <div
                    class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col items-center justify-center text-center gap-3 hover:-translate-y-1 hover:shadow-md transition-all">
                    <div
                        class="w-14 h-14 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-3xl">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <div>
                        <h3 class="text-3xl font-bold text-gray-800"><?= $selesaiHariIni ?></h3>
                        <p class="text-sm text-gray-500 font-medium mt-1">Selesai Hari Ini</p>
                    </div>
                </div>
            </div>

            <!-- Live Activity Feed -->
            <div class="mb-4">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class='bx bx-pulse text-primary text-xl'></i> Live Activity Feed
                </h3>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="space-y-6">
                    <?php if (count($aktivitas) > 0): ?>
                        <?php foreach ($aktivitas as $log): ?>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                <i class='bx bx-pulse text-xl'></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800"><span class="font-bold"><?= htmlspecialchars($log['nama_lengkap']) ?> (<?= htmlspecialchars($log['role']) ?>)</span>: <?= htmlspecialchars($log['deskripsi_log']) ?></p>
                                <span class="text-xs text-gray-500 font-medium mt-1 inline-block"><?= htmlspecialchars($log['waktu_kejadian']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">Belum ada aktivitas terekam.</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>