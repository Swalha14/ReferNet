<?php
session_start();
require_once '../ClassAutoLoad.php';

// Guard
$ObjAuth->requireLogin();
$ObjAuth->requireRole('doctor');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_referral.php');
    exit;
}

$conn       = $SQL->getConnection();
$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];

// Sanitise inputs
$patientId           = (int) ($_POST['patient_id']           ?? 0);
$receivingHospitalId = (int) ($_POST['receiving_hospital_id'] ?? 0);
$diagnosis           = trim($_POST['diagnosis']           ?? '');
$referralReason      = trim($_POST['referral_reason']     ?? '');
$urgencyLevel        = trim($_POST['urgency_level']       ?? '');
$examinationFindings = trim($_POST['examination_findings'] ?? '');
$treatmentGiven      = trim($_POST['treatment_given']     ?? '');
$clinicalSummary     = trim($_POST['clinical_summary']    ?? '');

// Validation
if (!$patientId || !$receivingHospitalId || !$diagnosis ||
    !$referralReason || !$urgencyLevel || !$clinicalSummary) {
    $_SESSION['error'] = 'Please fill in all required fields.';
    header('Location: create_referral.php');
    exit;
}

// Validate urgency level
$validUrgency = ['Low', 'Medium', 'High', 'Critical'];
if (!in_array($urgencyLevel, $validUrgency)) {
    $_SESSION['error'] = 'Invalid urgency level selected.';
    header('Location: create_referral.php');
    exit;
}

// Ensure receiving hospital is not the same as sending hospital
if ($receivingHospitalId === $hospitalId) {
    $_SESSION['error'] = 'Cannot refer a patient to your own hospital.';
    header('Location: create_referral.php');
    exit;
}

// Insert referral into DB
$stmt = $conn->prepare(
    "INSERT INTO referral (
        patient_id, doctor_id, sending_hospital_id, receiving_hospital_id,
        diagnosis, referral_reason, urgency_level,
        examination_findings, treatment_given, clinical_summary,
        status, referral_date
    ) VALUES (
        :patient_id, :doctor_id, :sending_hospital_id, :receiving_hospital_id,
        :diagnosis, :referral_reason, :urgency_level,
        :examination_findings, :treatment_given, :clinical_summary,
        'Pending Validation', NOW()
    )"
);

$stmt->execute([
    ':patient_id'            => $patientId,
    ':doctor_id'             => $userId,
    ':sending_hospital_id'   => $hospitalId,
    ':receiving_hospital_id' => $receivingHospitalId,
    ':diagnosis'             => $diagnosis,
    ':referral_reason'       => $referralReason,
    ':urgency_level'         => $urgencyLevel,
    ':examination_findings'  => $examinationFindings,
    ':treatment_given'       => $treatmentGiven,
    ':clinical_summary'      => $clinicalSummary,
]);

$referralId = $conn->lastInsertId();

// Fetch patient name for notification message
$stmt = $conn->prepare("SELECT full_name FROM patient WHERE patient_id = :pid");
$stmt->execute([':pid' => $patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$patientName = $patient['full_name'] ?? 'Unknown Patient';

// Fetch coordinator(s) at the same hospital
$stmt = $conn->prepare(
    "SELECT user_id, full_name, email FROM user
     WHERE hospital_id = :hid AND role = 'coordinator' AND is_active = 1"
);
$stmt->execute([':hid' => $hospitalId]);
$coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notify each coordinator at this hospital via DB notification
foreach ($coordinators as $coord) {
    $message = "New referral submitted by {$_SESSION['full_name']} for patient {$patientName}. "
             . "Diagnosis: {$diagnosis}. Urgency: {$urgencyLevel}. Please review.";

    $stmt = $conn->prepare(
        "INSERT INTO notification (user_id, referral_id, message, notification_type, is_read, sent_at)
         VALUES (:uid, :rid, :msg, 'new_referral', 0, NOW())"
    );
    $stmt->execute([
        ':uid' => $coord['user_id'],
        ':rid' => $referralId,
        ':msg' => $message,
    ]);
}

// Also notify the doctor themselves (confirmation)
$doctorMessage = "Your referral for patient {$patientName} (Diagnosis: {$diagnosis}) "
               . "has been submitted and is pending coordinator review.";

$stmt = $conn->prepare(
    "INSERT INTO notification (user_id, referral_id, message, notification_type, is_read, sent_at)
     VALUES (:uid, :rid, :msg, 'referral_submitted', 0, NOW())"
);
$stmt->execute([
    ':uid' => $userId,
    ':rid' => $referralId,
    ':msg' => $doctorMessage,
]);

$_SESSION['success'] = "Referral for {$patientName} submitted successfully. "
                     . "Your coordinator has been notified.";
header('Location: doctor_dashboard.php');
exit;
?>