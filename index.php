<?php
require_once 'config.php';
initDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Display System</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="container">
        <h1>🏛️ Auction Display System</h1>
        <div class="mode-buttons">
            <a href="manager.php" class="mode-btn manager">Manager</a>
            <a href="client.php" class="mode-btn client">Client Display</a>
        </div>
    </div>
</body>
</html>

