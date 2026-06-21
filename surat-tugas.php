<?php
require_once 'config.php';
requireLogin();

$id_laporan = $_GET['id'] ?? null;
if (!$id_laporan) {
    die("ID Laporan tidak valid.");
}

// Hanya manager ke atas yang boleh akses (bisa dihilangkan jika teknisi boleh print juga, tapi sesuai instruksi hanya manager)
$allowedRoles = ['manager_hr', 'manager_ops', 'manager_maintenance', 'gm', 'director'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    die("Anda tidak memiliki akses ke halaman ini.");
}

// Ambil Data Tiket & Penugasan
$stmt = $pdo->prepare("
    SELECT m.*, l.nama_lokasi, u.nama_lengkap as pelapor, t.waktu_mulai, t.waktu_selesai as tugas_selesai, 
           ut.nama_lengkap as nama_teknisi, ut.nik as nik_teknisi,
           um.nama_lengkap as nama_manager, um.role as role_manager
    FROM maintenance_reports m
    JOIN locations l ON m.location_id = l.id
    JOIN users u ON m.user_pelapor_id = u.id
    JOIN ticket_assignments t ON m.id_laporan = t.report_id
    JOIN users ut ON t.teknisi_id = ut.id
    JOIN users um ON t.ditugaskan_oleh = um.id
    WHERE m.id_laporan = ?
");
$stmt->execute([$id_laporan]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Data penugasan tidak ditemukan atau tiket belum ditugaskan.");
}

$page_title = "Surat Perintah Kerja (SPK) - " . htmlspecialchars($ticket['nomor_tiket']);
require_once 'layout_header.php';
?>
    <style>
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; overflow: visible !important; }
            body { display: block !important; overflow: auto !important; background: white !important; }
            .a4-container { margin: 0; padding: 15mm; box-shadow: none; width: 100%; }
        }
    </style>
            <!-- PAGE CONTENT START -->
    <div class="mb-4 text-right no-print w-full max-w-[210mm] mx-auto flex justify-end">
        <button onclick="window.print()" class="px-6 py-3 bg-red-800 text-white font-bold rounded-lg shadow hover:bg-red-900 transition-colors">
            <i class='bx bxs-printer'></i> Cetak SPK
        </button>
    </div>

    <div class="a4-container">
        <!-- Header / Kop Surat -->
        <div class="border-b-[3px] border-red-800 pb-4 mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-red-800 uppercase tracking-wider">GrandVault Nusantara</h1>
                <p class="text-sm text-gray-600 mt-1">Jl. Bypass Ngurah Rai No. 123, Bali, Indonesia 80361</p>
                <p class="text-sm text-gray-600">Telp: (0361) 1234567 | Email: maintenance@grandvault.id</p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-gray-800">SISTEM DMS</h2>
                <p class="text-sm text-gray-500 font-mono">DMS-SPK-<?= date('Y') ?></p>
            </div>
        </div>

        <!-- Judul Surat -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold uppercase underline decoration-2 underline-offset-4">Surat Perintah Kerja (SPK)</h2>
            <p class="text-gray-700 font-medium mt-2">Nomor Tiket: <span class="font-bold text-red-800"><?= htmlspecialchars($ticket['nomor_tiket']) ?></span></p>
        </div>

        <!-- Isi Surat -->
        <div class="space-y-6 text-sm leading-relaxed text-gray-800">
            <p>Melalui surat ini, Manajemen GrandVault Nusantara menugaskan kepada pegawai di bawah ini:</p>

            <table class="w-full text-left ml-4">
                <tr>
                    <th class="py-1 w-48 text-gray-600">Nama Teknisi</th>
                    <td class="py-1 font-bold">: <?= htmlspecialchars($ticket['nama_teknisi']) ?></td>
                </tr>
                <tr>
                    <th class="py-1 w-48 text-gray-600">Nomor Induk Karyawan</th>
                    <td class="py-1">: <?= htmlspecialchars($ticket['nik_teknisi'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="py-1 w-48 text-gray-600">Departemen</th>
                    <td class="py-1">: Engineering & Maintenance</td>
                </tr>
            </table>

            <p>Untuk segera melaksanakan pekerjaan perbaikan dengan rincian sebagai berikut:</p>

            <div class="border border-gray-300 rounded p-4 bg-gray-50 ml-4">
                <table class="w-full text-left">
                    <tr>
                        <th class="py-1.5 w-48 text-gray-600 align-top">Lokasi / Area</th>
                        <td class="py-1.5 align-top">: <span class="font-bold"><?= htmlspecialchars($ticket['nama_lokasi']) ?></span></td>
                    </tr>
                    <tr>
                        <th class="py-1.5 w-48 text-gray-600 align-top">Tingkat Prioritas</th>
                        <td class="py-1.5 align-top uppercase font-semibold text-red-700">: <?= htmlspecialchars($ticket['tingkat_prioritas']) ?></td>
                    </tr>
                    <tr>
                        <th class="py-1.5 w-48 text-gray-600 align-top">Judul Masalah</th>
                        <td class="py-1.5 align-top font-bold">: <?= htmlspecialchars($ticket['judul_masalah']) ?></td>
                    </tr>
                    <tr>
                        <th class="py-1.5 w-48 text-gray-600 align-top">Detail Keluhan</th>
                        <td class="py-1.5 align-top italic">: "<?= nl2br(htmlspecialchars($ticket['deskripsi'])) ?>"</td>
                    </tr>
                </table>
            </div>

            <p>Pekerjaan harus dilaksanakan sesuai dengan Standar Operasional Prosedur (SOP) keselamatan yang berlaku. SPK ini wajib dibawa selama bertugas dan diserahkan kembali setelah tugas diselesaikan di sistem DMS.</p>

            <!-- Signatures -->
            <div class="mt-16 flex justify-between px-10">
                <div class="text-center">
                    <p class="mb-20 text-gray-600">Disetujui & Ditugaskan Oleh,</p>
                    <p class="font-bold underline uppercase"><?= htmlspecialchars($ticket['nama_manager']) ?></p>
                    <p class="text-gray-500 text-xs mt-1 uppercase"><?= htmlspecialchars(str_replace('_', ' ', $ticket['role_manager'])) ?></p>
                </div>
                <div class="text-center">
                    <p class="mb-20 text-gray-600">Menerima Tugas,</p>
                    <p class="font-bold underline uppercase"><?= htmlspecialchars($ticket['nama_teknisi']) ?></p>
                    <p class="text-gray-500 text-xs mt-1 uppercase">TEKNISI BERTUGAS</p>
                </div>
            </div>
            
            <div class="mt-10 border-t border-gray-300 pt-4 text-xs text-center text-gray-400">
                Dokumen ini dihasilkan secara otomatis oleh NusaDMS (Document Management System) terintegrasi pada <?= date('d M Y, H:i') ?>.<br>
                Surat perintah ini sah tanpa stempel perusahaan karena diterbitkan secara terotentikasi dari sistem internal.
            </div>
        </div>
    </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>
