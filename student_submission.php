<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'config/config.php';

// === Validate reCAPTCHA ===
$recaptchaSecret = $_ENV['RECAPTCHA_SECRET'];
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
$verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
$responseData = json_decode($verifyResponse);

if (!$responseData->success) {
    echo "<script>alert('reCAPTCHA failed. Please try again.'); window.history.back();</script>";
    exit;
}

// === Get Form Data ===
$name       = $_POST['name'] ?? '';
$email      = $_POST['email'] ?? '';
$phone      = $_POST['phone'] ?? '';
$education  = $_POST['education'] ?? '';
$questions  = $_POST['questions'] ?? '';

// === Save to DB ===
$stmt = $conn->prepare("INSERT INTO enrollments (name, email, phone, education, questions) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $phone, $education, $questions);
$stmt->execute();
$stmt->close();
$conn->close();

// === Send Email ===
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtpout.secureserver.net'; // GoDaddy SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom($_ENV['MAIL_USERNAME'], 'SkillTern Enrollment');
    $mail->addAddress($_ENV['MAIL_USERNAME'], 'Admin'); // Receiver

    $mail->isHTML(true);
    $mail->Subject = 'New Enrollment Submission';
    $mail->Body    = "
        <h2>New Enrollment</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Education:</strong> $education</p>
        <p><strong>Questions:</strong><br>$questions</p>
    ";

    $mail->send();
    echo "<script>alert('Enrollment submitted successfully!'); window.location.href = 'index.html';</script>";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
