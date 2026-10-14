CREATE DATABASE IF NOT EXISTS hrms_recruitment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrms_recruitment;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS hasil_seleksi;
DROP TABLE IF EXISTS lamaran;
DROP TABLE IF EXISTS tahapan_seleksi;
DROP TABLE IF EXISTS manajer;
DROP TABLE IF EXISTS pelamar;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS lowongan;
DROP TABLE IF EXISTS divisi;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE divisi (
    id_divisi INT AUTO_INCREMENT PRIMARY KEY,
    nama_divisi VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager','hr','pelamar') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE manajer (
    id_manajer INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL UNIQUE,
    id_divisi INT NOT NULL,
    nama VARCHAR(150) NOT NULL,
    CONSTRAINT fk_manajer_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_manajer_divisi FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE pelamar (
    id_pelamar INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    no_hp VARCHAR(30) NOT NULL,
    alamat TEXT NOT NULL,
    tanggal_lahir DATE NOT NULL,
    CONSTRAINT fk_pelamar_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lowongan (
    id_lowongan INT AUTO_INCREMENT PRIMARY KEY,
    id_divisi INT NOT NULL,
    judul_lowongan VARCHAR(150) NOT NULL,
    job_description TEXT NOT NULL,
    job_specification TEXT NOT NULL,
    jumlah_kebutuhan INT NOT NULL,
    tanggal_pengajuan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Diajukan','Disetujui','Ditolak','Dipublikasikan','Ditutup') NOT NULL DEFAULT 'Diajukan',
    tanggal_publish DATETIME NULL,
    deadline DATE NULL,
    CONSTRAINT fk_lowongan_divisi FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tahapan_seleksi (
    id_tahapan INT AUTO_INCREMENT PRIMARY KEY,
    id_lowongan INT NOT NULL,
    nama_tahapan VARCHAR(100) NOT NULL,
    urutan INT NOT NULL,
    deskripsi TEXT NULL,
    UNIQUE KEY uq_tahapan (id_lowongan, urutan),
    CONSTRAINT fk_tahapan_lowongan FOREIGN KEY (id_lowongan) REFERENCES lowongan(id_lowongan) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lamaran (
    id_lamaran INT AUTO_INCREMENT PRIMARY KEY,
    id_lowongan INT NOT NULL,
    id_pelamar INT NOT NULL,
    id_tahapan_aktif INT NULL,
    cv_path VARCHAR(255) NOT NULL,
    cv_original_name VARCHAR(255) NOT NULL,
    tanggal_lamar DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status_lamaran ENUM('Diproses','Diterima','Ditolak') NOT NULL DEFAULT 'Diproses',
    UNIQUE KEY uq_lamaran (id_lowongan, id_pelamar),
    CONSTRAINT fk_lamaran_lowongan FOREIGN KEY (id_lowongan) REFERENCES lowongan(id_lowongan) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lamaran_pelamar FOREIGN KEY (id_pelamar) REFERENCES pelamar(id_pelamar) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lamaran_tahapan FOREIGN KEY (id_tahapan_aktif) REFERENCES tahapan_seleksi(id_tahapan) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE hasil_seleksi (
    id_hasil INT AUTO_INCREMENT PRIMARY KEY,
    id_lamaran INT NOT NULL,
    id_tahapan INT NOT NULL,
    hasil ENUM('Lulus','Tidak Lulus') NOT NULL,
    catatan TEXT NULL,
    tanggal_seleksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hasil (id_lamaran, id_tahapan),
    CONSTRAINT fk_hasil_lamaran FOREIGN KEY (id_lamaran) REFERENCES lamaran(id_lamaran) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_hasil_tahapan FOREIGN KEY (id_tahapan) REFERENCES tahapan_seleksi(id_tahapan) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO divisi (nama_divisi) VALUES ('Information Technology'), ('Finance'), ('Marketing');
INSERT INTO users (username,password,role) VALUES
('manager_it',SHA2('password',256),'manager'),
('hr_admin',SHA2('password',256),'hr'),
('rafi',SHA2('password',256),'pelamar');
INSERT INTO manajer (id_user,id_divisi,nama) VALUES (1,1,'Manager Information Technology');
INSERT INTO pelamar (id_user,nama,email,no_hp,alamat,tanggal_lahir) VALUES (3,'Muhammad Rafi','rafi@gmail.com','081234567890','Jakarta','2006-05-12');
INSERT INTO lowongan (id_divisi,judul_lowongan,job_description,job_specification,jumlah_kebutuhan,status,deadline) VALUES
(1,'Web Developer','Mengembangkan dan memelihara aplikasi web perusahaan.','Menguasai PHP, MySQL, HTML, CSS, JavaScript.',2,'Disetujui',DATE_ADD(CURDATE(),INTERVAL 30 DAY));
INSERT INTO tahapan_seleksi (id_lowongan,nama_tahapan,urutan,deskripsi) VALUES
(1,'Seleksi Administrasi',1,'Pemeriksaan CV dan dokumen.'),
(1,'Tes Teknis',2,'Tes kemampuan teknis.'),
(1,'Interview',3,'Wawancara HR dan user.');
