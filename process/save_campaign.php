<?php
session_start();
require_once '../classes/Member.php';
require_once '../classes/auditTrail.php';
require_once '../classes/BaseModelHelper.php';
require_once '../config/Database.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: ../member-login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$member = new Member();
$member->db = $db->db;

$auditTrail = new AuditTrail();
$auditTrail->db = $db->db;

$message = '';
$messageType = 'error';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_name = trim($_POST['campaign_name'] ?? $_POST['name'] ?? '');
    $campaign_status = trim($_POST['campaign_status'] ?? $_POST['status'] ?? 'Draft');
    $campaign_description = trim($_POST['campaign_description'] ?? $_POST['description'] ?? '');
    $schedule_time = trim($_POST['schedule_time'] ?? $_POST['scheduled'] ?? '');
    $owner_id = (int)($_POST['owner'] ?? $member_id);

    if (!$campaign_name || !$campaign_status || !$owner_id) {
        $message = "Please fill all required fields.";
    } else {
        $data = [
            'member_id' => $owner_id,
            'campaign_name' => $campaign_name,
            'campaign_description' => $campaign_description,
            'campaign_status' => $campaign_status,
            'schedule_time' => $schedule_time ? $schedule_time : null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $sql = $member->buildSQL('Campaigns', $data, 'insert');
        if ($member->db->query($sql)) {
            $campaign_id = $member->db->insert_id;
            
            // Log audit trail (using mysqli helper)
            BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                'member_id' => $member_id,
                'action_type' => 'CREATE_CAMPAIGN',
                'entity_type' => 'Campaigns',
                'entity_id' => $campaign_id,
                'details' => "Created campaign: {$campaign_name}",
                'performed_on' => date('Y-m-d H:i:s')
            ]);
            
            $message = "Campaign created successfully!";
            $messageType = 'success';
        } else {
            $message = "Error: " . $member->db->error;
        }
    }
}

// Redirect back to campaign page
header("Location: ../campaign.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>