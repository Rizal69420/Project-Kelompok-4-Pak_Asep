<?php
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth.php');
    exit();
}

// Handle Mark as Read
if (isset($_GET['mark_read'])) {
    $id = $_GET['mark_read'];
    $conn->query("UPDATE messages SET status = 'read' WHERE id = $id");
    header('Location: messages.php');
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM messages WHERE id = $id");
    header('Location: messages.php');
    exit();
}

// Handle Reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_reply'])) {
    $id = $_POST['message_id'];
    $reply = $_POST['admin_reply'];
    
    $sql = "UPDATE messages SET admin_reply = ?, status = 'replied' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $reply, $id);
    $stmt->execute();
    header('Location: messages.php');
    exit();
}

// Get all messages
$messages = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");

// Get message detail
$message_detail = null;
if (isset($_GET['view'])) {
    $id = $_GET['view'];
    $result = $conn->query("SELECT * FROM messages WHERE id = $id");
    $message_detail = $result->fetch_assoc();
    $conn->query("UPDATE messages SET status = 'read' WHERE id = $id");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan - Admin</title>
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

        .message-detail {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .message-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .message-from {
            font-weight: 600;
            color: var(--text-dark);
        }

        .message-body {
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 4px;
            margin: 1.5rem 0;
            line-height: 1.8;
        }

        .reply-form {
            background: var(--light-bg);
            padding: 1.5rem;
            border-radius: 4px;
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
            min-height: 150px;
            resize: vertical;
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

        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-unread {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-read {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-replied {
            background-color: #d4edda;
            color: #155724;
        }

        .row-unread {
            background-color: #fffaf0;
            font-weight: 500;
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
                    <li><a href="appointments.php">📅 Appointment</a></li>
                    <li><a href="users.php">👥 User</a></li>
                    <li><a href="messages.php" class="active">💬 Pesan</a></li>
                </ul>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Kelola Pesan</h1>
            </div>

            <?php if ($message_detail): ?>
            <div class="message-detail">
                <div class="message-header">
                    <div class="message-from">Dari: <?php echo htmlspecialchars($message_detail['name']); ?></div>
                    <div style="color: var(--text-light); font-size: 0.9rem; margin-top: 0.5rem;">
                        Email: <?php echo htmlspecialchars($message_detail['email']); ?>
                        <?php if ($message_detail['phone']): ?>
                            | Telp: <?php echo htmlspecialchars($message_detail['phone']); ?>
                        <?php endif; ?>
                    </div>
                    <div style="color: var(--text-light); font-size: 0.9rem; margin-top: 0.5rem;">
                        <?php echo date('d/m/Y H:i', strtotime($message_detail['created_at'])); ?>
                    </div>
                    <h3 style="margin-top: 1rem;">Subjek: <?php echo htmlspecialchars($message_detail['subject']); ?></h3>
                </div>

                <h4>Pesan:</h4>
                <div class="message-body">
                    <?php echo nl2br(htmlspecialchars($message_detail['message'])); ?>
                </div>

                <?php if ($message_detail['admin_reply']): ?>
                <h4>Balasan Admin:</h4>
                <div style="background: var(--primary-color); color: white; padding: 1rem; border-radius: 4px; margin: 1.5rem 0;">
                    <?php echo nl2br(htmlspecialchars($message_detail['admin_reply'])); ?>
                </div>
                <?php else: ?>
                <form method="POST" class="reply-form">
                    <input type="hidden" name="message_id" value="<?php echo $message_detail['id']; ?>">
                    <div class="form-group">
                        <label for="admin_reply">Balasan Admin</label>
                        <textarea id="admin_reply" name="admin_reply" required placeholder="Tulis balasan Anda..."></textarea>
                    </div>
                    <button type="submit" name="send_reply" class="btn btn-primary">Kirim Balasan</button>
                </form>
                <?php endif; ?>

                <div style="margin-top: 2rem;">
                    <a href="messages.php" class="btn btn-secondary">Kembali ke Daftar</a>
                    <a href="messages.php?delete=<?php echo $message_detail['id']; ?>" class="btn btn-danger" style="margin-left: 1rem;" onclick="return confirm('Yakin ingin menghapus?')">Hapus Pesan</a>
                </div>
            </div>
            <?php else: ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Subjek</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($msg = $messages->fetch_assoc()): 
                        ?>
                        <tr class="<?php echo $msg['status'] == 'unread' ? 'row-unread' : ''; ?>">
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td><?php echo htmlspecialchars($msg['email']); ?></td>
                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $msg['status']; ?>">
                                    <?php echo ucfirst($msg['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group-table">
                                    <a href="messages.php?view=<?php echo $msg['id']; ?>" class="btn-small btn-view">Lihat</a>
                                    <a href="messages.php?delete=<?php echo $msg['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
