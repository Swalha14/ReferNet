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
    // $result is an error message string
    $_SESSION['error'] = $result;
    header('Location: verify_otp.php');
    exit;
}

// OTP valid — fetch full user details BEFORE clearing temp session
$userId = $userId; // already set above

// Clear temporary OTP session vars
unset($_SESSION['otp_user_id']);
unset($_SESSION['otp_full_name']);
unset($_SESSION['otp_email']);

// Manually set user_id in session so currentUser() can find it
$_SESSION['user_id'] = $userId;

// Fetch full user details and create session
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

// Create full login session
$ObjAuth->createSession($user);

// Redirect to dashboard
header('Location: dashboard.php');
exit;
?>