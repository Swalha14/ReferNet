<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('coordinator');

$conn       = $SQL->getConnection();
$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];

// Pending Referrals
$stmt = $conn->prepare("
    SELECT
        r.referral_id,
        r.referral_date,
        r.diagnosis,
        r.urgency_level,
        r.status,
        p.full_name AS patient_name,
        d.full_name AS doctor_name,
        h.hospital_name AS receiving_hospital
    FROM referral r
    JOIN patient p ON r.patient_id = p.patient_id
    JOIN user d ON r.doctor_id = d.user_id
    JOIN hospital h ON r.receiving_hospital_id = h.hospital_id
    WHERE
        r.sending_hospital_id = :hid
        AND r.status = 'Awaiting Review'
    ORDER BY r.referral_date DESC
");

$stmt->execute([':hid' => $hospitalId]);
$pendingReferrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sidebar Counts
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM referral
    WHERE sending_hospital_id = :hid
    AND status = 'Awaiting Review'
");
$stmt->execute([':hid'=>$hospitalId]);
$pendingCount = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM referral
    WHERE receiving_hospital_id = :hid
    AND status = 'Pending Validation'
");
$stmt->execute([':hid'=>$hospitalId]);
$incomingCount = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM notification
    WHERE user_id = :uid
    AND is_read = 0
");
$stmt->execute([':uid'=>$userId]);
$unreadCount = $stmt->fetchColumn();

function statusBadge($status)
{
    $class = [
        'Awaiting Review'    => 'status-awaiting',
        'Pending Validation' => 'status-validation',
        'Approved'           => 'status-approved',
        'Rejected'           => 'status-rejected',
        'Scheduled'          => 'status-scheduled',
        'Completed'          => 'status-completed'
    ];

    $badge = $class[$status] ?? 'status-completed';

    return '<span class="status-badge '.$badge.'">'
        . htmlspecialchars($status) .
        '</span>';
}

$Objlayout->header($conf,'../');
?>

<link rel="stylesheet" href="../CSS/coord.css">

<div class="coord-layout">

    <aside class="sidebar">

        <div class="logo">
            <?= htmlspecialchars($conf['site_name']) ?>
        </div>

        <div class="subtitle">
            Hospital Referral System
        </div>

        <nav>

            <ul>

                <li>
                    <a href="coord_dashboard.php">
                        📊 Dashboard
                    </a>
                </li>

                <li>
                    <a href="pending_referrals.php" class="active">
                        ⏳ Pending Referrals

                        <?php if($pendingCount>0): ?>
                            <span class="badge-warning">
                                <?= $pendingCount ?>
                            </span>
                        <?php endif; ?>

                    </a>
                </li>

                <li>
                    <a href="incoming_referrals.php">
                        📥 Incoming Referrals

                        <?php if($incomingCount>0): ?>
                            <span class="badge-danger">
                                <?= $incomingCount ?>
                            </span>
                        <?php endif; ?>

                    </a>
                </li>

                <li>
                    <a href="outgoing_referrals.php">
                        📤 Outgoing Referrals
                    </a>
                </li>

                <li>
                    <a href="referral_history.php">
                        📁 Referral History
                    </a>
                </li>

                <li>
                    <a href="notifications.php">
                        🔔 Notifications

                        <?php if($unreadCount>0): ?>
                            <span class="badge-danger">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>

                    </a>
                </li>

            </ul>

        </nav>

        <div class="sidebar-footer">

            <div class="user-name">
                <?= htmlspecialchars($_SESSION['full_name']) ?>
            </div>

            <div class="hospital-name">
                <?= htmlspecialchars($_SESSION['hospital_name']) ?>
            </div>

            <div class="user-role">
                Referral Coordinator
            </div>

            <a href="../signout.php" class="btn-logout">
                🚪 Logout
            </a>

        </div>

    </aside>

    <main class="main-content">

        <div class="page-header">

            <h1>Pending Referrals</h1>

            <p>
                These referrals were submitted by doctors in your hospital and are awaiting your approval to be forwarded.
            </p>

        </div>

        <?php if(isset($_SESSION['success'])): ?>

            <div class="alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>

            <div class="alert-error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>

        <div class="card">

            <div class="card-header">

                <h3>⏳ Referrals Awaiting Review</h3>

                <div class="pending-pill">
                    <?= count($pendingReferrals) ?> Pending
                </div>

            </div>
            <?php if(empty($pendingReferrals)): ?>

    <div class="empty-state">

        <h3>No pending referrals.</h3>

        <p>
            Every referral submitted by doctors will appear here before
            being sent to the receiving hospital.
        </p>

    </div>

<?php else: ?>

<table class="referral-table">

    <thead>

        <tr>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Receiving Hospital</th>
            <th>Diagnosis</th>
            <th>Urgency</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Actions</th>
        </tr>

    </thead>

    <tbody>

    <?php foreach($pendingReferrals as $r): ?>

        <tr>

            <td>
                <?= htmlspecialchars($r['patient_name']) ?>
            </td>

            <td>
                <?= htmlspecialchars($r['doctor_name']) ?>
            </td>

            <td>
                <?= htmlspecialchars($r['receiving_hospital']) ?>
            </td>

            <td>
                <?= htmlspecialchars($r['diagnosis']) ?>
            </td>

            <td>

                <span class="urgency-badge urgency-<?= strtolower($r['urgency_level']) ?>">

                    <?= htmlspecialchars($r['urgency_level']) ?>

                </span>

            </td>

            <td>

                <?= statusBadge($r['status']) ?>

            </td>

            <td>

                <?= date('d M Y H:i', strtotime($r['referral_date'])) ?>

            </td>

            <td class="actions">

                <a href="view_referral.php?id=<?= $r['referral_id'] ?>" class="btn-view">

                    View

                </a>

            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

<?php endif; ?>

        </div>

    </main>

</div>

<?php
$Objlayout->footer($conf);
?>