<?php
session_start();
require_once '../ClassAutoLoad.php';

// Guard
$ObjAuth->requireLogin();
$ObjAuth->requireRole('coordinator');

$conn       = $SQL->getConnection();
$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];


  // Pending Referrals
$stmt = $conn->prepare(
    "SELECT
        r.referral_id,
        r.referral_date,
        r.diagnosis,
        r.urgency_level,
        r.status,

        p.full_name AS patient_name,

        d.full_name AS doctor_name,

        h.hospital_name AS receiving_hospital

     FROM referral r

     JOIN patient p
        ON r.patient_id = p.patient_id

     JOIN user d
        ON r.doctor_id = d.user_id

     JOIN hospital h
        ON r.receiving_hospital_id = h.hospital_id

     WHERE
        r.sending_hospital_id = :hid
        AND r.status = 'Submitted'

     ORDER BY r.referral_date DESC"
);

$stmt->execute([
    ':hid' => $hospitalId
]);

$pendingReferrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Sidebar Counts
$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM referral
     WHERE sending_hospital_id=:hid
     AND status='Submitted'"
);
$stmt->execute([
    ':hid'=>$hospitalId
]);
$pendingCount = $stmt->fetchColumn();

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM referral
     WHERE receiving_hospital_id=:hid
     AND status='Pending Validation'"
);
$stmt->execute([
    ':hid'=>$hospitalId
]);
$incomingCount = $stmt->fetchColumn();

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM notification
     WHERE user_id=:uid
     AND is_read=0"
);
$stmt->execute([
    ':uid'=>$userId
]);
$unreadCount = $stmt->fetchColumn();

function statusBadge($status)
{
    $colour = [
        'Submitted'=>'#3b82f6',
        'Pending Validation'=>'#f59e0b',
        'Approved'=>'#10b981',
        'Rejected'=>'#ef4444',
        'Scheduled'=>'#8b5cf6',
        'Completed'=>'#6b7280'
    ];

    $c = $colour[$status] ?? '#6b7280';

    return "<span style='
            background:$c;
            color:white;
            padding:4px 10px;
            border-radius:20px;
            font-size:12px;
            font-weight:bold;'>
            $status
            </span>";
}

$Objlayout->header($conf,'../');
?>

<div style="display:flex;min-height:100vh;font-family:Arial,sans-serif;">

<!-- ================= SIDEBAR ================= -->

<aside style="
width:240px;
background:#1d4ed8;
color:white;
padding:30px 20px;
display:flex;
flex-direction:column;
flex-shrink:0;">

    <div style="font-size:18px;font-weight:bold;margin-bottom:5px;">
        <?= htmlspecialchars($conf['site_name']) ?>
    </div>

    <div style="font-size:12px;opacity:.75;margin-bottom:30px;">
        Hospital Referral System
    </div>

    <nav>

    <ul style="list-style:none;padding:0;margin:0;">

        <li style="margin-bottom:8px;">
            <a href="coord_dashboard.php"
               style="
                color:white;
                text-decoration:none;
                display:block;
                padding:10px 14px;
                border-radius:8px;">
                📊 Dashboard
            </a>
        </li>

        <li style="margin-bottom:8px;">
            <a href="pending_referrals.php"
               style="
                color:white;
                text-decoration:none;
                display:block;
                padding:10px 14px;
                border-radius:8px;
                background:rgba(255,255,255,.15);">

                ⏳ Pending Referrals

                <?php if($pendingCount>0): ?>

                    <span style="
                    background:#f59e0b;
                    color:white;
                    border-radius:50%;
                    padding:1px 7px;
                    font-size:11px;
                    margin-left:6px;">

                    <?= $pendingCount ?>

                    </span>

                <?php endif; ?>

            </a>
        </li>

        <li style="margin-bottom:8px;">
            <a href="incoming_referrals.php"
               style="
                color:white;
                text-decoration:none;
                display:block;
                padding:10px 14px;
                border-radius:8px;">

                📥 Incoming Referrals

                <?php if($incomingCount>0): ?>

                <span style="
                background:#ef4444;
                color:white;
                border-radius:50%;
                padding:1px 7px;
                font-size:11px;
                margin-left:6px;">

                <?= $incomingCount ?>

                </span>

                <?php endif; ?>

            </a>
        </li>

        <li style="margin-bottom:8px;">
            <a href="outgoing_referrals.php"
               style="
                color:white;
                text-decoration:none;
                display:block;
                padding:10px 14px;
                border-radius:8px;">
                📤 Outgoing Referrals
            </a>
        </li>

        <li style="margin-bottom:8px;">
            <a href="referral_history.php"
               style="
                color:white;
                text-decoration:none;
                display:block;
                padding:10px 14px;
                border-radius:8px;">
                📁 Referral History
            </a>
        </li>

        <li style="margin-bottom:8px;">
            <a href="notifications.php"
               style="
                color:white;
                text-decoration:none;
                display:block;
                padding:10px 14px;
                border-radius:8px;">

                🔔 Notifications

                <?php if($unreadCount>0): ?>

                <span style="
                background:#ef4444;
                color:white;
                border-radius:50%;
                padding:1px 7px;
                font-size:11px;
                margin-left:6px;">

                <?= $unreadCount ?>

                </span>

                <?php endif; ?>

            </a>
        </li>

    </ul>

    </nav>

    <div style="
    margin-top:auto;
    padding-top:40px;
    border-top:1px solid rgba(255,255,255,.2);">

        <div style="font-size:13px;font-weight:bold;">
            <?= htmlspecialchars($_SESSION['full_name']) ?>
        </div>

        <div style="font-size:11px;opacity:.75;">
            <?= htmlspecialchars($_SESSION['hospital_name']) ?>
        </div>

        <div style="font-size:11px;opacity:.75;margin-bottom:12px;">
            Referral Coordinator
        </div>

        <a href="../signout.php"
           style="
            display:block;
            margin-top:18px;
            padding:12px;
            background:#dc2626;
            color:white;
            text-align:center;
            text-decoration:none;
            border-radius:8px;
            font-weight:bold;
            font-size:15px;">

            🚪 Logout

        </a>

    </div>

