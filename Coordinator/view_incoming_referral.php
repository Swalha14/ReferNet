<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('coordinator');

$conn = $SQL->getConnection();

$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];

$referralId = (int)($_GET['id'] ?? 0);

if (!$referralId) {
    $_SESSION['error'] = 'Invalid referral selected.';
    header('Location: incoming_referrals.php');
    exit;
}

/*
|----------------------------------------------------------
| Fetch referral
|----------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT r.*, 
       p.full_name AS patient_name,
       p.gender,
       p.date_of_birth,
       p.national_id,
       p.phone_number,
       p.address,
       h1.hospital_name AS sending_hospital,
       h2.hospital_name AS receiving_hospital,
       u.full_name AS doctor_name
FROM referral r
JOIN patient p ON r.patient_id = p.patient_id
JOIN hospital h1 ON r.sending_hospital_id = h1.hospital_id
JOIN hospital h2 ON r.receiving_hospital_id = h2.hospital_id
JOIN user u ON r.doctor_id = u.user_id
WHERE r.referral_id = :rid
AND r.receiving_hospital_id = :hid
LIMIT 1
");

$stmt->execute([
    ':rid' => $referralId,
    ':hid' => $hospitalId
]);

$referral = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$referral) {
    $_SESSION['error'] = 'Referral not found.';
    header('Location: incoming_referrals.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $notes  = trim($_POST['review_notes'] ?? '');

    if ($action === 'approve') {

        $stmt = $conn->prepare("
            UPDATE referral
            SET status = 'Approved',
                receiving_coordinator_id = :uid,
                review_notes = :notes,
                decision_date = NOW()
            WHERE referral_id = :rid
        ");

        $stmt->execute([
            ':uid'   => $userId,
            ':notes' => $notes,
            ':rid'   => $referralId
        ]);

        $ObjSendMail->sendReferralNotification(
            $_SESSION['email'],
            $_SESSION['full_name'],
            'Referral Approved',
            "Referral ID {$referralId} has been approved."
        );

        $_SESSION['success'] = 'Referral approved successfully.';
        header('Location: incoming_referrals.php');
        exit;
    }

    if ($action === 'reject') {

        if (!$notes) {
            $_SESSION['error'] = 'Rejection reason required.';
            header("Location: view_incoming_referral.php?id={$referralId}");
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE referral
            SET status = 'Rejected',
                receiving_coordinator_id = :uid,
                review_notes = :notes,
                decision_date = NOW()
            WHERE referral_id = :rid
        ");

        $stmt->execute([
            ':uid'   => $userId,
            ':notes' => $notes,
            ':rid'   => $referralId
        ]);

        $_SESSION['success'] = 'Referral rejected.';
        header('Location: incoming_referrals.php');
        exit;
    }
}
?>
<?php $Objlayout->header($conf,'../'); ?>

<link rel="stylesheet" href="../CSS/coord.css">

<div class="coord-layout">

    <!-- SIDEBAR (reuse your existing sidebar include if you have one) -->
    <aside class="sidebar">
        <div class="logo"><?= htmlspecialchars($conf['site_name']) ?></div>
        <div class="subtitle">Hospital Referral System</div>

        <ul>
            <li><a href="coord_dashboard.php">Dashboard</a></li>
            <li><a href="incoming_referrals.php">Incoming</a></li>
            <li><a href="outgoing_referrals.php">Outgoing</a></li>
            <li><a href="pending_referrals.php">Pending</a></li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <div class="hospital-name"><?= htmlspecialchars($_SESSION['hospital_name']) ?></div>
            <div class="user-role">Coordinator</div>

            <a class="btn-logout" href="../signout.php">Logout</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="page-header">
            <h1>Referral Details</h1>
            <p>Review incoming referral before decision</p>
        </div>

        <!-- PATIENT CARD -->
        <div class="card" style="margin-bottom:15px;">
            <div class="card-header">
                <h3>Patient Information</h3>
            </div>

            <p><b>Name:</b> <?= htmlspecialchars($referral['patient_name']) ?></p>
            <p><b>Gender:</b> <?= htmlspecialchars($referral['gender']) ?></p>
            <p><b>DOB:</b> <?= htmlspecialchars($referral['date_of_birth']) ?></p>
            <p><b>ID:</b> <?= htmlspecialchars($referral['national_id']) ?></p>
            <p><b>Phone:</b> <?= htmlspecialchars($referral['phone_number']) ?></p>
            <p><b>Address:</b> <?= htmlspecialchars($referral['address']) ?></p>
        </div>

        <!-- REFERRAL INFO -->
        <div class="card" style="margin-bottom:15px;">
            <div class="card-header">
                <h3>Referral Information</h3>
            </div>

            <p><b>Doctor:</b> <?= htmlspecialchars($referral['doctor_name']) ?></p>
            <p><b>From:</b> <?= htmlspecialchars($referral['sending_hospital']) ?></p>
            <p><b>To:</b> <?= htmlspecialchars($referral['receiving_hospital']) ?></p>
            <p><b>Urgency:</b> <?= htmlspecialchars($referral['urgency_level']) ?></p>
            <p><b>Status:</b> <?= htmlspecialchars($referral['status']) ?></p>
        </div>

        <!-- CLINICAL INFO -->
        <div class="card" style="margin-bottom:15px;">
            <div class="card-header">
                <h3>Clinical Information</h3>
            </div>

            <p><b>Diagnosis:</b><br><?= nl2br(htmlspecialchars($referral['diagnosis'])) ?></p>
            <p><b>Reason:</b><br><?= nl2br(htmlspecialchars($referral['referral_reason'])) ?></p>
            <p><b>Findings:</b><br><?= nl2br(htmlspecialchars($referral['examination_findings'])) ?></p>
            <p><b>Treatment:</b><br><?= nl2br(htmlspecialchars($referral['treatment_given'])) ?></p>
            <p><b>Summary:</b><br><?= nl2br(htmlspecialchars($referral['clinical_summary'])) ?></p>
        </div>

        <!-- ACTIONS -->
        <?php if ($referral['status'] === 'Pending Validation'): ?>

        <div class="card">

            <form method="POST">

                <label><b>Review Notes</b></label><br>
                <textarea name="review_notes" style="width:100%;height:100px;margin-top:8px;"></textarea>

                <div style="margin-top:15px;display:flex;gap:10px;">

                    <button type="submit" name="action" value="approve"
                            class="btn-view" style="background:#10b981;">
                        Approve
                    </button>

                    <button type="submit" name="action" value="reject"
                            class="btn-view" style="background:#ef4444;">
                        Reject
                    </button>

                </div>

            </form>

        </div>

        <?php else: ?>

        <div class="card">
            <b>Decision:</b> <?= htmlspecialchars($referral['status']) ?><br>
            <b>Notes:</b> <?= htmlspecialchars($referral['review_notes']) ?>
        </div>

        <?php endif; ?>

    </main>
</div>

<?php $Objlayout->footer($conf); ?>