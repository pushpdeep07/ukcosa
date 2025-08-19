<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<h3 style='text-align:center;margin-top:50px;color:red;'>Form not submitted properly. Please go back and try again.</h3>";
    exit;
}

// Database setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ukcosa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form fields
$country = $_POST['country'] ?? '';
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$reason = $_POST['reason'] ?? '';
$message = $_POST['message'] ?? '';

// File upload (if selected)
$file_name = '';
if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
    $file_name = basename($_FILES["file"]["name"]);
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . $file_name;
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
}

// Prepared statement to avoid SQL injection
$stmt = $conn->prepare("INSERT INTO feedback (country, fullname, email, phone, reason, message, file_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $country, $fullname, $email, $phone, $reason, $message, $file_name);

if ($stmt->execute()) {
    echo "<h2 style='text-align: center; margin-top: 50px;'>Thanks for your feedback! We'll get back to you shortly.</h2>";
} else {
    echo "<h3 style='color:red;'>Error: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();
?>
