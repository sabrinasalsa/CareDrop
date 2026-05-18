# CareDrop

Sistem Layanan Donasi Barang berbasis Web

## Deskripsi
Website ini akan menyediakan fitur publikasi kebutuhan yayasan/posko dan penawaran donasi barang, sehingga donatur dapat dengan mudah menemukan dan menyalurkan bantuan kepada posko atau yayasan yang tepat. Dengan adanya platform ini, diharapkan alur penyaluran donasi menjadi lebih transparan, terstruktur, dan tepat sasaran.

# Team & Roles
| Nama Anggota | Role | Tanggung Jawab |
|---|---|---|
| Sabrina Salsabila | Frontend & Backend Developer | Mendesain tampilan website, mengembangkan fitur sistem, dan mengelola integrasi frontend-backend |
| Mutia Ayu Safitri | Frontend & Backend Developer | Membuat tampilan antarmuka pengguna serta mengembangkan logika sistem dan fitur backend |
| Baiq Sabrina Ramadhani | Frontend Developer | Mendesain UI/UX dan mengembangkan tampilan website agar responsif dan interaktif |

# User / Actor Website & Features (Menu / Sitemap)
```text
CareDrop
│
├── USER (DONATUR)
│   │
│   ├── Landing Page
│   ├── Katalog Kebutuhan Barang
│   ├── Detail Kebutuhan Barang
│   ├── Sign Up
│   ├── Login
│   │
│   └── Dashboard Donatur
│       ├── Mengajukan Tawaran Donasi Barang
│       ├── Upload Bukti/Tawaran Barang
│       ├── Memasukkan Nomor Resi Pengiriman
│       ├── Melacak Status Pengiriman Barang
│       ├── Riwayat Donasi
│       ├── Unduh E-Sertifikat
│       ├── Kelola Profil
│       └── Logout
│
├── YAYASAN / POSKO
│   │
│   ├── Landing Page
│   ├── Informasi Yayasan
│   ├── Daftar Kebutuhan Barang
│   ├── Sign Up
│   ├── Login
│   │
│   └── Dashboard Yayasan
│       ├── Tambah Kebutuhan Barang
│       ├── Edit Kebutuhan Barang
│       ├── Tutup Daftar Kebutuhan
│       ├── Setujui Tawaran Donasi
│       ├── Tolak Tawaran Donasi
│       ├── Konfirmasi Penerimaan Barang
│       ├── Laporan Penerimaan Donasi
│       ├── Kelola Profil Yayasan
│       ├── Upload Berkas Legalitas
│       └── Logout
│
└── ADMIN
    │
    ├── Landing Page
    ├── Login
    │
    └── Dashboard Admin
        ├── Verifikasi Yayasan Baru
        ├── Kelola Kategori Barang
        ├── Kelola Template E-Sertifikat
        ├── Dashboard Analitik
        ├── Statistik Donasi
        ├── Kelola Data Pengguna
        ├── Kelola Data Yayasan
        └── Logout
```
# Tech Stack

| Technology | Fungsi |
|---|---|
| HTML | Membuat struktur halaman website |
| CSS | Mendesain tampilan dan layout website |
| JavaScript | Menambahkan interaktivitas pada website |
| PHP | Mengembangkan backend dan logika sistem |
| MySQL | Mengelola database sistem |
| Apache | Menjalankan web server localhost |
| XAMPP | Local development environment |

# DBMS Configuration

## DBMS
```text
MySQL
```

## Database Name
```text
caredrop
```

## Server Configuration

| Configuration | Value |
|---|---|
| Host | localhost |
| Username | root |
| Server | Apache (XAMPP) |

---

# Table Specification

## 1. users

| Field | Type | Keterangan |
|---|---|---|
| id_user | INT (PK) | ID pengguna |
| nama | VARCHAR(100) | Nama pengguna |
| email | VARCHAR(100) | Email pengguna |
| password | VARCHAR(255) | Password terenkripsi |
| role | ENUM('donatur','yayasan','admin') | Role pengguna |
| telepon | VARCHAR(20) | Nomor telepon |
| alamat | TEXT | Alamat pengguna |
| created_at | TIMESTAMP | Tanggal akun dibuat |

---

## 2. yayasan

| Field | Type | Keterangan |
|---|---|---|
| id_yayasan | INT (PK) | ID yayasan |
| id_user | INT (FK) | Relasi ke tabel users |
| nama_yayasan | VARCHAR(100) | Nama yayasan |
| legalitas | VARCHAR(255) | File legalitas |
| deskripsi | TEXT | Deskripsi yayasan |
| status_verifikasi | ENUM('pending','verified','rejected') | Status verifikasi |

---

## 3. kategori_barang

| Field | Type | Keterangan |
|---|---|---|
| id_kategori | INT (PK) | ID kategori |
| nama_kategori | VARCHAR(100) | Nama kategori barang |

---

## 4. kebutuhan_barang

| Field | Type | Keterangan |
|---|---|---|
| id_kebutuhan | INT (PK) | ID kebutuhan |
| id_yayasan | INT (FK) | Relasi ke yayasan |
| id_kategori | INT (FK) | Relasi ke kategori |
| nama_barang | VARCHAR(100) | Nama barang |
| jumlah | INT | Jumlah kebutuhan |
| deskripsi | TEXT | Deskripsi barang |
| status | ENUM('aktif','ditutup') | Status kebutuhan |

---

## 5. donasi

| Field | Type | Keterangan |
|---|---|---|
| id_donasi | INT (PK) | ID donasi |
| id_user | INT (FK) | Donatur |
| id_kebutuhan | INT (FK) | Kebutuhan terkait |
| jumlah_barang | INT | Jumlah barang donasi |
| status | ENUM('pending','disetujui','ditolak','dikirim','diterima') | Status donasi |
| created_at | TIMESTAMP | Tanggal donasi dibuat |

---

## 6. pengiriman

| Field | Type | Keterangan |
|---|---|---|
| id_pengiriman | INT (PK) | ID pengiriman |
| id_donasi | INT (FK) | Relasi ke donasi |
| nomor_resi | VARCHAR(100) | Nomor resi |
| jasa_kirim | VARCHAR(50) | Jasa ekspedisi |
| status_pengiriman | VARCHAR(50) | Status pengiriman |

---

## 7. sertifikat

| Field | Type | Keterangan |
|---|---|---|
| id_sertifikat | INT (PK) | ID sertifikat |
| id_donasi | INT (FK) | Relasi ke donasi |
| file_sertifikat | VARCHAR(255) | File sertifikat |
| tanggal_terbit | DATE | Tanggal terbit sertifikat |

---

# Database Relations

```text
users -> yayasan
yayasan -> kebutuhan_barang
kategori_barang -> kebutuhan_barang
users -> donasi
kebutuhan_barang -> donasi
donasi -> pengiriman
donasi -> sertifikat
```

