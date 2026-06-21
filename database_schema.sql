PRAGMA foreign_keys = ON;

-- =======================================================
-- 1. TABEL MASTER
-- =======================================================

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nik TEXT UNIQUE NOT NULL,
    nama_lengkap TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL, -- 'manager_hr', 'manager_ops', 'manager_maintenance', 'gm', 'director', 'teknisi', 'housekeeping'
    status_aktif INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_lokasi TEXT NOT NULL,
    kategori_lokasi TEXT NOT NULL,
    status TEXT DEFAULT 'tersedia',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =======================================================
-- 2. TABEL TRANSAKSI (TERIKAT)
-- =======================================================

CREATE TABLE IF NOT EXISTS maintenance_reports (
    id_laporan INTEGER PRIMARY KEY AUTOINCREMENT,
    nomor_tiket TEXT UNIQUE NOT NULL,
    user_pelapor_id INTEGER NOT NULL,
    location_id INTEGER NOT NULL,
    judul_masalah TEXT NOT NULL,
    deskripsi TEXT,
    tingkat_prioritas TEXT NOT NULL DEFAULT 'sedang', -- 'rendah', 'sedang', 'tinggi'
    status TEXT NOT NULL DEFAULT 'menunggu_approval', -- 'menunggu_approval', 'disetujui', 'dikerjakan', 'selesai', 'ditolak'
    waktu_lapor DATETIME DEFAULT CURRENT_TIMESTAMP,
    waktu_selesai DATETIME,
    FOREIGN KEY (user_pelapor_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS ticket_assignments (
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
);

CREATE TABLE IF NOT EXISTS room_inspections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    location_id INTEGER NOT NULL,
    user_inspektur_id INTEGER NOT NULL,
    hasil_inspeksi TEXT NOT NULL,
    catatan TEXT,
    waktu_inspeksi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_inspektur_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- =======================================================
-- 3. TABEL MANAJEMEN DOKUMEN (DMS)
-- =======================================================

CREATE TABLE IF NOT EXISTS documents (
    id_dokumen INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER, -- Bisa NULL jika dokumen tidak terikat ke tiket spesifik
    nama_file TEXT NOT NULL,
    tipe_file TEXT NOT NULL, -- 'foto_kerusakan', 'bukti_nota', 'tanda_terima'
    path_penyimpanan TEXT NOT NULL,
    hash_dokumen TEXT, -- Untuk keamanan integritas dokumen digital
    diunggah_oleh INTEGER NOT NULL,
    waktu_unggah DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES maintenance_reports(id_laporan) ON DELETE CASCADE,
    FOREIGN KEY (diunggah_oleh) REFERENCES users(id) ON DELETE RESTRICT
);

-- =======================================================
-- 4. TABEL LOG AUDIT (KEAMANAN)
-- =======================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    aksi TEXT NOT NULL,
    target_tabel TEXT,
    target_id INTEGER,
    deskripsi_log TEXT,
    ip_address TEXT,
    waktu_kejadian DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- =======================================================
-- 5. TABEL TOKEN OTORISASI
-- =======================================================

CREATE TABLE IF NOT EXISTS auth_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_used INTEGER DEFAULT 0
);

-- =======================================================
-- 6. TABEL MANAJEMEN STOK (INVENTORY)
-- =======================================================

CREATE TABLE IF NOT EXISTS inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kode_barang TEXT UNIQUE NOT NULL,
    nama_barang TEXT NOT NULL,
    stok_tersedia INTEGER NOT NULL DEFAULT 0,
    satuan TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    jumlah_digunakan INTEGER NOT NULL,
    waktu_penggunaan DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES maintenance_reports(id_laporan) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT
);
