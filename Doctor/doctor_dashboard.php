<?php 
session_start();
require_once '../ClassAutoLoad.php';

// Guard
$ObjAuth->requireLogin();
$ObjAuth->requireRole('coordinator');

$conn       = $SQL->getConnection();
$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];

// --- Stats ---
$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE receiving_hospital_id = :hid AND status IN ('Approved','Rejected','Scheduled','Completed')");
$stmt->execute([':hid' => $hospitalId]);
$totalIncoming = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE receiving_hospital_id = :hid AND status = 'Submitted'");
$stmt->execute([':hid' => $hospitalId]);
$pendingIncoming = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE sending_hospital_id = :hid AND status IN ('Approved','Rejected','Scheduled','Completed')");
$stmt->execute([':hid' => $hospitalId]);
$totalOutgoing = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE sending_hospital_id = :hid AND status = 'Awaiting Review'");
$stmt->execute([':hid' => $hospitalId]);
$pendingReferrals = $stmt->fetchColumn();

// Incoming
$stmt = $conn->prepare("
SELECT r.referral_id, r.diagnosis, r.urgency_level, r.status, r.referral_date,
p.full_name AS patient_name,
h.hospital_name AS sending_hospital
FROM referral r
JOIN patient p ON r.patient_id = p.patient_id
JOIN hospital h ON r.sending_hospital_id = h.hospital_id
WHERE r.receiving_hospital_id = :hid
AND r.status IN ('Approved','Rejected','Scheduled','Completed')
ORDER BY r.referral_date DESC
LIMIT 5
");
$stmt->execute([':hid' => $hospitalId]);
$incomingReferrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Outgoing
$stmt = $conn->prepare("
SELECT r.referral_id, r.diagnosis, r.urgency_level, r.status, r.referral_date,
p.full_name AS patient_name,
h.hospital_name AS receiving_hospital
FROM referral r
JOIN patient p ON r.patient_id = p.patient_id
JOIN hospital h ON r.receiving_hospital_id = h.hospital_id
WHERE r.sending_hospital_id = :hid
AND r.status IN ('Approved','Rejected','Scheduled','Completed')
ORDER BY r.referral_date DESC
LIMIT 5
");
$stmt->execute([':hid' => $hospitalId]);
$outgoingReferrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notifications
$stmt = $conn->prepare("SELECT message, notification_type, sent_at, is_read FROM notification WHERE user_id = :uid ORDER BY sent_at DESC LIMIT 5");
$stmt->execute([':uid' => $userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT COUNT(*) FROM notification WHERE user_id = :uid AND is_read = 0");
$stmt->execute([':uid' => $userId]);
$unreadCount = $stmt->fetchColumn();

// Status badge
function statusBadge(string $status): string
{
    $colours = [
        'Submitted'   => '#3b82f6',
        'Approved'    => '#10b981',
        'Rejected'    => '#ef4444',
        'Scheduled'   => '#8b5cf6',
        'Completed'   => '#6b7280',
    ];

    $c = $colours[$status] ?? '#6b7280';
    return "<span class='status-badge' style='background:$c'>$status</span>";
}

$Objlayout->header($conf, '../');
?>

<div class="coord-layout">

<!-- SIDEBAR -->
<aside class="sidebar">

<div class="logo"><?= htmlspecialchars($conf['site_name']) ?></div>
<div class="subtitle">Hospital Referral System</div>

<nav>
<ul>

<li><a href="coord_dashboard.php" class="active">📊 Dashboard</a></li>

<li>
<a href="pending_referrals.php">
⏳ Pending Referrals
<?php if ($pendingReferrals > 0): ?>
<span class="badge-warning"><?= $pendingReferrals ?></span>
<?php endif; ?>
</a>
</li>

<li><a href="incoming_referrals.php">📥 Incoming Referrals</a></li>
<li><a href="outgoing_referrals.php">📤 Outgoing Referrals</a></li>
<li><a href="referral_history.php">📁 Referral History</a></li>

<li>
<a href="notifications.php">
🔔 Notifications
<?php if ($unreadCount > 0): ?>
<span class="badge-danger"><?= $unreadCount ?></span>
<?php endif; ?>
</a>
</li>

</ul>
</nav>

<div class="sidebar-footer">

<div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
<div class="hospital-name"><?= htmlspecialchars($_SESSION['hospital_name']) ?></div>
<div class="user-role">Referral Coordinator</div>

<a href="../signout.php" class="btn-logout">🚪 Logout</a>

</div>

</aside>

<!-- MAIN -->
<main class="main-content">

<div class="page-header">
<h1>Coordinator Dashboard</h1>
<p>
Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?> —
<?= htmlspecialchars($_SESSION['hospital_name']) ?> |
<?= date('l, d F Y') ?>
</p>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
<?php unset($_SESSION['error']); endif; ?>

<!-- STATS -->
<div class="stats-grid">

<div class="stat-card" style="border-left:4px solid #f59e0b">
<div class="stat-icon">⏳</div>
<div class="stat-value" style="color:#f59e0b"><?= $pendingReferrals ?></div>
<div class="stat-label">Pending Referrals</div>
</div>

<div class="stat-card" style="border-left:4px solid #3b82f6">
<div class="stat-icon">📥</div>
<div class="stat-value" style="color:#3b82f6"><?= $totalIncoming ?></div>
<div class="stat-label">Incoming Referrals</div>
</div>

<div class="stat-card" style="border-left:4px solid #8b5cf6">
<div class="stat-icon">📤</div>
<div class="stat-value" style="color:#8b5cf6"><?= $totalOutgoing ?></div>
<div class="stat-label">Outgoing Referrals</div>
</div>

</div>
<!-- GRID -->
<div class="grid-2">

<!-- INCOMING -->
<div class="card">

<div class="card-header">
<h3>📥 Incoming Referrals</h3>
<a class="view-all" href="incoming_referrals.php">View all →</a>
</div>

<?php if (empty($incomingReferrals)): ?>
<div class="empty-state">No incoming referrals yet.</div>
<?php else: ?>

<table class="referral-table">
<thead>
<tr>
<th>Patient</th>
<th>From</th>
<th>Urgency</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($incomingReferrals as $r): ?>
<tr>
<td><?= htmlspecialchars($r['patient_name']) ?></td>
<td><?= htmlspecialchars($r['sending_hospital']) ?></td>
<td><?= htmlspecialchars($r['urgency_level']) ?></td>
<td><?= statusBadge($r['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php endif; ?>

</div>

<!-- OUTGOING -->
<div class="card">

<div class="card-header">
<h3>📤 Outgoing Referrals</h3>
<a class="view-all" href="outgoing_referrals.php">View all →</a>
</div>

<?php if (empty($outgoingReferrals)): ?>
<div class="empty-state">No outgoing referrals yet.</div>
<?php else: ?>

<table class="referral-table">
<thead>
<tr>
<th>Patient</th>
<th>To</th>
<th>Urgency</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($outgoingReferrals as $r): ?>
<tr>
<td><?= htmlspecialchars($r['patient_name']) ?></td>
<td><?= htmlspecialchars($r['receiving_hospital']) ?></td>
<td><?= htmlspecialchars($r['urgency_level']) ?></td>
<td><?= statusBadge($r['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php endif; ?>

</div>

</div>

<!-- NOTIFICATIONS -->
<div class="card">

<div class="card-header">
<h3>🔔 Recent Notifications</h3>
<a class="view-all" href="notifications.php">View all →</a>
</div>

<?php if (empty($notifications)): ?>
<div class="empty-state">No notifications yet.</div>
<?php else: ?>

<?php foreach ($notifications as $n): ?>
<div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
<p><?= htmlspecialchars($n['message']) ?></p>
<small><?= date('d M Y, H:i', strtotime($n['sent_at'])) ?></small>
</div>
<?php endforeach; ?>

<?php endif; ?>

</div>

</main>
</div>

<?php $Objlayout->footer($conf); ?>