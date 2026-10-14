HRMS RECRUITMENT PROTOTYPE v7 — CRUD LOWONGAN, DIVISI, DAN MANAJER

Akun demo:
- manager_it / password
- hr_admin / password
- rafi / password

DESAIN DATABASE:
- users: hanya data autentikasi (id_user, username, password, role, created_at).
- manajer: profil manajer dan FK id_user (UNIQUE) serta id_divisi.
- pelamar: profil pelamar dan FK id_user (UNIQUE).
- divisi: data divisi organisasi.
- lowongan: kebutuhan rekrutmen milik divisi.
- lamaran: pengajuan pelamar per lowongan, termasuk CV.
- tahapan_seleksi dan hasil_seleksi: alur seleksi per lowongan.

Fitur utama:
1. HR Admin dapat melakukan CRUD divisi di Dashboard HR > Kelola Divisi.
2. HR Admin dapat melakukan CRUD akun manajer dan menetapkan divisi melalui Kelola Akun Manajer.
3. HR Admin dapat melakukan CRUD lowongan melalui Dashboard HR > Kelola Lowongan, termasuk mengatur divisi, posisi, kebutuhan, deskripsi, spesifikasi, deadline, status, dan tahapan seleksi.
4. Lowongan yang sudah memiliki lamaran tidak dapat dihapus; status dapat diubah menjadi Ditutup. Tahapan seleksi dikunci setelah ada lamaran agar riwayat tetap konsisten.
5. Data login disimpan di users; profil manajer/pelamar disimpan di tabel masing-masing.
6. Manajer mengajukan kebutuhan lowongan untuk divisinya melalui alur pengajuan terpisah.
7. HR menyetujui/menolak pengajuan manajer melalui Pengajuan Lowongan, lalu dapat melengkapi dan memublikasikan lowongan.
8. Pelamar mendaftar akun, melihat lowongan, dan wajib mengunggah CV saat apply (PDF/DOC/DOCX, maksimal 5 MB).
9. HR dapat membuka CV dari halaman seleksi dan memberi hasil Lulus/Tidak Lulus per tahap.
10. Lamaran ditolak jika gagal pada tahap mana pun dan diterima jika lulus seluruh tahap.

INSTALASI:
1. Ekstrak folder hrms ke C:\xampp\htdocs\hrms (atau sesuaikan nama folder URL).
2. Buat ulang database testing dengan mengimpor database.sql melalui phpMyAdmin/MariaDB.
3. Pastikan folder uploads/cv dapat ditulis oleh PHP.
4. Buka http://localhost/hrms/

PENTING:
- database.sql menjatuhkan (DROP) tabel prototype lama sebelum membuat skema baru. Backup data terlebih dahulu; jangan import ke database berisi data penting.
- Skema v5 BERUBAH dari v4. Jangan sekadar mengganti file PHP di atas database v4. Untuk data v4 yang ingin dipertahankan, perlu migrasi data terpisah.
- Akun demo menggunakan hash SHA-256 untuk konsistensi prototype, bukan rekomendasi untuk sistem produksi.
