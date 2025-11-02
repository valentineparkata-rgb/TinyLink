<?php
session_start();
include __DIR__ . '/../db.php';

$message = '';
$showOtp = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_otp'])) {
        $email = trim($_POST['email']);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = 'Email already registered.';
            } else {
                $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['signup_email'] = $email;
                $_SESSION['generated_otp'] = $otp;
                $_SESSION['otp_time'] = time();
                $showOtp = true;
                $message = 'OTP generated successfully! (In production, this would be sent via email)';
            }
        } else {
            $message = 'Invalid email address.';
        }
    } elseif (isset($_POST['verify_signup'])) {
        $otp = trim($_POST['otp']);
        $password = $_POST['password'];
        $email = $_SESSION['signup_email'] ?? '';
        $storedOtp = $_SESSION['generated_otp'] ?? '';
        $otpTime = $_SESSION['otp_time'] ?? 0;
        
        if (time() - $otpTime > 600) {
            $message = 'OTP expired. Please request a new one.';
            unset($_SESSION['signup_email'], $_SESSION['generated_otp'], $_SESSION['otp_time']);
        } elseif ($otp === $storedOtp && $email) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password_hash, is_verified) VALUES (?, ?, true)");
            $stmt->execute([$email, $passwordHash]);
            
            unset($_SESSION['signup_email'], $_SESSION['generated_otp'], $_SESSION['otp_time']);
            $message = 'Registration successful! You can now login.';
        } else {
            $message = 'Invalid OTP. Please try again.';
        }
    }
}

if (isset($_SESSION['signup_email']) && isset($_SESSION['generated_otp']) && !isset($_POST['verify_signup'])) {
    $showOtp = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 400px; width: 90%; }
        h1 { color: #333; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #5568d3; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .otp-display { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center; }
        .otp-code { font-size: 24px; font-weight: bold; letter-spacing: 3px; margin: 10px 0; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #667eea; text-decoration: none; }
        .note { font-size: 12px; color: #666; margin-top: 10px; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sign Up</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, 'success') !== false || strpos($message, 'generated') !== false) ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$showOtp): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <button type="submit" name="send_otp">Send OTP</button>
            </form>
        <?php else: ?>
            <div class="otp-display">
                <p>Your OTP Code:</p>
                <div class="otp-code"><?php echo htmlspecialchars($_SESSION['generated_otp']); ?></div>
                <p class="note">Valid for 10 minutes</p>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Enter OTP:</label>
                    <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" minlength="6" required>
                </div>
                <button type="submit" name="verify_signup">Complete Registration</button>
            </form>
        <?php endif; ?>
        
        <div class="links">
            <a href="login.php">Already have an account? Login</a> |
            <a href="../public/index.php">Home</a>
        </div>
    </div>
</body>
</html>
