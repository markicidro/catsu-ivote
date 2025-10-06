<?php
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_maxlifetime', 3600);
session_start();
require 'config.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ivote_db";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

$email = trim($_POST['email'] ?? '');
$studentid = trim($_POST['studentid'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($email) || empty($studentid) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Query to find user
$sql = "SELECT id, email, student_id, password, role FROM users WHERE (email = ? OR student_id = ?) LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("ss", $email, $studentid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verify password (plain text - update to password_verify() when hashed)
    if ($password === $user['password']) {
        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Store in session
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['otp_expiry'] = time() + 300; // OTP expires in 5 minutes
        
        // Send OTP via email to the USER'S EMAIL (not hardcoded)
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            // Recipients - SEND TO USER'S EMAIL FROM DATABASE
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($user['email']); // ✅ This now gets the email from database
            
            // Optional: Add reply-to
            $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your iVote Login OTP Code';
            $mail->Body    = "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f4f4f4;'>
                        <div style='background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                            <h2 style='color: #06b6d4; margin-top: 0;'>iVote Login Verification</h2>
                            <p>Hello <strong>" . htmlspecialchars($user['email']) . "</strong>,</p>
                            <p>Your One-Time Password (OTP) for logging into the iVote system is:</p>
                            <div style='background: #e0f7fa; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                                <h1 style='color: #00838f; margin: 0; font-size: 36px; letter-spacing: 8px;'>{$otp}</h1>
                            </div>
                            <p><strong>This code will expire in 5 minutes.</strong></p>
                            <p>If you did not request this code, please ignore this email or contact support if you have concerns.</p>
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #777;'>
                                <strong>Student ID:</strong> " . htmlspecialchars($user['student_id']) . "<br>
                                <strong>Role:</strong> " . htmlspecialchars(ucfirst($user['role'])) . "
                            </p>
                            <p style='font-size: 12px; color: #777;'>© " . date('Y') . " iVote - CatSU Student Elections</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "Your iVote OTP code is: {$otp}. This code expires in 5 minutes. If you did not request this, please ignore this email.";
            
            $mail->send();
            
            // Return success WITHOUT showing OTP (security best practice)
            echo json_encode([
                "status" => "success",
                "message" => "OTP sent to " . maskEmail($user['email']),
                "role" => $user['role']
            ]);
            
        } catch (Exception $e) {
            // Email sending failed
            echo json_encode([
                "status" => "error",
                "message" => "Failed to send OTP email. Please try again or contact support.",
                "debug" => $mail->ErrorInfo // Remove this in production
            ]);
        }
        
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User not found. Check email or student ID."]);
}

$stmt->close();
$conn->close();

// Helper function to mask email for privacy
function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    
    $nameLength = strlen($name);
    if ($nameLength <= 2) {
        $maskedName = str_repeat('*', $nameLength);
    } else {
        $visibleChars = min(2, floor($nameLength / 2));
        $maskedName = substr($name, 0, $visibleChars) . str_repeat('*', $nameLength - $visibleChars);
    }
    
    return $maskedName . '@' . $domain;
}
?>