<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit("Only POST requests are allowed.");
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ukcosa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit("Database connection failed.");
}

$conn->query("CREATE TABLE IF NOT EXISTS counselling_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    query TEXT,
    destination VARCHAR(100),
    timeline VARCHAR(20),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$query = $_POST['query'] ?? '';
$destination = $_POST['destination'] ?? '';
$timeline = $_POST['timeline'] ?? '';

$stmt = $conn->prepare("INSERT INTO counselling_forms (name, phone, email, query, destination, timeline) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $phone, $email, $query, $destination, $timeline);

if (!$stmt->execute()) {
    http_response_code(500);
    exit("Failed to submit form.");
}

$stmt->close();
$conn->close();
?>
