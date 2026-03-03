<?php
session_start();
require_once '../database/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth.php');
    exit();
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../auth.php');
    exit();
}

// Fetch statistics
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='pending'")->fetch_assoc()['count'];
$total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE is_active=1")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];
$unread_messages = $conn->query("SELECT COUNT(*) as count FROM messages WHERE status='unread'")->fetch_assoc()['count'];
$total_services = $conn->query("SELECT COUNT(*) as count FROM services WHERE is_active=1")->fetch_assoc()['count'];

// Fetch recent appointments
$recent_appointments = $conn->query("
    SELECT a.*, u.name as user_name, d.specialization, 
    (SELECT s.name FROM services s WHERE s.id = a.service_id) as service_name
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    ORDER BY a.appointment_date DESC
    LIMIT 10
");

// Fetch real-time status
$appointment_status = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM appointments 
    GROUP BY status
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rumah Sakit Sehat Sentosa</title>
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
            z-index: 50;
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

        .admin-header h1 {
            color: var(--primary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info a {
            background-color: var(--danger-color);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .user-info a:hover {
            background-color: #c0392b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stat-card h3 {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.warning .number {
            color: var(--warning-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card.danger .number {
            color: var(--danger-color);
        }

        .stat-card.info {
            border-left-color: var(--secondary-color);
        }

        .stat-card.info .number {
            color: var(--secondary-color);
        }

        .section-title {
            font-size: 1.3rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-weight: 600;
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
            cursor: pointer;
            border: none;
        }

        .btn-edit {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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

        @media (max-width: 768px) {
            .admin-wrapper {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-sidebar.active {
                transform: translateX(0);
            }

            .admin-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-content">
                <div class="sidebar-logo">
                    🏥 Admin Panel
                </div>
                <ul class="sidebar-menu">
                    <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                    <li><a href="doctors.php">👨‍⚕️ Dokter</a></li>
                    <li><a href="departments.php">🏢 Departemen</a></li>
                    <li><a href="services.php">💊 Layanan</a></li>
                    <li><a href="appointments.php">📅 Appointment</a></li>
                    <li><a href="users.php">👥 User</a></li>
                    <li><a href="messages.php">💬 Pesan</a></li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Dashboard Admin</h1>
                <div class="user-info">
                    <span>Selamat datang, <strong><?php echo $_SESSION['name']; ?></strong></span>
                    <a href="?logout">Logout</a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Appointment</h3>
                    <div class="number"><?php echo $total_appointments; ?></div>
                </div>
                <div class="stat-card warning">
                    <h3>Appointment Pending</h3>
                    <div class="number"><?php echo $pending_appointments; ?></div>
                </div>
                <div class="stat-card info">
                    <h3>Total Dokter</h3>
                    <div class="number"><?php echo $total_doctors; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total User</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card danger">
                    <h3>Pesan Baru</h3>
                    <div class="number"><?php echo $unread_messages; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Layanan</h3>
                    <div class="number"><?php echo $total_services; ?></div>
                </div>
            </div>

            <!-- Status Overview -->
            <div style="margin-bottom: 2rem;">
                <h2 class="section-title">Status Appointment Real-Time</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <?php while ($status = $appointment_status->fetch_assoc()): ?>
                        <div class="stat-card">
                            <h3><?php echo ucfirst($status['status']); ?></h3>
                            <div class="number"><?php echo $status['count']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Recent Appointments -->
            <h2 class="section-title">Appointment Terbaru</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Layanan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($appt = $recent_appointments->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($appt['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($appt['specialization']); ?></td>
                            <td><?php echo htmlspecialchars($appt['service_name'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($appt['appointment_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $appt['status']; ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group-table">
                                    <a href="appointments.php?view=<?php echo $appt['id']; ?>" class="btn-small btn-view">Lihat</a>
                                    <a href="appointments.php?edit=<?php echo $appt['id']; ?>" class="btn-small btn-edit">Edit</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Auto-refresh dashboard every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
