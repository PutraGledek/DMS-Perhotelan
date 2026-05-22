<?php
$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Aktifkan constraint Foreign Key pada SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // Array Schema untuk eksekusi secara berurutan
    $schemas = [
        // 1. MASTER: Tabel Users
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nik TEXT UNIQUE NOT NULL,
            nama_lengkap TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL,
            status_aktif INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",

        // 2. MASTER: Tabel Locations
        "CREATE TABLE IF NOT EXISTS locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_lokasi TEXT NOT NULL,
            kategori_lokasi TEXT NOT NULL,
            status TEXT DEFAULT 'tersedia',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",

        // 3. TRANSACTION: Tabel Laporan / Tiket Maintenance
        "CREATE TABLE IF NOT EXISTS maintenance_reports (
            id_laporan INTEGER PRIMARY KEY AUTOINCREMENT,
            nomor_tiket TEXT UNIQUE NOT NULL,
            user_pelapor_id INTEGER NOT NULL,
            location_id INTEGER NOT NULL,
            judul_masalah TEXT NOT NULL,
            deskripsi TEXT,
            tingkat_prioritas TEXT NOT NULL DEFAULT 'sedang',
            status TEXT NOT NULL DEFAULT 'menunggu_approval',
            waktu_lapor DATETIME DEFAULT CURRENT_TIMESTAMP,
            waktu_selesai DATETIME,
            FOREIGN KEY (user_pelapor_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT
        );",

        // 4. TRANSACTION: Tabel Penugasan Teknisi
        "CREATE TABLE IF NOT EXISTS ticket_assignments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            report_id INTEGER NOT NULL,
            teknisi_id INTEGER NOT NULL,
            ditugaskan_oleh INTEGER NOT NULL,
            waktu_mulai DATETIME,
            waktu_selesai DATETIME,
            catatan_teknisi TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES maintenance_reports(id_laporan) ON DELETE CASCADE,
            FOREIGN KEY (teknisi_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (ditugaskan_oleh) REFERENCES users(id) ON DELETE RESTRICT
        );",

        // 5. TRANSACTION: Tabel Inspeksi Kamar
        "CREATE TABLE IF NOT EXISTS room_inspections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            location_id INTEGER NOT NULL,
            user_inspektur_id INTEGER NOT NULL,
            hasil_inspeksi TEXT NOT NULL,
            catatan TEXT,
            waktu_inspeksi DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
            FOREIGN KEY (user_inspektur_id) REFERENCES users(id) ON DELETE RESTRICT
        );",

        // 6. TRANSACTION: Tabel Manajemen Dokumen (DMS)
        "CREATE TABLE IF NOT EXISTS documents (
            id_dokumen INTEGER PRIMARY KEY AUTOINCREMENT,
            report_id INTEGER,
            nama_file TEXT NOT NULL,
            tipe_file TEXT NOT NULL,
            path_penyimpanan TEXT NOT NULL,
            hash_dokumen TEXT, 
            diunggah_oleh INTEGER NOT NULL,
            waktu_unggah DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES maintenance_reports(id_laporan) ON DELETE CASCADE,
            FOREIGN KEY (diunggah_oleh) REFERENCES users(id) ON DELETE RESTRICT
        );",

        // 7. TRANSACTION: Tabel Audit Logs
        "CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            aksi TEXT NOT NULL,
            target_tabel TEXT,
            target_id INTEGER,
            deskripsi_log TEXT,
            ip_address TEXT,
            waktu_kejadian DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
        );"
    ];

    foreach ($schemas as $sql) {
        $pdo->exec($sql);
    }

    echo "Database SQLite 'database.sqlite' berhasil dibuat dengan struktur relasi yang jelas dan efisien.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
