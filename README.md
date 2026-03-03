# 🏥 Sistem Informasi Rumah Sakit - Sehat Sentosa

Website rumah sakit lengkap dengan fitur booking appointment, manajemen dokter, dan admin panel.

## 🎯 Fitur Utama

### Fitur User
- ✅ Login/Register
- ✅ Lihat daftar dokter dan departemen
- ✅ Booking appointment dengan dokter
- ✅ Lihat riwayat appointment
- ✅ Hubungi rumah sakit
- ✅ Real-time appointment status

### Fitur Admin
- ✅ Dashboard dengan statistik real-time
- ✅ Kelola dokter (CRUD)
- ✅ Kelola departemen (CRUD)
- ✅ Kelola layanan (CRUD)
- ✅ Kelola appointment pasien
- ✅ Kelola user
- ✅ Balasan pesan dari pasien
- ✅ Real-time appointment status updates

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript
- **Real-time Updates**: JavaScript Fetch API + PHP JSON API

## 📁 Struktur Folder

```
Rumah Sakit/
├── database/
│   ├── config.php          # Konfigurasi database
│   └── create_db.php       # Script membuat database
├── admin/
│   ├── dashboard.php       # Dashboard admin
│   ├── doctors.php         # Kelola dokter
│   ├── departments.php     # Kelola departemen
│   ├── services.php        # Kelola layanan
│   ├── appointments.php    # Kelola appointment
│   ├── users.php           # Kelola user
│   └── messages.php        # Kelola pesan
├── user/
│   └── index.php          # Halaman utama user
├── api/
│   ├── handler.php        # API endpoints
│   └── client.js          # JavaScript client library
├── includes/
│   └── (untuk file utilitas)
├── auth.php               # Halaman login/register
├── styles.css             # CSS global
├── script.js              # JavaScript global
├── index.html             # Halaman awal (redirect)
└── README.md              # File ini
```

## 🚀 Instalasi & Setup

### 1. Persiapan
- Pastikan XAMPP sudah terinstall dan berjalan
- Pastikan MySQL service aktif

### 2. Download/Clone Project
```bash
# Copy project ke folder htdocs
cp -r "Rumah Sakit" C:/xampp/htdocs/
```

### 3. Buat Database
- Buka browser: http://localhost/Rumah%20Sakit/database/create_db.php
- Tunggu sampai muncul pesan "Database berhasil dibuat"
- Catat login admin yang ditampilkan

### 4. Konfigurasi Database (Jika Perlu)
Edit file `database/config.php` jika host/user/password berbeda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rumah_sakit_db');
```

## 🔐 Login Credentials

### Admin
- Email: `admin@sehat-sentosa.com`
- Password: `admin123`

### Dokter (Contoh)
- Email: `ahmad@sehat-sentosa.com`
- Password: `doctor123`

### User Baru
Bisa register di halaman login

## 📝 Cara Penggunaan

### Untuk User
1. **Register/Login** di http://localhost/Rumah%20Sakit/auth.php
2. **Jelajahi Website**:
   - Home: Informasi rumah sakit
   - About: Tentang kami
   - Departments: Daftar departemen
   - Services: Layanan kesehatan
   - Doctors: Daftar dokter
   - Contact: Hubungi kami

3. **Booking Appointment**:
   - Pilih dokter yang ingin dikunjungi
   - Pilih layanan (opsional)
   - Pilih tanggal & waktu
   - Submit

4. **Lihat Appointment**:
   - Menu "Appointments" untuk melihat riwayat

### Untuk Admin
1. **Login** dengan akun admin
2. **Akses Dashboard** di http://localhost/Rumah%20Sakit/admin/dashboard.php
3. **Kelola Data**:
   - **Dashboard**: Lihat statistik & appointment terbaru
   - **Dokter**: Tambah/Edit/Hapus dokter
   - **Departemen**: Kelola departemen
   - **Layanan**: Kelola layanan & harga
   - **Appointment**: Update status appointment pasien
   - **User**: Lihat daftar user terdaftar
   - **Pesan**: Baca & balas pesan dari pasien

## 🔄 Real-Time Features

### Dashboard Admin
- Update statistik appointment setiap 30 detik
- Real-time unread messages counter setiap 60 detik
- Auto-refresh dashboard setiap 30 detik

### User Appointments
- Appointment status update real-time
- Notifikasi status appointment

## 📊 Database Schema

### Tabel Utama
1. **users**: User, admin, dan dokter
2. **departments**: Departemen kesehatan
3. **doctors**: Informasi dokter
4. **services**: Layanan kesehatan
5. **appointments**: Booking appointment pasien
6. **messages**: Pesan/kontak dari pengunjung

## 🎨 Design Features

- **Responsive Design**: Optimal di desktop, tablet, dan mobile
- **User-Friendly UI**: Warna tema hijau (kesehatan) & biru (profesional)
- **Smooth Animations**: Transisi dan hover effects
- **Modern Layout**: Grid-based responsive layout
- **Accessible Forms**: Form validation dan error handling

## 🔒 Security Features

- **Password Hashing**: Menggunakan bcrypt
- **Session Management**: Session-based authentication
- **Role-Based Access**: User, Doctor, Admin roles
- **SQL Injection Prevention**: Prepared statements
- **Input Validation**: Server-side & client-side validation

## 🐛 Troubleshooting

### Database Connection Error
- Pastikan MySQL service berjalan
- Cek konfigurasi di `database/config.php`
- Jalankan script `database/create_db.php`

### Page Not Found (404)
- Pastikan folder path benar
- Periksa URL: http://localhost/Rumah%20Sakit/

### Blank Page
- Cek error di browser console (F12)
- Cek PHP error log di xampp/logs/

### Appointment Tidak Tersimpan
- Pastikan database terkoneksi
- Cek file permissions di folder data
- Lihat error message di halaman

## 📧 API Endpoints

### GET Requests
- `/api/handler.php?action=get_user_appointments` - Get user appointments
- `/api/handler.php?action=get_appointment_stats` - Get stats (admin)
- `/api/handler.php?action=get_unread_messages` - Get unread count
- `/api/handler.php?action=get_doctors` - Get doctors list
- `/api/handler.php?action=get_services` - Get services list

### POST Requests
- `/api/handler.php?action=book_appointment` - Create appointment
- `/api/handler.php?action=update_appointment_status` - Update status (admin)

## 📚 Fitur Lanjutan (To-Do)

- [ ] Email notification
- [ ] SMS reminder
- [ ] Payment gateway integration
- [ ] Report generation
- [ ] Medical records storage
- [ ] Video consultation
- [ ] Rating & review dokter

## 📞 Support

Untuk pertanyaan atau masalah, silakan hubungi:
- Email: info@sehat-sentosa.com
- Telepon: (021) 123-4567
- Website: www.sehat-sentosa.com

## 📄 License

© 2024 Rumah Sakit Sehat Sentosa. All rights reserved.

---

**Catatan**: Ini adalah sistem demo. Untuk production, tambahkan fitur security dan payment gateway yang sesuai.
