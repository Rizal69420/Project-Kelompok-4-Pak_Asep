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
    $conn->query("DELETE FROM appointments WHERE id = $id");
    header('Location: appointments.php');
    exit();
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['appointment_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    header('Location: appointments.php');
    exit();
}

// Get all appointments
$appointments = $conn->query("
    SELECT a.*, u.name as user_name, u.email as user_email, d.specialization,
    doc.name as doctor_name, s.name as service_name
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users doc ON d.user_id = doc.id
    LEFT JOIN services s ON a.service_id = s.id
    ORDER BY a.appointment_date DESC
");

// Get edit data
$edit_appt = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("
        SELECT a.*, u.name as user_name, d.specialization,
        doc.name as doctor_name
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN users doc ON d.user_id = doc.id
        WHERE a.id = $id
    ");
    $edit_appt = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Appointment - Admin</title>
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

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .edit-form {
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
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
                    <li><a href="departments.php">🏢 Departemen</a></li>
                    <li><a href="services.php">💊 Layanan</a></li>
                    <li><a href="appointments.php" class="active">📅 Appointment</a></li>
                    <li><a href="users.php">👥 User</a></li>
                    <li><a href="messages.php">💬 Pesan</a></li>
                </ul>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Kelola Appointment</h1>
            </div>

            <?php if ($edit_appt): ?>
            <div class="edit-form">
                <h2>Update Status Appointment</h2>
                <form method="POST">
                    <input type="hidden" name="appointment_id" value="<?php echo $edit_appt['id']; ?>">
                    
                    <div class="form-group">
                        <label>Pasien</label>
                        <input type="text" value="<?php echo htmlspecialchars($edit_appt['user_name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Email Pasien</label>
                        <input type="text" value="<?php echo htmlspecialchars($edit_appt['user_email']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Dokter</label>
                        <input type="text" value="<?php echo htmlspecialchars($edit_appt['doctor_name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Tanggal & Waktu</label>
                        <input type="text" value="<?php echo date('d/m/Y H:i', strtotime($edit_appt['appointment_date'])); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="pending" <?php echo $edit_appt['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $edit_appt['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $edit_appt['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $edit_appt['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    <a href="appointments.php" class="btn btn-secondary" style="margin-left: 1rem;">Batal</a>
                </form>
            </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Layanan</th>
                            <th>Tanggal & Waktu</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($appt = $appointments->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($appt['user_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($appt['user_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($appt['service_name'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($appt['appointment_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $appt['status']; ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group-table">
                                    <a href="appointments.php?edit=<?php echo $appt['id']; ?>" class="btn-small btn-edit">Edit</a>
                                    <a href="appointments.php?delete=<?php echo $appt['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
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
