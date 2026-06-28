<?php

class Forms
{
    /* ==============================================
       SIGNIN FORM
    =============================================== */
    public function signin()
    {
        ?>
        <form action="signin_process.php" method="post" class="refernet-form">

            <h2>ReferNet Login</h2>

            <div class="form-block">
                <h3>Secure Login</h3>

                <label>Email</label>
                <input type="email" name="email" required
                       value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">

                <label>Password</label>
                <input type="password" name="password" required>

                <label>Role</label>
                <select name="role" required>
                    <option value="">Select your role</option>
                    <option value="doctor"
                        <?= (($_GET['role'] ?? '') === 'doctor') ? 'selected' : '' ?>>
                        Doctor
                    </option>
                    <option value="coordinator"
                        <?= (($_GET['role'] ?? '') === 'coordinator') ? 'selected' : '' ?>>
                        Referral Coordinator
                    </option>
                </select>
            </div>

            <button type="submit">Sign In</button>

        </form>
        <?php
    }

    /* ==============================================
       OTP VERIFICATION FORM
    =============================================== */
    public function otp()
    {
        ?>
        <form action="otp_process.php" method="post" class="refernet-form">

            <h2>OTP Verification</h2>

            <div class="form-block">
                <h3>Enter Code</h3>
                <p style="font-size:13px;color:#6b7280;margin-top:0;">
                    A 6-digit code has been sent to your email address.
                    It expires in <strong>2 minutes</strong>.
                </p>

                <label>6-Digit OTP</label>
                <input type="text" name="otp" maxlength="6"
                       pattern="[0-9]{6}" placeholder="000000"
                       autocomplete="one-time-code" required
                       style="font-size:22px;letter-spacing:8px;text-align:center;">
            </div>

            <button type="submit">Verify &amp; Login</button>

            <p>
                Didn't receive the code?
                <a href="resend_otp.php">Resend OTP</a>
            </p>

            <p>
                Wrong account?
                <a href="signin.php">Back to Login</a>
            </p>

        </form>
        <?php
    }

