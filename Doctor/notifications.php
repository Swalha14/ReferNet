<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('doctor');

$conn   = $SQL->getConnection();
$userId = $_SESSION['user_id'];

// Mark all notifications as read when page is opened
$stmt = $conn->prepare(
    "UPDATE notification SET is_read = 1
     WHERE user_id = :uid AND is_read = 0"
);
$stmt->execute([':uid' => $userId]);

// Fetch all notifications for this doctor
$stmt = $conn->prepare(
    "SELECT n.notification_id, n.message, n.notification_type,
            n.is_read, n.sent_at, n.referral_id,
            p.full_name AS patient_name,
            r.diagnosis, r.status AS referral_status
     FROM notification n
     LEFT JOIN referral r ON n.referral_id = r.referral_id
     LEFT JOIN patient  p ON r.patient_id  = p.patient_id
     WHERE n.user_id = :uid
     ORDER BY n.sent_at DESC"
);
$stmt->execute([':uid' => $userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notification type icon + colour map
function notifStyle(string $type): array {
    $map = [
        'new_referral'       => ['icon' => '📋', 'colour' => '#3b82f6', 'bg' => '#eff6ff'],
        'referral_submitted' => ['icon' => '✅', 'colour' => '#10b981', 'bg' => '#f0fdf4'],
        'referral_forwarded' => ['icon' => '📤', 'colour' => '#8b5cf6', 'bg' => '#f5f3ff'],
        'approved'           => ['icon' => '✅', 'colour' => '#10b981', 'bg' => '#f0fdf4'],
        'rejected'           => ['icon' => '❌', 'colour' => '#ef4444', 'bg' => '#fef2f2'],
        'scheduled'          => ['icon' => '📅', 'colour' => '#8b5cf6', 'bg' => '#f5f3ff'],
        'completed'          => ['icon' => '🏁', 'colour' => '#6b7280', 'bg' => '#f9fafb'],
    ];
    return $map[$type] ?? ['icon' => '🔔', 'colour' => '#6b7280', 'bg' => '#f9fafb'];
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
                       style="color:white;text-decoration:none;display:block;padding:10px 14px;border-radius:8px;">
                        📋 My Referrals
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="notifications.php"
                       style="color:white;text-decoration:none;display:block;padding:10px 14px;
                              border-radius:8px;background:rgba(255,255,255,0.15);">
                        🔔 Notifications
                    </a>
                </li>
            </ul>
        </nav>
        <div style="padding-top:40px;border-top:1px solid rgba(255,255,255,0.2);margin-top:60px;">
            <div style="font-size:13px;font-weight:bold;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <div style="font-size:11px;opacity:0.75;"><?= htmlspecialchars($_SESSION['hospital_name']) ?></div>
            <div style="font-size:11px;opacity:0.75;margin-bottom:12px;"><?= htmlspecialchars($_SESSION['department']) ?></div>
            <a href="../signout.php"
               style="display:block;margin-top:18px;padding:12px;background:#dc2626;
                      color:white;text-align:center;text-decoration:none;border-radius:8px;
                      font-weight:bold;font-size:15px;">
                🚪 Logout
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main style="flex:1;padding:30px;background:#f4f6fb;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <div>
                <h1 style="margin:0;font-size:22px;color:#1f2937;">Notifications</h1>
                <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">
                    All updates related to your referrals — <?= count($notifications) ?> total
                </p>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <div style="background:white;border-radius:12px;padding:50px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.07);text-align:center;">
                <div style="font-size:48px;margin-bottom:12px;">🔔</div>
                <p style="color:#9ca3af;font-size:14px;">No notifications yet.</p>
                <a href="create_referral.php"
                   style="color:#1d4ed8;font-size:13px;text-decoration:none;">
                    Create a referral to get started →
                </a>
            </div>

        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($notifications as $n):
                    $style = notifStyle($n['notification_type']);
                ?>
                    <div style="background:white;border-radius:12px;padding:16px 20px;
                                box-shadow:0 2px 8px rgba(0,0,0,0.07);
                                border-left:4px solid <?= $style['colour'] ?>;
                                display:flex;align-items:flex-start;gap:14px;">

                        <!-- Icon -->
                        <div style="font-size:22px;margin-top:2px;">
                            <?= $style['icon'] ?>
                        </div>

                        <!-- Content -->
                        <div style="flex:1;">
                            <p style="margin:0;font-size:14px;color:#1f2937;line-height:1.5;">
                                <?= htmlspecialchars($n['message']) ?>
                            </p>

                            <!-- Referral link if exists -->
                            <?php if ($n['referral_id']): ?>
                                <div style="margin-top:6px;">
                                    <a href="referral_detail.php?id=<?= $n['referral_id'] ?>"
                                       style="font-size:12px;color:#1d4ed8;text-decoration:none;
                                              padding:3px 10px;border:1px solid #1d4ed8;
                                              border-radius:6px;">
                                        View Referral #<?= str_pad($n['referral_id'], 5, '0', STR_PAD_LEFT) ?>
                                    </a>
                                    <?php if ($n['referral_status']): ?>
                                        <?php
                                        $sColours = [
                                            'Pending Validation' => '#f59e0b',
                                            'Submitted'          => '#3b82f6',
                                            'Approved'           => '#10b981',
                                            'Rejected'           => '#ef4444',
                                            'Scheduled'          => '#8b5cf6',
                                            'Completed'          => '#6b7280',
                                        ];
                                        $sc = $sColours[$n['referral_status']] ?? '#6b7280';
                                        ?>
                                        <span style="margin-left:8px;background:<?= $sc ?>;color:white;
                                                     padding:2px 8px;border-radius:20px;font-size:11px;
                                                     font-weight:bold;">
                                            <?= htmlspecialchars($n['referral_status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Timestamp -->
                            <p style="margin:6px 0 0;font-size:11px;color:#9ca3af;">
                                <?= date('d M Y, H:i', strtotime($n['sent_at'])) ?>
                            </p>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php $Objlayout->footer($conf); ?>