<?php
session_start();
require_once 'ClassAutoLoad.php';

// If no pending OTP session, send back to login
if (empty($_SESSION['otp_user_id'])) {
    $_SESSION['error'] = 'Please log in first.';
    header('Location: signin.php');
    exit;
}

// If already fully logged in, go straight to dashboard
if (!empty($_SESSION['logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$Objlayout->header($conf);
$Objlayout->nav($conf);
?>

<section class="form-section">

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-box">
            <?= htmlspecialchars($_SESSION['error']); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="error-box" style="background:#dcfce7;color:#166534;">
            <?= htmlspecialchars($_SESSION['success']); ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="form-card">

        <!-- Show who the OTP was sent to -->
        <p style="text-align:center;font-size:13px;color:#6b7280;margin-bottom:5px;">
            Verifying login for
            <strong><?= htmlspecialchars($_SESSION['otp_full_name']) ?></strong><br>
            Code sent to <strong><?= htmlspecialchars($_SESSION['otp_email']) ?></strong>
        </p>

        <?php $Objform->otp(); ?>

    </div>

</section>

<?php
$Objlayout->footer($conf);
?>