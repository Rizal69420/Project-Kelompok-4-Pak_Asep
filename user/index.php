<?php
session_start();
require_once '../database/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php');
    exit();
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../auth.php');
    exit();
}

// Get page from request
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Get data for different pages
if ($page == 'home' || $page == 'about' || $page == 'departments' || $page == 'services' || $page == 'doctors' || $page == 'contact') {
    // These pages exist
} else {
    $page = 'home';
}

// Fetch departments
$departments = $conn->query("SELECT * FROM departments WHERE is_active = 1");

// Fetch doctors
$doctors = $conn->query("
    SELECT d.*, dept.name as dept_name, u.name as user_name
    FROM doctors d
    LEFT JOIN departments dept ON d.department_id = dept.id
    LEFT JOIN users u ON d.user_id = u.id
    WHERE d.is_active = 1
    ORDER BY d.department_id
");

// Fetch services
$services = $conn->query("
    SELECT s.*, dept.name as dept_name
    FROM services s
    LEFT JOIN departments dept ON s.department_id = dept.id
    WHERE s.is_active = 1
");

// Handle appointment booking
$book_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $notes = $_POST['notes'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    if (empty($doctor_id) || empty($appointment_date)) {
        $book_message = '<div class="alert alert-danger">Harap isi semua field yang diperlukan!</div>';
    } else {
        $sql = "INSERT INTO appointments (user_id, doctor_id, service_id, appointment_date, notes) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $user_id, $doctor_id, $service_id, $appointment_date, $notes);
        
        if ($stmt->execute()) {
            $book_message = '<div class="alert alert-success">✓ Appointment berhasil dibuat! Anda akan dihubungi untuk konfirmasi.</div>';
        } else {
            $book_message = '<div class="alert alert-danger">Terjadi kesalahan saat membuat appointment!</div>';
        }
        $stmt->close();
    }
}

// Handle contact form
$contact_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $contact_message = '<div class="alert alert-danger">Harap isi semua field!</div>';
    } else {
        $sql = "INSERT INTO messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        
        if ($stmt->execute()) {
            $contact_message = '<div class="alert alert-success">✓ Pesan Anda telah dikirim! Kami akan menghubungi Anda segera.</div>';
        } else {
            $contact_message = '<div class="alert alert-danger">Terjadi kesalahan!</div>';
        }
        $stmt->close();
    }
}

