<?php
require_once 'config.php';
initDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - Auction Display System</title>
    <link rel="stylesheet" href="css/manager.css">
</head>
<body>
    <div class="header">
        <h1>🎛️ Manager Control Panel</h1>
        <a href="index.php" class="btn btn-back">← Back to Home</a>
    </div>
    
    <div class="actions">
        <button class="btn btn-primary" onclick="openCreateModal()">+ Create New Item</button>
        <button class="btn btn-secondary" onclick="loadItems()">🔄 Refresh</button>
    </div>
    
    <div id="currentDisplay" class="current-display hidden">
        <strong>Currently Displaying:</strong> <span id="currentDisplayName"></span>
    </div>
    
    <div id="itemsContainer" class="items-grid">
        <div class="empty-state">Loading items...</div>
    </div>
    
    <!-- Create/Edit Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create Item</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="itemForm" onsubmit="saveItem(event)">
                <input type="hidden" id="itemId" name="item_id">
                <div class="form-group">
                    <label for="itemName">Item Name:</label>
                    <input type="text" id="itemName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="itemImage">Image:</label>
                    <input type="file" id="itemImage" name="image" accept="image/*" required>
                    <small id="imageNote" class="image-note">Required for new items</small>
                </div>
                <div id="currentImagePreview" class="current-image-preview">
                    <img id="currentImage" src="" alt="Current image">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let editingItemId = null;
        
        function loadItems() {
            fetch('api.php?action=get_items')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayItems(data.items);
                        updateCurrentDisplay();
                    }
                });
        }
        
        function updateCurrentDisplay() {
            fetch('api.php?action=get_current_display')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.display.item_id) {
                        document.getElementById('currentDisplay').classList.remove('hidden');
                        document.getElementById('currentDisplayName').textContent = data.display.name;
                    } else {
                        document.getElementById('currentDisplay').classList.add('hidden');
                    }
                });
        }
        
        function displayItems(items) {
            const container = document.getElementById('itemsContainer');
            
            if (items.length === 0) {
                container.innerHTML = '<div class="empty-state">No items yet. Create your first item!</div>';
                return;
            }
            
            container.innerHTML = items.map(item => `
                <div class="item-card">
                    <img src="${item.image_path}" alt="${item.name}">
                    <h3>${item.name}</h3>
                    <div class="item-actions">
                        <button class="btn btn-secondary" onclick="displayItem(${item.id})">📺 Display</button>
                        <button class="btn btn-primary" onclick="editItem(${item.id}, '${item.name.replace(/'/g, "\\'")}', '${item.image_path}')">✏️ Edit</button>
                        <button class="btn btn-danger" onclick="deleteItem(${item.id})">🗑️ Delete</button>
                    </div>
                </div>
            `).join('');
        }
        
        function openCreateModal() {
            editingItemId = null;
            document.getElementById('modalTitle').textContent = 'Create Item';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            document.getElementById('itemImage').required = true;
            document.getElementById('imageNote').classList.remove('hidden');
            document.getElementById('currentImagePreview').classList.add('hidden');
            document.getElementById('itemModal').classList.add('show');
        }
        
        function editItem(id, name, imagePath) {
            editingItemId = id;
            document.getElementById('modalTitle').textContent = 'Edit Item';
            document.getElementById('itemId').value = id;
            document.getElementById('itemName').value = name;
            document.getElementById('itemImage').required = false;
            document.getElementById('imageNote').textContent = 'Leave empty to keep current image';
            document.getElementById('currentImagePreview').classList.remove('hidden');
            document.getElementById('currentImage').src = imagePath;
            document.getElementById('itemModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('itemModal').classList.remove('show');
        }
        
        function saveItem(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('action', editingItemId ? 'update_item' : 'create_item');
            formData.append('name', document.getElementById('itemName').value);
            
            if (editingItemId) {
                formData.append('item_id', editingItemId);
                const imageFile = document.getElementById('itemImage').files[0];
                if (imageFile) {
                    formData.append('image', imageFile);
                }
            } else {
                formData.append('image', document.getElementById('itemImage').files[0]);
            }
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadItems();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            });
        }
        
        function displayItem(itemId) {
            const formData = new FormData();
            formData.append('action', 'set_current_display');
            formData.append('item_id', itemId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updateCurrentDisplay();
                    alert('Item is now being displayed on all client screens!');
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            });
        }
        
        function deleteItem(itemId) {
            if (!confirm('Are you sure you want to delete this item?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_item');
            formData.append('item_id', itemId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadItems();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('itemModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Load items on page load
        loadItems();
        setInterval(updateCurrentDisplay, 5000); // Update current display every 5 seconds
    </script>
</body>
</html>

