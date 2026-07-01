<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('doctor');

$conn   = $SQL->getConnection();
$userId = $_SESSION['user_id'];

// Filter by status if provided
$statusFilter = trim($_GET['status'] ?? '');
$validStatuses = ['Pending Validation','Submitted','Approved','Rejected','Scheduled','Completed'];

$whereStatus = '';
$params      = [':uid' => $userId];

if ($statusFilter && in_array($statusFilter, $validStatuses)) {
    $whereStatus      = "AND r.status = :status";
    $params[':status'] = $statusFilter;
}

$stmt = $conn->prepare(
    "SELECT r.referral_id, r.diagnosis, r.urgency_level, r.status,
            r.referral_date, r.review_notes, r.decision_date,
            p.full_name  AS patient_name,
            p.national_id,
            p.gender,
            hs.hospital_name AS sending_hospital,
            hr.hospital_name AS receiving_hospital
     FROM referral r
     JOIN patient  p  ON r.patient_id            = p.patient_id
     JOIN hospital hs ON r.sending_hospital_id   = hs.hospital_id
     JOIN hospital hr ON r.receiving_hospital_id = hr.hospital_id
     WHERE r.doctor_id = :uid $whereStatus
     ORDER BY r.referral_date DESC"
);
$stmt->execute($params);
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status badge helper
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
    return "<span style='background:{$c};color:white;padding:3px 10px;
                border-radius:20px;font-size:12px;font-weight:bold;'>{$status}</span>";
}

// Urgency badge helper
function urgencyBadge(string $urgency): string {
    $colours = [
        'Low'      => '#10b981',
        'Medium'   => '#f59e0b',
        'High'     => '#ef4444',
        'Critical' => '#7f1d1d',
    ];
    $c = $colours[$urgency] ?? '#6b7280';
    return "<span style='background:{$c};color:white;padding:3px 10px;
                border-radius:20px;font-size:12px;font-weight:bold;'>{$urgency}</span>";
}

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
            <div>
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
                    font-size:15px;
                    box-shadow:0 2px 6px rgba(0,0,0,.2);">
                🚪 Logout
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main style="flex:1;padding:30px;background:#f4f6fb;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <div>
                <h1 style="margin:0;font-size:22px;color:#1f2937;">My Referrals</h1>
                <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">
                    All referrals you have submitted — <?= count($referrals) ?> found
                </p>
            </div>
            <a href="create_referral.php"
               style="background:#1d4ed8;color:white;padding:10px 20px;border-radius:8px;
                      text-decoration:none;font-weight:bold;font-size:13px;">
                ➕ New Referral
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:20px;">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
            <?php
            $tabs = ['All', 'Submitted','Pending Validation', 'Approved', 'Rejected', 'Scheduled', 'Completed'];
            foreach ($tabs as $tab):
                $val      = $tab === 'All' ? '' : $tab;
                $isActive = $statusFilter === $val;
                $bg       = $isActive ? '#1d4ed8' : 'white';
                $col      = $isActive ? 'white' : '#374151';
            ?>
                <a href="view_referrals.php<?= $val ? '?status=' . urlencode($val) : '' ?>"
                   style="padding:7px 14px;border-radius:20px;font-size:13px;text-decoration:none;
                          background:<?= $bg ?>;color:<?= $col ?>;
                          box-shadow:0 1px 4px rgba(0,0,0,0.1);">
                    <?= $tab ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Referrals Table -->
        <div style="background:white;border-radius:12px;padding:24px;
                    box-shadow:0 2px 8px rgba(0,0,0,0.07);">

            <?php if (empty($referrals)): ?>
                <div style="text-align:center;padding:40px 0;color:#9ca3af;">
                    <div style="font-size:40px;margin-bottom:10px;">📋</div>
                    <p style="font-size:14px;">No referrals found.</p>
                    <a href="create_referral.php"
                       style="color:#1d4ed8;font-size:13px;text-decoration:none;">
                        Create your first referral →
                    </a>
                </div>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="border-bottom:2px solid #f3f4f6;color:#6b7280;font-size:12px;">
                            <th style="text-align:left;padding:10px 8px;">REF ID</th>
                            <th style="text-align:left;padding:10px 8px;">Patient</th>
                            <th style="text-align:left;padding:10px 8px;">Diagnosis</th>
                            <th style="text-align:left;padding:10px 8px;">Receiving Hospital</th>
                            <th style="text-align:left;padding:10px 8px;">Urgency</th>
                            <th style="text-align:left;padding:10px 8px;">Status</th>
                            <th style="text-align:left;padding:10px 8px;">Date</th>
                            <th style="text-align:left;padding:10px 8px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referrals as $r): ?>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:10px 8px;color:#6b7280;font-size:12px;">
                                    #<?= str_pad($r['referral_id'], 5, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td style="padding:10px 8px;">
                                    <div style="font-weight:bold;"><?= htmlspecialchars($r['patient_name']) ?></div>
                                    <div style="font-size:11px;color:#9ca3af;"><?= htmlspecialchars($r['national_id']) ?></div>
                                </td>
                                <td style="padding:10px 8px;"><?= htmlspecialchars($r['diagnosis']) ?></td>
                                <td style="padding:10px 8px;font-size:12px;"><?= htmlspecialchars($r['receiving_hospital']) ?></td>
                                <td style="padding:10px 8px;"><?= urgencyBadge($r['urgency_level']) ?></td>
                                <td style="padding:10px 8px;"><?= statusBadge($r['status']) ?></td>
                                <td style="padding:10px 8px;color:#6b7280;font-size:12px;">
                                    <?= date('d M Y', strtotime($r['referral_date'])) ?>
                                </td>
                                <td style="padding:10px 8px;">
                                    <a href="referral_detail.php?id=<?= $r['referral_id'] ?>"
                                       style="color:#1d4ed8;font-size:12px;text-decoration:none;
                                              padding:4px 10px;border:1px solid #1d4ed8;border-radius:6px;">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php if ($r['status'] === 'Rejected' && $r['review_notes']): ?>
                                <tr style="background:#fff5f5;">
                                    <td colspan="8" style="padding:8px 16px;font-size:12px;color:#991b1b;">
                                        ❌ <strong>Rejection reason:</strong>
                                        <?= htmlspecialchars($r['review_notes']) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php $Objlayout->footer($conf); ?>