</aside>

<!-- ================= MAIN CONTENT ================= -->

<main style="
flex:1;
padding:30px;
background:#f4f6fb;">

<div style="margin-bottom:25px;">

<h1 style="margin:0;font-size:24px;color:#1f2937;">
Pending Referrals
</h1>

<p style="margin:5px 0 0;color:#6b7280;">
These referrals were submitted by doctors in your hospital and are awaiting your approval to be forwarded.
</p>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div style="background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:20px;">
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:20px;">
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div style="
background:white;
border-radius:12px;
padding:20px;
box-shadow:0 2px 8px rgba(0,0,0,.07);">

<div style="
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:18px;">

<h3 style="margin:0;font-size:16px;color:#1f2937;">
⏳ Referrals Awaiting Submission
</h3>

<div style="
background:#eff6ff;
color:#2563eb;
padding:6px 12px;
border-radius:20px;
font-size:13px;
font-weight:bold;">

<?= count($pendingReferrals) ?> Pending
</div>
</div>

<?php if(empty($pendingReferrals)): ?>

<div style="
padding:50px;
text-align:center;
color:#9ca3af;">

<h3>No pending referrals.</h3>

<p>
Every referral submitted by doctors will appear here before
being sent to the receiving hospital.
</p>
</div>

<?php else: ?>

<table style="
width:100%;
border-collapse:collapse;
font-size:13px;">

<thead>

<tr style="
background:#f9fafb;
border-bottom:2px solid #e5e7eb;">
<th style="padding:12px;text-align:left;">Patient</th>
<th style="padding:12px;text-align:left;">Doctor</th>
<th style="padding:12px;text-align:left;">Receiving Hospital</th>
<th style="padding:12px;text-align:left;">Diagnosis</th>
<th style="padding:12px;text-align:left;">Urgency</th>
<th style="padding:12px;text-align:left;">Status</th>
<th style="padding:12px;text-align:left;">Submitted</th>
<th style="padding:12px;text-align:center;">Actions</th>
</tr>

</thead>

<tbody>

<?php foreach($pendingReferrals as $r): ?>

<tr style="border-bottom:1px solid #f3f4f6;">

<td style="padding:12px;">
<?= htmlspecialchars($r['patient_name']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($r['doctor_name']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($r['receiving_hospital']) ?>
</td>

<td style="padding:12px;">
<?= htmlspecialchars($r['diagnosis']) ?>
</td>

<?php
$urgencyColours = [
      'Low'      => '#10b981',
        'Medium'   => '#f59e0b',
        'High'     => '#ef4444',
        'Critical' => '#7f1d1d',
];
?>

<td style="padding:12px;">
    <span style="
        background:<?= $urgencyColours[$r['urgency_level']] ?? '#6b7280' ?>;
        color:white;
        padding:4px 10px;
        border-radius:20px;
        font-size:12px;
        font-weight:bold;">
        <?= htmlspecialchars($r['urgency_level']) ?>
    </span>
</td>

<td style="padding:12px;">
<?= statusBadge($r['status']) ?>
</td>

<td style="padding:12px;">
<?= date('d M Y H:i',strtotime($r['referral_date'])) ?>
</td>

<td style="padding:12px;text-align:center;white-space:nowrap;">

<a href="view_referral.php?id=<?= $r['referral_id'] ?>"
style="
display:inline-block;
padding:8px 12px;
background:#2563eb;
color:white;
text-decoration:none;
border-radius:6px;
font-size:12px;
margin-right:6px;">

View

</a>

<a href="submit_referral.php?id=<?= $r['referral_id'] ?>"
onclick="return confirm('Submit this referral to the receiving hospital?');"
style="
display:inline-block;
padding:8px 12px;
background:#16a34a;
color:white;
text-decoration:none;
border-radius:6px;
font-size:12px;">

Submit

</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table><?php endif; ?>
</div>
</main>
</div>

<?php
$Objlayout->footer($conf);
?>