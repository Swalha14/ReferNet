<?php
session_start();
require_once '../ClassAutoLoad.php';

$ObjAuth->requireLogin();
$ObjAuth->requireRole('doctor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_referral.php');
    exit;
}

$conn       = $SQL->getConnection();
$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];

// --- Patient inputs ---
$fullName            = trim($_POST['full_name']       ?? '');
$nationalId          = trim($_POST['national_id']     ?? '');
$gender              = trim($_POST['gender']          ?? '');
$dateOfBirth         = trim($_POST['date_of_birth']   ?? '');
$phoneNumber         = trim($_POST['phone_number']    ?? '');
$patientEmail        = trim($_POST['patient_email']   ?? '');
$currentWard         = trim($_POST['current_ward']    ?? '');
$address             = trim($_POST['address']         ?? '');

// --- Referral inputs ---
$receivingHospitalId = (int)($_POST['receiving_hospital_id'] ?? 0);
$diagnosis           = trim($_POST['diagnosis']            ?? '');
$referralReason      = trim($_POST['referral_reason']      ?? '');
$urgencyLevel        = trim($_POST['urgency_level']        ?? '');
$examinationFindings = trim($_POST['examination_findings'] ?? '');
$treatmentGiven      = trim($_POST['treatment_given']      ?? '');
$clinicalSummary     = trim($_POST['clinical_summary']     ?? '');

// --- Validation ---
if (!$fullName || !$nationalId || !$gender || !$dateOfBirth) {
    $_SESSION['error'] = 'Please fill in all required patient fields.';
    header('Location: create_referral.php');
    exit;
}

if (!$receivingHospitalId || !$diagnosis || !$referralReason || !$urgencyLevel || !$clinicalSummary) {
    $_SESSION['error'] = 'Please fill in all required clinical fields.';
    header('Location: create_referral.php');
    exit;
}

$validUrgency = ['Low', 'Medium', 'High', 'Critical'];
if (!in_array($urgencyLevel, $validUrgency)) {
    $_SESSION['error'] = 'Invalid urgency level selected.';
    header('Location: create_referral.php');
    exit;
}

if ($receivingHospitalId === $hospitalId) {
    $_SESSION['error'] = 'Cannot refer a patient to your own hospital.';
    header('Location: create_referral.php');
    exit;
}

// --- Patient: check if national ID already exists ---
$stmt = $conn->prepare("SELECT patient_id FROM patient WHERE national_id = :nid LIMIT 1");
$stmt->execute([':nid' => $nationalId]);
$existingPatient = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingPatient) {
    // Use existing patient record and warn the doctor
    $patientId = $existingPatient['patient_id'];
    $duplicateWarning = "A patient with National ID {$nationalId} already exists in the system. Their existing record has been used for this referral.";
} else {
    // Insert new patient
    $stmt = $conn->prepare(
        "INSERT INTO patient (full_name, gender, date_of_birth, national_id,
                              phone_number, email, address, current_ward)
         VALUES (:full_name, :gender, :dob, :nid,
                 :phone, :email, :address, :ward)"
    );
    $stmt->execute([
        ':full_name' => $fullName,
        ':gender'    => $gender,
        ':dob'       => $dateOfBirth,
        ':nid'       => $nationalId,
        ':phone'     => $phoneNumber ?: null,
        ':email'     => $patientEmail ?: null,
        ':address'   => $address      ?: null,
        ':ward'      => $currentWard  ?: null,
    ]);
    $patientId        = $conn->lastInsertId();
    $duplicateWarning = null;
}

// --- Insert Referral ---
$stmt = $conn->prepare(
    "INSERT INTO referral (
        patient_id, doctor_id, sending_hospital_id, receiving_hospital_id,
        diagnosis, referral_reason, urgency_level,
        examination_findings, treatment_given, clinical_summary,
        status, referral_date
    ) VALUES (
        :patient_id, :doctor_id, :sending_hid, :receiving_hid,
        :diagnosis, :referral_reason, :urgency_level,
        :exam_findings, :treatment, :clinical_summary,
        'Pending Validation', NOW()
    )"
);
$stmt->execute([
    ':patient_id'       => $patientId,
    ':doctor_id'        => $userId,
    ':sending_hid'      => $hospitalId,
    ':receiving_hid'    => $receivingHospitalId,
    ':diagnosis'        => $diagnosis,
    ':referral_reason'  => $referralReason,
    ':urgency_level'    => $urgencyLevel,
    ':exam_findings'    => $examinationFindings ?: null,
    ':treatment'        => $treatmentGiven      ?: null,
    ':clinical_summary' => $clinicalSummary,
]);
$referralId = $conn->lastInsertId();

// --- Notify coordinators at this hospital (in-system) ---
$stmt = $conn->prepare(
    "SELECT user_id, full_name, email FROM user
     WHERE hospital_id = :hid AND role = 'coordinator' AND is_active = 1"
);
$stmt->execute([':hid' => $hospitalId]);
$coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($coordinators as $coord) {
    $message = "New referral submitted by {$_SESSION['full_name']} for patient {$fullName}. "
             . "Diagnosis: {$diagnosis}. Urgency: {$urgencyLevel}. Please review.";

    $stmt = $conn->prepare(
        "INSERT INTO notification (user_id, referral_id, message, notification_type, is_read, sent_at)
         VALUES (:uid, :rid, :msg, 'new_referral', 0, NOW())"
    );
    $stmt->execute([':uid' => $coord['user_id'], ':rid' => $referralId, ':msg' => $message]);
}

// --- Confirmation notification for doctor ---
$stmt = $conn->prepare(
    "INSERT INTO notification (user_id, referral_id, message, notification_type, is_read, sent_at)
     VALUES (:uid, :rid, :msg, 'referral_submitted', 0, NOW())"
);
$stmt->execute([
    ':uid' => $userId,
    ':rid' => $referralId,
    ':msg' => "Your referral for patient {$fullName} (Diagnosis: {$diagnosis}) has been submitted and is pending coordinator review.",
]);

// --- Email patient if they provided one ---
if ($patientEmail) {
    $ObjSendMail->sendReferralNotification(
        $patientEmail,
        $fullName,
        'Your Referral Has Been Submitted',
        "Dear {$fullName},\n\nA referral has been created for you by {$_SESSION['full_name']} "
        . "at {$_SESSION['hospital_name']}.\n\nDiagnosis: {$diagnosis}\nUrgency: {$urgencyLevel}\n\n"
        . "You will be notified once the receiving hospital responds."
    );
}

// --- Success message ---
$_SESSION['success'] = "Referral for {$fullName} submitted successfully. Your coordinator has been notified.";

if ($duplicateWarning) {
    $_SESSION['success'] .= " ⚠️ Note: {$duplicateWarning}";
}

header('Location: doctor_dashboard.php');
exit;
?>