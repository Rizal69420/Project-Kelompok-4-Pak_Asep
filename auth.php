<?php
session_start();
require_once 'database/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/index.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] == 'admin') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../user/index.php');
                }
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Email tidak ditemukan!';
        }
        $stmt->close();
    }
}

// Handle Register
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'user')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);
            
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan saat registrasi!';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rumah Sakit Sehat Sentosa</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .auth-container {
            max-width: 1000px;
            margin: 4rem auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 0 20px;
        }
        
        .auth-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .auth-form h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 168, 107, 0.1);
        }
        
        .form-group button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .form-group button:hover {
            background-color: #008c5e;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .toggle-form a {
            color: var(--primary-color);
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        
        .toggle-form a:hover {
            text-decoration: underline;
        }
        
        .form-hidden {
            display: none;
        }
        
        .info-box {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-box h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .info-box p {
            margin-bottom: 1rem;
            line-height: 1.8;
        }
        
        .info-box .feature {
            display: flex;
            align-items: center;
            margin: 1rem 0;
            gap: 10px;
        }
        
        .info-box .feature::before {
            content: "✓";
            font-weight: bold;
            font-size: 1.3rem;
        }
        
        @media (max-width: 768px) {
            .auth-container {
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

    <div class="auth-container">
        <!-- Login Form -->
        <div class="auth-form" id="loginForm">
            <h2>Login</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="login_email">Email</label>
                    <input type="email" id="login_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="login">Login</button>
                </div>
            </form>
            
            <div class="toggle-form">
                Belum punya akun? <a onclick="toggleForm()">Daftar di sini</a>
                <p style="margin-top: 1rem; font-size: 0.85rem; opacity: 0.9;">
                    Demo: admin@sehat-sentosa.com / admin123
                </p>
            </div>
        </div>

        <!-- Register Form -->
        <div class="auth-form form-hidden" id="registerForm">
            <h2>Daftar Akun</h2>
            
            <?php if (!empty($error) && isset($_POST['register'])): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="reg_name">Nama Lengkap</label>
                    <input type="text" id="reg_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="reg_email">Email</label>
                    <input type="email" id="reg_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="reg_phone">Nomor Telepon</label>
                    <input type="tel" id="reg_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="reg_confirm_password">Konfirmasi Password</label>
                    <input type="password" id="reg_confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="register">Daftar</button>
                </div>
            </form>
            
            <div class="toggle-form">
                Sudah punya akun? <a onclick="toggleForm()">Login di sini</a>
            </div>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <h3>🏥 Selamat Datang</h3>
            <p>Rumah Sakit Sehat Sentosa menyediakan layanan kesehatan terbaik dengan teknologi modern.</p>
            
            <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Fitur Kami:</h4>
            <div class="feature">Booking appointment dengan dokter spesialis</div>
            <div class="feature">Riwayat medical record lengkap</div>
            <div class="feature">Layanan emergency 24/7</div>
            <div class="feature">Konsultasi dengan dokter profesional</div>
            <div class="feature">Sistem pembayaran yang aman</div>
            
            <p style="margin-top: 2rem; font-size: 0.9rem; opacity: 0.85;">
                Untuk mengakses fitur admin, gunakan akun admin yang telah tersedia.
            </p>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Rumah Sakit Sehat Sentosa. Semua hak dilindungi.</p>
    </footer>

    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            loginForm.classList.toggle('form-hidden');
            registerForm.classList.toggle('form-hidden');
        }
    </script>
</body>
</html>
