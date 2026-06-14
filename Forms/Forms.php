<?php

class Forms
{
    /* =========================
       SIGNUP FORM
    ========================== */
    public function signup()
    {
        ?>

        <form action="signup_process.php" method="post" class="refernet-form">

            <h2>Hospital Registration</h2>

            <!-- HOSPITAL DETAILS -->
            <div class="form-block">
                <h3>Hospital Details</h3>

                <label>Hospital Name</label>
                <input type="text" name="hospital_name" required>

                <label>County</label>
                <input type="text" name="county" required>

                <label>Hospital Level</label>
                <select name="level" required>
                    <option value="">Select Level</option>
                    <option>Level 1</option>
                    <option>Level 2</option>
                    <option>Level 3</option>
                    <option>Level 4</option>
                    <option>Level 5</option>
                    <option>Level 6</option>
                </select>
            </div>

            <!-- LOGIN DETAILS -->
            <div class="form-block">
                <h3>Account Login Details</h3>

                <label>Email (Referral System Login)</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <!-- COORDINATOR DETAILS -->
            <div class="form-block">
                <h3>Referral Coordinator Details</h3>

                <label>Full Name</label>
                <input type="text" name="coordinator_name" required>

                <label>Phone Number</label>
                <input type="text" name="coordinator_phone" required>

                <label>Email (Optional)</label>
                <input type="email" name="coordinator_email">
            </div>

            <!-- SYSTEM ROLE (HIDDEN - IMPORTANT) -->
            <input type="hidden" name="role" value="Referral Coordinator">

            <button type="submit">Create Hospital Account</button>

            <p>
                <a href="signin.php">Already registered? Sign in</a>
            </p>

        </form>

        <?php
    }


    /* =========================
       SIGNIN FORM
    ========================== */
    public function signin()
    {
        ?>

        <form action="signin_process.php" method="post" class="refernet-form">

            <h2>Hospital Login</h2>

            <div class="form-block">
                <h3>Login Details</h3>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">Sign In</button>

            <p>
                <a href="forgot_password.php">Forgot password?</a>
            </p>

            <p>
                <a href="signup.php">Create hospital account</a>
            </p>

        </form>

        <?php
    }
}
?>