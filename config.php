<?php
// config.php
// Konfigurasi Database dan Session Utama Sistem GrandVault Nusantara

// Memulai session jika belum dimulai (untuk menyimpan data login)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tentukan path absolut ke file SQLite
$dbPath = __DIR__ . '/database.sqlite';

try {
    // Membuat instance koneksi PDO ke SQLite
    $pdo = new PDO("sqlite:" . $dbPath);
    
    // Konfigurasi agar PDO menampilkan error secara detail (Exception)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Atur mode fetch default menjadi array asosiatif (biar lebih mudah dipanggil seperti $row['nama'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // WAJIB UNTUK SQLITE: Aktifkan pengecekan Foreign Key untuk keamanan relasi tabel
    $pdo->exec("PRAGMA foreign_keys = ON;");
    
    // AUTO-MIGRASI: Cek apakah tabel users sudah ada. Jika belum, buat semua tabel otomatis.
    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if (!$check->fetch()) {
        $schemaSql = file_get_contents(__DIR__ . '/database_schema.sql');
        if ($schemaSql) {
            $pdo->exec($schemaSql);
        }
    }
    
    // Auto-migrasi tambahan untuk tabel auth_tokens (Dynamic Authorization Codes)
    $pdo->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_used INTEGER DEFAULT 0
    );");
    
    // Auto-migrasi untuk tabel Inventory
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode_barang TEXT UNIQUE NOT NULL,
        nama_barang TEXT NOT NULL,
        stok_tersedia INTEGER NOT NULL DEFAULT 0,
        satuan TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_usage (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        report_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        jumlah_digunakan INTEGER NOT NULL,
        waktu_penggunaan DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (report_id) REFERENCES maintenance_reports(id_laporan) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT
    );");
} catch (PDOException $e) {
    // Hentikan eksekusi script jika database tidak ditemukan atau rusak
    die("<div style='font-family: Arial; padding: 20px; border-left: 5px solid red; background: #ffeeee;'>
            <h3 style='color: red; margin-top:0;'>⚠️ Sistem Terhenti: Koneksi Database Gagal</h3>
            <p>Pastikan file <b>database.sqlite</b> telah dibuat dan dapat diakses.</p>
            <p style='color: #666; font-size: 14px;'><i>Pesan Error: " . htmlspecialchars($e->getMessage()) . "</i></p>
         </div>");
}

// =========================================================
// Fungsi Utility Global
// =========================================================

/**
 * Validasi apakah user sudah login.
 * Panggil fungsi ini di awal halaman yang bersifat tertutup (Dashboard, dll).
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Validasi apakah user yang login memiliki hak akses manajerial.
 */
function requireManager() {
    requireLogin();
    $managerRoles = ['manager_hr', 'manager_ops', 'manager_maintenance', 'gm', 'director'];
    if (!in_array($_SESSION['role'], $managerRoles)) {
        die("Akses Ditolak. Halaman ini hanya untuk level Manajemen.");
    }
}
?>
