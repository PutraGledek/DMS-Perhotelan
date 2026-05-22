<?php
require_once 'config.php';
requireLogin();

// Konfigurasi Filter
$filterTanggal = $_GET['tanggal'] ?? '';
$filterKategori = $_GET['kategori'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$whereClauses = [];
$params = [];

if (!empty($filterTanggal)) {
    $whereClauses[] = "DATE(COALESCE(m.waktu_selesai, m.waktu_lapor)) >= ?";
    $params[] = $filterTanggal;
}

if (!empty($filterKategori) && $filterKategori !== 'Semua Kategori') {
    $whereClauses[] = "l.kategori_lokasi = ?";
    $params[] = $filterKategori;
}

if (!empty($filterStatus) && $filterStatus !== 'Semua Status') {
    if ($filterStatus == 'Selesai / Archived') {
        $whereClauses[] = "m.status IN ('selesai', 'ditolak')";
    } elseif ($filterStatus == 'Dalam Proses') {
        $whereClauses[] = "m.status IN ('menunggu_approval', 'dikerjakan')";
    }
}

$whereSql = "";
if (count($whereClauses) > 0) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

// Ambil data riwayat laporan (Aktif maupun Selesai/Ditolak) beserta filternya
$stmt = $pdo->prepare("
    SELECT m.*, l.nama_lokasi, l.kategori_lokasi, u.nama_lengkap as nama_approver
    FROM maintenance_reports m
    LEFT JOIN locations l ON m.location_id = l.id
    LEFT JOIN ticket_assignments t ON m.id_laporan = t.report_id
    LEFT JOIN users u ON t.ditugaskan_oleh = u.id
    $whereSql
    ORDER BY COALESCE(m.waktu_selesai, m.waktu_lapor) DESC
");
$stmt->execute($params);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat & Laporan - NusaDMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
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
            <a href="maintenance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-calendar-event text-2xl'></i> Maintenance
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors bg-red-50 text-primary border-l-4 border-primary">
                <i class='bx bx-history text-2xl text-accent'></i> Riwayat & Laporan
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
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Riwayat Dokumen (Arsip DMS)</h1>
                    <p class="text-sm text-gray-500 mt-1">Daftar keseluruhan data laporan dan status kebijakan retensi (Archiving Policy).</p>
                </div>
                <button id="btnExportPDF" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all shadow-sm">
                    <i class='bx bxs-file-pdf text-red-500 text-lg'></i> Ekspor PDF
                </button>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                
                <!-- Filter Bar -->
                <form method="GET" action="riwayat.php" class="flex flex-wrap gap-4 mb-6 pb-6 border-b border-gray-100">
                    <div class="flex-1 min-w-[200px]">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Tanggal Mulai</label>
                        <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Kategori Lokasi</label>
                        <select name="kategori" class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                            <option <?= $filterKategori == 'Semua Kategori' || $filterKategori == '' ? 'selected' : '' ?>>Semua Kategori</option>
                            <option <?= $filterKategori == 'Kamar' ? 'selected' : '' ?>>Kamar</option>
                            <option <?= $filterKategori == 'Fasilitas Umum' ? 'selected' : '' ?>>Fasilitas Umum</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Status</label>
                        <select name="status" class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                            <option <?= $filterStatus == 'Semua Status' || $filterStatus == '' ? 'selected' : '' ?>>Semua Status</option>
                            <option <?= $filterStatus == 'Dalam Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                            <option <?= $filterStatus == 'Selesai / Archived' ? 'selected' : '' ?>>Selesai / Archived</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-6 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary-hover transition-all text-sm h-[38px]">
                            Filter
                        </button>
                        <a href="riwayat.php" class="px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition-all text-sm h-[38px] flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </form>

                <!-- Table -->
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">ID Dokumen</th>
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Lokasi</th>
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Masalah</th>
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Disetujui Oleh</th>
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Selesai Tanggal</th>
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status DMS</th>
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status Arsip (Retention)</th>
                                <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($history) === 0): ?>
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-gray-500">Belum ada riwayat dokumen yang diarsipkan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $row): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                        <td class="p-4 font-bold text-primary"><?= htmlspecialchars($row['nomor_tiket']) ?></td>
                                        <td class="p-4 text-sm font-medium text-gray-800"><?= htmlspecialchars($row['nama_lokasi']) ?></td>
                                        <td class="p-4 text-sm text-gray-600 truncate max-w-[150px]"><?= htmlspecialchars($row['judul_masalah']) ?></td>
                                        <td class="p-4 text-sm text-gray-800"><?= htmlspecialchars($row['nama_approver'] ?? '-') ?></td>
                                        <td class="p-4 text-sm text-gray-600"><?= $row['waktu_selesai'] ? date('d M Y', strtotime($row['waktu_selesai'])) : '-' ?></td>
                                        <td class="p-4">
                                            <?php
                                            $badgeClass = 'bg-gray-100 text-gray-600 border-gray-200';
                                            if ($row['status'] == 'menunggu_approval') $badgeClass = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                            elseif ($row['status'] == 'dikerjakan') $badgeClass = 'bg-blue-100 text-blue-700 border-blue-200';
                                            elseif ($row['status'] == 'ditolak') $badgeClass = 'bg-red-100 text-red-600 border-red-200';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold <?= $badgeClass ?> border">
                                                <?= strtoupper(str_replace('_', ' ', $row['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <?php if (in_array($row['status'], ['selesai', 'ditolak'])): ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                    <i class='bx bx-archive-in'></i> Permanen
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-orange-50 text-orange-600 border border-orange-200">
                                                    <i class='bx bx-loader-alt bx-spin'></i> Dalam Proses
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <button class="text-gray-400 hover:text-primary transition-colors" title="View Detail">
                                                <i class='bx bx-search-alt text-2xl'></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-between items-center text-sm text-gray-500">
                    <p>Menampilkan 1 hingga 3 dari 124 data</p>
                    <div class="flex gap-1">
                        <button class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-gray-400 cursor-not-allowed">Prev</button>
                        <button class="px-3 py-1 border border-primary bg-primary text-white rounded">1</button>
                        <button class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-gray-700">2</button>
                        <button class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-gray-700">3</button>
                        <button class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-gray-700">Next</button>
                    </div>
                </div>

            </div>
            <!-- PAGE CONTENT END -->
        </main>
    </div>
    <script>
        document.getElementById('btnExportPDF').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape');
            
            // Header / Kop Surat DMS
            doc.setFontSize(18);
            doc.setTextColor(139, 35, 35); // Primary color
            doc.text('GrandVault Nusantara DMS', 14, 20);
            
            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.text('Dokumen Riwayat Laporan & Arsip Maintenance', 14, 28);
            
            // Tanda Waktu Cetak
            doc.setFontSize(10);
            const today = new Date();
            const dateStr = today.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const timeStr = today.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            doc.text('Dicetak pada: ' + dateStr + ' ' + timeStr, 14, 34);

            // Mengambil Data dari Tabel HTML
            const rows = [];
            const table = document.querySelector('table');
            const trs = table.querySelectorAll('tbody tr');
            trs.forEach(tr => {
                const tds = tr.querySelectorAll('td');
                // Pastikan bukan baris "Belum ada riwayat"
                if (tds.length >= 7) { 
                    rows.push([
                        tds[0].innerText.trim(),
                        tds[1].innerText.trim(),
                        tds[2].innerText.trim(),
                        tds[3].innerText.trim(),
                        tds[4].innerText.trim(),
                        tds[5].innerText.trim().replace(/\n/g, ''), // Hapus enter pada label status
                        tds[6].innerText.trim().replace(/\n/g, '')
                    ]);
                }
            });

            if (rows.length === 0) {
                alert('Tidak ada data laporan untuk diekspor.');
                return;
            }

            // Generate Tabel PDF yang rapi
            doc.autoTable({
                head: [['ID Dokumen', 'Lokasi', 'Masalah', 'Disetujui Oleh', 'Selesai', 'Status DMS', 'Status Arsip']],
                body: rows,
                startY: 40,
                theme: 'grid',
                headStyles: { fillColor: [139, 35, 35], textColor: [255, 255, 255] },
                alternateRowStyles: { fillColor: [249, 249, 249] },
                styles: { fontSize: 9, cellPadding: 4, font: 'helvetica' },
                columnStyles: {
                    0: { fontStyle: 'bold', textColor: [139, 35, 35] }
                }
            });

            // Menyimpan Dokumen
            doc.save('Arsip_DMS_GrandVault_' + Date.now() + '.pdf');
        });
    </script>
</body>
</html>
