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

if (!isset($_GET['contact_id']) || !isset($_GET['group_id'])) {
    $message = "Contact ID and Group ID are required.";
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

$contact_id = (int) $_GET['contact_id'];
$group_id = (int) $_GET['group_id'];

// Verify contact belongs to member
$contact = BaseModelHelper::mysqliFind($db->db, 'Contacts', 'contact_id', $contact_id);
if (!$contact || $contact['member_id'] != $member_id) {
    $message = "Contact not found or access denied.";
} else {
    // Verify group belongs to member
    $group = BaseModelHelper::mysqliFind($db->db, 'ContactGroups', 'group_id', $group_id);
    if (!$group || $group['member_id'] != $member_id) {
        $message = "Group not found or access denied.";
    } else {
        // Remove contact from group
        $sql = "DELETE FROM Group_Members WHERE group_id = {$group_id} AND contact_id = {$contact_id}";
        if ($db->db->query($sql)) {
            // Log audit trail
            BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                'member_id' => $member_id,
                'action_type' => 'REMOVE_CONTACT_FROM_GROUP',
                'entity_type' => 'Group_Members',
                'entity_id' => $contact_id,
                'details' => "Removed contact from group: {$group['group_name']} (now solo)",
                'performed_on' => date('Y-m-d H:i:s')
            ]);
            
            $message = "Contact removed from group successfully! Contact is now solo.";
            $messageType = 'success';
        } else {
            $message = "Error removing contact: " . $db->db->error;
        }
    }
}

// Check if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => $messageType === 'success', 'message' => $message]);
    exit;
}

header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>

