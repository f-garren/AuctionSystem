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
    <div class="status-indicator" id="statusIndicator">
        <div class="status-dot" id="statusDot"></div>
        <span id="statusText">Connected</span>
    </div>
    
    <div class="display-container" id="displayContainer">
        <div class="no-item">Waiting for next item...</div>
    </div>
    
    <script>
        let currentItemId = null;
        let isFirstLoad = true;
        let consecutiveFailures = 0;
        const MAX_FAILURES = 2;
        
        function updateConnectionStatus(connected) {
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('statusText');
            const statusIndicator = document.getElementById('statusIndicator');
            
            if (connected) {
                statusDot.classList.remove('disconnected');
                statusDot.classList.add('connected');
                statusText.textContent = 'Connected';
                statusIndicator.classList.remove('disconnected');
                consecutiveFailures = 0;
            } else {
                statusDot.classList.remove('connected');
                statusDot.classList.add('disconnected');
                statusText.textContent = 'Disconnected';
                statusIndicator.classList.add('disconnected');
            }
        }
        
        function updateDisplay() {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);
            
            fetch('api.php?action=get_current_display', {
                method: 'GET',
                signal: controller.signal
            })
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        updateConnectionStatus(true);
                        const container = document.getElementById('displayContainer');
                        const newItemId = data.display.item_id;
                        const itemChanged = newItemId !== currentItemId;
                        
                        if (newItemId && newItemId > 0 && data.display.name && data.display.image_path) {
                            container.innerHTML = `
                                <img src="${data.display.image_path}" alt="${data.display.name}" class="item-image">
                                <div class="item-name">${data.display.name}</div>
                            `;
                        } else if (data.display.auction_ended) {
                            container.innerHTML = `<div class="no-item">Thank you!</div>`;
                        } else {
                            container.innerHTML = `<div class="no-item">Waiting for next item...</div>`;
                        }
                        
                        // Update current item ID and mark first load as complete
                        if (itemChanged) {
                            currentItemId = newItemId;
                        }
                        isFirstLoad = false;
                    } else {
                        throw new Error('API returned unsuccessful response');
                    }
                    clearTimeout(timeoutId);
                })
                .catch(err => {
                    clearTimeout(timeoutId);
                    console.error('Error fetching display:', err);
                    consecutiveFailures++;
                    if (consecutiveFailures >= MAX_FAILURES) {
                        updateConnectionStatus(false);
                    }
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

