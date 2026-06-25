<?php
session_start();
require_once 'ClassAutoLoad.php';

// Must be logged in
$ObjAuth->requireLogin();

// Redirect to correct dashboard based on role
switch ($_SESSION['role']) {
    case 'doctor':
        header('Location: Doctor/doctor_dashboard.php');
        exit;

    case 'coordinator':
        header('Location: Coordinator/coord_dashboard.php');
        exit;

    default:
        // Unknown role — destroy session and send back to login
        $ObjAuth->logout();
        exit;
}
?>