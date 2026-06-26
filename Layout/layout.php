<?php

class layout
{
    /* =========================
        HEADER
    ========================= */
    public function header($conf, $path = '')
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $conf['site_name']; ?></title>

        <link rel="stylesheet" href="<?= $path ?>CSS/style.css">
        <script src="<?= $path ?>JS/script.js" defer></script>
    </head>
    <body>
    <?php
}

    /* =========================
        NAVBAR (UPDATED AUTH FLOW)
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

                    <!-- anchor link -->
                    <li><a href="#how-it-works">How It Works</a></li>

                    <?php if ($loggedIn): ?>
                        <?php if ($isAdmin): ?>
                            <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="referrals.php">Referrals</a></li>
                        <?php endif; ?>
                        <li><a href="signout.php">Logout</a></li>
                    <?php else: ?>
                        <!-- NO SIGN UP -->
                        <li><a href="signin.php">Login</a></li>
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
                    Clinical responsibility is maintained through doctor-initiated referrals,
                    coordinated internally before being forwarded to receiving hospitals.
                </p>
                <a href="signin.php" class="btn-primary">Get Started</a>
            </div>
        </section>
        <?php
    }

    /* =========================
        WHY USE REFERNET
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
                    <p>Standardized communication between hospitals and coordinators.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Reduced Errors</h3>
                    <p>Eliminates missing or incomplete referral documentation.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Real-Time Tracking</h3>
                    <p>Monitor referral status from submission to completion.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Improved Efficiency</h3>
                    <p>Removes paper-based processes and manual coordination delays.</p>
                </div>

                <div class="feature-card highlight">
                    <h3>Maintains Clinical Responsibility</h3>
                    <p>
                        Doctors initiate referrals based on clinical assessment, while coordinators
                        manage routing and communication between facilities.
                    </p>
                </div>

            </div>
        </section>
        <?php
    }

    /* =========================
        HOW IT WORKS (UNCHANGED EXACTLY)
    ========================= */
    public function how_to_use($conf)
    {
        ?>
        <section class="section alt" id="how-it-works">

            <h2 class="section-title">How It Works</h2>

            <div class="steps-container">

                <div class="step">
                    <div class="circle">1</div>
                    <div>
                        <h3>Doctor Creates Referral</h3>
                        <p>
                            The doctor evaluates the patient and submits a clinical referral with diagnosis,
                            notes, and required level of care.
                        </p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">2</div>
                    <div>
                        <h3>Internal Referral Coordinator Review</h3>
                        <p>
                            The referral coordinator checks completeness, validates details,
                            and prepares the referral for forwarding.
                        </p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">3</div>
                    <div>
                        <h3>Forward to Receiving Hospital</h3>
                        <p>
                            The referral is securely sent to the receiving hospital’s referral coordinator.
                        </p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">4</div>
                    <div>
                        <h3>Receiving Coordinator Review</h3>
                        <p>
                            The receiving coordinator checks capacity and clinical suitability,
                            then responds with acceptance or rejection.
                        </p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">5</div>
                    <div>
                        <h3>Scheduling & Transfer</h3>
                        <p>
                            If accepted, an appointment or transfer is scheduled and confirmed.
                        </p>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="step">
                    <div class="circle">6</div>
                    <div>
                        <h3>Tracking & Completion</h3>
                        <p>
                            All stakeholders can track the referral until the patient transfer is completed.
                        </p>
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