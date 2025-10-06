<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'marktevar15@gmail.com';      // CHANGE THIS
    $mail->Password   = 'xnqqmtlriagenuam';        // CHANGE THIS (your app password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Recipients
    $mail->setFrom('marktevar15@gmail.com', 'iVote Test');  // CHANGE THIS
    $mail->addAddress('marktevar15@gmail.com');             // Send to yourself
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'iVote Test Email';
    $mail->Body    = '<h1>Success!</h1><p>PHPMailer is working correctly with Gmail SMTP.</p>';
    
    $mail->send();
    echo '✅ Test email sent successfully! Check your inbox.';
    
} catch (Exception $e) {
    echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>