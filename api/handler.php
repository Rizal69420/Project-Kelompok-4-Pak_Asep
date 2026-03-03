<?php
header('Content-Type: application/json');
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    // Get appointment status for current user
    case 'get_user_appointments':
        $result = $conn->query("
            SELECT a.id, a.status, a.appointment_date,
            (SELECT u.name FROM users u LEFT JOIN doctors d ON d.user_id = u.id WHERE d.id = a.doctor_id) as doctor_name,
            (SELECT s.name FROM services s WHERE s.id = a.service_id) as service_name
            FROM appointments a
            WHERE a.user_id = {$_SESSION['user_id']}
            ORDER BY a.appointment_date DESC
            LIMIT 5
        ");
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'appointments' => $appointments
        ]);
        break;

    // Get appointment statistics for admin
    case 'get_appointment_stats':
        if ($_SESSION['role'] != 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $total = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
        $pending = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='pending'")->fetch_assoc()['count'];
        $confirmed = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='confirmed'")->fetch_assoc()['count'];
        $completed = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status='completed'")->fetch_assoc()['count'];

        echo json_encode([
            'success' => true,
            'total' => $total,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'completed' => $completed
        ]);
        break;

    // Get unread messages count
    case 'get_unread_messages':
        if ($_SESSION['role'] != 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $count = $conn->query("SELECT COUNT(*) as count FROM messages WHERE status='unread'")->fetch_assoc()['count'];

        echo json_encode([
            'success' => true,
            'unread_count' => $count
        ]);
        break;

    // Get doctors list
    case 'get_doctors':
        $result = $conn->query("
            SELECT d.id, 
            (SELECT u.name FROM users u WHERE u.id = d.user_id) as name,
            d.specialization,
            (SELECT name FROM departments WHERE id = d.department_id) as department,
            d.experience_years
            FROM doctors d
            WHERE d.is_active = 1
        ");

        $doctors = [];
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }

        echo json_encode([
            'success' => true,
            'doctors' => $doctors
        ]);
        break;

    // Get services list
    case 'get_services':
        $result = $conn->query("
            SELECT s.id, s.name, s.price, s.description,
            (SELECT name FROM departments WHERE id = s.department_id) as department
            FROM services s
            WHERE s.is_active = 1
        ");

        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }

        echo json_encode([
            'success' => true,
            'services' => $services
        ]);
        break;

    // Book appointment
    case 'book_appointment':
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request method']);
            exit();
        }

        $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : '';
        $service_id = isset($_POST['service_id']) ? $_POST['service_id'] : '';
        $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

        if (empty($doctor_id) || empty($appointment_date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $sql = "INSERT INTO appointments (user_id, doctor_id, service_id, appointment_date, notes) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $_SESSION['user_id'], $doctor_id, $service_id, $appointment_date, $notes);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Appointment berhasil dibuat'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create appointment']);
        }
        break;

    // Update appointment status (admin only)
    case 'update_appointment_status':
        if ($_SESSION['role'] != 'admin' || $_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $appointment_id = isset($_POST['appointment_id']) ? $_POST['appointment_id'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';

        if (empty($appointment_id) || empty($status)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $sql = "UPDATE appointments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $appointment_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Status appointment berhasil diperbarui'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update appointment']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
?>
