<?php
// seed.php - Pusat Otorisasi Manajemen Sistem GrandVault Nusantara
require_once 'config.php';

// 1. Proses Background: Pastikan akun Direktur selalu tersedia
$nama = 'nandoTampan';
$password = 'test1';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$nik = 'DIR-001';
$email = 'nando@grandvault.co.id';
$role = 'director';

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (nik, nama_lengkap, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nik, $nama, $email, $hashedPassword, $role]);
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// 2. Tampilan Frontend: Generator Kode Otorisasi
$authCode = "-";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $authCode = "GV-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $stmt = $pdo->prepare("INSERT INTO auth_tokens (token) VALUES (?)");
    $stmt->execute([$authCode]);
    $message = "Kode token baru berhasil dibuat dan siap digunakan (1 kali pakai).";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Otorisasi - GrandVault Nusantara</title>
    <!-- Tailwind CSS v4 CDN -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style type="text/tailwindcss">
        @theme {
            --color-primary: #8B2323;
            --color-primary-hover: #6E1C1C;
            --color-accent: #D4AF37;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen font-sans">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
        <div class="bg-gradient-to-r from-primary to-primary-hover p-6 text-center">
            <div class="w-16 h-16 mx-auto bg-white/20 backdrop-blur rounded-full flex items-center justify-center mb-4 shadow-inner">
                <i class='bx bxs-key text-3xl text-accent'></i>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-wide">Pusat Otorisasi</h2>
            <p class="text-red-100 text-sm mt-1">Sistem Manajemen GrandVault</p>
        </div>
        
        <div class="p-8 text-center">
            <p class="text-gray-600 text-sm leading-relaxed mb-6">
                Tekan tombol di bawah untuk menghasilkan token rahasia (sekali pakai). Berikan kode tersebut kepada staf yang akan mendaftar.
            </p>
            
            <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg text-sm mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-yellow-50 border-2 border-dashed border-accent rounded-xl py-5 px-4 mb-6 shadow-sm">
                <span class="block text-xs font-bold text-yellow-700 uppercase tracking-widest mb-2">Token Registrasi:</span>
                <span class="text-3xl font-mono font-extrabold text-gray-800 tracking-widest"><?= htmlspecialchars($authCode) ?></span>
            </div>
            
            <form method="POST" action="">
                <button type="submit" name="generate" class="w-full py-3 bg-accent text-white font-bold rounded-xl hover:bg-yellow-600 transition-colors shadow-md mb-4 flex items-center justify-center gap-2">
                    <i class='bx bx-refresh text-xl'></i> Generate Kode Baru
                </button>
            </form>
            
            <div class="flex flex-col gap-3">
                <a href="register.php" class="w-full py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary-hover transition-colors shadow-md">
                    Menuju Halaman Registrasi
                </a>
                <a href="login.php" class="w-full py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-colors">
                    Kembali ke Login
                </a>
            </div>
        </div>
    </div>

</body>
</html>
