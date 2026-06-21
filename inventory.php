<?php
require_once 'config.php';
requireLogin();

// Fitur ini dibatasi untuk Manajemen
$allowedRoles = ['manager_ops', 'manager_maintenance', 'gm', 'director'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    die("Akses Ditolak. Halaman ini khusus untuk level Manajemen.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $kode_barang = trim($_POST['kode_barang'] ?? '');
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $stok_tersedia = (int)($_POST['stok_tersedia'] ?? 0);
        $satuan = trim($_POST['satuan'] ?? '');
        
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory_items (kode_barang, nama_barang, stok_tersedia, satuan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$kode_barang, $nama_barang, $stok_tersedia, $satuan]);
            $success = "Barang baru berhasil ditambahkan ke dalam sistem!";
        } catch (Exception $e) {
            $error = "Terjadi kesalahan (Kode barang mungkin duplikat). " . $e->getMessage();
        }
    } elseif ($action === 'update_stock') {
        $item_id = $_POST['item_id'] ?? '';
        $tambahan_stok = (int)($_POST['tambahan_stok'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE inventory_items SET stok_tersedia = stok_tersedia + ? WHERE id = ?");
            $stmt->execute([$tambahan_stok, $item_id]);
            $success = "Stok berhasil ditambahkan!";
        } catch (Exception $e) {
            $error = "Gagal mengupdate stok. " . $e->getMessage();
        }
    }
}

// Fetch list of items
$stmt = $pdo->query("SELECT * FROM inventory_items ORDER BY nama_barang ASC");
$items = $stmt->fetchAll();

$page_title = "Manajemen Stok - NusaDMS";
require_once 'layout_header.php';
?>
            <!-- PAGE CONTENT START -->
            <div class="flex justify-between items-end mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Pengaturan Sistem</h1>
                    <p class="text-sm text-gray-500 mt-1">Manajemen pengguna sistem DMS dan konfigurasi material.</p>
                </div>
            </div>

            <div class="flex gap-4 mb-8 border-b border-gray-200 overflow-x-auto whitespace-nowrap hide-scrollbar">
                <a href="pengaturan.php" class="px-4 py-3 text-sm font-semibold text-gray-500 hover:text-primary transition-colors">Manajemen User</a>
                <a href="inventory.php" class="px-4 py-3 text-sm font-bold text-primary border-b-2 border-primary">Manajemen Stok Barang</a>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg mb-6">
                    <p class="text-xs text-red-600 font-semibold"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg mb-6">
                    <p class="text-xs text-green-600 font-semibold"><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Form Tambah Barang -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 lg:col-span-1 h-fit">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i class='bx bx-plus-circle text-primary'></i> Tambah Barang Baru</h3>
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="text-xs font-semibold text-gray-600 block mb-1">Kode Barang / SKU <span class="text-red-500">*</span></label>
                            <input type="text" name="kode_barang" required placeholder="Contoh: FR-R32" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary focus:outline-none bg-gray-50">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600 block mb-1">Nama Barang <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_barang" required placeholder="Contoh: Freon R32" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary focus:outline-none bg-gray-50">
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="text-xs font-semibold text-gray-600 block mb-1">Stok Awal <span class="text-red-500">*</span></label>
                                <input type="number" name="stok_tersedia" required min="0" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary focus:outline-none bg-gray-50">
                            </div>
                            <div class="flex-1">
                                <label class="text-xs font-semibold text-gray-600 block mb-1">Satuan <span class="text-red-500">*</span></label>
                                <input type="text" name="satuan" required placeholder="Pcs, Unit, Tabung" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary focus:outline-none bg-gray-50">
                            </div>
                        </div>
                        <button type="submit" class="w-full py-2.5 mt-2 bg-primary text-white font-bold rounded-lg hover:bg-primary-hover transition-colors shadow-sm">
                            Simpan Barang
                        </button>
                    </form>
                </div>

                <!-- Daftar Barang -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden lg:col-span-2">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class='bx bx-list-ul text-primary'></i> Daftar Stok & Persediaan</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-4 font-semibold border-b border-gray-200">Kode</th>
                                    <th class="px-6 py-4 font-semibold border-b border-gray-200">Nama Barang</th>
                                    <th class="px-6 py-4 font-semibold border-b border-gray-200 text-center">Stok</th>
                                    <th class="px-6 py-4 font-semibold border-b border-gray-200 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada barang di inventaris.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs font-semibold text-gray-600"><?= htmlspecialchars($item['kode_barang']) ?></td>
                                        <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($item['nama_barang']) ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $item['stok_tersedia'] > 5 ? 'bg-green-100 text-green-700' : ($item['stok_tersedia'] > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                                <?= htmlspecialchars($item['stok_tersedia']) ?> <?= htmlspecialchars($item['satuan']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <form action="" method="POST" class="inline-flex items-center gap-2">
                                                <input type="hidden" name="action" value="update_stock">
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                <input type="number" name="tambahan_stok" placeholder="+ Add" min="1" required class="w-16 px-2 py-1 text-xs border border-gray-300 rounded focus:border-primary focus:outline-none">
                                                <button type="submit" class="p-1.5 bg-gray-100 text-primary hover:bg-red-50 rounded shadow-sm border border-gray-200 transition-colors" title="Tambah Stok">
                                                    <i class='bx bx-plus font-bold'></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>
