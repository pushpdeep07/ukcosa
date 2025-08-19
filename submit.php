<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Update if password is set
$dbname = "ukcosa";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Collect form data
$name = $_POST['name'];
$email = $_POST['email'];
$mobile = $_POST['mobile'];
$city = $_POST['city'];
$destination = $_POST['destination'];
$coaching = $_POST['coaching'];
$loan = $_POST['loan'];

// Insert into database
$sql = "INSERT INTO enquiries (name, email, mobile, city, destination, coaching, loan, status)
        VALUES ('$name', '$email', '$mobile', '$city', '$destination', '$coaching', '$loan', 'Pending contact')";

if ($conn->query($sql) === TRUE) {
  echo "<h2>Thanks for your enquiry</h2>
        <p>One of our team members will connect with you shortly.</p>";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
