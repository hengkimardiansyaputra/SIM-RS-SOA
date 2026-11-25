-- ============================================================================
-- DATABASE: db_rumah_sakit_soa
-- SERVER B (Server Basisdata dan Web Service)
-- Studi Kasus: Sistem Informasi Manajemen Pasien Rumah Sakit Berbasis SOA & RBAC
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `db_rumah_sakit_soa`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_rumah_sakit_soa`;

-- ----------------------------------------------------------------------------
-- 1. Tabel: roles (Daftar Role untuk Role-Based Access Control / RBAC)
-- ----------------------------------------------------------------------------
DROP VIEW IF EXISTS `v_pasien_lengkap`;
DROP VIEW IF EXISTS `v_dokter_lengkap`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `pasien`;
DROP TABLE IF EXISTS `dokter`;
DROP TABLE IF EXISTS `pengguna`;
DROP TABLE IF EXISTS `asuransi`;
DROP TABLE IF EXISTS `poliklinik`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id_role` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_role` varchar(30) NOT NULL,
  `nama_role` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `uk_roles_kode` (`kode_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id_role`, `kode_role`, `nama_role`, `keterangan`) VALUES
(1, 'ADMIN', 'Administrator RS', 'Mengelola seluruh data master, poliklinik, dokter, pasien, asuransi, pengguna, dan laporan'),
(2, 'PETUGAS_POLI', 'Petugas Poliklinik', 'Mengelola data pasien dan layanan pada Poliklinik yang menjadi kewenangannya'),
(3, 'DOKTER', 'Dokter', 'Dokter sebagai pengguna aplikasi yang melihat dan menangani pasien pada polikliniknya'),
(4, 'DIREKTUR', 'Manajemen / Direktur RS', 'Melihat dashboard eksekutif dan laporan rekapitulasi seluruh Rumah Sakit'),
(5, 'PASIEN', 'Pasien', 'Pasien sebagai pengguna aplikasi yang dapat melihat profil rekam medis dan status asuransinya sendiri');

-- ----------------------------------------------------------------------------
-- 2. Tabel: poliklinik (Data Unit Layanan Poliklinik Rumah Sakit)
-- ----------------------------------------------------------------------------
CREATE TABLE `poliklinik` (
  `id_poliklinik` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_poliklinik` varchar(20) NOT NULL,
  `nama_poliklinik` varchar(150) NOT NULL,
  `gedung_lantai` varchar(100) DEFAULT NULL,
  `jam_operasional` varchar(100) DEFAULT '08:00 - 16:00 WIB',
  `keterangan` text DEFAULT NULL,
  `status_aktif` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_poliklinik`),
  UNIQUE KEY `uk_poliklinik_kode` (`kode_poliklinik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `poliklinik` (`id_poliklinik`, `kode_poliklinik`, `nama_poliklinik`, `gedung_lantai`, `jam_operasional`, `keterangan`, `status_aktif`) VALUES
(1, 'POLI-UMUM', 'Poliklinik Umum', 'Gedung A - Lantai 1', '08:00 - 20:00 WIB', 'Pelayanan pemeriksaan kesehatan umum dan rujukan dasar', 'Aktif'),
(2, 'POLI-ANAK', 'Poliklinik Kesehatan Anak', 'Gedung A - Lantai 2', '08:00 - 16:00 WIB', 'Pelayanan kesehatan bayi, balita, anak, dan imunisasi', 'Aktif'),
(3, 'POLI-DALAM', 'Poliklinik Penyakit Dalam', 'Gedung B - Lantai 1', '08:00 - 16:00 WIB', 'Pelayanan spesialis penyakit dalam (internis) dan metabolik', 'Aktif'),
(4, 'POLI-GIGI', 'Poliklinik Gigi & Mulut', 'Gedung B - Lantai 2', '08:00 - 15:00 WIB', 'Pelayanan kesehatan gigi, mulut, dan ortodonti', 'Aktif'),
(5, 'POLI-JANTUNG', 'Poliklinik Jantung & Pembuluh Darah', 'Gedung C - Lantai 1', '09:00 - 15:00 WIB', 'Pelayanan kardiologi, EKG, dan rehabilitasi jantung', 'Aktif');

