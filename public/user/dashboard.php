<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/../db.php';

if (isset($_GET['delete'])) {
    $linkId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM links WHERE id = ? AND user_id = ?");
    $stmt->execute([$linkId, $_SESSION['user_id']]);
    header('Location: dashboard.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM links WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { color: #667eea; font-size: 36px; margin-bottom: 10px; }
        .stat-card p { color: #666; }
        table { width: 100%; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; }
        th { background: #667eea; color: white; }
        tr:nth-child(even) { background: #f8f9fa; }
        .actions a { color: #667eea; text-decoration: none; margin-right: 10px; }
        .actions a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #5568d3; }
        .delete { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo count($links); ?></h3>
                <p>Total Links</p>
            </div>
            <div class="stat-card">
                <h3><?php echo array_sum(array_column($links, 'click_count')); ?></h3>
                <p>Total Clicks</p>
            </div>
        </div>
        
        <a href="../public/index.php" class="btn">Create New Link</a>
        <a href="logout.php" class="btn">Logout</a>
        
        <h2 style="margin: 30px 0 20px;">Your Links</h2>
        
        <?php if (empty($links)): ?>
            <p>No links created yet. <a href="../public/index.php">Create your first link!</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Short Code</th>
                        <th>Long URL</th>
                        <th>Clicks</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td>
                                <a href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/<?php echo htmlspecialchars($link['short_code']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($link['short_code']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars(substr($link['long_url'], 0, 50)) . (strlen($link['long_url']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo $link['click_count']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($link['created_at'])); ?></td>
                            <td class="actions">
                                <a href="?delete=<?php echo $link['id']; ?>" class="delete" onclick="return confirm('Delete this link?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
