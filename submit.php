<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoload dependencies
require 'vendor/autoload.php';


// === reCAPTCHA ===
$recaptchaSecret = '6LdaUn0rAAAAAHnZksod31ZcBxjJh_zZq4nS_Qli';
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
$verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
$responseData = json_decode($verifyResponse);

if (!$responseData->success) {
    echo "<script>alert('reCAPTCHA failed.'); window.history.back();</script>";
    exit;
}

// === Form Data ===
$institutionName = $_POST['institutionName'] ?? '';
$contactPerson   = $_POST['contactPerson'] ?? '';
$designation     = $_POST['designation'] ?? '';
$location        = $_POST['location'] ?? '';
$email           = $_POST['email'] ?? '';
$phone           = $_POST['phone'] ?? '';
$message         = $_POST['message'] ?? '';
$otherProgram    = $_POST['otherProgram'] ?? '';
$programs = [];

for ($i = 1; $i <= 5; $i++) {
    if (!empty($_POST["program$i"])) {
        $programs[] = $_POST["program$i"];
    }
}
if (!empty($otherProgram)) {
    $programs[] = $otherProgram;
}
$programsStr = implode(', ', $programs);

// === Store in DB ===
require 'config/database.php';

$stmt = $conn->prepare("INSERT INTO partner_submissions (institution_name, contact_person, designation, location, email, phone, programs, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $institutionName, $contactPerson, $designation, $location, $email, $phone, $programsStr, $message);

if (!$stmt->execute()) {
    die("DB Error: " . $stmt->error);
}
$stmt->close();
$conn->close();

// === Send Email ===
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtpout.secureserver.net';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom($_ENV['MAIL_USERNAME'], 'SkillTern Website');
    $mail->addAddress('adithyak@sunrisedigital.co.in', 'Admin');

    $mail->isHTML(true);
    $mail->Subject = 'New Partner Form Submission';
    $mail->Body    = "
        <h2>New Partner Submission</h2>
        <p><strong>Institution Name:</strong> $institutionName</p>
        <p><strong>Contact Person:</strong> $contactPerson</p>
        <p><strong>Designation:</strong> $designation</p>
        <p><strong>Location:</strong> $location</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Programs:</strong> $programsStr</p>
        <p><strong>Message:</strong><br>$message</p>
    ";

    $mail->send();
    echo "<script>alert('Form submitted successfully!'); window.location.href = 'index.html';</script>";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
