<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('coordinator');

$conn=$SQL->getConnection();

$userId=$_SESSION['user_id'];
$hospitalId=$_SESSION['hospital_id'];

/*
|--------------------------------------------------------------------------
| Sidebar Counts
|--------------------------------------------------------------------------
*/

$stmt=$conn->prepare("
SELECT COUNT(*)
FROM referral
WHERE sending_hospital_id=:hid
AND status='Awaiting Review'
");
$stmt->execute([':hid'=>$hospitalId]);
$pendingCount=$stmt->fetchColumn();

$stmt=$conn->prepare("
SELECT COUNT(*)
FROM referral
WHERE receiving_hospital_id=:hid
AND status='Pending Validation'
");
$stmt->execute([':hid'=>$hospitalId]);
$incomingCount=$stmt->fetchColumn();

$stmt=$conn->prepare("
SELECT COUNT(*)
FROM notification
WHERE user_id=:uid
AND is_read=0
");
$stmt->execute([':uid'=>$userId]);
$unreadCount=$stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search=trim($_GET['search']??'');
$status=trim($_GET['status']??'');

/*
|--------------------------------------------------------------------------
| Outgoing Referrals
|--------------------------------------------------------------------------
| Sent from this hospital, but exclude Awaiting Review
|--------------------------------------------------------------------------
*/

$sql="
SELECT
r.referral_id,
r.referral_date,
r.diagnosis,
r.urgency_level,
r.status,
p.full_name AS patient_name,
h.hospital_name AS receiving_hospital,
d.full_name AS doctor_name
FROM referral r
JOIN patient p ON r.patient_id=p.patient_id
JOIN hospital h ON r.receiving_hospital_id=h.hospital_id
JOIN user d ON r.doctor_id=d.user_id
WHERE r.sending_hospital_id=:hid
AND r.status!='Awaiting Review'
";

$params=[
':hid'=>$hospitalId
];

if($search!==''){
$sql.="
AND(
p.full_name LIKE :search
OR r.referral_id LIKE :search
OR r.diagnosis LIKE :search
)
";
$params[':search']="%{$search}%";
}

if($status!==''){
$sql.="
AND r.status=:status
";
$params[':status']=$status;
}

$sql.=" ORDER BY r.referral_date DESC";

$stmt=$conn->prepare($sql);
$stmt->execute($params);

$referrals=$stmt->fetchAll(PDO::FETCH_ASSOC);

function statusBadge($status){

$class=[
'Pending Validation'=>'status-validation',
'Approved'=>'status-approved',
'Rejected'=>'status-rejected',
'Scheduled'=>'status-scheduled',
'Completed'=>'status-completed'
];

$badge=$class[$status]??'status-completed';

return '<span class="status-badge '.$badge.'">'
.htmlspecialchars($status).
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

        <ul>

            <li>
                <a href="coord_dashboard.php">
                    📊 Dashboard
                </a>
            </li>

            <li>
                <a href="pending_referrals.php">
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
                        <span class="badge-warning">
                            <?= $incomingCount ?>
                        </span>
                    <?php endif; ?>

                </a>
            </li>

            <li>
                <a href="outgoing_referrals.php" class="active">
                    📤 Outgoing Referrals
                </a>
            </li>

            <li>
                <a href="referral_history.php">
                    📁 Referral History
                </a>
            </li>

            <li>
                <a href="coord_notifications.php">
                    🔔 Notifications

                    <?php if($unreadCount>0): ?>
                        <span class="badge-danger">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>

                </a>
            </li>

        </ul>

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
            <h1>Outgoing Referrals</h1>
            <p>Referrals sent from <?= htmlspecialchars($_SESSION['hospital_name']) ?>.</p>
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

                <h3>Outgoing Referrals</h3>

                <span class="pending-pill">
                    <?= count($referrals) ?> Referral<?= count($referrals)!=1?'s':'' ?>
                </span>

            </div>

            <!-- FILTERS -->
            <form method="GET" class="filter-bar">

                <input
                    type="text"
                    name="search"
                    placeholder="Search patient, diagnosis or referral ID..."
                    value="<?= htmlspecialchars($search) ?>"
                    class="filter-input">

                <select name="status" class="filter-select">

                    <option value="">All Statuses</option>

                    <option value="Approved" <?= $status=='Approved'?'selected':'' ?>>Approved</option>
                    <option value="Rejected" <?= $status=='Rejected'?'selected':'' ?>>Rejected</option>
                    <option value="Scheduled" <?= $status=='Scheduled'?'selected':'' ?>>Scheduled</option>
                    <option value="Completed" <?= $status=='Completed'?'selected':'' ?>>Completed</option>

                </select>

                <button class="btn-view">
                    Search
                </button>

            </form>

            <?php if(empty($referrals)): ?>

                <div class="empty-state">
                    No outgoing referrals found.
                </div>

            <?php else: ?>

                <table class="referral-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Receiving Hospital</th>
                            <th>Diagnosis</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="actions">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach($referrals as $row): ?>

                            <tr>

                                <td>#<?= $row['referral_id'] ?></td>

                                <td><?= htmlspecialchars($row['patient_name']) ?></td>

                                <td><?= htmlspecialchars($row['doctor_name']) ?></td>

                                <td><?= htmlspecialchars($row['receiving_hospital']) ?></td>

                                <td><?= htmlspecialchars($row['diagnosis']) ?></td>

                                <td>
                                    <span class="urgency-badge urgency-<?= strtolower($row['urgency_level']) ?>">
                                        <?= htmlspecialchars($row['urgency_level']) ?>
                                    </span>
                                </td>

                                <td><?= statusBadge($row['status']) ?></td>

                                <td><?= date('d M Y',strtotime($row['referral_date'])) ?></td>

                                <td class="actions">
                                    <a href="view_outgoing_referral.php?id=<?= $row['referral_id'] ?>" class="btn-view">
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

<?php $Objlayout->footer($conf); ?>