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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name'] ?? $_POST['name'] ?? '');
    $group_description = trim($_POST['group_description'] ?? $_POST['description'] ?? '');
    $max_contacts = (int)($_POST['max_contacts'] ?? 0); // 0 means unlimited
    
    if (empty($group_name)) {
        $message = "Group Name is required.";
    } else {
        $data = [
            'member_id' => $member_id,
            'group_name' => $group_name,
            'group_description' => $group_description,
            'max_contacts' => $max_contacts > 0 ? $max_contacts : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $group_id = BaseModelHelper::mysqliCreate($db->db, 'ContactGroups', $data);
        
        if ($group_id) {
            // Log audit trail
            BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                'member_id' => $member_id,
                'action_type' => 'CREATE_GROUP',
                'entity_type' => 'ContactGroups',
                'entity_id' => $group_id,
                'details' => "Created group: {$group_name}" . ($max_contacts > 0 ? " (Max: {$max_contacts})" : ""),
                'performed_on' => date('Y-m-d H:i:s')
            ]);
            
            $message = "Group added successfully!";
            $messageType = 'success';
        } else {
            $message = "Error adding group: " . $db->db->error;
        }
    }
}

header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>
