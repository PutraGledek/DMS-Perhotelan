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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan User - NusaDMS</title>
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
            <a href="riwayat.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent">
                <i class='bx bx-history text-2xl'></i> Riwayat & Laporan
            </a>
            <a href="pengaturan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors bg-red-50 text-primary border-l-4 border-primary">
                <i class='bx bx-user-circle text-2xl text-accent'></i> Pengaturan User
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
                        <span class="text-sm font-semibold"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
                        <span class="text-xs text-gray-500 uppercase"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
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
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Pengaturan User & Hak Akses</h1>
                    <p class="text-sm text-gray-500 mt-1">Manajemen pengguna sistem DMS (Role-Based Access Control).</p>
                </div>
                <a href="register.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-hover transition-all shadow-md shadow-red-900/20">
                    <i class='bx bx-user-plus text-lg'></i> Add New User
                </a>
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
        </main>
    </div>
</body>
</html>

