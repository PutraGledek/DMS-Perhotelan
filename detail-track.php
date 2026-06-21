<?php
require_once 'config.php';
requireLogin();

$id_laporan = $_GET['id'] ?? null;
if (!$id_laporan) {
    header("Location: riwayat.php");
    exit;
}

// 1. Ambil Data Tiket Utama
$stmt = $pdo->prepare("
    SELECT m.*, l.nama_lokasi, l.kategori_lokasi, u.nama_lengkap as pelapor
    FROM maintenance_reports m
    JOIN locations l ON m.location_id = l.id
    JOIN users u ON m.user_pelapor_id = u.id
    WHERE m.id_laporan = ?
");
$stmt->execute([$id_laporan]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Dokumen tidak ditemukan.");
}

// 2. Ambil Penugasan & Teknisi
$stmtTech = $pdo->prepare("
    SELECT t.*, u.nama_lengkap as nama_teknisi 
    FROM ticket_assignments t 
    LEFT JOIN users u ON t.teknisi_id = u.id 
    WHERE t.report_id = ?
");
$stmtTech->execute([$id_laporan]);
$assignment = $stmtTech->fetch();

// 3. Ambil Material/Barang Digunakan
$stmtInv = $pdo->prepare("
    SELECT iu.*, ii.nama_barang, ii.satuan, ii.kode_barang 
    FROM inventory_usage iu 
    JOIN inventory_items ii ON iu.item_id = ii.id 
    WHERE iu.report_id = ?
");
$stmtInv->execute([$id_laporan]);
$inventoryUsage = $stmtInv->fetchAll();

// 4. Ambil Foto Lampiran
$stmtDoc = $pdo->prepare("SELECT * FROM documents WHERE report_id = ? ORDER BY waktu_unggah ASC");
$stmtDoc->execute([$id_laporan]);
$documents = $stmtDoc->fetchAll();
$fotoPelapor = null;
$fotoTeknisi = null;
foreach ($documents as $doc) {
    if ($doc['tipe_file'] == 'foto_masalah' && !$fotoPelapor) $fotoPelapor = $doc;
    if ($doc['tipe_file'] == 'foto_penyelesaian' && !$fotoTeknisi) $fotoTeknisi = $doc;
}

// 5. Ambil Timeline (Audit Logs)
$stmtLog = $pdo->prepare("
    SELECT a.*, u.nama_lengkap, u.role 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.target_id = ? AND a.target_tabel = 'maintenance_reports'
    ORDER BY a.waktu_kejadian ASC
");
$stmtLog->execute([$id_laporan]);
$auditLogs = $stmtLog->fetchAll();

// Helper CSS
$badgeClass = 'bg-gray-100 text-gray-700 border-gray-200';
$iconClass = 'bx-info-circle';
if ($ticket['status'] === 'menunggu_approval') {
    $badgeClass = 'bg-yellow-100 text-yellow-700 border-yellow-200';
    $iconClass = 'bx-time';
} elseif ($ticket['status'] === 'dikerjakan') {
    $badgeClass = 'bg-blue-100 text-blue-700 border-blue-200';
    $iconClass = 'bx-loader-circle';
} elseif ($ticket['status'] === 'selesai') {
    $badgeClass = 'bg-green-100 text-green-700 border-green-200';
    $iconClass = 'bx-check-shield';
} elseif ($ticket['status'] === 'ditolak') {
    $badgeClass = 'bg-red-100 text-red-700 border-red-200';
    $iconClass = 'bx-x-circle';
}

$urgensiColor = 'bg-gray-100 text-gray-700';
if ($ticket['tingkat_prioritas'] == 'tinggi') $urgensiColor = 'bg-red-100 text-red-700';
elseif ($ticket['tingkat_prioritas'] == 'sedang') $urgensiColor = 'bg-orange-100 text-orange-700';
$page_title = "Detail Dokumen " . htmlspecialchars($ticket['nomor_tiket']) . " - NusaDMS";
require_once 'layout_header.php';
?>
    <style>
        @media print {
            aside { display: none !important; }
            header { display: none !important; }
            .no-print { display: none !important; }
            main { padding: 0 !important; overflow: visible !important; }
            body { display: block !important; overflow: auto !important; background: white !important; }
            .print-border { border: 1px solid #e5e7eb !important; }
            .print-break-inside-avoid { break-inside: avoid; }
            /* Memaksa elemen cetak tidak terpotong sembarangan */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
            <!-- PAGE CONTENT START -->
            <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <a href="riwayat.php" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors bg-white shadow-sm no-print">
                        <i class='bx bx-arrow-back text-xl text-gray-600'></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Dokumen Laporan <?= htmlspecialchars($ticket['nomor_tiket']) ?></h1>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $badgeClass ?>">
                                <i class='bx <?= $iconClass ?>'></i> <?= strtoupper(str_replace('_', ' ', $ticket['status'])) ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">NusaDMS - Detail dan Jejak Rekam Tiket Maintenance.</p>
                    </div>
                </div>
                <button onclick="window.print()" class="no-print inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-hover shadow-md transition-colors">
                    <i class='bx bxs-printer text-xl'></i> Ekspor PDF
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 print-border">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class='bx bx-info-circle text-primary'></i> Informasi Laporan Awal
                        </h3>
                        <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pelapor</span>
                                <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($ticket['pelapor']) ?></span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Lokasi Kejadian</span>
                                <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($ticket['nama_lokasi']) ?></span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Kategori</span>
                                <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($ticket['kategori_lokasi']) ?></span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Urgensi</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold <?= $urgensiColor ?> uppercase"><?= htmlspecialchars($ticket['tingkat_prioritas']) ?></span>
                            </div>
                            <div class="col-span-2 mt-2">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Judul & Deskripsi Lengkap</span>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="font-bold text-gray-800 mb-2"><?= htmlspecialchars($ticket['judul_masalah']) ?></h4>
                                    <p class="text-sm text-gray-700 leading-relaxed">"<?= htmlspecialchars($ticket['deskripsi']) ?>"</p>
                                </div>
                            </div>
                            <?php if ($fotoPelapor): ?>
                            <div class="col-span-2 mt-2 print-break-inside-avoid">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2"><i class='bx bx-camera'></i> Foto Bukti Laporan</span>
                                <img src="<?= htmlspecialchars($fotoPelapor['path_penyimpanan']) ?>" class="w-full max-h-80 object-cover rounded-lg border border-gray-200 shadow-sm" alt="Foto Pelapor">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (in_array($ticket['status'], ['dikerjakan', 'selesai']) && $assignment): ?>
                    <!-- Penyelesaian dan Material -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 print-border print-break-inside-avoid">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class='bx bx-check-double text-green-600'></i> Hasil Penanganan & Penugasan
                        </h3>
                        <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Teknisi Bertugas</span>
                                <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($assignment['nama_teknisi']) ?></span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Waktu Selesai</span>
                                <span class="text-sm font-bold text-gray-800"><?= $assignment['waktu_selesai'] ? date('d M Y, H:i', strtotime($assignment['waktu_selesai'])) : 'Dalam pengerjaan...' ?></span>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Catatan Teknisi</span>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm text-gray-700">
                                    <?= $assignment['catatan_teknisi'] ? nl2br(htmlspecialchars($assignment['catatan_teknisi'])) : '<em>Belum ada catatan penyelesaian</em>' ?>
                                </div>
                            </div>

                            <!-- Inventory Usage -->
                            <div class="col-span-2 mt-2 bg-blue-50/50 p-4 rounded-lg border border-blue-100 print-break-inside-avoid">
                                <span class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3 flex items-center gap-2"><i class='bx bx-box text-blue-600'></i> Material/Barang Digunakan</span>
                                <?php if (count($inventoryUsage) > 0): ?>
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        <?php foreach ($inventoryUsage as $iu): ?>
                                            <li class="flex justify-between items-center bg-white p-2.5 rounded border border-gray-200 shadow-sm">
                                                <div class="font-medium"><?= htmlspecialchars($iu['nama_barang']) ?> <span class="text-xs text-gray-400 ml-2 font-normal">(Kode: <?= htmlspecialchars($iu['kode_barang']) ?>)</span></div>
                                                <div class="font-bold text-gray-800"><?= $iu['jumlah_digunakan'] ?> <span class="text-xs font-normal text-gray-500"><?= htmlspecialchars($iu['satuan']) ?></span></div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500 italic">Tidak ada material tambahan yang dicatat.</p>
                                <?php endif; ?>
                            </div>

                            <?php if ($fotoTeknisi): ?>
                            <div class="col-span-2 mt-4 print-break-inside-avoid">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2"><i class='bx bx-check-shield text-green-600'></i> Foto Penyelesaian Pekerjaan</span>
                                <img src="<?= htmlspecialchars($fotoTeknisi['path_penyimpanan']) ?>" class="w-full max-h-80 object-cover rounded-lg border border-gray-200 shadow-sm" alt="Foto Teknisi">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column: Audit Trail & Actions -->
                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 print-border print-break-inside-avoid">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class='bx bx-history text-primary'></i> Rekam Jejak (Timeline)
                        </h3>
                        <div class="relative pl-7 border-l-2 border-gray-200 space-y-7 ml-2">
                            <?php foreach ($auditLogs as $idx => $log): ?>
                            <?php 
                                // Logic warna marker timeline berdasarkan action
                                $markerColor = 'bg-gray-400';
                                $boxColor = 'bg-gray-50 border-gray-200';
                                if (str_contains($log['aksi'], 'CREATE')) { $markerColor = 'bg-blue-400'; $boxColor = 'bg-blue-50 border-blue-200'; }
                                elseif (str_contains($log['aksi'], 'APPROVE')) { $markerColor = 'bg-yellow-400'; $boxColor = 'bg-yellow-50 border-yellow-200'; }
                                elseif (str_contains($log['aksi'], 'COMPLETE')) { $markerColor = 'bg-green-500'; $boxColor = 'bg-green-50 border-green-200'; }
                                elseif (str_contains($log['aksi'], 'REJECT')) { $markerColor = 'bg-red-500'; $boxColor = 'bg-red-50 border-red-200'; }
                            ?>
                            <div class="relative">
                                <div class="absolute -left-[35px] top-1 w-4 h-4 rounded-full <?= $markerColor ?> border-[3px] border-white shadow-sm ring-1 ring-gray-200"></div>
                                <div class="text-xs font-bold text-gray-500 mb-1.5"><?= date('d M Y, H:i:s', strtotime($log['waktu_kejadian'])) ?></div>
                                <div class="<?= $boxColor ?> p-3.5 rounded-lg border text-sm shadow-sm">
                                    <strong class="text-gray-800 block mb-0.5"><?= htmlspecialchars($log['aksi']) ?></strong>
                                    <p class="text-gray-600 text-xs mb-1.5"><?= htmlspecialchars($log['deskripsi_log']) ?></p>
                                    <span class="text-gray-400 text-[10px] font-semibold uppercase">Oleh: <?= htmlspecialchars($log['nama_lengkap']) ?> (<?= htmlspecialchars($log['role']) ?>)</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Log Aktivitas (Sistem Keamanan) -->
                    <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-sm p-6 text-gray-100 print-break-inside-avoid print-border">
                        <h3 class="text-base font-bold text-white border-b border-gray-600 pb-3 mb-4 flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class='bx bx-check-shield text-accent text-xl'></i> Retensi Dokumen</span>
                            <span class="text-[10px] font-mono text-gray-400 bg-gray-900 px-2 py-1 rounded">DMS-SEC-LOG</span>
                        </h3>
                        <div class="space-y-3 font-mono text-xs">
                            <div class="flex flex-col gap-1">
                                <span class="text-blue-300 font-bold">Dokumen Digital Hash:</span>
                                <span class="text-gray-300 break-all leading-tight">SHA256:<?= hash('sha256', $ticket['id_laporan'] . $ticket['waktu_lapor']) ?></span>
                            </div>
                            <?php if ($fotoPelapor): ?>
                            <div class="flex flex-col gap-1 mt-2">
                                <span class="text-green-400 font-bold">Bukti Foto Valid:</span>
                                <span class="text-gray-300 break-all leading-tight"><?= htmlspecialchars($fotoPelapor['hash_dokumen']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-600 flex items-center gap-2 text-xs text-gray-400">
                            <i class='bx bx-lock-alt text-green-400 text-lg'></i>
                            <p>Terekam secara permanen di Arsip DMS.</p>
                        </div>
                    </div>

                </div>
            </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>
