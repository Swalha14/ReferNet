<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('doctor');

$conn       = $SQL->getConnection();
$userId     = $_SESSION['user_id'];
$referralId = (int)($_GET['id'] ?? 0);

if (!$referralId) {
    $_SESSION['error'] = 'Invalid referral.';
    header('Location: view_referrals.php');
    exit;
}

// Fetch referral — ensure it belongs to this doctor
$stmt = $conn->prepare(
    "SELECT r.*,
            p.full_name    AS patient_name,
            p.national_id, p.gender, p.date_of_birth, p.phone_number, p.address,
            hs.hospital_name AS sending_hospital,
            hr.hospital_name AS receiving_hospital,
            u.full_name    AS doctor_name,
            u.department,
            rc.full_name   AS referring_coord_name,
            rv.full_name   AS receiving_coord_name
     FROM referral r
     JOIN patient  p   ON r.patient_id              = p.patient_id
     JOIN hospital hs  ON r.sending_hospital_id     = hs.hospital_id
     JOIN hospital hr  ON r.receiving_hospital_id   = hr.hospital_id
     JOIN user     u   ON r.doctor_id               = u.user_id
     LEFT JOIN user rc ON r.referring_coordinator_id  = rc.user_id
     LEFT JOIN user rv ON r.receiving_coordinator_id  = rv.user_id
     WHERE r.referral_id = :rid AND r.doctor_id = :uid
     LIMIT 1"
);
$stmt->execute([':rid' => $referralId, ':uid' => $userId]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) {
    $_SESSION['error'] = 'Referral not found or access denied.';
    header('Location: view_referrals.php');
    exit;
}

