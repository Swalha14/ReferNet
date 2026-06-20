<?php

class Forms
{
    /* =========================
       LOGIN FORM (MAIN ENTRY)
    ========================= */
    public function signin()
    {
        ?>

        <form action="signin_process.php" method="post" class="refernet-form">

            <h2>ReferNet Login</h2>

            <div class="form-block">
                <h3>Secure Login</h3>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">Sign In</button>

            <p>
                First time here?
                <a href="onboarding.php">Get Started Now</a>
            </p>

        </form>

        <?php
    }


    /* =========================
       ONBOARDING (IDENTITY CHECK)
    ========================= */
    public function onboarding()
    {
        ?>

        <form action="onboarding_process.php" method="post" class="refernet-form">

            <h2>Account Verification</h2>

            <div class="form-block">
                <h3>User Identity</h3>

                <label>Full Name</label>
                <input type="text" name="full_name" required>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Role</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Referral Coordinator">Referral Coordinator</option>
                </select>

                <label>Hospital</label>
                <select name="hospital_id" required>
                    <option value="">Select Hospital</option>
                    <option value="1">Kenya National Hospital</option>
                    <option value="2">Nairobi West Hospital</option>
                    <option value="3">Aga Khan Hospital</option>
                    <option value="4">Kenyatta University Hospital</option>
                </select>
            </div>

            <button type="submit">Continue</button>

            <p>
                Already verified?
                <a href="signin.php">Back to Login</a>
            </p>

        </form>

        <?php
    }


    /* =========================
       OTP VERIFICATION
    ========================= */
    public function otp()
    {
        ?>

        <form action="otp_verify.php" method="post" class="refernet-form">

            <h2>OTP Verification</h2>

            <div class="form-block">
                <h3>Enter Code</h3>

                <label>6-digit OTP sent to your email</label>
                <input type="text" name="otp" maxlength="6" required>
            </div>

            <button type="submit">Verify OTP</button>

            <p>
                Didn't receive code?
                <a href="resend_otp.php">Resend OTP</a>
            </p>

        </form>

        <?php
    }
}
?>