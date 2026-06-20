<?php
session_start();
require_once 'ClassAutoLoad.php';

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
        <?php $Objform->onboarding(); ?>
    </div>

</section>

<?php
$Objlayout->footer($conf);
?>