// Fetch appointment if exists
$stmt = $conn->prepare(
    "SELECT * FROM appointment WHERE referral_id = :rid LIMIT 1"
);
$stmt->execute([':rid' => $referralId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

// Status badge
function statusBadge(string $status): string {
    $colours = [
        'Pending Validation' => '#f59e0b',
        'Submitted'          => '#3b82f6',
        'Approved'           => '#10b981',
        'Rejected'           => '#ef4444',
        'Scheduled'          => '#8b5cf6',
        'Completed'          => '#6b7280',
    ];
    $c = $colours[$status] ?? '#6b7280';
    return "<span style='background:{$c};color:white;padding:5px 14px;
                border-radius:20px;font-size:13px;font-weight:bold;'>{$status}</span>";
}

// Urgency badge
function urgencyBadge(string $urgency): string {
    $colours = [
        'Low'      => '#10b981',
        'Medium'   => '#f59e0b',
        'High'     => '#ef4444',
        'Critical' => '#7f1d1d',
    ];
    $c = $colours[$urgency] ?? '#6b7280';
    return "<span style='background:{$c};color:white;padding:5px 14px;
                border-radius:20px;font-size:13px;font-weight:bold;'>{$urgency}</span>";
}

// Status timeline steps
$steps = [
    'Pending Validation' => 1,
    'Submitted'          => 2,
    'Approved'           => 3,
    'Rejected'           => 3,
    'Scheduled'          => 4,
    'Completed'          => 5,
];
$currentStep = $steps[$r['status']] ?? 1;

$Objlayout->header($conf, '../');
?>

<div style="display:flex;min-height:100vh;font-family:Arial,sans-serif;">

    <!-- SIDEBAR -->
    <aside style="width:240px;background:#1d4ed8;color:white;padding:30px 20px;flex-shrink:0;">
        <div style="font-size:18px;font-weight:bold;margin-bottom:5px;">
            <?= htmlspecialchars($conf['site_name']) ?>
        </div>
        <div style="font-size:12px;opacity:0.75;margin-bottom:30px;">Hospital Referral System</div>
        <nav>
            <ul style="list-style:none;padding:0;margin:0;">
                <li style="margin-bottom:8px;">
                    <a href="doctor_dashboard.php"
                       style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;">
                        📊 Dashboard
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="create_referral.php"
                       style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;">
                        ➕ Create Referral
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="view_referrals.php"
                       style="color:white;text-decoration:none;display:block;padding:10px 14px;
                              border-radius:8px;background:rgba(255,255,255,0.15);">
                        📋 My Referrals
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
        <div style="padding-top:40px;border-top:1px solid rgba(255,255,255,0.2);margin-top:60px;">
            <div style="font-size:13px;font-weight:bold;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <div style="font-size:11px;opacity:0.75;"><?= htmlspecialchars($_SESSION['hospital_name']) ?></div>
            <div style="font-size:11px;opacity:0.75;margin-bottom:12px;"><?= htmlspecialchars($_SESSION['department']) ?></div>
            <a href="../signout.php" style="color:#bfdbfe;font-size:12px;text-decoration:none;">🚪 Logout</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main style="flex:1;padding:30px;background:#f4f6fb;">

        <!-- Back + Title -->
        <div style="margin-bottom:20px;">
            <a href="view_referrals.php"
               style="color:#1d4ed8;font-size:13px;text-decoration:none;">
                ← Back to My Referrals
            </a>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                <div>
                    <h1 style="margin:0;font-size:22px;color:#1f2937;">
                        Referral #<?= str_pad($r['referral_id'], 5, '0', STR_PAD_LEFT) ?>
                    </h1>
                    <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">
                        Submitted on <?= date('d M Y, H:i', strtotime($r['referral_date'])) ?>
                    </p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <?= urgencyBadge($r['urgency_level']) ?>
                    <?= statusBadge($r['status']) ?>
                </div>
            </div>
        </div>

        <!-- Status Timeline -->
        <div style="background:white;border-radius:12px;padding:20px;
                    box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:20px;">
            <h3 style="margin:0 0 16px;font-size:14px;color:#6b7280;">REFERRAL PROGRESS</h3>
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <?php
                $timelineSteps = [
                    1 => 'Pending Validation',
                    2 => 'Submitted',
                    3 => $r['status'] === 'Rejected' ? 'Rejected' : 'Approved',
                    4 => 'Scheduled',
                    5 => 'Completed',
                ];
                $isRejected = $r['status'] === 'Rejected';

                foreach ($timelineSteps as $num => $label):
                    $done    = $num < $currentStep;
                    $active  = $num === $currentStep;
                    $rejected = $isRejected && $num === 3;

                    $circleBg = $done ? '#10b981' : ($active ? ($rejected ? '#ef4444' : '#1d4ed8') : '#e5e7eb');
                    $textCol  = ($done || $active) ? '#1f2937' : '#9ca3af';
                ?>
                    <div style="display:flex;flex-direction:column;align-items:center;flex:1;">
                        <div style="width:32px;height:32px;border-radius:50%;background:<?= $circleBg ?>;
                                    color:white;display:flex;align-items:center;justify-content:center;
                                    font-size:13px;font-weight:bold;">
                            <?= $done ? '✓' : $num ?>
                        </div>
                        <div style="font-size:11px;color:<?= $textCol ?>;margin-top:6px;text-align:center;">
                            <?= $label ?>
                        </div>
                    </div>
                    <?php if ($num < 5): ?>
                        <div style="flex:1;height:2px;background:<?= $done ? '#10b981' : '#e5e7eb' ?>;
                                    margin-bottom:18px;"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <!-- Patient Details -->
            <div style="background:white;border-radius:12px;padding:20px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.07);">
                <h3 style="margin:0 0 14px;font-size:14px;color:#1d4ed8;border-bottom:2px solid #eff6ff;padding-bottom:8px;">
                    👤 Patient Information
                </h3>
                <?php
                $fields = [
                    'Full Name'    => $r['patient_name'],
                    'National ID'  => $r['national_id'],
                    'Gender'       => $r['gender'],
                    'Date of Birth'=> date('d M Y', strtotime($r['date_of_birth'])),
                    'Phone'        => $r['phone_number'],
                    'Address'      => $r['address'],
                ];
                foreach ($fields as $label => $value): ?>
                    <div style="display:flex;justify-content:space-between;
                                padding:6px 0;border-bottom:1px solid #f9fafb;font-size:13px;">
                        <span style="color:#6b7280;"><?= $label ?></span>
                        <span style="font-weight:bold;color:#1f2937;">
                            <?= htmlspecialchars($value ?? '—') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Referral Info -->
            <div style="background:white;border-radius:12px;padding:20px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.07);">
                <h3 style="margin:0 0 14px;font-size:14px;color:#1d4ed8;border-bottom:2px solid #eff6ff;padding-bottom:8px;">
                    🏥 Referral Information
                </h3>
                <?php
                $fields = [
                    'Referring Doctor'   => $r['doctor_name'],
                    'Department'         => $r['department'],
                    'Sending Hospital'   => $r['sending_hospital'],
                    'Receiving Hospital' => $r['receiving_hospital'],
                    'Referring Coord'    => $r['referring_coord_name'] ?? 'Not yet assigned',
                    'Receiving Coord'    => $r['receiving_coord_name'] ?? 'Not yet assigned',
                    'Decision Date'      => $r['decision_date'] ? date('d M Y', strtotime($r['decision_date'])) : '—',
                ];
                foreach ($fields as $label => $value): ?>
                    <div style="display:flex;justify-content:space-between;
                                padding:6px 0;border-bottom:1px solid #f9fafb;font-size:13px;">
                        <span style="color:#6b7280;"><?= $label ?></span>
                        <span style="font-weight:bold;color:#1f2937;">
                            <?= htmlspecialchars($value ?? '—') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Clinical Information -->
            <div style="background:white;border-radius:12px;padding:20px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.07);">
                <h3 style="margin:0 0 14px;font-size:14px;color:#059669;border-bottom:2px solid #f0fdf4;padding-bottom:8px;">
                    🩺 Clinical Information
                </h3>
                <?php
                $clinicalFields = [
                    'Diagnosis'            => $r['diagnosis'],
                    'Referral Reason'      => $r['referral_reason'],
                    'Examination Findings' => $r['examination_findings'] ?? '—',
                    'Treatment Given'      => $r['treatment_given'] ?? '—',
                    'Clinical Summary'     => $r['clinical_summary'],
                ];
                foreach ($clinicalFields as $label => $value): ?>
                    <div style="padding:8px 0;border-bottom:1px solid #f9fafb;font-size:13px;">
                        <div style="color:#6b7280;margin-bottom:3px;"><?= $label ?></div>
                        <div style="color:#1f2937;font-weight:bold;">
                            <?= nl2br(htmlspecialchars($value ?? '—')) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Right column: Rejection notes + Appointment -->
            <div>

                <?php if ($r['status'] === 'Rejected' && $r['review_notes']): ?>
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;
                                padding:20px;margin-bottom:20px;">
                        <h3 style="margin:0 0 10px;font-size:14px;color:#991b1b;">
                            ❌ Rejection Reason
                        </h3>
                        <p style="margin:0;font-size:13px;color:#7f1d1d;">
                            <?= nl2br(htmlspecialchars($r['review_notes'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($appointment): ?>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;">
                        <h3 style="margin:0 0 14px;font-size:14px;color:#059669;">
                            📅 Appointment Details
                        </h3>
                        <?php
                        $apptFields = [
                            'Date'       => date('d M Y', strtotime($appointment['appointment_date'])),
                            'Time'       => date('H:i', strtotime($appointment['appointment_time'])),
                            'Department' => $appointment['department'] ?? '—',
                            'Status'     => $appointment['status'],
                            'Notes'      => $appointment['notes'] ?? '—',
                        ];
                        foreach ($apptFields as $label => $value): ?>
                            <div style="display:flex;justify-content:space-between;
                                        padding:6px 0;border-bottom:1px solid #dcfce7;font-size:13px;">
                                <span style="color:#6b7280;"><?= $label ?></span>
                                <span style="font-weight:bold;color:#1f2937;">
                                    <?= htmlspecialchars($value) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (in_array($r['status'], ['Approved', 'Scheduled', 'Completed'])): ?>
                    <div style="background:#eff6ff;border-radius:12px;padding:20px;
                                font-size:13px;color:#1d4ed8;text-align:center;">
                        📅 Appointment details will appear here once scheduled.
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </main>
</div>

<?php $Objlayout->footer($conf); ?>