<?php
require_once 'config.php';
requireManager(); // Hanya bisa diakses manajer/direktur

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        
        // Proteksi jangan sampai disable diri sendiri
        if ($_POST['action'] === 'disable' && $userId !== $_SESSION['user_id']) {
            $pdo->query("UPDATE users SET status_aktif = 0 WHERE id = $userId");
        } elseif ($_POST['action'] === 'enable') {
            $pdo->query("UPDATE users SET status_aktif = 1 WHERE id = $userId");
        } elseif ($_POST['action'] === 'change_role' && isset($_POST['new_role'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$_POST['new_role'], $userId]);
        }
        
        header("Location: pengaturan.php");
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
$availableRoles = ['manager_hr', 'manager_ops', 'manager_maintenance', 'gm', 'director', 'teknisi', 'housekeeping'];
$page_title = "Pengaturan User - NusaDMS";
require_once 'layout_header.php';
?>
            <!-- PAGE CONTENT START -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Pengaturan Sistem</h1>
                    <p class="text-sm text-gray-500 mt-1">Manajemen pengguna sistem DMS dan konfigurasi material.</p>
                </div>
                <a href="register.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-hover transition-all shadow-md shadow-red-900/20">
                    <i class='bx bx-user-plus text-lg'></i> Add New User
                </a>
            </div>

            <div class="flex gap-4 mb-8 border-b border-gray-200 overflow-x-auto whitespace-nowrap hide-scrollbar">
                <a href="pengaturan.php" class="px-4 py-3 text-sm font-bold text-primary border-b-2 border-primary">Manajemen User</a>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">User</th>
                                <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Peran (Role)</th>
                                <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <?php 
                            $initial = strtoupper(substr($u['nama_lengkap'], 0, 1));
                            $isAktif = $u['status_aktif'] == 1;
                            $rowClass = $isAktif ? 'bg-gray-800 text-white' : 'bg-gray-400 text-white';
                            ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="p-5 flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full <?= $rowClass ?> flex items-center justify-center font-bold"><?= $initial ?></div>
                                    <span class="font-bold <?= $isAktif ? 'text-gray-800' : 'text-gray-500' ?>"><?= htmlspecialchars($u['nama_lengkap']) ?></span>
                                </td>
                                <td class="p-5 text-sm <?= $isAktif ? 'text-gray-600' : 'text-gray-500' ?>"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="p-5">
                                    <form method="POST" action="pengaturan.php" class="m-0">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <select name="new_role" onchange="this.form.submit()" <?= !$isAktif ? 'disabled' : '' ?> class="px-3 py-1.5 <?= $isAktif ? 'bg-gray-50 border-gray-300' : 'bg-gray-100 border-gray-200 cursor-not-allowed' ?> border rounded text-sm text-gray-800 focus:outline-none focus:border-primary">
                                            <?php foreach ($availableRoles as $r): ?>
                                                <option value="<?= $r ?>" <?= $u['role'] == $r ? 'selected' : '' ?>><?= strtoupper(str_replace('_', ' ', $r)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td class="p-5">
                                    <?php if ($isAktif): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Aktif</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-600">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-5 text-right space-x-2">
                                    <form method="POST" action="pengaturan.php" class="inline m-0">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <?php if ($isAktif): ?>
                                            <input type="hidden" name="action" value="disable">
                                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menonaktifkan pengguna ini?')" class="px-3 py-1.5 border border-red-200 rounded text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">Disable User</button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="enable">
                                            <button type="submit" class="px-3 py-1.5 border border-green-200 bg-green-50 rounded text-xs font-semibold text-green-700 hover:bg-green-100 transition-colors">Enable User</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- PAGE CONTENT END -->
<?php require_once 'layout_footer.php'; ?>
