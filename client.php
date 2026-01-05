<?php
require_once 'config.php';
initDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Display - Auction System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            color: white;
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .display-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        
        .item-image {
            max-width: 90%;
            max-height: 70vh;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-in;
        }
        
        .item-name {
            font-size: 4em;
            font-weight: bold;
            text-align: center;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.5s ease-in;
            padding: 0 20px;
        }
        
        .no-item {
            font-size: 3em;
            color: #666;
            text-align: center;
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .status-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 20px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #4CAF50;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>
<body>
    <div class="status-indicator">
        <div class="status-dot"></div>
        <span>Connected</span>
    </div>
    
    <div class="display-container" id="displayContainer">
        <div class="no-item">Waiting for item to be displayed...</div>
    </div>
    
    <script>
        function updateDisplay() {
            fetch('api.php?action=get_current_display')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('displayContainer');
                        
                        if (data.display.item_id && data.display.name && data.display.image_path) {
                            container.innerHTML = `
                                <img src="${data.display.image_path}" alt="${data.display.name}" class="item-image">
                                <div class="item-name">${data.display.name}</div>
                            `;
                        } else {
                            container.innerHTML = '<div class="no-item">Waiting for item to be displayed...</div>';
                        }
                    }
                })
                .catch(err => {
                    console.error('Error fetching display:', err);
                });
        }
        
        // Update immediately on load
        updateDisplay();
        
        // Update every 2 seconds to check for changes
        setInterval(updateDisplay, 2000);
        
        // Prevent context menu and selection for cleaner display
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
    </script>
</body>
</html>

