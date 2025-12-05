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

// Get contact ID
$contact_id = (int)($_GET['id'] ?? 0);
if (!$contact_id) {
    $message = "Contact ID is required.";
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Fetch contact details
$contact = BaseModelHelper::mysqliFind($db->db, 'Contacts', 'contact_id', $contact_id);
if (!$contact || $contact['member_id'] != $member_id) {
    $message = "Contact not found or you don't have permission to edit it.";
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $honorifics = trim($_POST['honorifics'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_status = trim($_POST['contact_status'] ?? 'Active');
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $message = "First name, last name, and email are required.";
    } else {
        // Check if email already exists for another contact of this member
        $existing = BaseModelHelper::mysqliGetAll($db->db, 'Contacts', ['member_id' => $member_id, 'email' => $email]);
        if (!empty($existing) && $existing[0]['contact_id'] != $contact_id) {
            $message = "Email already exists for another contact.";
        } else {
            $data = [
                'honorifics' => $honorifics,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'email' => $email,
                'contact_status' => $contact_status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (BaseModelHelper::mysqliUpdate($db->db, 'Contacts', 'contact_id', $contact_id, $data)) {
                // Log audit trail
                BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                    'member_id' => $member_id,
                    'action_type' => 'UPDATE_CONTACT',
                    'entity_type' => 'Contacts',
                    'entity_id' => $contact_id,
                    'details' => "Updated contact: {$first_name} {$last_name}",
                    'performed_on' => date('Y-m-d H:i:s')
                ]);
                
                $message = "Contact updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Error updating contact: " . $db->db->error;
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
    <title>Edit Contact - Email Marketing System</title>
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
        .form-group select {
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
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
        <h2>Edit Contact</h2>
        
        <form method="POST">
            <div class="form-group">
                <label>Honorifics</label>
                <input type="text" name="honorifics" value="<?= htmlspecialchars($contact['honorifics'] ?? '') ?>" placeholder="Mr./Ms./Dr.">
            </div>
            
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($contact['first_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($contact['middle_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($contact['last_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($contact['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="contact_status" required>
                    <option value="Active" <?= $contact['contact_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $contact['contact_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Contact</button>
            <a href="../member_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
