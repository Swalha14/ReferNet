<?php 
session_start();
require_once '../ClassAutoLoad.php';

// Guard
$ObjAuth->requireLogin();
$ObjAuth->requireRole('coordinator');

$conn = $SQL->getConnection();

$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];
$referralId = (int)($_GET['id'] ?? 0);

if (!$referralId) {
    $_SESSION['error'] = 'Invalid referral selected.';
    header('Location: pending_referrals.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Fetch Referral
| Coordinator can only view referrals created by doctors
| in THEIR hospital that are still awaiting submission.
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT
    r.*,

    p.full_name,
    p.gender,
    p.date_of_birth,
    p.national_id,
    p.phone_number,
    p.address,
    p.current_ward,

    d.full_name AS doctor_name,
    d.department,

    hs.hospital_name AS sending_hospital,
    hr.hospital_name AS receiving_hospital

FROM referral r

JOIN patient p
ON r.patient_id = p.patient_id

JOIN user d
ON r.doctor_id = d.user_id

JOIN hospital hs
ON r.sending_hospital_id = hs.hospital_id

JOIN hospital hr
ON r.receiving_hospital_id = hr.hospital_id

WHERE
    r.referral_id = :rid
AND r.sending_hospital_id = :hid
AND r.status = 'Awaiting Review'

LIMIT 1
");

$stmt->execute([
    ':rid' => $referralId,
    ':hid' => $hospitalId
]);

$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) {
    $_SESSION['error'] = 'Referral not found.';
    header('Location: pending_referrals.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Referring Coordinator
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT full_name
FROM user
WHERE hospital_id = :hid
AND role = 'coordinator'
AND is_active = 1
LIMIT 1
");

$stmt->execute([
    ':hid' => $hospitalId
]);

$referringCoordinator = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Sidebar Counts
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT COUNT(*)
FROM referral
WHERE sending_hospital_id=:hid
AND status='Submitted'
");
$stmt->execute([':hid'=>$hospitalId]);
$pendingCount = $stmt->fetchColumn();

$stmt = $conn->prepare("
SELECT COUNT(*)
FROM referral
WHERE receiving_hospital_id=:hid
AND status='Pending Validation'
");
$stmt->execute([':hid'=>$hospitalId]);
$incomingCount = $stmt->fetchColumn();

$stmt = $conn->prepare("
SELECT COUNT(*)
FROM notification
WHERE user_id=:uid
AND is_read=0
");
$stmt->execute([
    ':uid'=>$userId
]);
$unreadCount = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Badges
|--------------------------------------------------------------------------
*/

function statusBadge($status)
{
    $colours = [
        'Submitted'          => '#3b82f6',
        'Pending Validation' => '#f59e0b',
        'Approved'           => '#10b981',
        'Rejected'           => '#ef4444',
        'Scheduled'          => '#8b5cf6',
        'Completed'          => '#6b7280'
    ];

    $c = $colours[$status] ?? '#6b7280';

    return "<span style='
        background:$c;
        color:white;
        padding:6px 14px;
        border-radius:20px;
        font-size:13px;
        font-weight:bold;'>
        $status
    </span>";
}

function urgencyBadge($urgency)
{
    $colours = [
        'Low'      => '#10b981',
        'Medium'   => '#f59e0b',
        'High'     => '#ef4444',
        'Critical' => '#7f1d1d'
    ];

    $c = $colours[$urgency] ?? '#6b7280';

    return "<span style='
        background:$c;
        color:white;
        padding:6px 14px;
        border-radius:20px;
        font-size:13px;
        font-weight:bold;'>
        $urgency
    </span>";
}

/*
|--------------------------------------------------------------------------
| Timeline
|--------------------------------------------------------------------------
*/

$steps = [
    'Submitted'          => 1,
    'Pending Validation' => 2,
    'Approved'           => 3,
    'Scheduled'          => 4,
    'Completed'          => 5
];

$currentStep = $steps[$r['status']] ?? 1;

$Objlayout->header($conf,'../');
?>

<div style="display:flex;min-height:100vh;font-family:Arial,sans-serif;">

<!-- Sidebar -->

<aside style="width:240px;background:#1d4ed8;color:white;padding:30px 20px;display:flex;flex-direction:column;">

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
style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;">
📊 Dashboard
</a>
</li>

<li style="margin-bottom:8px;">
<a href="pending_referrals.php"
style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;background:rgba(255,255,255,.15);">
⏳ Pending Referrals
</a>
</li>

<li style="margin-bottom:8px;">
<a href="incoming_referrals.php"
style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;">
📥 Incoming Referrals
</a>
</li>

<li style="margin-bottom:8px;">
<a href="outgoing_referrals.php"
style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;">
📤 Outgoing Referrals
</a>
</li>

<li style="margin-bottom:8px;">
<a href="notifications.php"
style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;">
🔔 Notifications
</a>
</li>

</ul>

</nav>

<div style="margin-top:auto;padding-top:40px;border-top:1px solid rgba(255,255,255,.2);">

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
style="display:block;padding:12px;background:#dc2626;color:white;text-decoration:none;text-align:center;border-radius:8px;">
🚪 Logout
</a>

</div>

</aside>

<!-- Main -->

<main style="flex:1;padding:30px;background:#f4f6fb;">

<a href="pending_referrals.php"
style="text-decoration:none;color:#2563eb;font-size:13px;">
← Back to Pending Referrals
</a>

<div style="display:flex;justify-content:space-between;align-items:center;margin:15px 0 25px;">

<div>

<h1 style="margin:0;">
Referral #<?= str_pad($r['referral_id'],5,'0',STR_PAD_LEFT) ?>
</h1>

<p style="margin:5px 0 0;color:#6b7280;">
Submitted
<?= date('d M Y H:i',strtotime($r['referral_date'])) ?>
</p>

</div>

<div style="display:flex;gap:10px;">
<?= urgencyBadge($r['urgency_level']) ?>
<?= statusBadge($r['status']) ?>
</div>

</div>
<!-- Timeline -->

<div style="
background:white;
border-radius:12px;
padding:20px;
box-shadow:0 2px 8px rgba(0,0,0,.07);
margin-bottom:20px;">

<h3 style="margin:0 0 18px;color:#6b7280;font-size:14px;">
REFERRAL PROGRESS
</h3>

<div style="display:flex;align-items:center;justify-content:space-between;">

<?php

$timeline = [
    1 => 'Awaiting Review',
    2 => 'Pending Validation',
    3 => 'Approved',
    4 => 'Scheduled',
    5 => 'Completed'
];

foreach($timeline as $step=>$label):

$done = $step < $currentStep;
$active = $step == $currentStep;

$circleColour = $done
    ? '#10b981'
    : ($active ? '#3b82f6' : '#e5e7eb');

?>

<div style="display:flex;flex-direction:column;align-items:center;flex:1;">

<div style="
width:34px;
height:34px;
border-radius:50%;
background:<?= $circleColour ?>;
display:flex;
align-items:center;
justify-content:center;
color:white;
font-weight:bold;">

<?= $done ? '✓' : $step ?>

</div>

<div style="
margin-top:8px;
font-size:11px;
text-align:center;
color:<?= ($done||$active)?'#1f2937':'#9ca3af' ?>;">

<?= $label ?>

</div>

</div>

<?php if($step<5): ?>

<div style="
flex:1;
height:2px;
background:<?= $done ? '#10b981' : '#e5e7eb' ?>;
margin-bottom:22px;">
</div>

<?php endif; ?>

<?php endforeach; ?>

</div>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

<!-- Patient Information -->

<div style="
background:white;
border-radius:12px;
padding:20px;
box-shadow:0 2px 8px rgba(0,0,0,.07);">

<h3 style="
margin:0 0 15px;
color:#2563eb;
border-bottom:2px solid #eff6ff;
padding-bottom:8px;">

👤 Patient Information

</h3>

<?php

$patient = [

'Full Name'=>$r['full_name'],
'National ID'=>$r['national_id'],
'Gender'=>$r['gender'],
'Date of Birth'=>date('d M Y',strtotime($r['date_of_birth'])),
'Phone'=>$r['phone_number'] ?: '—',
'Ward'=>$r['current_ward'] ?: '—',
'Address'=>$r['address'] ?: '—'

];

foreach($patient as $label=>$value):

?>

<div style="
display:flex;
justify-content:space-between;
padding:7px 0;
border-bottom:1px solid #f3f4f6;">

<span style="color:#6b7280;">
<?= $label ?>
</span>

<span style="font-weight:bold;">
<?= htmlspecialchars($value) ?>
</span>

</div>

<?php endforeach; ?>

</div>

<!-- Referral Information -->

<div style="
background:white;
border-radius:12px;
padding:20px;
box-shadow:0 2px 8px rgba(0,0,0,.07);">

<h3 style="
margin:0 0 15px;
color:#2563eb;
border-bottom:2px solid #eff6ff;
padding-bottom:8px;">

🏥 Referral Information

</h3>

<?php

$info=[

'Doctor'=>$r['doctor_name'],
'Department'=>$r['department'],
'Sending Hospital'=>$r['sending_hospital'],
'Receiving Hospital'=>$r['receiving_hospital'],
'Referral Coordinator'=>$referringCoordinator ?: '—'

];

foreach($info as $label=>$value):

?>

<div style="
display:flex;
justify-content:space-between;
padding:7px 0;
border-bottom:1px solid #f3f4f6;">

<span style="color:#6b7280;">
<?= $label ?>
</span>

<span style="font-weight:bold;">
<?= htmlspecialchars($value) ?>
</span>

</div>

<?php endforeach; ?>

</div>

<!-- Clinical -->

<div style="
grid-column:1 / span 2;
background:white;
border-radius:12px;
padding:20px;
box-shadow:0 2px 8px rgba(0,0,0,.07);">

<h3 style="
margin:0 0 15px;
color:#059669;
border-bottom:2px solid #ecfdf5;
padding-bottom:8px;">

🩺 Clinical Information

</h3>

<?php

$clinical=[

'Diagnosis'=>$r['diagnosis'],
'Referral Reason'=>$r['referral_reason'],
'Examination Findings'=>$r['examination_findings'] ?: '—',
'Treatment Given'=>$r['treatment_given'] ?: '—',
'Clinical Summary'=>$r['clinical_summary']

];

foreach($clinical as $label=>$value):

?>

<div style="
padding:10px 0;
border-bottom:1px solid #f3f4f6;">

<div style="
font-size:13px;
color:#6b7280;
margin-bottom:4px;">

<?= $label ?>

</div>

<div style="
font-weight:bold;
color:#1f2937;">

<?= nl2br(htmlspecialchars($value)) ?>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<!-- Buttons -->

<div style="
display:flex;
justify-content:space-between;
margin-top:25px;">

<a href="pending_referrals.php"
style="
padding:12px 18px;
background:#6b7280;
color:white;
text-decoration:none;
border-radius:8px;
font-weight:bold;">

← Back

</a>

<a href="submit_referral.php?id=<?= $r['referral_id'] ?>"
onclick="return confirm('Submit this referral to the receiving hospital?');"
style="
padding:12px 20px;
background:#16a34a;
color:white;
text-decoration:none;
border-radius:8px;
font-weight:bold;">

 Submit Referral

</a>

</div>

</main>

</div>

<?php $Objlayout->footer($conf); ?>