-- ----------------------------------------------------------------------------
-- 3. Tabel: asuransi (Data Penjamin / Asuransi Pasien)
-- ----------------------------------------------------------------------------
CREATE TABLE `asuransi` (
  `id_asuransi` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_asuransi` varchar(20) NOT NULL,
  `nama_asuransi` varchar(150) NOT NULL,
  `jenis_asuransi` enum('BPJS Kesehatan','Asuransi Swasta','Perusahaan/Korporasi','Jaminan Pemerintah') NOT NULL DEFAULT 'BPJS Kesehatan',
  `persen_tanggungan` decimal(5,2) NOT NULL DEFAULT 100.00,
  `plafon_maksimal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `telepon_callcenter` varchar(50) DEFAULT NULL,
  `status_aktif` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_asuransi`),
  UNIQUE KEY `uk_asuransi_kode` (`kode_asuransi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `asuransi` (`id_asuransi`, `kode_asuransi`, `nama_asuransi`, `jenis_asuransi`, `persen_tanggungan`, `plafon_maksimal`, `telepon_callcenter`, `status_aktif`) VALUES
(1, 'ASR-BPJS1', 'BPJS Kesehatan - Kelas 1', 'BPJS Kesehatan', 100.00, 50000000.00, '165', 'Aktif'),
(2, 'ASR-BPJS2', 'BPJS Kesehatan - Kelas 2', 'BPJS Kesehatan', 100.00, 35000000.00, '165', 'Aktif'),
(3, 'ASR-BPJS3', 'BPJS Kesehatan - Kelas 3 (PBI/Mandiri)', 'BPJS Kesehatan', 100.00, 25000000.00, '165', 'Aktif'),
(4, 'ASR-PRU', 'Prudential HealthCare Shield', 'Asuransi Swasta', 90.00, 100000000.00, '1500085', 'Aktif'),
(5, 'ASR-INHEALTH', 'Mandiri Inhealth Gold', 'Perusahaan/Korporasi', 95.00, 75000000.00, '14078', 'Aktif');

-- ----------------------------------------------------------------------------
-- 4. Tabel: pengguna (Akun Pengguna Aplikasi Berbasis RBAC)
--    Password default semua akun contoh: password
-- ----------------------------------------------------------------------------
CREATE TABLE `pengguna` (
  `id_pengguna` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_role` smallint(5) UNSIGNED NOT NULL,
  `id_poliklinik` bigint(20) UNSIGNED DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(200) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `status_aktif` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `uk_pengguna_username` (`username`),
  KEY `idx_pengguna_role` (`id_role`),
  KEY `idx_pengguna_poliklinik` (`id_poliklinik`),
  CONSTRAINT `fk_pengguna_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pengguna_poliklinik` FOREIGN KEY (`id_poliklinik`) REFERENCES `poliklinik` (`id_poliklinik`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hash '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' = 'password'
INSERT INTO `pengguna` (`id_pengguna`, `id_role`, `id_poliklinik`, `username`, `password_hash`, `nama_lengkap`, `email`, `no_hp`, `status_aktif`) VALUES
(1, 1, NULL, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator SIMRS', 'admin@rs-umb.ac.id', '08117300001', 'Aktif'),
(2, 2, 1, 'petugas.umum', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Poliklinik Umum', 'poli.umum@rs-umb.ac.id', '08117300002', 'Aktif'),
(3, 4, NULL, 'direktur', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. H. Ahmad Fauzi, M.Kes (Direktur RS)', 'direktur@rs-umb.ac.id', '08117300003', 'Aktif'),
(4, 3, 1, 'dr.andi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. Andi Pratama', 'dr.andi@rs-umb.ac.id', '08127300101', 'Aktif'),
(5, 3, 3, 'dr.siti', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dr. Siti Rahmawati, Sp.PD', 'dr.siti@rs-umb.ac.id', '08127300102', 'Aktif'),
(6, 5, 1, 'RM-2026-0001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hengki Mardian Syaputra', 'hengki@student.umb.ac.id', '082177889900', 'Aktif'),
(7, 5, 3, 'RM-2026-0002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rina Puspita Sari', 'rina.puspita@gmail.com', '085266778899', 'Aktif');

-- ----------------------------------------------------------------------------
-- 5. Tabel: dokter (Dokter Bisa Menjadi Pengguna Aplikasi via FK id_pengguna)
-- ----------------------------------------------------------------------------
CREATE TABLE `dokter` (
  `id_dokter` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengguna` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Nullable: diisi bila Dokter memiliki akun Pengguna aplikasi',
  `id_poliklinik` bigint(20) UNSIGNED NOT NULL,
  `nip_dokter` varchar(30) NOT NULL,
  `no_sip` varchar(50) NOT NULL,
  `nama_dokter` varchar(200) NOT NULL,
  `spesialisasi` varchar(150) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `jadwal_praktik` varchar(150) DEFAULT NULL,
  `status_dokter` enum('Aktif','Cuti','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_dokter`),
  UNIQUE KEY `uk_dokter_nip` (`nip_dokter`),
  UNIQUE KEY `uk_dokter_pengguna` (`id_pengguna`),
  KEY `idx_dokter_poliklinik` (`id_poliklinik`),
  CONSTRAINT `fk_dokter_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dokter_poliklinik` FOREIGN KEY (`id_poliklinik`) REFERENCES `poliklinik` (`id_poliklinik`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dokter` (`id_dokter`, `id_pengguna`, `id_poliklinik`, `nip_dokter`, `no_sip`, `nama_dokter`, `spesialisasi`, `jenis_kelamin`, `no_hp`, `email`, `jadwal_praktik`, `status_dokter`) VALUES
(1, 4, 1, '198805122015041001', '503/001/SIP-DOK/2026', 'dr. Andi Pratama', 'Dokter Umum', 'L', '08127300101', 'dr.andi@rs-umb.ac.id', 'Senin - Jumat (08:00 - 14:00)', 'Aktif'),
(2, 5, 3, '198511202012122002', '503/002/SIP-DOK/2026', 'dr. Siti Rahmawati, Sp.PD', 'Spesialis Penyakit Dalam', 'P', '08127300102', 'dr.siti@rs-umb.ac.id', 'Senin - Kamis (09:00 - 15:00)', 'Aktif'),
(3, NULL, 2, '199003152018012003', '503/003/SIP-DOK/2026', 'dr. Maya Kusuma, Sp.A', 'Spesialis Anak', 'P', '08127300103', 'dr.maya@rs-umb.ac.id', 'Selasa - Sabtu (08:00 - 14:00)', 'Aktif'),
(4, NULL, 4, '199207082019031004', '503/004/SIP-DOK/2026', 'drg. Budi Santoso', 'Dokter Gigi', 'L', '08127300104', 'drg.budi@rs-umb.ac.id', 'Senin - Jumat (08:00 - 15:00)', 'Aktif'),
(5, NULL, 5, '198301252010011005', '503/005/SIP-DOK/2026', 'dr. Hendra Wijaya, Sp.JP', 'Spesialis Jantung & Pembuluh Darah', 'L', '08127300105', 'dr.hendra@rs-umb.ac.id', 'Rabu - Jumat (09:00 - 14:00)', 'Aktif');

-- ----------------------------------------------------------------------------
-- 6. Tabel: pasien
--    - Pasien Bisa Menjadi Pengguna Aplikasi (via FK id_pengguna nullable)
--    - Pasien Bisa Menggunakan Asuransi (via FK id_asuransi nullable; NULL = Umum)
-- ----------------------------------------------------------------------------
CREATE TABLE `pasien` (
  `id_pasien` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengguna` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Nullable: diisi bila Pasien terdaftar sebagai Pengguna Aplikasi',
  `id_poliklinik` bigint(20) UNSIGNED NOT NULL,
  `id_dokter` bigint(20) UNSIGNED DEFAULT NULL,
  `id_asuransi` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Nullable: diisi bila Pasien menggunakan Asuransi, NULL jika Umum',
  `no_rm` varchar(30) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `no_kartu_asuransi` varchar(50) DEFAULT NULL,
  `nama_pasien` varchar(200) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date NOT NULL,
  `golongan_darah` enum('A','B','AB','O','-') NOT NULL DEFAULT '-',
  `no_hp` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `keluhan_diagnosa` text DEFAULT NULL,
  `tanggal_daftar` date NOT NULL,
  `status_pasien` enum('Rawat Jalan','Rawat Inap','Selesai','Rujuk') NOT NULL DEFAULT 'Rawat Jalan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pasien`),
  UNIQUE KEY `uk_pasien_no_rm` (`no_rm`),
  UNIQUE KEY `uk_pasien_nik` (`nik`),
  UNIQUE KEY `uk_pasien_pengguna` (`id_pengguna`),
  KEY `idx_pasien_poliklinik` (`id_poliklinik`),
  KEY `idx_pasien_dokter` (`id_dokter`),
  KEY `idx_pasien_asuransi` (`id_asuransi`),
  CONSTRAINT `fk_pasien_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pasien_poliklinik` FOREIGN KEY (`id_poliklinik`) REFERENCES `poliklinik` (`id_poliklinik`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pasien_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pasien_asuransi` FOREIGN KEY (`id_asuransi`) REFERENCES `asuransi` (`id_asuransi`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pasien` (`id_pasien`, `id_pengguna`, `id_poliklinik`, `id_dokter`, `id_asuransi`, `no_rm`, `nik`, `no_kartu_asuransi`, `nama_pasien`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `golongan_darah`, `no_hp`, `alamat`, `keluhan_diagnosa`, `tanggal_daftar`, `status_pasien`) VALUES
(1, 6, 1, 1, 1, 'RM-2026-0001', '1771021505040001', '0001283910211', 'Hengki Mardian Syaputra', 'L', 'Bengkulu', '2004-05-15', 'O', '082177889900', 'Jl. Bali, Kampung Bali, Kota Bengkulu', 'Pemeriksaan kesehatan umum & observasi demam', '2026-11-10', 'Rawat Jalan'),
(2, 7, 3, 2, 4, 'RM-2026-0002', '1771042208980002', 'PRU-99283110', 'Rina Puspita Sari', 'P', 'Curup', '1998-08-22', 'A', '085266778899', 'Jl. S. Parman No. 45, Padang Jati, Bengkulu', 'Kontrol rutin hipertensi dan profil lipid', '2026-11-12', 'Rawat Jalan'),
(3, NULL, 2, 3, 2, 'RM-2026-0003', '1771011011200003', '0001928374655', 'Muhammad Raffi Pratama', 'L', 'Bengkulu', '2020-11-10', 'B', '081367889911', 'Jl. Danau Dendam Tak Sudah, Bengkulu', 'Imunisasi lanjutan dan konsultasi gizi anak', '2026-11-14', 'Selesai'),
(4, NULL, 4, 4, NULL, 'RM-2026-0004', '1771030503950004', NULL, 'Dimas Anggara Putra', 'L', 'Manna', '1995-03-05', 'AB', '082288990011', 'Jl. Hibrida Raya No. 12, Sidomulyo, Bengkulu', 'Pembersihan karang gigi (scaling) dan tambal gigi', '2026-11-15', 'Selesai'),
(5, NULL, 5, 5, 5, 'RM-2026-0005', '1771051907750005', 'MIH-77819201', 'Hj. Ratna Dewi', 'P', 'Argamakmur', '1975-07-19', 'O', '081173991122', 'Jl. Ahmad Yani No. 88, Kebun Ros, Bengkulu', 'Observasi nyeri dada dan pemeriksaan EKG', '2026-11-16', 'Rawat Inap');

-- ----------------------------------------------------------------------------
-- 7. Tabel: audit_log (Pencatatan Aktivitas Sistem & Web Service)
-- ----------------------------------------------------------------------------
CREATE TABLE `audit_log` (
  `id_audit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengguna` bigint(20) UNSIGNED DEFAULT NULL,
  `tabel_nama` varchar(100) NOT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `aksi` enum('LOGIN','LOGOUT','INSERT','UPDATE','DELETE','VIEW') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `waktu` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_audit`),
  KEY `idx_audit_pengguna` (`id_pengguna`),
  KEY `idx_audit_waktu` (`waktu`),
  CONSTRAINT `fk_audit_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_log` (`id_pengguna`, `tabel_nama`, `record_id`, `aksi`, `ip_address`, `keterangan`) VALUES
(1, 'pengguna', '1', 'LOGIN', '127.0.0.1', 'Login Administrator SIMRS melalui Web Service Server B');

-- ----------------------------------------------------------------------------
-- 8. View: v_pasien_lengkap & v_dokter_lengkap (Untuk Kebutuhan Laporan SOA)
-- ----------------------------------------------------------------------------
CREATE VIEW `v_pasien_lengkap` AS
SELECT
  ps.`id_pasien`,
  ps.`no_rm`,
  ps.`nik`,
  ps.`nama_pasien`,
  ps.`jenis_kelamin`,
  CASE WHEN ps.`jenis_kelamin` = 'L' THEN 'Laki-laki' ELSE 'Perempuan' END AS `jenis_kelamin_text`,
  ps.`tempat_lahir`,
  ps.`tanggal_lahir`,
  ps.`golongan_darah`,
  ps.`no_hp`,
  ps.`alamat`,
  ps.`keluhan_diagnosa`,
  ps.`tanggal_daftar`,
  ps.`status_pasien`,
  ps.`id_poliklinik`,
  pl.`kode_poliklinik`,
  pl.`nama_poliklinik`,
  ps.`id_dokter`,
  d.`nama_dokter`,
  d.`spesialisasi`,
  ps.`id_asuransi`,
  a.`kode_asuransi`,
  a.`nama_asuransi`,
  a.`jenis_asuransi`,
  a.`persen_tanggungan`,
  ps.`no_kartu_asuransi`,
  CASE WHEN ps.`id_asuransi` IS NOT NULL THEN 'Peserta Asuransi' ELSE 'Umum / Mandiri' END AS `kategori_pembiayaan`,
  ps.`id_pengguna`,
  pg.`username` AS `username_akun`
FROM `pasien` ps
INNER JOIN `poliklinik` pl ON ps.`id_poliklinik` = pl.`id_poliklinik`
LEFT JOIN `dokter` d ON ps.`id_dokter` = d.`id_dokter`
LEFT JOIN `asuransi` a ON ps.`id_asuransi` = a.`id_asuransi`
LEFT JOIN `pengguna` pg ON ps.`id_pengguna` = pg.`id_pengguna`;

CREATE VIEW `v_dokter_lengkap` AS
SELECT
  d.`id_dokter`,
  d.`nip_dokter`,
  d.`no_sip`,
  d.`nama_dokter`,
  d.`spesialisasi`,
  d.`jenis_kelamin`,
  d.`no_hp`,
  d.`email`,
  d.`jadwal_praktik`,
  d.`status_dokter`,
  d.`id_poliklinik`,
  pl.`kode_poliklinik`,
  pl.`nama_poliklinik`,
  d.`id_pengguna`,
  pg.`username` AS `username_akun`,
  pg.`status_aktif` AS `status_akun`
FROM `dokter` d
INNER JOIN `poliklinik` pl ON d.`id_poliklinik` = pl.`id_poliklinik`
LEFT JOIN `pengguna` pg ON d.`id_pengguna` = pg.`id_pengguna`;

COMMIT;
