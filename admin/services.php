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
    $conn->query("DELETE FROM services WHERE id = $id");
    header('Location: services.php');
    exit();
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $department_id = $_POST['department_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!empty($id)) {
        $sql = "UPDATE services SET name=?, description=?, price=?, department_id=?, is_active=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiii", $name, $description, $price, $department_id, $is_active, $id);
        $stmt->execute();
    } else {
        $sql = "INSERT INTO services (name, description, price, department_id, is_active) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdii", $name, $description, $price, $department_id, $is_active);
        $stmt->execute();
    }
    header('Location: services.php');
    exit();
}

// Get services
$services = $conn->query("
    SELECT s.*, d.name as dept_name
    FROM services s
    LEFT JOIN departments d ON s.department_id = d.id
    ORDER BY s.id DESC
");

// Get departments
$departments = $conn->query("SELECT * FROM departments WHERE is_active = 1");

// Get edit data
$edit_service = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM services WHERE id = $id");
    $edit_service = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Layanan - Admin</title>
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
                    <li><a href="doctors.php">👨‍⚕️ Dokter</a></li>
                    <li><a href="departments.php">🏢 Departemen</a></li>
                    <li><a href="services.php" class="active">💊 Layanan</a></li>
                    <li><a href="appointments.php">📅 Appointment</a></li>
                    <li><a href="users.php">👥 User</a></li>
                    <li><a href="messages.php">💬 Pesan</a></li>
                </ul>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1><?php echo $edit_service ? 'Edit Layanan' : 'Tambah Layanan Baru'; ?></h1>
                <a href="services.php" class="btn btn-primary">Kembali ke Daftar</a>
            </div>

            <div class="form-container">
                <form method="POST">
                    <?php if ($edit_service): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_service['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nama Layanan *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_service['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="department_id">Departemen</label>
                            <select id="department_id" name="department_id">
                                <option value="">Pilih Departemen</option>
                                <?php 
                                $departments->data_seek(0);
                                while ($dept = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($edit_service && $dept['id'] == $edit_service['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo $dept['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="price">Harga (Rp) *</label>
                            <input type="number" id="price" name="price" step="1000" value="<?php echo htmlspecialchars($edit_service['price'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" <?php echo ($edit_service && $edit_service['is_active']) ? 'checked' : ($edit_service ? '' : 'checked'); ?>>
                                Aktif
                            </label>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="description">Deskripsi *</label>
                            <textarea id="description" name="description" required><?php echo htmlspecialchars($edit_service['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_service ? 'Simpan Perubahan' : 'Tambah Layanan'; ?>
                    </button>
                </form>
            </div>

            <h2 style="margin: 2rem 0 1rem;">Daftar Layanan</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Layanan</th>
                            <th>Departemen</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($service = $services->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($service['name']); ?></td>
                            <td><?php echo htmlspecialchars($service['dept_name'] ?? '-'); ?></td>
                            <td>Rp <?php echo number_format($service['price'], 0, ',', '.'); ?></td>
                            <td>
                                <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 20px; background-color: <?php echo $service['is_active'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $service['is_active'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $service['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group-table">
                                    <a href="services.php?edit=<?php echo $service['id']; ?>" class="btn-small btn-edit">Edit</a>
                                    <a href="services.php?delete=<?php echo $service['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
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
