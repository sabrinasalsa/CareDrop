-- ============================================================
--  CareDrop – Database Schema Lengkap
--  Database : caredrop
--  Charset  : utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS caredrop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE caredrop;

-- ------------------------------------------------------------
--  1. USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap      VARCHAR(150) NOT NULL,
    email             VARCHAR(200) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    no_telp           VARCHAR(20)  DEFAULT NULL,
    alamat            TEXT         DEFAULT NULL,
    role              ENUM('admin','donatur','penerima') NOT NULL DEFAULT 'donatur',
    status_verifikasi ENUM('verified','pending','rejected') NOT NULL DEFAULT 'verified',
    avatar            VARCHAR(255) DEFAULT NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  2. KATALOG KEBUTUHAN
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS katalog_kebutuhan (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    yayasan_id        INT          NOT NULL,
    nama_barang       VARCHAR(200) NOT NULL,
    kategori          VARCHAR(100) DEFAULT NULL,
    urgensi           ENUM('high','med','low') NOT NULL DEFAULT 'med',
    target_butuh      INT          NOT NULL DEFAULT 1,
    jumlah_terkumpul  INT          NOT NULL DEFAULT 0,
    deskripsi         TEXT         DEFAULT NULL,
    aktif             TINYINT(1)   NOT NULL DEFAULT 1,
    status_aktif      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (yayasan_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  3. DONASI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS donasi (
    id                 VARCHAR(30)  NOT NULL PRIMARY KEY,
    donatur_id         INT          NOT NULL,
    katalog_id         INT          NOT NULL,
    qty_donasi         INT          NOT NULL DEFAULT 1,
    deskripsi_kondisi  TEXT         DEFAULT NULL,
    foto_barang        VARCHAR(255) DEFAULT NULL,
    status_donasi      ENUM('menunggu','disetujui','ditolak','dikirim','selesai','dibatalkan')
                       NOT NULL DEFAULT 'menunggu',
    alasan_tolak       TEXT         DEFAULT NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (donatur_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (katalog_id)  REFERENCES katalog_kebutuhan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  4. PENGIRIMAN
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengiriman (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    donasi_id       VARCHAR(30)  NOT NULL UNIQUE,
    kurir           VARCHAR(100) DEFAULT NULL,
    no_resi         VARCHAR(100) DEFAULT NULL,
    tipe_layanan    VARCHAR(50)  DEFAULT 'mandiri',
    kota_asal       VARCHAR(100) DEFAULT NULL,
    kota_tujuan     VARCHAR(100) DEFAULT NULL,
    berat_kg        DECIMAL(6,2) DEFAULT NULL,
    estimasi_ongkir INT          DEFAULT NULL,
    status_kirim    VARCHAR(50)  DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (donasi_id) REFERENCES donasi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  5. BERKAS LEGALITAS (dibuat otomatis oleh upload_legalitas.php,
--     tapi kita definisikan di sini agar terstruktur)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS berkas_legalitas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    yayasan_id  INT          NOT NULL,
    jenis       VARCHAR(80)  NOT NULL,
    nama_file   VARCHAR(255) NOT NULL,
    keterangan  TEXT         DEFAULT NULL,
    status      ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (yayasan_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  DATA AWAL
--  Password semua akun: "password"
--  Hash bcrypt cost-12 dari Laravel/PHP password_hash()
-- ============================================================

-- Users
INSERT INTO users (nama_lengkap, email, password, no_telp, alamat, role, status_verifikasi) VALUES
('Admin CareDrop',
 'admin@caredrop.id',
 '$2y$10$XQU6K2I2RXzORLy.27I0CuRFpspj6fyi3yoJ0PSoLBz6oraPV/PL6',
 '0811-0000-0001', 'Kantor CareDrop, Mataram', 'admin', 'verified'),

('Sabrina Salsabila',
 'sabrina@email.com',
 '$2y$10$XQU6K2I2RXzORLy.27I0CuRFpspj6fyi3yoJ0PSoLBz6oraPV/PL6',
 '0812-3456-7890', 'Jl. Majapahit No. 45, Mataram', 'donatur', 'verified'),

('Andi Wijaya',
 'andi@email.com',
 '$2y$10$XQU6K2I2RXzORLy.27I0CuRFpspj6fyi3yoJ0PSoLBz6oraPV/PL6',
 '0813-9999-8888', 'Jl. Langko No. 22, Mataram', 'donatur', 'verified'),

('Panti Asuhan Al-Ikhlas',
 'alikhlas@yayasan.id',
 '$2y$10$XQU6K2I2RXzORLy.27I0CuRFpspj6fyi3yoJ0PSoLBz6oraPV/PL6',
 '(0370) 632-100', 'Jl. Pejanggik No. 12, Mataram', 'penerima', 'verified'),

('Yayasan Peduli Anak NTB',
 'peduli.anak@yayasan.id',
 '$2y$10$XQU6K2I2RXzORLy.27I0CuRFpspj6fyi3yoJ0PSoLBz6oraPV/PL6',
 '(0370) 741-200', 'Jl. Saleh Sungkar No. 5, Lombok', 'penerima', 'verified');

-- Katalog Kebutuhan (yayasan id=4 dan id=5)
INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul, deskripsi) VALUES
(4, 'Seragam Sekolah SD',      'Pakaian',        'high', 50, 12, 'Seragam merah-putih ukuran anak SD usia 7-12 tahun, kondisi layak pakai.'),
(4, 'Buku Pelajaran SD',       'Alat Tulis',     'high', 80, 30, 'Buku paket kurikulum Merdeka untuk kelas 1-6 SD.'),
(4, 'Sepatu Anak',             'Pakaian',        'med',  40, 8,  'Sepatu sekolah warna hitam ukuran 28-37.'),
(4, 'Tas Sekolah',             'Perlengkapan',   'med',  30, 5,  'Tas ransel untuk anak SD, kondisi baik.'),
(5, 'Selimut Anak',            'Perlengkapan',   'high', 60, 20, 'Selimut hangat ukuran anak-anak.'),
(5, 'Alat Tulis Lengkap',      'Alat Tulis',     'med',  100,45, 'Set pensil, penghapus, penggaris, dan buku tulis.'),
(5, 'Pakaian Layak Pakai',     'Pakaian',        'low',  150,90, 'Pakaian anak usia 5-15 tahun dalam kondisi baik.'),
(5, 'Makanan Non-Perishable',  'Sembako',        'high', 200,60, 'Beras, mie instan, minyak goreng, dan kebutuhan pokok lainnya.');
