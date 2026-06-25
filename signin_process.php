<?php
session_start();
require_once 'ClassAutoLoad.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signin.php');
    exit;
}

// Sanitise inputs
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role']     ?? '');

// Basic validation
if (!$email || !$password || !$role) {
    $_SESSION['error'] = 'Please fill in all fields.';
    header('Location: signin.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email address.';
    header('Location: signin.php');
    exit;
}

// Verify credentials against DB
$user = $ObjAuth->verifyCredentials($email, $password, $role);

if (!$user) {
    $_SESSION['error'] = 'Invalid email, password, or role. Please try again.';
    header('Location: signin.php');
    exit;
}

// Credentials valid — generate OTP
$otpCode = $ObjAuth->generateOTP($user['user_id']);

// Send OTP via PHPMailer
$sent = $ObjSendMail->sendOTP($user['email'], $user['full_name'], $otpCode);

if (!$sent) {
    $_SESSION['error'] = 'Failed to send OTP email. Please try again or contact support.';
    header('Location: signin.php');
    exit;
}

// Store user_id in session temporarily (not fully logged in yet)
$_SESSION['otp_user_id']   = $user['user_id'];
$_SESSION['otp_full_name'] = $user['full_name'];
$_SESSION['otp_email']     = $user['email'];

// Redirect to OTP verification page
$_SESSION['success'] = 'A 6-digit OTP has been sent to ' . $user['email'] . '. It expires in 2 minutes.';
header('Location: verify_otp.php');
exit;
?>