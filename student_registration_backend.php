<?php
file_put_contents('log.txt', 'Reached backend at '.date('Y-m-d H:i:s')."\n", FILE_APPEND);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// DATABASE CONNECTION
$host = 'localhost';
$db   = 'ukcosa';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// // PHPMailer Setup - Uncomment when ready
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;
// require 'vendor/autoload.php';

// function sendOTP($email, $otp, $type) {
//     $mail = new PHPMailer(true);
//     try {
//         $mail->isSMTP();
//         $mail->Host = 'smtp.gmail.com';
//         $mail->SMTPAuth = true;
//         $mail->Username = 'your@gmail.com';
//         $mail->Password = 'your-app-password';
//         $mail->SMTPSecure = 'tls';
//         $mail->Port = 587;
//         $mail->setFrom('your@gmail.com', 'UKCoSA Global');
//         $mail->addAddress($email);
//         $mail->isHTML(true);

//         $mail->Subject = $type === 'register' ? 'UKCoSA Registration - OTP' : 'UKCoSA Login - OTP';
//         $mail->Body = "<h3>Your OTP is: $otp</h3>";
//         $mail->send();
//         return true;
//     } catch (Exception $e) {
//         error_log("Mailer Error: " . $mail->ErrorInfo);
//         return false;
//     }
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method.";
    exit();
}

if (!isset($_POST['form_type'])) {
    echo "Missing form type";
    exit();
}

$form_type = $_POST['form_type'];

// ======================= REGISTER FLOW =======================
if ($form_type === 'register') {
    $required = ['full_name', 'country_code', 'mobile_number', 'email', 'destination', 'study_type', 'area_of_study'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo "Missing field: $field";
            exit();
        }
    }

    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo "Email already registered. Please log in.";
        exit();
    }

    $_SESSION['otp'] = rand(100000, 999999);
    $_SESSION['email'] = $email;
    $_SESSION['register_data'] = [
        'full_name'     => $_POST['full_name'],
        'country_code'  => $_POST['country_code'],
        'mobile_number' => $_POST['mobile_number'],
        'destination'   => $_POST['destination'],
        'study_type'    => $_POST['study_type'],
        'area_of_study' => $_POST['area_of_study']
    ];

    // Uncomment this when PHPMailer is set up
    // if (sendOTP($email, $_SESSION['otp'], 'register')) {
    //     header('Location: verify.html');
    //     exit();
    // } else {
    //     echo "Failed to send OTP.";
    //     exit();
    // }

    echo "OTP sent (simulated). Please verify.";
    exit();
}

// ======================= LOGIN FLOW =======================
elseif ($form_type === 'login') {
    if (!isset($_POST['email'])) {
        echo "Missing email";
        exit();
    }

    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() === 0) {
        echo "Email not registered. Please register first.";
        exit();
    }

    $_SESSION['otp'] = rand(100000, 999999);
    $_SESSION['email'] = $email;
    $_SESSION['login'] = true;

    // Uncomment when PHPMailer works
    // if (sendOTP($email, $_SESSION['otp'], 'login')) {
    //     header('Location: verify.html');
    //     exit();
    // } else {
    //     echo "Failed to send OTP.";
    //     exit();
    // }

    echo "OTP sent (simulated). Please verify.";
    exit();
}

// ======================= VERIFY FLOW =======================
elseif ($form_type === 'verify') {
    if (!isset($_POST['otp']) || !isset($_POST['email'])) {
        echo "Missing OTP or email";
        exit();
    }

    $entered_otp = $_POST['otp'];
    $email = $_POST['email'];

    if (!isset($_SESSION['otp']) || $_SESSION['otp'] != $entered_otp || $_SESSION['email'] != $email) {
        echo "Invalid OTP or session expired.";
        exit();
    }

    // ==== Registration Finalization ====
    if (isset($_SESSION['register_data'])) {
        $data = $_SESSION['register_data'];
        $stmt = $pdo->prepare("INSERT INTO students (full_name, country_code, mobile_number, email, destination, study_type, area_of_study) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['full_name'],
            $data['country_code'],
            $data['mobile_number'],
            $email,
            $data['destination'],
            $data['study_type'],
            $data['area_of_study']
        ]);
        unset($_SESSION['register_data']);
    }

    // Clear OTP/session info
    unset($_SESSION['otp']);
    unset($_SESSION['email']);
    unset($_SESSION['login']);

    header("Location: student_dashboard.html");
    exit();
}

else {
    echo "Invalid form_type.";
    exit();
}
?>
