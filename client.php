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
    <link rel="stylesheet" href="css/client.css">
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

