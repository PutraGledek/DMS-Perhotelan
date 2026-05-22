<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penyelesaian Dokumen - NusaDMS</title>
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
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors bg-red-50 text-primary border-l-4 border-primary">
                <i class='bx bx-grid-alt text-2xl text-accent'></i> Dasbor
            </a>
            <a href="buat-laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-wrench text-2xl'></i> Pelaporan
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
                <a href="maintenance.php" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors bg-white shadow-sm">
                    <i class='bx bx-arrow-back text-xl text-gray-600'></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Penyelesaian Dokumen #RPT-001</h1>
                    <p class="text-sm text-gray-500 mt-1">Formulir penutupan dokumen (DMS Closing) oleh teknisi.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 max-w-4xl mb-12">
                <form onsubmit="event.preventDefault(); if(!signed) { alert('PERINGATAN: Harap bubuhkan Tanda Tangan Digital terlebih dahulu untuk validasi dokumen!'); return; } alert('Sukses! Tanda Terima Perbaikan telah diterbitkan. Dokumen kini diarsipkan secara permanen ke dalam DMS.'); window.location.href='.php';">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-semibold text-gray-800">Catatan Tindakan Perbaikan <span class="text-red-500">*</span></label>
                            <textarea required rows="3" placeholder="Jelaskan secara detail tindakan yang telah dilakukan (Contoh: Telah dilakukan pembersihan filter AC dan isi freon)..." class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-red-100 transition-all"></textarea>
                        </div>

                        <!-- Integrasi Stok (Inventory Link) -->
                        <div class="space-y-3 md:col-span-2 mt-2 bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-800 flex items-center gap-2"><i class='bx bx-box text-primary'></i> Material/Barang yang Digunakan (Integrasi Stok)</h4>
                            <div class="flex flex-col sm:flex-row gap-4 items-end">
                                <div class="flex-1 w-full">
                                    <label class="text-xs font-semibold text-gray-600 block mb-1">Nama Barang</label>
                                    <select class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-primary focus:outline-none bg-white">
                                        <option>-- Pilih Material --</option>
                                        <option>Freon R32</option>
                                        <option>Bohlam LED 15W</option>
                                        <option>Sprei King Size</option>
                                        <option>Bantal Kepala</option>
                                    </select>
                                </div>
                                <div class="w-24 shrink-0">
                                    <label class="text-xs font-semibold text-gray-600 block mb-1">Jumlah</label>
                                    <input type="number" value="1" min="1" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-primary focus:outline-none bg-white">
                                </div>
                                <div class="w-32 shrink-0">
                                    <label class="text-xs font-semibold text-gray-600 block mb-1">Satuan</label>
                                    <input type="text" value="Unit" disabled class="w-full px-3 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-500">
                                </div>
                                <button type="button" class="px-4 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover shadow-sm"><i class='bx bx-plus text-lg'></i></button>
                            </div>
                        </div>

                        <!-- Versioning Foto Area -->
                        <div class="space-y-2 md:col-span-2 mt-4">
                            <label class="text-sm font-semibold text-gray-800 mb-2 block flex justify-between items-center">
                                <span>Unggah Foto Hasil Perbaikan <span class="text-red-500">*</span></span>
                                <span class="text-xs font-medium text-primary cursor-pointer hover:underline flex items-center gap-1"><i class='bx bx-history'></i> Riwayat Foto</span>
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center bg-gray-50 hover:bg-red-50 hover:border-primary cursor-pointer transition-all" onclick="document.getElementById('file-upload-2').click()">
                                
                                <div class="flex gap-6 mb-4 items-center justify-center">
                                    <div class="text-center relative">
                                        <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&q=80&w=80" class="w-16 h-16 object-cover rounded-lg border border-gray-300 opacity-60 grayscale shadow-sm" alt="V1">
                                        <span class="text-[10px] font-bold text-gray-500 mt-1 block">Versi 1 (Asli)</span>
                                    </div>
                                    <i class='bx bx-right-arrow-alt text-gray-400 text-xl'></i>
                                    <div class="text-center relative">
                                        <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&q=80&w=80" class="w-16 h-16 object-cover rounded-lg border-2 border-primary shadow-md" alt="V2">
                                        <span class="absolute -top-2 -right-2 bg-green-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-white">Baru</span>
                                        <span class="text-[10px] font-bold text-primary mt-1 block">Versi 2 (Update)</span>
                                    </div>
                                </div>
                                <h4 class="text-sm font-semibold text-gray-800 group-hover:text-primary transition-colors">Klik untuk mengganti foto versi terbaru</h4>
                                <input type="file" id="file-upload-2" class="hidden" accept="image/*">
                            </div>
                        </div>

                        <!-- Signature Validation Area -->
                        <div class="space-y-2 md:col-span-2 mt-4">
                            <label class="text-sm font-semibold text-gray-800 mb-2 block flex items-center justify-between">
                                <span>Tanda Tangan Digital Teknisi <span class="text-red-500">*</span></span>
                                <span class="text-xs font-normal text-gray-500 bg-gray-100 px-2 py-1 rounded border border-gray-200">Diperlukan untuk validasi DMS</span>
                            </label>
                            <div id="sig-pad" class="h-40 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 flex flex-col items-center justify-center cursor-pointer hover:border-primary hover:bg-red-50 transition-all relative group" onclick="signDoc()">
                                <i class='bx bx-edit-alt text-4xl text-gray-400 group-hover:text-primary mb-3 transition-colors'></i>
                                <span class="text-sm text-gray-500 font-medium group-hover:text-primary transition-colors">Klik area ini untuk membubuhkan Tanda Tangan</span>
                            </div>
                            
                            <div id="sig-validation" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg shadow-sm">
                                <p class="text-xs font-bold text-green-700 flex items-center gap-1.5"><i class='bx bx-check-shield text-base'></i> Tanda tangan ini terverifikasi secara digital melalui User Session: [ID-USER-ANDI]</p>
                                <p class="text-[11px] text-green-600 mt-1 font-mono ml-5" id="sig-timestamp">Timestamp: --</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end gap-4">
                        <a href="maintenance.php" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Kembali</a>
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 hover:-translate-y-0.5 transition-all shadow-md shadow-green-900/20">
                            <i class='bx bx-archive-in text-lg'></i> Simpan ke Arsip DMS
                        </button>
                    </div>
                </form>
            </div>
            <!-- PAGE CONTENT END -->
        </main>
    </div>

    <script>
        let signed = false;
        function signDoc() {
            const pad = document.getElementById('sig-pad');
            const validationBox = document.getElementById('sig-validation');
            const timestamp = document.getElementById('sig-timestamp');
            
            // Get formatted date
            const now = new Date();
            const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            // Menampilkan tanda tangan digital simulasi
            pad.innerHTML = `
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-white rounded-xl">
                    <span class="text-5xl text-primary transform -rotate-6 font-serif italic opacity-90 tracking-widest" style="font-family: 'Brush Script MT', 'Dancing Script', cursive;">
                        Joko Teknisi
                    </span>
                    <span class="absolute bottom-3 text-[10px] font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full border border-green-200 flex items-center gap-1">
                        <i class='bx bx-check-shield text-sm'></i> Disahkan Digital
                    </span>
                </div>
            `;
            // Mengubah style border
            pad.classList.remove('border-dashed', 'bg-gray-50');
            pad.classList.add('border-solid', 'border-green-500', 'ring-4', 'ring-green-50');
            
            // Tampilkan validasi & timestamp
            validationBox.classList.remove('hidden');
            timestamp.innerText = `Timestamp: ${dateStr}, ${timeStr} WIB`;
            
            signed = true;
        }
    </script>
</body>
</html>