// Get user appointments
$user_appointments = $conn->query("
    SELECT a.*, d.specialization, u.name as doctor_name, s.name as service_name
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.user_id = {$_SESSION['user_id']}
    ORDER BY a.appointment_date DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rumah Sakit Sehat Sentosa</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .user-header-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .user-header-nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: color 0.3s;
        }

        .user-header-nav a:hover {
            color: var(--primary-color);
        }

        .user-header-nav a.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .user-info {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .user-info a {
            background-color: var(--danger-color);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .user-info a:hover {
            background-color: #c0392b;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .doctor-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-top: 4px solid var(--primary-color);
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .doctor-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .doctor-card h3 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .doctor-card .specialty {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .doctor-card .experience {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .service-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }

        .service-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .service-price {
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 1.2rem;
            margin: 1rem 0;
        }

        .appointment-table {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .booking-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin: 2rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
        }

        .form-group textarea {
            grid-column: 1 / -1;
            resize: vertical;
            min-height: 100px;
        }

        .hero-user {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 3rem;
        }

        .hero-user h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .hero-user p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        @media (max-width: 768px) {
            .user-header-nav {
                flex-direction: column;
                gap: 0.5rem;
                font-size: 0.9rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .doctor-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">Rumah Sakit Sehat Sentosa</div>
        </div>
    </header>

    <div class="user-header">
        <div class="user-header-nav">
            <a href="?page=home" onclick="setActivePage(event)" class="<?php echo $page == 'home' ? 'active' : ''; ?>">🏠 Home</a>
            <a href="?page=about" onclick="setActivePage(event)" class="<?php echo $page == 'about' ? 'active' : ''; ?>">ℹ️ About</a>
            <a href="?page=departments" onclick="setActivePage(event)" class="<?php echo $page == 'departments' ? 'active' : ''; ?>">🏢 Departments</a>
            <a href="?page=services" onclick="setActivePage(event)" class="<?php echo $page == 'services' ? 'active' : ''; ?>">💊 Services</a>
            <a href="?page=doctors" onclick="setActivePage(event)" class="<?php echo $page == 'doctors' ? 'active' : ''; ?>">👨‍⚕️ Doctors</a>
            <a href="?page=contact" onclick="setActivePage(event)" class="<?php echo $page == 'contact' ? 'active' : ''; ?>">📞 Contact</a>
            <a href="?page=appointments" onclick="setActivePage(event)" class="<?php echo $page == 'appointments' ? 'active' : ''; ?>">📅 Appointments</a>
        </div>
        <div class="user-info">
            <span>👋 <?php echo $_SESSION['name']; ?></span>
            <a href="?logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- HOME PAGE -->
        <?php if ($page == 'home'): ?>
        <div class="hero-user">
            <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
            <p>Kami siap memberikan pelayanan kesehatan terbaik untuk Anda</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin: 2rem 0;">
            <div class="card">
                <div class="card-icon">📅</div>
                <h3>Buat Appointment</h3>
                <p>Pesan jadwal konsultasi dengan dokter spesialis pilihan Anda</p>
                <a href="?page=doctors" class="btn btn-primary" style="display: block; text-align: center;">Booking Sekarang</a>
            </div>
            
            <div class="card">
                <div class="card-icon">🏥</div>
                <h3>Departemen</h3>
                <p>Jelajahi berbagai departemen kesehatan kami yang lengkap</p>
                <a href="?page=departments" class="btn btn-primary" style="display: block; text-align: center;">Lihat Departemen</a>
            </div>
            
            <div class="card">
                <div class="card-icon">💊</div>
                <h3>Layanan Kami</h3>
                <p>Temukan informasi lengkap tentang layanan kesehatan kami</p>
                <a href="?page=services" class="btn btn-primary" style="display: block; text-align: center;">Explore</a>
            </div>
        </div>

        <!-- ABOUT PAGE -->
        <?php elseif ($page == 'about'): ?>
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Tentang Kami</h1>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Rumah Sakit Sehat Sentosa</h2>
            <p style="line-height: 1.8; margin-bottom: 1rem;">
                Rumah Sakit Sehat Sentosa didirikan pada tahun 2000 dengan komitmen untuk memberikan pelayanan kesehatan berkualitas tinggi kepada seluruh masyarakat. Dengan perkembangan zaman, kami terus berinovasi dan meningkatkan standar pelayanan medis.
            </p>
            
            <h3 style="color: var(--primary-color); margin-top: 2rem; margin-bottom: 1rem;">Visi Kami</h3>
            <p style="line-height: 1.8; margin-bottom: 1rem;">
                Menjadi rumah sakit terdepan dalam inovasi kesehatan yang memberikan dampak positif bagi masyarakat luas dengan teknologi terkini dan tenaga medis profesional.
            </p>
            
            <h3 style="color: var(--primary-color); margin-top: 2rem; margin-bottom: 1rem;">Misi Kami</h3>
            <ul style="margin-left: 2rem; line-height: 1.8;">
                <li>Memberikan perawatan kesehatan yang aman dan efektif</li>
                <li>Menjangkau dan melayani semua lapisan masyarakat dengan terjangkau</li>
                <li>Mengembangkan sumber daya manusia yang berkualitas</li>
                <li>Melakukan penelitian dan pengembangan dalam bidang medis</li>
            </ul>
            
            <h3 style="color: var(--primary-color); margin-top: 2rem; margin-bottom: 1rem;">Fasilitas Kami</h3>
            <div class="cards-grid">
                <div class="card">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🔬</div>
                    <h4>Laboratorium Modern</h4>
                    <p>Dilengkapi peralatan diagnostik terkini untuk hasil yang akurat</p>
                </div>
                <div class="card">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🏥</div>
                    <h4>Ruang Inap Nyaman</h4>
                    <p>Kamar-kamar yang bersih dan nyaman untuk pemulihan pasien</p>
                </div>
                <div class="card">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🚑</div>
                    <h4>Emergency 24/7</h4>
                    <p>Layanan emergency selalu siap melayani setiap saat</p>
                </div>
            </div>
        </div>

        <!-- DEPARTMENTS PAGE -->
        <?php elseif ($page == 'departments'): ?>
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Departemen Kami</h1>
        
        <div class="cards-grid">
            <?php while ($dept = $departments->fetch_assoc()): ?>
            <div class="card">
                <div class="card-icon" style="font-size: 3rem;">🏥</div>
                <h3><?php echo htmlspecialchars($dept['name']); ?></h3>
                <p><?php echo htmlspecialchars($dept['description']); ?></p>
                <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 1rem;">
                    Departemen kami didukung oleh dokter spesialis berpengalaman dan fasilitas medis modern.
                </p>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- SERVICES PAGE -->
        <?php elseif ($page == 'services'): ?>
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Layanan Kesehatan Kami</h1>
        
        <div class="service-grid">
            <?php while ($service = $services->fetch_assoc()): ?>
            <div class="service-card">
                <h3>💊 <?php echo htmlspecialchars($service['name']); ?></h3>
                <p style="color: var(--text-light); margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($service['description']); ?>
                </p>
                <p style="color: var(--text-light); font-size: 0.85rem;">
                    Departemen: <?php echo htmlspecialchars($service['dept_name'] ?? '-'); ?>
                </p>
                <div class="service-price">
                    Rp <?php echo number_format($service['price'], 0, ',', '.'); ?>
                </div>
                <a href="?page=doctors" class="btn btn-primary" style="display: block; text-align: center;">Pesan Sekarang</a>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- DOCTORS PAGE -->
        <?php elseif ($page == 'doctors'): ?>
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Tim Dokter Kami</h1>
        
        <?php echo $book_message; ?>
        
        <div class="doctor-grid">
            <?php while ($doctor = $doctors->fetch_assoc()): ?>
            <div class="doctor-card">
                <div class="doctor-avatar">👨‍⚕️</div>
                <h3><?php echo htmlspecialchars($doctor['user_name']); ?></h3>
                <div class="specialty"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                <div class="experience">📌 <?php echo $doctor['experience_years']; ?> tahun pengalaman</div>
                <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($doctor['bio'] ?? ''); ?>
                </p>
                <p style="color: var(--text-light); font-size: 0.85rem; margin-bottom: 1rem;">
                    Departemen: <strong><?php echo htmlspecialchars($doctor['dept_name']); ?></strong>
                </p>
                <a href="#booking" onclick="setDoctorForBooking(<?php echo $doctor['id']; ?>, '<?php echo htmlspecialchars($doctor['user_name']); ?>')" class="btn btn-primary" style="display: block; text-align: center;">Booking</a>
            </div>
            <?php endwhile; ?>
        </div>

        <div id="booking" class="booking-form" style="margin-top: 3rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">📅 Buat Appointment</h2>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="doctor_id">Pilih Dokter *</label>
                        <select id="doctor_id" name="doctor_id" required>
                            <option value="">-- Pilih Dokter --</option>
                            <?php 
                            $doctors->data_seek(0);
                            while ($doctor = $doctors->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    <?php echo htmlspecialchars($doctor['user_name']) . ' - ' . htmlspecialchars($doctor['specialization']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="service_id">Layanan (Opsional)</label>
                        <select id="service_id" name="service_id">
                            <option value="">-- Pilih Layanan --</option>
                            <?php 
                            $services->data_seek(0);
                            while ($service = $services->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Tanggal & Waktu *</label>
                        <input type="datetime-local" id="appointment_date" name="appointment_date" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="notes">Catatan/Keluhan (Opsional)</label>
                        <textarea id="notes" name="notes" placeholder="Deskripsikan keluhan atau pertanyaan Anda..."></textarea>
                    </div>
                </div>

                <button type="submit" name="book_appointment" class="btn btn-primary">Konfirmasi Appointment</button>
            </form>
        </div>

        <!-- APPOINTMENTS PAGE -->
        <?php elseif ($page == 'appointments'): ?>
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Appointment Saya</h1>
        
        <div class="appointment-table">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Dokter</th>
                        <th>Spesialisasi</th>
                        <th>Layanan</th>
                        <th>Tanggal & Waktu</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $has_appointments = false;
                    while ($appt = $user_appointments->fetch_assoc()): 
                        $has_appointments = true;
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                        <td><?php echo htmlspecialchars($appt['specialization']); ?></td>
                        <td><?php echo htmlspecialchars($appt['service_name'] ?? '-'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($appt['appointment_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $appt['status']; ?>">
                                <?php echo ucfirst($appt['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if (!$has_appointments): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">
                            Anda belum memiliki appointment. <a href="?page=doctors">Buat appointment sekarang</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- CONTACT PAGE -->
        <?php elseif ($page == 'contact'): ?>
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Hubungi Kami</h1>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div class="card">
                <h3>📍 Lokasi</h3>
                <p>Jl. Kesehatan No. 123<br>Jakarta Pusat 12345<br>Indonesia</p>
            </div>
            <div class="card">
                <h3>📞 Kontak</h3>
                <p>Telepon: (021) 123-4567<br>Email: info@sehat-sentosa.com<br>Jam Kerja: 24/7</p>
            </div>
        </div>

        <?php echo $contact_message; ?>
        
        <div class="booking-form">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Kirim Pesan ke Kami</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nama Lengkap *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Nomor Telepon</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="subject">Subjek *</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="message">Pesan *</label>
                        <textarea id="message" name="message" required placeholder="Tuliskan pertanyaan atau masukan Anda..."></textarea>
                    </div>
                </div>

                <button type="submit" name="send_message" class="btn btn-primary">Kirim Pesan</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Rumah Sakit Sehat Sentosa. Semua hak dilindungi.</p>
    </footer>

    <script>
        function setActivePage(event) {
            event.preventDefault();
            // Link already changes page via href
        }

        function setDoctorForBooking(doctorId, doctorName) {
            document.getElementById('doctor_id').value = doctorId;
            document.querySelector('select[name="doctor_id"]').focus();
        }

        // Real-time updates - check for new appointment status
        setInterval(function() {
            // This would fetch real-time data from an API
            console.log('Checking for updates...');
        }, 5000);
    </script>
</body>
</html>
