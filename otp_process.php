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
$userId = (int) $_SESSION['otp_user_id'];

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

// Temporarily set user_id so currentUser() can fetch the record
$_SESSION['user_id'] = $userId;

// Fetch user details
$user = $ObjAuth->currentUser();

if (!$user) {
    unset($_SESSION['user_id']);

    $_SESSION['error'] = 'User not found. Please log in again.';
    header('Location: signin.php');
    exit;
}

// Clear OTP session variables
unset($_SESSION['otp_user_id']);
unset($_SESSION['otp_full_name']);
unset($_SESSION['otp_email']);

// Store the user for the password change process
$_SESSION['change_password_user'] = $user['user_id'];

// Remove temporary user_id session
unset($_SESSION['user_id']);

// Redirect to change password page
header('Location: change_password.php');
exit;
?>