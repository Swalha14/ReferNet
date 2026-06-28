<?php
session_start();
require_once 'ClassAutoLoad.php';

$Objlayout->header($conf);
$Objlayout->nav($conf);
?>

<section class="form-section">

    <div class="form-card">

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error-alert">
                <?= htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success-alert">
                <?= htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php $Objform->signin(); ?>

    </div>

</section>

<?php
$Objlayout->footer($conf);
?>