<?php
session_start();
include __DIR__ . '/../db.php';

$message = '';
$shortUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['long_url'])) {
    $longUrl = trim($_POST['long_url']);
    
    if (filter_var($longUrl, FILTER_VALIDATE_URL)) {
        $stmt = $db->prepare("SELECT short_code FROM links WHERE long_url = ?");
        $stmt->execute([$longUrl]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $shortCode = $existing['short_code'];
        } else {
            $shortCode = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $stmt = $db->prepare("INSERT INTO links (user_id, long_url, short_code) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $longUrl, $shortCode]);
        }
        
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $shortUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . $shortCode;
        $message = 'Short URL created successfully!';
    } else {
        $message = 'Please enter a valid URL.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Shortener</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; width: 90%; }
        h1 { color: #333; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #5568d3; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .result { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; word-break: break-all; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #667eea; text-decoration: none; margin: 0 10px; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 URL Shortener</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $shortUrl ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($shortUrl): ?>
            <div class="result">
                <strong>Your Short URL:</strong><br>
                <a href="<?php echo htmlspecialchars($shortUrl); ?>" target="_blank">
                    <?php echo htmlspecialchars($shortUrl); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="text" name="long_url" placeholder="Enter your long URL here..." required>
            </div>
            <button type="submit">Shorten URL</button>
        </form>
        
        <div class="links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../user/dashboard.php">Dashboard</a>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="../admin/index.php">Admin Panel</a>
                <?php endif; ?>
                <a href="../user/logout.php">Logout</a>
            <?php else: ?>
                <a href="../user/login.php">Login</a>
                <a href="../user/signup.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
