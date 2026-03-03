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
    $conn->query("DELETE FROM departments WHERE id = $id");
    header('Location: departments.php');
    exit();
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $name = $_POST['name'];
    $description = $_POST['description'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!empty($id)) {
        $sql = "UPDATE departments SET name=?, description=?, is_active=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $name, $description, $is_active, $id);
        $stmt->execute();
    } else {
        $sql = "INSERT INTO departments (name, description, is_active) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $description, $is_active);
        $stmt->execute();
    }
    header('Location: departments.php');
    exit();
}

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY id DESC");

// Get edit data
$edit_dept = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM departments WHERE id = $id");
    $edit_dept = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Departemen - Admin</title>
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }

        .form-group textarea {
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
                    <li><a href="departments.php" class="active">🏢 Departemen</a></li>
                    <li><a href="services.php">💊 Layanan</a></li>
                    <li><a href="appointments.php">📅 Appointment</a></li>
                    <li><a href="users.php">👥 User</a></li>
                    <li><a href="messages.php">💬 Pesan</a></li>
                </ul>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1><?php echo $edit_dept ? 'Edit Departemen' : 'Tambah Departemen Baru'; ?></h1>
                <a href="departments.php" class="btn btn-primary">Kembali ke Daftar</a>
            </div>

            <div class="form-container">
                <form method="POST">
                    <?php if ($edit_dept): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_dept['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Nama Departemen *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_dept['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($edit_dept['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?php echo ($edit_dept && $edit_dept['is_active']) ? 'checked' : ($edit_dept ? '' : 'checked'); ?>>
                            Aktif
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_dept ? 'Simpan Perubahan' : 'Tambah Departemen'; ?>
                    </button>
                </form>
            </div>

            <h2 style="margin: 2rem 0 1rem;">Daftar Departemen</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($dept = $departments->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($dept['name']); ?></td>
                            <td><?php echo htmlspecialchars($dept['description']); ?></td>
                            <td>
                                <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 20px; background-color: <?php echo $dept['is_active'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $dept['is_active'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $dept['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group-table">
                                    <a href="departments.php?edit=<?php echo $dept['id']; ?>" class="btn-small btn-edit">Edit</a>
                                    <a href="departments.php?delete=<?php echo $dept['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
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
