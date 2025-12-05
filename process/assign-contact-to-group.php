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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    $group_id = (int)($_POST['group_id'] ?? 0);
    
    if (!$contact_id || !$group_id) {
        $message = "Contact ID and Group ID are required.";
    } else {
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
                // Check if contact is already in this group
                $existing = BaseModelHelper::mysqliGetAll($db->db, 'Group_Members', ['group_id' => $group_id, 'contact_id' => $contact_id]);
                if (!empty($existing)) {
                    $message = "Contact is already in this group.";
                } else {
                    // Check group limit
                    $currentCount = count(BaseModelHelper::mysqliGetAll($db->db, 'Group_Members', ['group_id' => $group_id]));
                    $maxContacts = $group['max_contacts'] ?? null;
                    
                    if ($maxContacts !== null && $maxContacts > 0 && $currentCount >= $maxContacts) {
                        $message = "Group limit reached. Maximum {$maxContacts} contacts allowed. Current: {$currentCount}";
                    } else {
                        // Add contact to group
                        $data = [
                            'group_id' => $group_id,
                            'contact_id' => $contact_id,
                            'added_on' => date('Y-m-d H:i:s')
                        ];
                        
                        if (BaseModelHelper::mysqliCreate($db->db, 'Group_Members', $data)) {
                            // Log audit trail
                            BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                                'member_id' => $member_id,
                                'action_type' => 'ASSIGN_CONTACT_TO_GROUP',
                                'entity_type' => 'Group_Members',
                                'entity_id' => $contact_id,
                                'details' => "Assigned contact to group: {$group['group_name']}",
                                'performed_on' => date('Y-m-d H:i:s')
                            ]);
                            
                            $message = "Contact assigned to group successfully!";
                            $messageType = 'success';
                        } else {
                            $message = "Error assigning contact: " . $db->db->error;
                        }
                    }
                }
            }
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

