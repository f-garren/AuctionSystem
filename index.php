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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 40px;
            font-size: 2.5em;
        }
        
        .mode-buttons {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .mode-btn {
            padding: 20px 40px;
            font-size: 1.3em;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-decoration: none;
            display: block;
            color: white;
        }
        
        .mode-btn.manager {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .mode-btn.manager:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .mode-btn.client {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .mode-btn.client:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.4);
        }
    </style>
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

