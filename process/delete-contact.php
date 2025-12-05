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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../member_dashboard.php?message=" . urlencode("No contact selected.") . "&type=error");
    exit;
}

$contact_id = (int) $_GET['id'];

// Verify contact belongs to this member
$contact = BaseModelHelper::mysqliFind($db->db, 'Contacts', 'contact_id', $contact_id);
if (!$contact || $contact['member_id'] != $member_id) {
    header("Location: ../member_dashboard.php?message=" . urlencode("Contact not found or you don't have permission to delete it.") . "&type=error");
    exit;
}

// Delete contact
if (BaseModelHelper::mysqliDelete($db->db, 'Contacts', 'contact_id', $contact_id)) {
    // Log audit trail
    BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
        'member_id' => $member_id,
        'action_type' => 'DELETE_CONTACT',
        'entity_type' => 'Contacts',
        'entity_id' => $contact_id,
        'details' => "Deleted contact: {$contact['first_name']} {$contact['last_name']}",
        'performed_on' => date('Y-m-d H:i:s')
    ]);
    
    header("Location: ../member_dashboard.php?message=" . urlencode("Contact deleted successfully") . "&type=success");
    exit;
} else {
    header("Location: ../member_dashboard.php?message=" . urlencode("Error deleting contact: " . $db->db->error) . "&type=error");
    exit;
}
?>
