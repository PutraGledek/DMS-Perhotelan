<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $auth_code = $_POST['auth_code'] ?? '';

    // Validasi Dasar
    if (empty($fullname) || empty($nik) || empty($role) || empty($email) || empty($password)) {
        $error = "Semua kolom wajib diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi sandi tidak cocok.";
    } else {
        // Validasi Auth Code ke Database Token
        $stmt = $pdo->prepare("SELECT id FROM auth_tokens WHERE token = ? AND is_used = 0");
        $stmt->execute([$auth_code]);
        $tokenRow = $stmt->fetch();
        
        if (!$tokenRow) {
            $error = "Kode Otorisasi Manajemen tidak valid atau sudah terpakai.";
        } else {
            // Cek apakah email atau nik sudah terdaftar
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR nik = ?");
        $stmt->execute([$email, $nik]);
        if ($stmt->fetch()) {
            $error = "Email atau NIK sudah terdaftar dalam sistem.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (nik, nama_lengkap, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nik, $fullname, $email, $hashedPassword, $role]);
                
                // Tandai token sudah dipakai
                $pdo->query("UPDATE auth_tokens SET is_used = 1 WHERE id = " . $tokenRow['id']);
                
                $success = "Akun berhasil dibuat! Silakan login untuk melanjutkan.";
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Manajer - GrandVault Nusantara</title>
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
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Left Panel: Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary to-primary-hover relative items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative z-10 flex flex-col items-center text-center px-12">
            <div class="w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white mb-6 shadow-xl">
                <i class='bx bxs-shield-plus text-5xl'></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">GrandVault</h1>
            <h2 class="text-xl tracking-[0.3em] font-medium text-accent uppercase mb-6">Nusantara</h2>
            
            <!-- Context Box -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-5 max-w-md mt-4">
                <div class="flex items-center justify-center gap-3 text-accent mb-2">
                    <i class='bx bxs-lock-alt text-xl'></i>
                    <span class="font-bold tracking-wide uppercase text-sm">Akses Terbatas</span>
                </div>
                <p class="text-red-50 text-sm leading-relaxed">
                    Halaman registrasi ini dibatasi secara ketat hanya untuk personel dengan hak akses <strong>Manajer ke atas</strong>. Seluruh aktivitas pembuatan akun akan diaudit oleh sistem.
                </p>
            </div>
        </div>
        <!-- Decorative elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute bottom-[10%] -right-[10%] w-[60%] h-[60%] rounded-full bg-accent/10 blur-3xl"></div>
        </div>
    </div>

    <!-- Right Panel: Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-white p-8 overflow-y-auto">
        <div class="w-full max-w-md py-8">
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-white">
                    <i class='bx bxs-shield-plus text-2xl'></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 leading-none">GrandVault</h2>
                    <span class="text-xs uppercase font-bold text-primary tracking-widest">Nusantara</span>
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Registrasi Manajer</h2>
                <p class="text-gray-500 mt-2 text-sm">Pendaftaran khusus akun level manajemen & direksi.</p>
            </div>

            <!-- Warning Alert -->
            <div class="bg-red-50 border-l-4 border-primary p-4 rounded-r-lg mb-6">
                <div class="flex items-start">
                    <i class='bx bx-info-circle text-primary text-xl mt-0.5 mr-3'></i>
                    <div>
                        <h4 class="text-sm font-bold text-primary">Validasi Otorisasi</h4>
                        <p class="text-xs text-red-800 mt-1">Sistem memerlukan <strong>Kode Otorisasi Manajemen</strong> yang valid untuk melanjutkan registrasi.</p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg mb-6">
                <div class="flex items-start">
                    <i class='bx bx-x-circle text-red-500 text-xl mt-0.5 mr-3'></i>
                    <div>
                        <h4 class="text-sm font-bold text-red-800">Registrasi Gagal</h4>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg mb-6">
                <div class="flex items-start">
                    <i class='bx bx-check-circle text-green-500 text-xl mt-0.5 mr-3'></i>
                    <div>
                        <h4 class="text-sm font-bold text-green-800">Registrasi Berhasil</h4>
                        <p class="text-xs text-green-600 mt-1"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label for="fullname" class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class='bx bx-id-card text-gray-400 text-lg'></i>
                            </div>
                            <input type="text" id="fullname" name="fullname" class="block w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none" placeholder="Sesuai kartu identitas" required>
                        </div>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label for="nik" class="block text-sm font-semibold text-gray-700 mb-1.5">NIK Karyawan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class='bx bx-barcode-reader text-gray-400 text-lg'></i>
                            </div>
                            <input type="text" id="nik" name="nik" class="block w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none" placeholder="Contoh: GV-2024-889" required>
                        </div>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label for="role" class="block text-sm font-semibold text-gray-700 mb-1.5">Pilih Jabatan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class='bx bx-briefcase text-gray-400 text-lg'></i>
                            </div>
                            <select id="role" name="role" class="block w-full pl-11 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none appearance-none" required>
                                <option value="" disabled selected>Pilih Jabatan</option>
                                <option value="manager_hr">Manajer HR</option>
                                <option value="manager_ops">Manajer Operasional</option>
                                <option value="manager_maintenance">Manajer Maintenance</option>
                                <option value="gm">General Manager</option>
                                <option value="director">Direktur</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                                <i class='bx bx-chevron-down text-xl'></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Alamat Email Perusahaan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class='bx bx-envelope text-gray-400 text-lg'></i>
                            </div>
                            <input type="email" id="email" name="email" class="block w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none" placeholder="email@grandvault.co.id" required>
                        </div>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Kata Sandi Baru</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class='bx bx-lock-alt text-gray-400 text-lg'></i>
                            </div>
                            <input type="password" id="password" name="password" class="block w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none" placeholder="Minimal 8 karakter" required>
                        </div>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class='bx bx-check-shield text-gray-400 text-lg'></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" class="block w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none" placeholder="Ulangi kata sandi" required>
                        </div>
                    </div>

                    <input type="hidden" name="auth_code" id="hidden_auth_code" value="">
                </div>

                <div class="pt-4">
                    <button type="button" onclick="showAuthModal()" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all hover:-translate-y-0.5">
                        <i class='bx bx-user-plus text-lg'></i> Lanjut Pendaftaran
                    </button>
                </div>
            </form>

            <!-- Modal Auth Code -->
            <div id="authModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center px-4 backdrop-blur-sm transition-all">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden transform scale-100 transition-all">
                    <div class="bg-gradient-to-r from-primary to-primary-hover p-5 text-center">
                        <i class='bx bxs-lock-alt text-4xl text-accent mb-2'></i>
                        <h3 class="text-lg font-bold text-white">Verifikasi Otorisasi</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4 text-center">Silakan minta <strong>Kode Token</strong> dari Manajer/Direktur Anda (melalui Pusat Otorisasi) untuk melanjutkan.</p>
                        
                        <div class="mb-5">
                            <input type="text" id="modal_auth_code" class="block w-full px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-lg text-lg focus:bg-white focus:ring-2 focus:ring-accent focus:border-accent outline-none font-mono tracking-widest text-center font-bold text-gray-800" placeholder="GV-XXXXX">
                        </div>
                        
                        <div class="flex gap-3">
                            <button type="button" onclick="closeAuthModal()" class="flex-1 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                Batal
                            </button>
                            <button type="button" onclick="submitRegistration()" class="flex-1 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary-hover transition-colors text-sm flex justify-center items-center gap-2">
                                Daftarkan <i class='bx bx-check'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function showAuthModal() {
                // Validasi singkat JS sebelum popup
                if(!document.getElementById('fullname').value || !document.getElementById('email').value || !document.getElementById('password').value) {
                    alert('Harap lengkapi semua kolom biodata terlebih dahulu.');
                    return;
                }
                document.getElementById('authModal').classList.remove('hidden');
            }
            
            function closeAuthModal() {
                document.getElementById('authModal').classList.add('hidden');
            }
            
            function submitRegistration() {
                const code = document.getElementById('modal_auth_code').value;
                if(!code) {
                    alert('Kode otorisasi tidak boleh kosong!');
                    return;
                }
                document.getElementById('hidden_auth_code').value = code;
                document.querySelector('form').submit();
            }
            </script>

            <div class="mt-6 border-t border-gray-100 pt-6">
                <p class="text-center text-sm text-gray-500">
                    Sudah memiliki akun? <a href="login.php" class="font-semibold text-primary hover:text-primary-hover">Masuk di sini</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
