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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $message = "Group ID is required.";
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

$group_id = (int) $_GET['id'];

// Fetch group details for audit trail
$group = BaseModelHelper::mysqliFind($db->db, 'ContactGroups', 'group_id', $group_id);
if (!$group || $group['member_id'] != $member_id) {
    $message = "Group not found or you don't have permission to delete it.";
    header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Delete group (cascade will handle related records)
if (BaseModelHelper::mysqliDelete($db->db, 'ContactGroups', 'group_id', $group_id)) {
    // Log audit trail
    BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
        'member_id' => $member_id,
        'action_type' => 'DELETE_GROUP',
        'entity_type' => 'ContactGroups',
        'entity_id' => $group_id,
        'details' => "Deleted group: {$group['group_name']}",
        'performed_on' => date('Y-m-d H:i:s')
    ]);
    
    $message = "Group deleted successfully!";
    $messageType = 'success';
} else {
    $message = "Failed to delete group: " . $db->db->error;
}

header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>
