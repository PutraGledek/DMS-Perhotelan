<?php
// Pastikan session sudah dimulai sebelum file ini di-include
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set variabel default jika tidak ada
$page_title = $page_title ?? 'GrandVault Nusantara DMS';
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $custom_role ?? $_SESSION['role'] ?? 'staff';
$user_name = $custom_name ?? $_SESSION['nama'] ?? 'Pengguna';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Tailwind CSS v4 CDN -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: #8B2323;
            --color-primary-hover: #6E1C1C;
            --color-accent: #D4AF37;
        }
        body { font-family: 'Inter', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 flex h-screen overflow-hidden">
    <!-- Mobile Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0 fixed inset-y-0 left-0 z-50 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out">
        <div class="h-18 flex items-center px-6 border-b border-gray-200 gap-3 shrink-0">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-primary-hover text-white shadow-md">
                <i class='bx bxs-institution text-2xl'></i>
            </div>
            <div class="flex flex-col">
                <h2 class="text-lg font-extrabold text-gray-800 tracking-tight leading-none">GrandVault</h2>
                <span class="text-[10px] uppercase font-bold text-primary tracking-widest mt-0.5">Nusantara</span>
            </div>
            <!-- Close button on mobile -->
            <button onclick="toggleSidebar()" class="md:hidden ml-auto text-gray-500 hover:text-primary">
                <i class='bx bx-x text-2xl'></i>
            </button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors <?= $current_page == 'index.php' ? 'bg-red-50 text-primary border-l-4 border-primary' : 'text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent' ?>">
                <i class='bx bx-grid-alt text-2xl <?= $current_page == 'index.php' ? 'text-accent' : '' ?>'></i> Dasbor
            </a>
            <a href="buat-laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors <?= $current_page == 'buat-laporan.php' ? 'bg-red-50 text-primary border-l-4 border-primary' : 'text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent' ?>">
                <i class='bx bx-wrench text-2xl <?= $current_page == 'buat-laporan.php' ? 'text-accent' : '' ?>'></i> Pelaporan
            </a>
            
            <?php if (in_array($user_role, ['manager_hr', 'manager_ops', 'manager_maintenance', 'gm', 'director'])): ?>
            <a href="penugasan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors <?= $current_page == 'penugasan.php' ? 'bg-red-50 text-primary border-l-4 border-primary' : 'text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent' ?>">
                <i class='bx bx-clipboard text-2xl <?= $current_page == 'penugasan.php' ? 'text-accent' : '' ?>'></i> Penugasan SPK
            </a>
            <?php endif; ?>
            
            <a href="maintenance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors <?= $current_page == 'maintenance.php' || $current_page == 'penyelesaian.php' ? 'bg-red-50 text-primary border-l-4 border-primary' : 'text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent' ?>">
                <i class='bx bx-calendar-event text-2xl <?= $current_page == 'maintenance.php' || $current_page == 'penyelesaian.php' ? 'text-accent' : '' ?>'></i> Maintenance
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors <?= $current_page == 'riwayat.php' || $current_page == 'detail-track.php' || $current_page == 'surat-tugas.php' ? 'bg-red-50 text-primary border-l-4 border-primary' : 'text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent' ?>">
                <i class='bx bx-history text-2xl <?= $current_page == 'riwayat.php' || $current_page == 'detail-track.php' || $current_page == 'surat-tugas.php' ? 'text-accent' : '' ?>'></i> Riwayat & Laporan
            </a>
            <a href="pengaturan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors <?= $current_page == 'pengaturan.php' ? 'bg-red-50 text-primary border-l-4 border-primary' : 'text-gray-500 hover:bg-gray-50 hover:text-primary border-l-4 border-transparent' ?>">
                <i class='bx bx-user-circle text-2xl <?= $current_page == 'pengaturan.php' ? 'text-accent' : '' ?>'></i> Pengaturan User
            </a>
        </nav>
        <!-- Logout button at bottom of sidebar -->
        <div class="p-4 border-t border-gray-200">
            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors text-red-600 hover:bg-red-50">
                <i class='bx bx-log-out text-2xl'></i> Keluar
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden w-full">
        <!-- Header -->
        <header class="h-18 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-8 shrink-0">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-500 hover:text-primary transition-colors focus:outline-none p-1">
                    <i class='bx bx-menu text-3xl'></i>
                </button>
                <div class="flex-1 md:flex-none flex items-center bg-gray-50 rounded-full px-4 py-2 md:px-5 md:py-2.5 max-w-[200px] md:max-w-none md:w-80 border border-transparent focus-within:border-primary focus-within:ring-2 focus-within:ring-red-100 transition-all">
                    <i class='bx bx-search text-gray-400 text-lg md:text-xl mr-2 md:mr-3'></i>
                    <input type="text" placeholder="Cari ID laporan, lokasi..." class="bg-transparent border-none outline-none w-full text-xs md:text-sm text-gray-700">
                </div>
            </div>
            <div class="flex items-center gap-4 md:gap-6 shrink-0 ml-2">
                <button class="relative text-gray-500 hover:scale-110 transition-transform">
                    <i class='bx bx-bell text-xl md:text-2xl'></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] md:text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white">3</span>
                </button>
                <div class="flex items-center gap-2 md:gap-3 cursor-pointer hover:bg-gray-50 p-1.5 rounded-lg transition-colors">
                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-primary text-white flex items-center justify-center font-semibold text-base md:text-lg"><?= htmlspecialchars($user_initial) ?></div>
                    <div class="hidden sm:flex flex-col">
                        <span class="text-sm font-semibold whitespace-nowrap"><?= htmlspecialchars($user_name) ?></span>
                        <span class="text-xs text-gray-500 uppercase"><?= htmlspecialchars($user_role) ?></span>
                    </div>
                    <i class='bx bx-chevron-down text-gray-400 text-xl hidden sm:block'></i>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 p-4 md:p-8 overflow-y-auto">
            <!-- PAGE CONTENT START -->
