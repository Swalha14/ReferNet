<?php
session_start();
require_once 'ClassAutoLoad.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verify_otp.php');
    exit;
}

// Must have a pending OTP session
if (empty($_SESSION['otp_user_id'])) {
    $_SESSION['error'] = 'Session expired. Please log in again.';
    header('Location: signin.php');
    exit;
}

$submittedOtp = trim($_POST['otp'] ?? '');
$userId       = (int) $_SESSION['otp_user_id'];

// Basic validation
if (!$submittedOtp || strlen($submittedOtp) !== 6 || !ctype_digit($submittedOtp)) {
    $_SESSION['error'] = 'Please enter a valid 6-digit OTP.';
    header('Location: verify_otp.php');
    exit;
}

// Verify OTP
$result = $ObjAuth->verifyOTP($userId, $submittedOtp);

if ($result !== true) {
    $_SESSION['error'] = $result;
    header('Location: verify_otp.php');
    exit;
}

// OTP valid — set user_id so currentUser() can fetch details
$_SESSION['user_id'] = $userId;
$user = $ObjAuth->currentUser();

if (!$user) {
    $_SESSION['error'] = 'User not found. Please log in again.';
    header('Location: signin.php');
    exit;
}

// Clear temporary OTP session vars
unset($_SESSION['otp_user_id']);
unset($_SESSION['otp_full_name']);
unset($_SESSION['otp_email']);

// First login — force password change
if (empty($user['password_changed'])) {
    // Store only the user_id for password change — not a full session
    unset($_SESSION['user_id']);
    $_SESSION['change_password_user'] = $userId;
    header('Location: change_password.php');
    exit;
}

// Password already set — create full session and go to dashboard
$ObjAuth->createSession($user);
header('Location: dashboard.php');
exit;
?>