    /* ==============================================
       CREATE REFERRAL FORM (Doctor)
    =============================================== */
    public function createReferral(array $hospitals, array $patients)
    {
        ?>
        <form action="Doctor/create_referral_process.php" method="post" class="refernet-form">

            <h2>Create New Referral</h2>

            <!-- Patient Information -->
            <div class="form-block">
                <h3>Patient Information</h3>

                <label>Select Patient</label>
                <select name="patient_id" required>
                    <option value="">Select patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['patient_id'] ?>">
                            <?= htmlspecialchars($p['full_name']) ?>
                            — <?= htmlspecialchars($p['national_id']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Receiving Hospital -->
            <div class="form-block">
                <h3>Receiving Hospital</h3>

                <label>Receiving Hospital</label>
                <select name="receiving_hospital_id" required>
                    <option value="">Select receiving hospital</option>
                    <?php foreach ($hospitals as $h): ?>
                        <option value="<?= $h['hospital_id'] ?>">
                            <?= htmlspecialchars($h['hospital_name']) ?>
                            (<?= htmlspecialchars($h['level']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Clinical Information -->
            <div class="form-block">
                <h3>Clinical Information</h3>

                <label>Primary Diagnosis</label>
                <input type="text" name="diagnosis" required
                       placeholder="e.g. Congestive Heart Failure">

                <label>Referral Reason</label>
                <textarea name="referral_reason" rows="3" required
                          placeholder="Reason for referring this patient..."
                          style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;margin-top:5px;"></textarea>

                <label>Urgency Level</label>
                <select name="urgency_level" required>
                    <option value="">Select urgency</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>

                <label>Examination Findings</label>
                <textarea name="examination_findings" rows="3"
                          placeholder="Key examination findings..."
                          style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;margin-top:5px;"></textarea>

                <label>Treatment Given</label>
                <textarea name="treatment_given" rows="3"
                          placeholder="Treatment provided before referral..."
                          style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;margin-top:5px;"></textarea>

                <label>Clinical Summary</label>
                <textarea name="clinical_summary" rows="4"
                          placeholder="Overall clinical summary..."
                          style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;margin-top:5px;"></textarea>
            </div>

            <button type="submit">Submit Referral</button>

            <p>
                <a href="Doctor/doctor_dashboard.php">Cancel</a>
            </p>

        </form>
        <?php
    }

    /* ==============================================
       FORWARD REFERRAL FORM (Referring Coordinator)
    =============================================== */
    public function forwardReferral(array $referral)
    {
        ?>
        <form action="Coordinator/forward_referral_process.php" method="post" class="refernet-form">

            <h2>Forward Referral</h2>

            <input type="hidden" name="referral_id"
                   value="<?= (int)$referral['referral_id'] ?>">

            <div class="form-block">
                <h3>Referral Summary</h3>
                <p style="font-size:13px;color:#374151;">
                    <strong>Patient:</strong> <?= htmlspecialchars($referral['patient_name']) ?><br>
                    <strong>Diagnosis:</strong> <?= htmlspecialchars($referral['diagnosis']) ?><br>
                    <strong>Urgency:</strong> <?= htmlspecialchars($referral['urgency_level']) ?><br>
                    <strong>Receiving Hospital:</strong> <?= htmlspecialchars($referral['receiving_hospital']) ?>
                </p>
            </div>

            <div class="form-block">
                <h3>Coordinator Notes</h3>
                <label>Review Notes (optional)</label>
                <textarea name="review_notes" rows="4"
                          placeholder="Add any notes before forwarding..."
                          style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;margin-top:5px;"></textarea>
            </div>

            <button type="submit">Forward to Receiving Hospital</button>

            <p>
                <a href="Coordinator/coord_dashboard.php">Cancel</a>
            </p>

        </form>
        <?php
    }

    /* ==============================================
       APPROVE / REJECT REFERRAL FORM (Receiving Coordinator)
    =============================================== */
    public function reviewReferral(array $referral)
    {
        ?>
        <form action="Coordinator/review_referral_process.php" method="post" class="refernet-form">

            <h2>Review Incoming Referral</h2>

            <input type="hidden" name="referral_id"
                   value="<?= (int)$referral['referral_id'] ?>">

            <div class="form-block">
                <h3>Referral Details</h3>
                <p style="font-size:13px;color:#374151;line-height:1.8;">
                    <strong>Patient:</strong> <?= htmlspecialchars($referral['patient_name']) ?><br>
                    <strong>From Hospital:</strong> <?= htmlspecialchars($referral['sending_hospital']) ?><br>
                    <strong>Diagnosis:</strong> <?= htmlspecialchars($referral['diagnosis']) ?><br>
                    <strong>Urgency:</strong> <?= htmlspecialchars($referral['urgency_level']) ?><br>
                    <strong>Referral Reason:</strong> <?= htmlspecialchars($referral['referral_reason']) ?><br>
                    <strong>Clinical Summary:</strong> <?= htmlspecialchars($referral['clinical_summary']) ?>
                </p>
            </div>

            <div class="form-block">
                <h3>Decision</h3>

                <label>Decision</label>
                <select name="decision" required id="decision-select">
                    <option value="">Select decision</option>
                    <option value="Approved">Approve Referral</option>
                    <option value="Rejected">Reject Referral</option>
                </select>

                <div id="rejection-reason" style="display:none;margin-top:10px;">
                    <label>Reason for Rejection</label>
                    <textarea name="review_notes" rows="3"
                              placeholder="Provide reason for rejection..."
                              style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;margin-top:5px;"></textarea>
                </div>
            </div>

            <button type="submit">Submit Decision</button>

            <p>
                <a href="Coordinator/coord_dashboard.php">Cancel</a>
            </p>

        </form>

        <script>
            document.getElementById('decision-select').addEventListener('change', function () {
                var rejDiv = document.getElementById('rejection-reason');
                rejDiv.style.display = this.value === 'Rejected' ? 'block' : 'none';
            });
        </script>
        <?php
    }

    /* ==============================================
       SCHEDULE APPOINTMENT FORM (Receiving Coordinator)
    =============================================== */
    public function scheduleAppointment(array $referral)
    {
        ?>
        <form action="Coordinator/schedule_appointment_process.php" method="post" class="refernet-form">

            <h2>Schedule Appointment</h2>

            <input type="hidden" name="referral_id"
                   value="<?= (int)$referral['referral_id'] ?>">

            <div class="form-block">
                <h3>Patient</h3>
                <p style="font-size:13px;color:#374151;">
                    <strong><?= htmlspecialchars($referral['patient_name']) ?></strong><br>
                    Diagnosis: <?= htmlspecialchars($referral['diagnosis']) ?>
                </p>
            </div>

            <div class="form-block">
                <h3>Appointment Details</h3>

                <label>Appointment Date</label>
                <input type="date" name="appointment_date" required
                       min="<?= date('Y-m-d') ?>">

                <label>Appointment Time</label>
                <input type="time" name="appointment_time" required>

                <label>Department</label>
                <input type="text" name="department"
                       placeholder="e.g. Cardiology, Oncology"
                       value="<?= htmlspecialchars($_SESSION['department'] ?? '') ?>">

                <label>Notes (optional)</label>
                <textarea name="notes" rows="3"
                          placeholder="Any additional appointment notes..."
                          style="width:100%;padding:10px;border-radius:8px;border:1px solid #d1d5db;margin-top:5px;"></textarea>
            </div>

            <button type="submit">Confirm Appointment</button>

            <p>
                <a href="Coordinator/coord_dashboard.php">Cancel</a>
            </p>

        </form>
        <?php
    }
    
    /* ==============================================
   CHANGE PASSWORD FORM
============================================== */
public function changePassword()
{
    ?>
    <form action="change_password_process.php" method="post" class="refernet-form">

        <h2>Create New Password</h2>

        <div class="form-block">

            <h3>First Time Setup</h3>

            <p style="font-size:13px;color:#6b7280;margin-top:0;">
                Your account has been verified.
                Please create a new password before continuing.
            </p>

            <label>New Password</label>
            <input
                type="password"
                name="new_password"
                minlength="8"
                required
                placeholder="Enter a new password">

            <label>Confirm Password</label>
            <input
                type="password"
                name="confirm_password"
                minlength="8"
                required
                placeholder="Confirm your new password">

        </div>

        <button type="submit">Save Password</button>

    </form>
    <?php
}
}
?>