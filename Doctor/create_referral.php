<?php
session_start();
require_once '../ClassAutoLoad.php';

// Guard
$ObjAuth->requireLogin();
$ObjAuth->requireRole('doctor');

$conn       = $SQL->getConnection();
$userId     = $_SESSION['user_id'];
$hospitalId = $_SESSION['hospital_id'];

// Fetch all hospitals except the doctor's own hospital (can't refer to yourself)
$stmt = $conn->prepare(
    "SELECT hospital_id, hospital_name, level
     FROM hospital
     WHERE hospital_id != :hid AND is_active = 1
     ORDER BY hospital_name"
);
$stmt->execute([':hid' => $hospitalId]);
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all patients
$stmt = $conn->prepare(
    "SELECT patient_id, full_name, national_id, gender, date_of_birth
     FROM patient
     ORDER BY full_name"
);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$Objlayout->header($conf, '../');
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
                    <a href="doctor_dashboard.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;">
                        📊 Dashboard
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="create_referral.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;background:rgba(255,255,255,0.15);">
                        ➕ Create Referral
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="view_referrals.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;">
                        📋 My Referrals
                    </a>
                </li>
                <li style="margin-bottom:8px;">
                    <a href="notifications.php"
                       style="color:white;text-decoration:none;display:block;
                              padding:10px 14px;border-radius:8px;">
                        🔔 Notifications
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
                <?= htmlspecialchars($_SESSION['department']) ?>
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
                    font-size:15px;
                    box-shadow:0 2px 6px rgba(0,0,0,.2);">
                🚪 Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main style="flex:1;padding:30px;background:#f4f6fb;">

        <div style="margin-bottom:25px;">
            <h1 style="margin:0;font-size:22px;color:#1f2937;">Create New Referral</h1>
            <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">
                Complete all required fields and submit for coordinator review.
            </p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:20px;">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="create_referral_process.php" method="post">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">

            <!-- LEFT COLUMN -->
            <div>

                <!-- Patient Information -->
                <div style="background:white;border-radius:12px;padding:24px;
                            box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:20px;
                            border-left:4px solid #3b82f6;">
                    <h3 style="margin:0 0 16px;color:#1d4ed8;font-size:15px;">
                        Patient Information
                    </h3>

                    <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                        Select Patient <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="patient_id" required id="patient-select"
                            style="width:100%;padding:10px;border-radius:8px;
                                   border:1px solid #d1d5db;margin-bottom:12px;">
                        <option value="">-- Select patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"
                                    data-name="<?= htmlspecialchars($p['full_name']) ?>"
                                    data-id="<?= htmlspecialchars($p['national_id']) ?>"
                                    data-gender="<?= htmlspecialchars($p['gender']) ?>"
                                    data-dob="<?= htmlspecialchars($p['date_of_birth']) ?>">
                                <?= htmlspecialchars($p['full_name']) ?>
                                — <?= htmlspecialchars($p['national_id']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Patient details auto-fill -->
                    <div id="patient-details"
                         style="display:none;background:#eff6ff;border-radius:8px;
                                padding:12px;font-size:13px;color:#374151;">
                        <strong>Name:</strong> <span id="pd-name"></span> &nbsp;|&nbsp;
                        <strong>ID:</strong> <span id="pd-id"></span> &nbsp;|&nbsp;
                        <strong>Gender:</strong> <span id="pd-gender"></span> &nbsp;|&nbsp;
                        <strong>DOB:</strong> <span id="pd-dob"></span>
                    </div>
                </div>

                <!-- Receiving Hospital -->
                <div style="background:white;border-radius:12px;padding:24px;
                            box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:20px;
                            border-left:4px solid #8b5cf6;">
                    <h3 style="margin:0 0 16px;color:#7c3aed;font-size:15px;">
                        Receiving Hospital
                    </h3>

                    <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                        Select Receiving Hospital <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="receiving_hospital_id" required
                            style="width:100%;padding:10px;border-radius:8px;
                                   border:1px solid #d1d5db;">
                        <option value="">-- Select hospital --</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= $h['hospital_id'] ?>">
                                <?= htmlspecialchars($h['hospital_name']) ?>
                                (<?= htmlspecialchars($h['level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Clinical Information -->
                <div style="background:white;border-radius:12px;padding:24px;
                            box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:20px;
                            border-left:4px solid #10b981;">
                    <h3 style="margin:0 0 16px;color:#059669;font-size:15px;">
                        Clinical Information
                    </h3>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                                Primary Diagnosis <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="diagnosis" required
                                   placeholder="e.g. Congestive Heart Failure"
                                   style="width:100%;padding:10px;border-radius:8px;
                                          border:1px solid #d1d5db;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                                Urgency Level <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="urgency_level" required
                                    style="width:100%;padding:10px;border-radius:8px;
                                           border:1px solid #d1d5db;">
                                <option value="">-- Select urgency --</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top:14px;">
                        <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                            Referral Reason <span style="color:#ef4444;">*</span>
                        </label>
                        <textarea name="referral_reason" required rows="3"
                                  placeholder="Reason for referring this patient..."
                                  style="width:100%;padding:10px;border-radius:8px;
                                         border:1px solid #d1d5db;box-sizing:border-box;"></textarea>
                    </div>

                    <div style="margin-top:14px;">
                        <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                            Examination Findings
                        </label>
                        <textarea name="examination_findings" rows="3"
                                  placeholder="Key examination findings..."
                                  style="width:100%;padding:10px;border-radius:8px;
                                         border:1px solid #d1d5db;box-sizing:border-box;"></textarea>
                    </div>

                    <div style="margin-top:14px;">
                        <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                            Treatment Given
                        </label>
                        <textarea name="treatment_given" rows="3"
                                  placeholder="Treatment provided before referral..."
                                  style="width:100%;padding:10px;border-radius:8px;
                                         border:1px solid #d1d5db;box-sizing:border-box;"></textarea>
                    </div>

                    <div style="margin-top:14px;">
                        <label style="display:block;font-size:13px;font-weight:bold;margin-bottom:5px;">
                            Clinical Summary <span style="color:#ef4444;">*</span>
                        </label>
                        <textarea name="clinical_summary" required rows="4"
                                  placeholder="Overall clinical summary..."
                                  style="width:100%;padding:10px;border-radius:8px;
                                         border:1px solid #d1d5db;box-sizing:border-box;"></textarea>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN — Referral Summary -->
            <div>
                <div style="background:white;border-radius:12px;padding:24px;
                            box-shadow:0 2px 8px rgba(0,0,0,0.07);position:sticky;top:20px;">
                    <h3 style="margin:0 0 16px;color:#1f2937;font-size:15px;">
                        📋 Referral Summary
                    </h3>

                    <div style="font-size:13px;color:#374151;line-height:2;">
                        <div><strong>Referring Doctor:</strong><br>
                            <?= htmlspecialchars($_SESSION['full_name']) ?></div>
                        <div style="margin-top:8px;"><strong>Department:</strong><br>
                            <?= htmlspecialchars($_SESSION['department']) ?></div>
                        <div style="margin-top:8px;"><strong>Referring Hospital:</strong><br>
                            <?= htmlspecialchars($_SESSION['hospital_name']) ?></div>
                        <div style="margin-top:8px;"><strong>Date:</strong><br>
                            <?= date('d M Y') ?></div>
                    </div>

                    <div style="margin-top:20px;padding:12px;background:#eff6ff;
                                border-radius:8px;font-size:12px;color:#1d4ed8;">
                        ℹ️ After submission, the referral will be sent to your hospital's
                        Referral Coordinator for review before forwarding.
                    </div>

                    <div style="margin-top:16px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">
                            Status after submission:
                        </div>
                        <span style="background:#f59e0b;color:white;padding:4px 12px;
                                     border-radius:20px;font-size:12px;font-weight:bold;">
                            Pending Validation
                        </span>
                    </div>

                    <!-- Submit Buttons -->
                    <button type="submit"
                            style="width:100%;margin-top:24px;padding:12px;
                                   background:#1d4ed8;color:white;border:none;
                                   border-radius:10px;font-weight:bold;font-size:14px;
                                   cursor:pointer;">
                        Submit Referral
                    </button>

                    <a href="doctor_dashboard.php"
                       style="display:block;text-align:center;margin-top:10px;
                              font-size:13px;color:#6b7280;text-decoration:none;">
                        Cancel
                    </a>
                </div>
            </div>

        </div>
        </form>

    </main>
</div>

<!-- Auto-fill patient details on select -->
<script>
    document.getElementById('patient-select').addEventListener('change', function () {
        var opt     = this.options[this.selectedIndex];
        var details = document.getElementById('patient-details');

        if (this.value) {
            document.getElementById('pd-name').textContent   = opt.dataset.name;
            document.getElementById('pd-id').textContent     = opt.dataset.id;
            document.getElementById('pd-gender').textContent = opt.dataset.gender;
            document.getElementById('pd-dob').textContent    = opt.dataset.dob;
            details.style.display = 'block';
        } else {
            details.style.display = 'none';
        }
    });
</script>

<?php $Objlayout->footer($conf); ?>