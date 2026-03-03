<?php
// Create Database and Tables
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        die("Connection Failed: " . $conn->connect_error);
    }
    
    // Create Database
    $sql = "CREATE DATABASE IF NOT EXISTS rumah_sakit_db";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully<br>";
    }
    
    // Select database
    $conn->select_db("rumah_sakit_db");
    
    // Create Users Table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(15),
        role ENUM('user', 'admin', 'doctor') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "Users table created<br>";
    
    // Create Departments Table
    $sql = "CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "Departments table created<br>";
    
    // Create Doctors Table
    $sql = "CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        department_id INT NOT NULL,
        specialization VARCHAR(100) NOT NULL,
        experience_years INT,
        photo_url VARCHAR(255),
        bio TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    echo "Doctors table created<br>";
    
    // Create Services Table
    $sql = "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2),
        department_id INT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    )";
    $conn->query($sql);
    echo "Services table created<br>";
    
    // Create Appointments Table
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        doctor_id INT NOT NULL,
        service_id INT,
        appointment_date DATETIME NOT NULL,
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
    )";
    $conn->query($sql);
    echo "Appointments table created<br>";
    
    // Create Messages/Contact Table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15),
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
        admin_reply TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "Messages table created<br>";
    
    // Insert Sample Data
    // Departments
    $departments = [
        ['Kardiologi', 'Spesialis jantung dan pembuluh darah'],
        ['Neurologi', 'Perawatan sistem saraf'],
        ['Ortopedi', 'Perawatan tulang dan sendi'],
        ['Pediatri', 'Kesehatan anak-anak'],
        ['Ginekologi', 'Kesehatan wanita']
    ];
    
    foreach ($departments as $dept) {
        $sql = "INSERT INTO departments (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $dept[0], $dept[1]);
        $stmt->execute();
    }
    echo "Sample departments inserted<br>";
    
    // Create Admin User
    $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
    $sql = "INSERT INTO users (name, email, password, phone, role) VALUES ('Admin', 'admin@sehat-sentosa.com', ?, '021-123456', 'admin')
            ON DUPLICATE KEY UPDATE id=id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $admin_password);
    $stmt->execute();
    echo "Admin user created<br>";
    
    // Create Sample Doctors
    $doctors_data = [
        ['Dr. Ahmad Santoso', 'Kardiologi', 15, 'ahmad@sehat-sentosa.com'],
        ['Dr. Budi Raharjo', 'Neurologi', 12, 'budi@sehat-sentosa.com'],
        ['Dr. Citra Dewi', 'Pediatri', 10, 'citra@sehat-sentosa.com']
    ];
    
    foreach ($doctors_data as $doctor) {
        // First create doctor user
        $password = password_hash('doctor123', PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, '021-987654', 'doctor')
                ON DUPLICATE KEY UPDATE id=id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $doctor[0], $doctor[3], $password);
        $stmt->execute();
        
        // Get user id
        $user_id_result = $conn->query("SELECT id FROM users WHERE email = '{$doctor[3]}'");
        $user_row = $user_id_result->fetch_assoc();
        $user_id = $user_row['id'];
        
        // Get department id
        $dept_result = $conn->query("SELECT id FROM departments WHERE name = '{$doctor[1]}'");
        $dept_row = $dept_result->fetch_assoc();
        $dept_id = $dept_row['id'];
        
        // Insert doctor
        $sql = "INSERT INTO doctors (user_id, department_id, specialization, experience_years, bio) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE id=id";
        $stmt = $conn->prepare($sql);
        $bio = "Dokter spesialis " . $doctor[1] . " dengan pengalaman " . $doctor[2] . " tahun";
        $stmt->bind_param("iisds", $user_id, $dept_id, $doctor[1], $doctor[2], $bio);
        $stmt->execute();
    }
    echo "Sample doctors created<br>";
    
    // Create Sample Services
    $services = [
        ['Pemeriksaan Umum', 'Konsultasi kesehatan rutin', 150000, 1],
        ['Operasi Darurat', 'Layanan 24/7 untuk keadaan darurat', 2000000, 1],
        ['Laboratorium', 'Tes darah dan diagnostik', 200000, 2],
        ['Radiologi', 'X-ray, MRI, dan CT scan', 500000, 2],
        ['Pemeriksaan Anak', 'Kesehatan dan imunisasi anak', 100000, 3]
    ];
    
    foreach ($services as $service) {
        $sql = "INSERT INTO services (name, description, price, department_id) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE id=id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $service[0], $service[1], $service[2], $service[3]);
        $stmt->execute();
    }
    echo "Sample services created<br>";
    
    echo "<h2 style='color: green;'>✓ Database berhasil dibuat dan dikonfigurasi!</h2>";
    echo "<p>Admin Email: admin@sehat-sentosa.com</p>";
    echo "<p>Admin Password: admin123</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
