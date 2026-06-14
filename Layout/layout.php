<?php

class layout
{
    /* =========================
        HEADER
    ========================= */
    public function header($conf)
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo $conf['site_name']; ?></title>

            <link rel="stylesheet" href="CSS/style.css">
            <script src="JS/script.js" defer></script>
        </head>
        <body>
        <?php
    }

    /* =========================
        NAVBAR
    ========================= */
    public function nav($conf)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $loggedIn = isset($_SESSION['user_id']) || isset($_SESSION['hospital_id']);
        $isAdmin  = isset($_SESSION['admin_id']);
        ?>

        <header class="navbar">
            <div class="logo"><?php echo $conf['site_name']; ?></div>

            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>

                    <?php if ($loggedIn): ?>
                        <?php if ($isAdmin): ?>
                            <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="referrals.php">Referrals</a></li>
                        <?php endif; ?>
                        <li><a href="signout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="signup.php">Sign Up</a></li>
                        <li><a href="signin.php">Sign In</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>

        <?php
    }

    /* =========================
        HERO
    ========================= */
    public function banner($conf)
    {
        ?>
        <section class="hero">
            <div class="hero-content">
                <h1><?php echo $conf['site_name']; ?></h1>
                <p>
                    A hospital referral coordination system that improves communication,
                    tracking, and patient transfer efficiency between healthcare facilities.
                </p>
                <a href="signin.php" class="btn-primary">Get Started</a>
            </div>
        </section>
        <?php
    }

    /* =========================
        WHY USE REFERNET (IMPACT ONLY)
    ========================= */
    public function why_use_system($conf)
    {
        ?>
        <section class="section">
            <h2 class="section-title">Why Use <?php echo $conf['site_name']; ?>?</h2>

        

            <div class="feature-grid">

                <div class="feature-card highlight">
                    <h3>Faster Patient Transfers</h3>
                    <p>Speeds up referral communication and reduces waiting time for critical care decisions.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Improved Continuity of Care</h3>
                    <p>Ensures patient information follows the patient across healthcare facilities.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Better Communication</h3>
                    <p>Standardized digital communication between referring and receiving hospitals.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Reduced Errors</h3>
                    <p>Eliminates missing or incomplete referral documentation.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Real-Time Tracking</h3>
                    <p>Allows hospitals to monitor referral status from submission to completion.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Improved Efficiency</h3>
                    <p>Removes reliance on paper-based systems and manual coordination.</p>
                </div>

            </div>
        </section>
        <?php
    }

    /* =========================
        HOW IT WORKS
    ========================= */
    public function how_to_use($conf)
    {
        ?>
        <section class="section alt">
            <h2 class="section-title">How It Works</h2>

            <div class="steps-container">

                <div class="step">
                    <div class="circle">1</div>
                    <div>
                        <h3>Sign In</h3>
                        <p>Secure access for hospital staff.</p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">2</div>
                    <div>
                        <h3>Create Referral</h3>
                        <p>Enter patient and clinical details.</p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">3</div>
                    <div>
                        <h3>Review</h3>
                        <p>Hospital approves or rejects referral.</p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">4</div>
                    <div>
                        <h3>Schedule</h3>
                        <p>Appointments created for approved cases.</p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">5</div>
                    <div>
                        <h3>Track</h3>
                        <p>Real-time status updates & notifications.</p>
                    </div>
                </div>

            </div>
        </section>
        <?php
    }

    /* =========================
        FOOTER
    ========================= */
    public function footer($conf)
    {
        ?>
        <footer class="footer">
            <h3><?php echo $conf['site_name']; ?></h3>
            <p>Hospital Referral Coordination System</p>

            <p>Contact: support@refernet.com | +254 700 000 000</p>

            <p class="copyright">
                &copy; <?php echo date("Y"); ?> <?php echo $conf['site_name']; ?>
            </p>
        </footer>
        </body>
        </html>
        <?php
    }
}
?>