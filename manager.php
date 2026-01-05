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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 2em;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #0b7dda;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #da190b;
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .item-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .item-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .item-card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .item-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }
        
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .form-group input[type="file"] {
            padding: 5px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .current-display {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
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
    
    <div id="currentDisplay" class="current-display" style="display: none;">
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
                    <small id="imageNote" style="color: #666; display: block; margin-top: 5px;">Required for new items</small>
                </div>
                <div id="currentImagePreview" style="margin-bottom: 15px; display: none;">
                    <img id="currentImage" src="" alt="Current image" style="max-width: 100%; border-radius: 5px;">
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
                        document.getElementById('currentDisplay').style.display = 'block';
                        document.getElementById('currentDisplayName').textContent = data.display.name;
                    } else {
                        document.getElementById('currentDisplay').style.display = 'none';
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
            document.getElementById('imageNote').style.display = 'block';
            document.getElementById('currentImagePreview').style.display = 'none';
            document.getElementById('itemModal').style.display = 'block';
        }
        
        function editItem(id, name, imagePath) {
            editingItemId = id;
            document.getElementById('modalTitle').textContent = 'Edit Item';
            document.getElementById('itemId').value = id;
            document.getElementById('itemName').value = name;
            document.getElementById('itemImage').required = false;
            document.getElementById('imageNote').textContent = 'Leave empty to keep current image';
            document.getElementById('currentImagePreview').style.display = 'block';
            document.getElementById('currentImage').src = imagePath;
            document.getElementById('itemModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
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

