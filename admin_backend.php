<?php
session_start();

// Auto expire session after 10 minutes of inactivity
$timeout = 600; // 600 seconds = 10 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    echo json_encode(['error' => 'Session expired']);
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update activity timestamp

header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=ukcosa", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ✅ Session check (used by frontend to verify login)
    case 'is_logged_in':
        echo json_encode(['logged_in' => isset($_SESSION['admin_logged_in'])]);
        break;

    // ✅ Login handler
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = $admin['username']; // Save name for greeting
            $_SESSION['login_time'] = time(); // For session expiration logic
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
        break;

    // ✅ Return logged-in admin's name
    case 'get_admin_name':
        echo json_encode(['name' => $_SESSION['admin_name'] ?? 'Admin']);
        break;

    // ✅ Logout handler
    case 'logout':
        session_unset();
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    // ✅ Insert new admin
    case 'insert_admin':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['error' => 'Username and password required']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);

        echo json_encode(['success' => true]);
        break;

    // ✅ Update enquiry status (AJAX handler)
    case 'update_enquiry_status':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'];
        $status = $input['status'];
        $stmt = $pdo->prepare("UPDATE enquiries SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $id]);
        echo json_encode(['success' => $success]);
        break;

    // ✅ Fetch all dashboard data
    case 'fetch_dashboard_data':
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $admins = $pdo->query("SELECT username FROM admin")->fetchAll(PDO::FETCH_ASSOC);
        $students = $pdo->query("SELECT * FROM students ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        $feedbacks = $pdo->query("SELECT * FROM feedback ORDER BY submitted_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        $counselling_forms = $pdo->query("SELECT * FROM counselling_forms ORDER BY submitted_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        $enquiries = $pdo->query("SELECT * FROM enquiries ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'admins' => $admins,
            'students' => $students,
            'feedbacks' => $feedbacks,
            'counselling_forms' => $counselling_forms,
            'enquiries' => $enquiries
        ]);
        break;

    // ✅ Fallback
    default:
        echo json_encode(['error' => 'Invalid action']);
}
