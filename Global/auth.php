<?php

class Auth
{
    private $conn;
    private array $conf;

    public function __construct($conn, array $conf)
    {
        $this->conn = $conn;
        $this->conf = $conf;
    }

    /* ==============================================
       Verify login credentials (email + password + role)
       Returns user array on success, false on failure
    =============================================== */
    public function verifyCredentials(string $email, string $password, string $role): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT u.*, h.hospital_name
             FROM user u
             JOIN hospital h ON u.hospital_id = h.hospital_id
             WHERE u.email = :email
               AND u.role  = :role
               AND u.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':email' => $email, ':role' => $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        return $user;
    }

    /* ==============================================
       Generate a 6-digit OTP, store in DB, return code
       Expires in 2 minutes
    =============================================== */
    public function generateOTP(int $userId): string
    {
        // Invalidate any existing unused OTPs for this user
        $stmt = $this->conn->prepare(
            "UPDATE otp SET used = 1 WHERE user_id = :uid AND used = 0"
        );
        $stmt->execute([':uid' => $userId]);

        // Generate new OTP — use MySQL NOW() to avoid PHP/MySQL timezone mismatch
        $otp  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $this->conn->prepare(
            "INSERT INTO otp (user_id, otp_code, expires_at)
             VALUES (:uid, :otp, DATE_ADD(NOW(), INTERVAL 2 MINUTE))"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':otp' => $otp,
        ]);

        return $otp;
    }

    /* ==============================================
       Verify submitted OTP for a user
       Returns true on success, string error on failure
    =============================================== */
    public function verifyOTP(int $userId, string $submittedOtp): true|string
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM otp
             WHERE user_id  = :uid
               AND otp_code = :otp
               AND used     = 0
               AND expires_at > NOW()
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId, ':otp' => $submittedOtp]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // Distinguish expired vs wrong code
            $stmt2 = $this->conn->prepare(
                "SELECT * FROM otp
                 WHERE user_id  = :uid
                   AND otp_code = :otp
                   AND used     = 0
                 LIMIT 1"
            );
            $stmt2->execute([':uid' => $userId, ':otp' => $submittedOtp]);
            $expired = $stmt2->fetch(PDO::FETCH_ASSOC);

            return $expired ? 'Your OTP has expired. Please request a new one.' : 'Invalid OTP. Please try again.';
        }

        // Mark OTP as used
        $stmt = $this->conn->prepare(
            "UPDATE otp SET used = 1 WHERE otp_id = :id"
        );
        $stmt->execute([':id' => $record['otp_id']]);

        return true;
    }

    /* ==============================================
       Create session after successful OTP verification
    =============================================== */
    public function createSession(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true); // prevent session fixation

        $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['hospital_id']   = $user['hospital_id'];
        $_SESSION['hospital_name'] = $user['hospital_name'];
        $_SESSION['department']    = $user['department'] ?? '';
        $_SESSION['logged_in']     = true;
    }

    /* ==============================================
       Guard — call at top of every protected page
       Redirects to signin if not logged in
    =============================================== */
    public function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please log in to access that page.';
            header('Location: signin.php');
            exit;
        }
    }

    /* ==============================================
       Guard — restrict page to a specific role
       Call after requireLogin()
    =============================================== */
    public function requireRole(string $role): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SESSION['role'] !== $role) {
            $_SESSION['error'] = 'You do not have permission to access that page.';
            header('Location: dashboard.php');
            exit;
        }
    }

    /* ==============================================
       Destroy session (logout)
    =============================================== */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        header('Location: signin.php');
        exit;
    }

    /* ==============================================
       Get currently logged-in user's full details from DB
    =============================================== */
    public function currentUser(): array|false
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) return false;

        $stmt = $this->conn->prepare(
            "SELECT u.*, h.hospital_name
             FROM user u
             JOIN hospital h ON u.hospital_id = h.hospital_id
             WHERE u.user_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>