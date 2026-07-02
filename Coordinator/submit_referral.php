<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('coordinator');

$conn = $SQL->getConnection();

$userId = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];

$referralId = (int)($_GET['id'] ?? 0);

if (!$referralId) {
    $_SESSION['error'] = 'Invalid referral selected.';
    header('Location: pending_referrals.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify Referral
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT
    referral_id,
    receiving_hospital_id,
    status
FROM referral
WHERE referral_id = :rid
AND sending_hospital_id = :hid
LIMIT 1
");

$stmt->execute([
    ':rid' => $referralId,
    ':hid' => $hospitalId
]);

$referral = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$referral) {
    $_SESSION['error'] = 'Referral not found.';
    header('Location: pending_referrals.php');
    exit;
}

if ($referral['status'] !== 'Awaiting Review') {
    $_SESSION['error'] = 'This referral has already been processed.';
    header('Location: pending_referrals.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Find Receiving Coordinator
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT
    user_id,
    full_name,
    email
FROM user
WHERE hospital_id = :hid
AND role = 'coordinator'
AND is_active = 1
LIMIT 1
");

$stmt->execute([
    ':hid' => $referral['receiving_hospital_id']
]);

$coordinator = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coordinator) {
    $_SESSION['error'] = 'Receiving hospital has no active referral coordinator.';
    header('Location: pending_referrals.php');
    exit;
}

$receivingCoordinator = $coordinator['user_id'];

/*
|--------------------------------------------------------------------------
| Submit Referral
|--------------------------------------------------------------------------
*/

$conn->beginTransaction();

try {

    $stmt = $conn->prepare("
    UPDATE referral
    SET
        status = 'Pending Validation',
        referring_coordinator_id = :coord
    WHERE referral_id = :rid
    AND status = 'Awaiting Review'
    ");

    $stmt->execute([
        ':coord' => $userId,
        ':rid'   => $referralId
    ]);

    /*
    |--------------------------------------------------------------------------
    | In-system Notification
    |--------------------------------------------------------------------------
    */

    $message = "A new referral has been forwarded to your hospital and is awaiting validation.";

    $stmt = $conn->prepare("
    INSERT INTO notification
    (
        user_id,
        referral_id,
        message,
        notification_type,
        is_read,
        sent_at
    )
    VALUES
    (
        :uid,
        :rid,
        :msg,
        'new_referral',
        0,
        NOW()
    )
    ");

    $stmt->execute([
        ':uid' => $receivingCoordinator,
        ':rid' => $referralId,
        ':msg' => $message
    ]);

    /*
    |--------------------------------------------------------------------------
    | Email Notification
    |--------------------------------------------------------------------------
    */

    $ObjSendMail->sendReferralNotification(
        $coordinator['email'],
        $coordinator['full_name'],
        'New Referral Awaiting Validation',
        "Dear {$coordinator['full_name']},\n\n"
        . "A referral has been forwarded to your hospital and is awaiting your validation.\n\n"
        . "Referral ID: {$referralId}\n"
        . "Sending Hospital: {$_SESSION['hospital_name']}\n\n"
        . "Please log in to ReferNet to review the referral and either approve or reject it.\n\n"
        . "Regards,\n"
        . "ReferNet"
    );

    /*
    |--------------------------------------------------------------------------
    | Notify Referring Coordinator
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
    INSERT INTO notification
    (
        user_id,
        referral_id,
        message,
        notification_type,
        is_read,
        sent_at
    )
    VALUES
    (
        :uid,
        :rid,
        :msg,
        'referral_forwarded',
        0,
        NOW()
    )
    ");

    $stmt->execute([
        ':uid' => $userId,
        ':rid' => $referralId,
        ':msg' => 'You successfully forwarded the referral to the receiving hospital.'
    ]);

    $conn->commit();

    $_SESSION['success'] = 'Referral submitted successfully to the receiving hospital.';

} catch (Exception $e) {

    $conn->rollBack();

    $_SESSION['error'] = 'Failed to submit referral.';
}

header('Location: pending_referrals.php');
exit;