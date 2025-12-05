<?php
session_start();
require_once '../classes/Member.php';
require_once '../classes/BaseModelHelper.php';
require_once '../config/Database.php';

// Ensure member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: ../member-login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$message = '';
$messageType = 'error';

// Get group ID
$group_id = (int)($_GET['id'] ?? 0);
if (!$group_id) {
    $message = "Group ID is required.";
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Fetch group details
$group = BaseModelHelper::mysqliFind($db->db, 'ContactGroups', 'group_id', $group_id);
if (!$group || $group['member_id'] != $member_id) {
    $message = "Group not found or you don't have permission to edit it.";
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Get current contact count
$currentContacts = BaseModelHelper::mysqliGetAll($db->db, 'Group_Members', ['group_id' => $group_id]);
$currentCount = count($currentContacts);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name'] ?? $_POST['name'] ?? '');
    $group_description = trim($_POST['group_description'] ?? $_POST['description'] ?? '');
    $max_contacts = (int)($_POST['max_contacts'] ?? 0); // 0 means unlimited
    
    if (empty($group_name)) {
        $message = "Group Name is required.";
    } else {
        // Validate: new limit should not be less than current contacts
        if ($max_contacts > 0 && $max_contacts < $currentCount) {
            $message = "Cannot set limit to {$max_contacts}. Group already has {$currentCount} contacts. Please remove some contacts first.";
        } else {
            $data = [
                'group_name' => $group_name,
                'group_description' => $group_description,
                'max_contacts' => $max_contacts > 0 ? $max_contacts : null
            ];
            
            if (BaseModelHelper::mysqliUpdate($db->db, 'ContactGroups', 'group_id', $group_id, $data)) {
                // Log audit trail
                BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                    'member_id' => $member_id,
                    'action_type' => 'UPDATE_GROUP',
                    'entity_type' => 'ContactGroups',
                    'entity_id' => $group_id,
                    'details' => "Updated group: {$group_name}" . ($max_contacts > 0 ? " (Max: {$max_contacts})" : ""),
                    'performed_on' => date('Y-m-d H:i:s')
                ]);
                
                $message = "Group updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Error updating group: " . $db->db->error;
            }
        }
    }
    
    // Redirect after POST
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Group - Email Marketing System</title>
    <style>
        /* ====== Dark Theme Variables ====== */
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --bg-hover: #475569;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --border: #334155;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .form-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px var(--shadow);
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--accent);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border);
            margin-top: 0.75rem;
            width: 100%;
        }

        .btn-secondary:hover {
            background: var(--bg-hover);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Group</h2>
        
        <div class="info-box">
            <strong>Current Status:</strong> This group has <?= $currentCount ?> contact(s).
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Group Name *</label>
                <input type="text" name="group_name" value="<?= htmlspecialchars($group['group_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="group_description"><?= htmlspecialchars($group['group_description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Max Contacts (0 = Unlimited)</label>
                <input type="number" name="max_contacts" value="<?= htmlspecialchars($group['max_contacts'] ?? 0) ?>" min="<?= $currentCount ?>" placeholder="0 for unlimited">
                <small>Set 0 for unlimited contacts. Minimum: <?= $currentCount ?> (current contact count)</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Group</button>
            <a href="../member_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
