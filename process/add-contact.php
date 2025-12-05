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
    $honorifics = trim($_POST['honorifics'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_status = trim($_POST['contact_status'] ?? 'Active');
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $message = "First name, last name, and email are required.";
    } else {
        // Check if email already exists for this member
        $existing = BaseModelHelper::mysqliGetAll($db->db, 'Contacts', ['member_id' => $member_id, 'email' => $email]);
        if (!empty($existing)) {
            $message = "Email already exists for this member.";
        } else {
            $data = [
                'member_id' => $member_id,
                'honorifics' => $honorifics,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'email' => $email,
                'contact_status' => $contact_status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $contact_id = BaseModelHelper::mysqliCreate($db->db, 'Contacts', $data);
            
            if ($contact_id) {
                // Log audit trail
                BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                    'member_id' => $member_id,
                    'action_type' => 'CREATE_CONTACT',
                    'entity_type' => 'Contacts',
                    'entity_id' => $contact_id,
                    'details' => "Created contact: {$first_name} {$last_name}",
                    'performed_on' => date('Y-m-d H:i:s')
                ]);
                
                $message = "Contact added successfully!";
                $messageType = 'success';
            } else {
                $message = "Error adding contact: " . $db->db->error;
            }
        }
    }
}

header("Location: ../member_dashboard.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>
