<?php
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth.php');
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM doctors WHERE id = $id");
    header('Location: doctors.php');
    exit();
}

// Handle Add/Update Doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $department_id = $_POST['department_id'];
    $specialization = $_POST['specialization'];
    $experience_years = $_POST['experience_years'];
    $bio = $_POST['bio'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!empty($id)) {
        // Update
        $sql = "UPDATE doctors SET department_id=?, specialization=?, experience_years=?, bio=?, is_active=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdsii", $department_id, $specialization, $experience_years, $bio, $is_active, $id);
        $stmt->execute();
    } else {
        // Add new
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : NULL;
        $sql = "INSERT INTO doctors (user_id, department_id, specialization, experience_years, bio, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdsdi", $user_id, $department_id, $specialization, $experience_years, $bio, $is_active);
        $stmt->execute();
    }
    header('Location: doctors.php');
    exit();
}

// Get doctors
$doctors = $conn->query("
    SELECT d.*, dept.name as dept_name, u.name as user_name, u.email
    FROM doctors d
    LEFT JOIN departments dept ON d.department_id = dept.id
    LEFT JOIN users u ON d.user_id = u.id
    ORDER BY d.id DESC
");

// Get departments
$departments = $conn->query("SELECT * FROM departments WHERE is_active = 1");

// Get edit data
$edit_doctor = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM doctors WHERE id = $id");
    $edit_doctor = $result->fetch_assoc();
}

// Get available doctors from users
$available_doctors = $conn->query("SELECT * FROM users WHERE role = 'doctor'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dokter - Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .admin-wrapper {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background: var(--light-bg);
        }

        .admin-sidebar {
            background: var(--text-dark);
            color: white;
            padding: 2rem 0;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            overflow-y: auto;
        }

        .admin-sidebar-content {
            padding: 0 1.5rem;
        }

        .sidebar-logo {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 0.5rem 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 0.8rem 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
            padding-left: 1.5rem;
        }

        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .btn-add {
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-add:hover {
            background-color: #008c5e;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-grid {
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
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }

        .form-group textarea {
            grid-column: 1 / -1;
            resize: vertical;
            min-height: 100px;
        }

        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .btn-group-table {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-small:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .admin-wrapper {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-content {
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-content">
                <div class="sidebar-logo">🏥 Admin Panel</div>
                <ul class="sidebar-menu">
                    <li><a href="dashboard.php">📊 Dashboard</a></li>
                    <li><a href="doctors.php" class="active">👨‍⚕️ Dokter</a></li>
                    <li><a href="departments.php">🏢 Departemen</a></li>
                    <li><a href="services.php">💊 Layanan</a></li>
                    <li><a href="appointments.php">📅 Appointment</a></li>
                    <li><a href="users.php">👥 User</a></li>
                    <li><a href="messages.php">💬 Pesan</a></li>
                </ul>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1><?php echo $edit_doctor ? 'Edit Dokter' : 'Tambah Dokter Baru'; ?></h1>
                <a href="doctors.php" class="btn-add">Kembali ke Daftar</a>
            </div>

            <?php if ($_GET['edit'] ?? false): ?>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $edit_doctor['id']; ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="department_id">Departemen</label>
                                <select id="department_id" name="department_id" required>
                                    <option value="">Pilih Departemen</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $edit_doctor['department_id'] ? 'selected' : ''; ?>>
                                            <?php echo $dept['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="specialization">Spesialisasi</label>
                                <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($edit_doctor['specialization']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="experience_years">Tahun Pengalaman</label>
                                <input type="number" id="experience_years" name="experience_years" value="<?php echo $edit_doctor['experience_years']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="bio">Biografi</label>
                                <textarea id="bio" name="bio"><?php echo htmlspecialchars($edit_doctor['bio'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" <?php echo $edit_doctor['is_active'] ? 'checked' : ''; ?>>
                                    Aktif
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="form-container">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="department_id">Departemen</label>
                                <select id="department_id" name="department_id" required>
                                    <option value="">Pilih Departemen</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dept['id']; ?>">
                                            <?php echo $dept['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="specialization">Spesialisasi</label>
                                <input type="text" id="specialization" name="specialization" placeholder="Contoh: Kardiologi" required>
                            </div>

                            <div class="form-group">
                                <label for="experience_years">Tahun Pengalaman</label>
                                <input type="number" id="experience_years" name="experience_years" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="bio">Biografi</label>
                                <textarea id="bio" name="bio" placeholder="Deskripsi singkat tentang dokter"></textarea>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" checked>
                                    Aktif
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Tambah Dokter</button>
                    </form>
                </div>
            <?php endif; ?>

            <h2 style="margin: 2rem 0 1rem;">Daftar Dokter</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Departemen</th>
                            <th>Spesialisasi</th>
                            <th>Pengalaman (Tahun)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($doctor = $doctors->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($doctor['user_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($doctor['dept_name']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td><?php echo $doctor['experience_years']; ?> Tahun</td>
                            <td>
                                <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 20px; background-color: <?php echo $doctor['is_active'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $doctor['is_active'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $doctor['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group-table">
                                    <a href="doctors.php?edit=<?php echo $doctor['id']; ?>" class="btn-small btn-edit">Edit</a>
                                    <a href="doctors.php?delete=<?php echo $doctor['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
