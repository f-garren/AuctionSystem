<?php
require_once 'config.php';
initDB();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = getDB();
    
    switch ($action) {
        case 'get_items':
            $stmt = $db->query("SELECT * FROM items ORDER BY created_at DESC");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'items' => $items]);
            break;
            
        case 'get_current_display':
            $stmt = $db->query("SELECT cd.item_id, i.name, i.image_path 
                               FROM current_display cd 
                               LEFT JOIN items i ON cd.item_id = i.id 
                               WHERE cd.id = 1");
            $display = $stmt->fetch(PDO::FETCH_ASSOC);
            // item_id = -1 means auction ended
            if ($display && $display['item_id'] == -1) {
                $display['auction_ended'] = true;
            }
            echo json_encode(['success' => true, 'display' => $display]);
            break;
            
        case 'set_current_display':
            $item_id = $_POST['item_id'] ?? null;
            $stmt = $db->prepare("UPDATE current_display SET item_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1");
            $stmt->execute([$item_id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'create_item':
            $name = $_POST['name'] ?? '';
            $image_path = '';
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $filename = uniqid() . '.' . $ext;
                    $target = UPLOAD_DIR . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $image_path = UPLOAD_URL . $filename;
                    }
                }
            }
            
            if (empty($name) || empty($image_path)) {
                throw new Exception('Name and image are required');
            }
            
            $stmt = $db->prepare("INSERT INTO items (name, image_path) VALUES (?, ?)");
            $stmt->execute([$name, $image_path]);
            $item_id = $db->lastInsertId();
            
            echo json_encode(['success' => true, 'item_id' => $item_id]);
            break;
            
        case 'update_item':
            $item_id = $_POST['item_id'] ?? null;
            $name = $_POST['name'] ?? '';
            
            if (!$item_id || empty($name)) {
                throw new Exception('Item ID and name are required');
            }
            
            // Update name
            $stmt = $db->prepare("UPDATE items SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$name, $item_id]);
            
            // Update image if provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    // Get old image path
                    $stmt = $db->prepare("SELECT image_path FROM items WHERE id = ?");
                    $stmt->execute([$item_id]);
                    $old_image = $stmt->fetchColumn();
                    
                    // Delete old image
                    if ($old_image && file_exists(__DIR__ . '/' . $old_image)) {
                        unlink(__DIR__ . '/' . $old_image);
                    }
                    
                    // Upload new image
                    $filename = uniqid() . '.' . $ext;
                    $target = UPLOAD_DIR . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $image_path = UPLOAD_URL . $filename;
                        $stmt = $db->prepare("UPDATE items SET image_path = ? WHERE id = ?");
                        $stmt->execute([$image_path, $item_id]);
                    }
                }
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_item':
            $item_id = $_POST['item_id'] ?? null;
            
            if (!$item_id) {
                throw new Exception('Item ID is required');
            }
            
            // Get image path
            $stmt = $db->prepare("SELECT image_path FROM items WHERE id = ?");
            $stmt->execute([$item_id]);
            $image_path = $stmt->fetchColumn();
            
            // Delete image file
            if ($image_path && file_exists(__DIR__ . '/' . $image_path)) {
                unlink(__DIR__ . '/' . $image_path);
            }
            
            // Delete item
            $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$item_id]);
            
            // Clear current display if this item was being displayed
            $stmt = $db->prepare("UPDATE current_display SET item_id = NULL WHERE item_id = ?");
            $stmt->execute([$item_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

