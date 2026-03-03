<?php
// Auto-redirect untuk admin login
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    header('Location: dashboard.php');
} else {
    header('Location: ../auth.php');
}
?>
