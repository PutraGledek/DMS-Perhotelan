<?php
$page_title = "Penyelesaian Dokumen - NusaDMS";
require_once 'layout_header.php';
?>
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
<?php require_once 'layout_footer.php'; ?>
