<?php
session_start();
require_once 'ClassAutoLoad.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: change_password.php');
    exit;
}

// User must have verified OTP first
if (empty($_SESSION['change_password_user'])) {
    $_SESSION['error'] = 'Session expired. Please log in again.';
    header('Location: signin.php');
    exit;
}

$userId = (int) $_SESSION['change_password_user'];

$newPassword     = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

// Validate input
if (!$newPassword || !$confirmPassword) {
    $_SESSION['error'] = 'Please fill in all fields.';
    header('Location: change_password.php');
    exit;
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = 'Passwords do not match.';
    header('Location: change_password.php');
    exit;
}

if (strlen($newPassword) < 8) {
    $_SESSION['error'] = 'Password must be at least 8 characters long.';
    header('Location: change_password.php');
    exit;
}

// Prevent using the default password again
if ($newPassword === 'password') {
    $_SESSION['error'] = 'Please choose a different password.';
    header('Location: change_password.php');
    exit;
}

// Update password
$updated = $ObjAuth->changePassword($userId, $newPassword);

if (!$updated) {
    $_SESSION['error'] = 'Failed to update password. Please try again.';
    header('Location: change_password.php');
    exit;
}

// Clear temporary session data
unset($_SESSION['change_password_user']);
unset($_SESSION['user_id']);
unset($_SESSION['logged_in']);

session_regenerate_id(true);

$_SESSION['success'] = 'Password changed successfully. Please sign in using your new password.';

header('Location: signin.php');
exit;
?>