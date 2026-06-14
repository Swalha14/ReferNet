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

    <div class="form-card">
        <?php $Objform->signup(); ?>
    </div>

</section>

<?php
$Objlayout->footer($conf);
?>