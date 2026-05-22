<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - NusaDMS</title>
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
        <div class="h-18 flex items-center px-6 border-b border-gray-200 gap-3">
            <i class='bx bxs-buildings text-primary text-3xl'></i>
            <h2 class="text-xl font-bold text-primary tracking-tight">NusaDMS</h2>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 text-primary border-l-4 border-primary font-medium transition-colors">
                <i class='bx bx-grid-alt text-2xl text-accent'></i> Dasbor
            </a>
            <a href="buat-laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent font-medium transition-colors">
                <i class='bx bx-wrench text-2xl'></i> Pelaporan
            </a>
            <a href="inspeksi-kamar.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent"><i class='bx bx-check-shield text-2xl'></i> Inspeksi Kamar</a>
            <a href="maintenance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent font-medium transition-colors">
                <i class='bx bx-calendar-event text-2xl'></i> Maintenance
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent font-medium transition-colors">
                <i class='bx bx-history text-2xl'></i> Riwayat & Laporan
            </a>
            <a href="pengaturan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent font-medium transition-colors">
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
                        <span class="text-sm font-semibold">Andi Setiawan</span>
                        <span class="text-xs text-gray-500">Supervisor</span>
                    </div>
                    <i class='bx bx-chevron-down text-gray-400 text-xl'></i>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 p-8 overflow-y-auto">
            <!-- PAGE CONTENT START -->
            <div class="flex items-center gap-4 mb-8">
                <a href="index.php" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors bg-white shadow-sm">
                    <i class='bx bx-arrow-back text-xl text-gray-600'></i>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Detail Laporan #RPT-001</h1>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 border border-yellow-200">
                            <i class='bx bx-time'></i> Menunggu Approval
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Dokumen tinjauan Supervisor (DMS Workflow).</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class='bx bx-info-circle text-primary'></i> Informasi Kerusakan
                        </h3>
                        <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pelapor</span>
                                <span class="text-sm font-bold text-gray-800">Rina (Housekeeping)</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Lokasi Kejadian</span>
                                <span class="text-sm font-bold text-gray-800">Kamar 302</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Kategori</span>
                                <span class="text-sm font-bold text-gray-800">Mekanikal & Elektrikal (MEP)</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Urgensi</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">Sedang</span>
                            </div>
                            <div class="col-span-2 mt-2">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Deskripsi Lengkap</span>
                                <p class="text-sm text-gray-700 bg-gray-50 p-4 rounded-lg border border-gray-200 leading-relaxed">Tamu komplain AC tidak dingin dan ada sedikit tetesan air dari unit indoor. Sudah dicek pengaturan remote normal. Mohon tim teknisi segera memeriksa freon atau kemungkinan filter tersumbat.</p>
                            </div>
                            <div class="col-span-2 mt-2">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Foto Lampiran Bukti (DMS)</span>
                                <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&q=80&w=800" class="w-full h-72 object-cover rounded-lg border border-gray-200 shadow-sm" alt="Bukti Kerusakan">
                            </div>
                        </div>
                    </div>
                    
                    <!-- NEW LOG AKTIVITAS (Audit Trail Integrity) -->
                    <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-sm p-6 text-gray-100">
                        <h3 class="text-base font-bold text-white border-b border-gray-600 pb-3 mb-4 flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class='bx bx-fingerprint text-accent text-xl'></i> Log Aktivitas (Sistem Keamanan)</span>
                            <span class="text-[10px] font-mono text-gray-400 bg-gray-900 px-2 py-1 rounded">DMS-SEC-LOG</span>
                        </h3>
                        <div class="space-y-3 font-mono text-xs">
                            <div class="flex gap-3">
                                <span class="text-gray-400">[12:01]</span>
                                <span class="text-blue-300 font-bold">Rina (HK):</span>
                                <span class="text-gray-200">Dokumen Dibuat (Checksum Valid)</span>
                            </div>
                            <div class="flex gap-3">
                                <span class="text-gray-400">[14:02]</span>
                                <span class="text-yellow-400 font-bold">Sistem:</span>
                                <span class="text-gray-200">Laporan dikunci untuk Approval. Mengamankan _metadata_ gambar.</span>
                            </div>
                            <div class="flex gap-3">
                                <span class="text-gray-400">[14:10]</span>
                                <span class="text-green-400 font-bold">Manager:</span>
                                <span class="text-gray-200">Foto diverifikasi (Hash Valid: e99a18c4).</span>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-600 flex items-center gap-2 text-xs text-gray-400">
                            <i class='bx bx-lock-alt text-green-400 text-lg'></i>
                            <p>Dokumen ini terenkripsi dan memiliki Digital Fingerprint: <span class="text-white font-mono font-bold tracking-wider ml-1">#DMS-99283-X</span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Audit Trail & Actions -->
                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class='bx bx-history text-primary'></i> Rekam Jejak (Timeline)
                        </h3>
                        <div class="relative pl-7 border-l-2 border-gray-200 space-y-7 ml-2">
                            <div class="relative">
                                <div class="absolute -left-[35px] top-1 w-4 h-4 rounded-full bg-primary border-[3px] border-white shadow-sm ring-1 ring-gray-200"></div>
                                <div class="text-xs font-bold text-gray-500 mb-1.5">29 Apr 2026, 12:01</div>
                                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200 text-sm">
                                    <strong class="text-gray-800 block mb-0.5">Dokumen Laporan Dibuat</strong>
                                    <span class="text-gray-500 text-xs">Oleh: Rina (HK)</span>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="absolute -left-[35px] top-1 w-4 h-4 rounded-full bg-yellow-400 border-[3px] border-white shadow-sm ring-1 ring-gray-200 animate-pulse"></div>
                                <div class="text-xs font-bold text-gray-500 mb-1.5">29 Apr 2026, 12:05</div>
                                <div class="bg-yellow-50 p-3.5 rounded-lg border border-yellow-200 text-sm">
                                    <strong class="text-yellow-800 block mb-0.5">Menunggu Approval</strong>
                                    <span class="text-yellow-700 text-xs">Diteruskan ke Supervisor Teknisi</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-wider text-center border-b border-gray-100 pb-3">Tindakan Supervisor</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Tugaskan Ke:</label>
                                <select class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary transition-all mb-3" id="teknisi">
                                    <option value="">-- Pilih Teknisi --</option>
                                    <option value="joko">Teknisi Joko</option>
                                    <option value="bambang">Teknisi Bambang</option>
                                    <option value="agus">Teknisi Agus</option>
                                </select>
                            </div>
                            <button onclick="if(!document.getElementById('teknisi').value) { alert('Silakan pilih teknisi terlebih dahulu sebelum menyetujui!'); return; } alert('Dokumen disetujui! Tugas telah didisposisikan ke teknisi terkait.'); window.location.href='.php';" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 transition-all shadow-md shadow-green-900/20">
                                <i class='bx bx-check-double text-xl'></i> Setujui & Tugaskan
                            </button>
                            <button onclick="alert('Laporan ditolak dan akan dikembalikan ke pelapor.'); window.location.href='.php';" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 bg-white border border-red-200 text-red-600 text-sm font-bold rounded-lg hover:bg-red-50 transition-all">
                                <i class='bx bx-x text-xl'></i> Tolak Laporan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- PAGE CONTENT END -->
        </main>
    </div>
</body>
</html>

