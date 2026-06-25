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
// Incoming referrals (this hospital is receiving)
$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE receiving_hospital_id = :hid");
$stmt->execute([':hid' => $hospitalId]);
$totalIncoming = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE receiving_hospital_id = :hid AND status = 'Submitted'");
$stmt->execute([':hid' => $hospitalId]);
$pendingIncoming = $stmt->fetchColumn();

// Outgoing referrals (this hospital is sending)
$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE sending_hospital_id = :hid");
$stmt->execute([':hid' => $hospitalId]);
$totalOutgoing = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE sending_hospital_id = :hid AND status = 'Pending Validation'");
$stmt->execute([':hid' => $hospitalId]);
$pendingValidation = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE receiving_hospital_id = :hid AND status = 'Approved'");
$stmt->execute([':hid' => $hospitalId]);
$approvedCount = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM referral WHERE receiving_hospital_id = :hid AND status = 'Rejected'");
$stmt->execute([':hid' => $hospitalId]);
$rejectedCount = $stmt->fetchColumn();

// --- Incoming Referrals (last 5) — this hospital is receiving ---
$stmt = $conn->prepare(
    "SELECT r.referral_id, r.diagnosis, r.urgency_level, r.status, r.referral_date,
            p.full_name  AS patient_name,
            h.hospital_name AS sending_hospital
     FROM referral r
     JOIN patient  p ON r.patient_id         = p.patient_id
     JOIN hospital h ON r.sending_hospital_id = h.hospital_id
     WHERE r.receiving_hospital_id = :hid
     ORDER BY r.referral_date DESC
     LIMIT 5"
);
$stmt->execute([':hid' => $hospitalId]);
$incomingReferrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Outgoing Referrals (last 5) — this hospital is sending ---
$stmt = $conn->prepare(
    "SELECT r.referral_id, r.diagnosis, r.urgency_level, r.status, r.referral_date,
            p.full_name  AS patient_name,
            h.hospital_name AS receiving_hospital
     FROM referral r
     JOIN patient  p ON r.patient_id            = p.patient_id
     JOIN hospital h ON r.receiving_hospital_id  = h.hospital_id
     WHERE r.sending_hospital_id = :hid
     ORDER BY r.referral_date DESC
     LIMIT 5"
);
$stmt->execute([':hid' => $hospitalId]);
$outgoingReferrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Unread Notifications ---
$stmt = $conn->prepare(
    "SELECT n.message, n.notification_type, n.sent_at, n.is_read
     FROM notification n
     WHERE n.user_id = :uid
     ORDER BY n.sent_at DESC
     LIMIT 5"
);
$stmt->execute([':uid' => $userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT COUNT(*) FROM notification WHERE user_id = :uid AND is_read = 0");
$stmt->execute([':uid' => $userId]);
$unreadCount = $stmt->fetchColumn();

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
    $colour = $colours[$status] ?? '#6b7280';
    return "<span style='background:{$colour};color:white;padding:3px 10px;
                border-radius:20px;font-size:12px;font-weight:bold;'>
                {$status}
            </span>";
}

$Objlayout->header($conf);
?>

<div style="display:flex;min-height:100vh;font-family:Arial,sans-serif;">

    <!-- SIDEBAR -->
    <aside style="width:240px;background:#1d4ed8;color:white;padding:30px 20px;flex-shrink:0;">
        <div style="font-size:18px;font-weight:bold;margin-bottom:5px;">
            <?= htmlspecialchars($conf['site_name']) ?>
        </div>
        <div style="font-size:12px;opacity:0.75;margin-bottom:30px;">
            Hospital Referral System
        </div>

        <nav>
            <ul style="list-style:none;padding:0;margin:0;">
                <li style="margin-bottom:8px;">
                    <a href="coord_dashboard.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;background:rgba(255,255,255,0.15);">
                        📊 Dashboard
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="incoming_referrals.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;">
                        📥 Incoming Referrals
                        <?php if ($pendingIncoming > 0): ?>
                            <span style="background:#ef4444;color:white;border-radius:50%;
                                         padding:1px 7px;font-size:11px;margin-left:5px;">
                                <?= $pendingIncoming ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="outgoing_referrals.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;">
                        📤 Outgoing Referrals
                        <?php if ($pendingValidation > 0): ?>
                            <span style="background:#f59e0b;color:white;border-radius:50%;
                                         padding:1px 7px;font-size:11px;margin-left:5px;">
                                <?= $pendingValidation ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="referral_history.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;">
                        📁 Referral History
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="notifications.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;">
                        🔔 Notifications
                        <?php if ($unreadCount > 0): ?>
                            <span style="background:#ef4444;color:white;border-radius:50%;
                                         padding:1px 7px;font-size:11px;margin-left:5px;">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </nav>

        <div style="padding-top:40px;border-top:1px solid rgba(255,255,255,0.2);margin-top:60px;">
            <div style="font-size:13px;font-weight:bold;">
                <?= htmlspecialchars($_SESSION['full_name']) ?>
            </div>
            <div style="font-size:11px;opacity:0.75;">
                <?= htmlspecialchars($_SESSION['hospital_name']) ?>
            </div>
            <div style="font-size:11px;opacity:0.75;margin-bottom:12px;">
                Referral Coordinator
            </div>
            <a href="../signout.php"
               style="color:#bfdbfe;font-size:12px;text-decoration:none;">
                🚪 Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main style="flex:1;padding:30px;background:#f4f6fb;">

        <div style="margin-bottom:25px;">
            <h1 style="margin:0;font-size:22px;color:#1f2937;">Coordinator Dashboard</h1>
            <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">
                Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?> —
                <?= htmlspecialchars($_SESSION['hospital_name']) ?> |
                <?= date('l, d F Y') ?>
            </p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:20px;">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:20px;">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:25px;">
            <?php
            $stats = [
                ['label' => 'Incoming Referrals',   'value' => $totalIncoming,    'colour' => '#3b82f6', 'icon' => '📥'],
                ['label' => 'Pending Review',        'value' => $pendingIncoming,  'colour' => '#f59e0b', 'icon' => '⏳'],
                ['label' => 'Outgoing Referrals',    'value' => $totalOutgoing,    'colour' => '#8b5cf6', 'icon' => '📤'],
                ['label' => 'Pending Validation',    'value' => $pendingValidation,'colour' => '#f59e0b', 'icon' => '📋'],
                ['label' => 'Approved',              'value' => $approvedCount,    'colour' => '#10b981', 'icon' => '✅'],
                ['label' => 'Rejected',              'value' => $rejectedCount,    'colour' => '#ef4444', 'icon' => '❌'],
            ];
            foreach ($stats as $s): ?>
                <div style="background:white;border-radius:12px;padding:20px;
                            box-shadow:0 2px 8px rgba(0,0,0,0.07);
                            border-left:4px solid <?= $s['colour'] ?>;">
                    <div style="font-size:24px;margin-bottom:6px;"><?= $s['icon'] ?></div>
                    <div style="font-size:26px;font-weight:bold;color:<?= $s['colour'] ?>;">
                        <?= $s['value'] ?>
                    </div>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                        <?= $s['label'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Incoming + Outgoing Tables -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

            <!-- Incoming Referrals -->
            <div style="background:white;border-radius:12px;padding:20px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.07);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h3 style="margin:0;font-size:15px;color:#1f2937;">📥 Incoming Referrals</h3>
                    <a href="incoming_referrals.php"
                       style="font-size:12px;color:#1d4ed8;text-decoration:none;">View all →</a>
                </div>

                <?php if (empty($incomingReferrals)): ?>
                    <p style="color:#9ca3af;font-size:13px;text-align:center;padding:20px 0;">
                        No incoming referrals yet.
                    </p>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:2px solid #f3f4f6;color:#6b7280;">
                                <th style="text-align:left;padding:8px 6px;">Patient</th>
                                <th style="text-align:left;padding:8px 6px;">From</th>
                                <th style="text-align:left;padding:8px 6px;">Urgency</th>
                                <th style="text-align:left;padding:8px 6px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incomingReferrals as $r): ?>
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:8px 6px;"><?= htmlspecialchars($r['patient_name']) ?></td>
                                    <td style="padding:8px 6px;"><?= htmlspecialchars($r['sending_hospital']) ?></td>
                                    <td style="padding:8px 6px;"><?= htmlspecialchars($r['urgency_level']) ?></td>
                                    <td style="padding:8px 6px;"><?= statusBadge($r['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Outgoing Referrals -->
            <div style="background:white;border-radius:12px;padding:20px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.07);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h3 style="margin:0;font-size:15px;color:#1f2937;">📤 Outgoing Referrals</h3>
                    <a href="outgoing_referrals.php"
                       style="font-size:12px;color:#1d4ed8;text-decoration:none;">View all →</a>
                </div>

                <?php if (empty($outgoingReferrals)): ?>
                    <p style="color:#9ca3af;font-size:13px;text-align:center;padding:20px 0;">
                        No outgoing referrals yet.
                    </p>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:2px solid #f3f4f6;color:#6b7280;">
                                <th style="text-align:left;padding:8px 6px;">Patient</th>
                                <th style="text-align:left;padding:8px 6px;">To</th>
                                <th style="text-align:left;padding:8px 6px;">Urgency</th>
                                <th style="text-align:left;padding:8px 6px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($outgoingReferrals as $r): ?>
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:8px 6px;"><?= htmlspecialchars($r['patient_name']) ?></td>
                                    <td style="padding:8px 6px;"><?= htmlspecialchars($r['receiving_hospital']) ?></td>
                                    <td style="padding:8px 6px;"><?= htmlspecialchars($r['urgency_level']) ?></td>
                                    <td style="padding:8px 6px;"><?= statusBadge($r['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>

        <!-- Notifications -->
        <div style="background:white;border-radius:12px;padding:20px;
                    box-shadow:0 2px 8px rgba(0,0,0,0.07);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h3 style="margin:0;font-size:15px;color:#1f2937;">🔔 Recent Notifications</h3>
                <a href="notifications.php"
                   style="font-size:12px;color:#1d4ed8;text-decoration:none;">View all →</a>
            </div>

            <?php if (empty($notifications)): ?>
                <p style="color:#9ca3af;font-size:13px;text-align:center;padding:20px 0;">
                    No notifications yet.
                </p>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <div style="padding:10px 0;border-bottom:1px solid #f3f4f6;
                                <?= !$n['is_read'] ? 'background:#eff6ff;border-radius:6px;padding:10px;margin-bottom:6px;' : '' ?>">
                        <p style="margin:0;font-size:13px;color:#374151;">
                            <?= htmlspecialchars($n['message']) ?>
                        </p>
                        <p style="margin:3px 0 0;font-size:11px;color:#9ca3af;">
                            <?= date('d M Y, H:i', strtotime($n['sent_at'])) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php $Objlayout->footer($conf); ?>