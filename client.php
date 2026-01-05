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
        <div class="no-item">Waiting for next item...</div>
    </div>
    
    <script>
        let currentItemId = null;
        let isFirstLoad = true;
        
        function updateDisplay() {
            fetch('api.php?action=get_current_display')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('displayContainer');
                        const newItemId = data.display.item_id;
                        const itemChanged = newItemId !== currentItemId;
                        
                        // Only animate if the item actually changed (or on first load)
                        // Also animate when transitioning to/from auction ended state
                        const wasEnded = currentItemId == -1;
                        const isEnded = newItemId == -1 || data.display.auction_ended;
                        const shouldAnimate = itemChanged || isFirstLoad || (wasEnded !== isEnded);
                        
                        if (newItemId && newItemId > 0 && data.display.name && data.display.image_path) {
                            container.innerHTML = `
                                <img src="${data.display.image_path}" alt="${data.display.name}" 
                                     class="item-image${shouldAnimate ? ' animate' : ''}">
                                <div class="item-name${shouldAnimate ? ' animate' : ''}">${data.display.name}</div>
                            `;
                        } else if (newItemId == -1 || data.display.auction_ended) {
                            container.innerHTML = `<div class="no-item${shouldAnimate ? ' animate' : ''}">Thank you!</div>`;
                        } else {
                            container.innerHTML = `<div class="no-item${shouldAnimate ? ' animate' : ''}">Waiting for next item...</div>`;
                        }
                        
                        // Update current item ID and mark first load as complete
                        if (itemChanged) {
                            currentItemId = newItemId;
                        }
                        isFirstLoad = false;
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

