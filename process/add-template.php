<?php
session_start();
require_once '../classes/Member.php';
require_once '../classes/template.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = trim($_POST['template_name'] ?? $_POST['template_title'] ?? '');
    $template_content = trim($_POST['template_content'] ?? '');
    $template_subject = trim($_POST['template_subject'] ?? '');
    $template_status = trim($_POST['template_status'] ?? $_POST['status'] ?? 'Active');
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);
    
    if (!$template_name || !$template_content || !$campaign_id) {
        $message = "Template name, content, and campaign are required.";
    } else {
        // Verify campaign belongs to member
        $campaign = BaseModelHelper::mysqliFind($db->db, 'Campaigns', 'campaign_id', $campaign_id);
        if (!$campaign || $campaign['member_id'] != $member_id) {
            $message = "Invalid campaign or access denied.";
        } else {
            // Handle file upload
            $attachment_path = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/attachments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file = $_FILES['attachment'];
                $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt'];
                $max_size = 10 * 1024 * 1024; // 10MB
                
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $file_size = $file['size'];
                
                if (!in_array($file_ext, $allowed_types)) {
                    $message = "Invalid file type. Allowed: " . implode(', ', $allowed_types);
                } elseif ($file_size > $max_size) {
                    $message = "File size exceeds 10MB limit.";
                } else {
                    $filename = 'template_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $attachment_path = 'uploads/attachments/' . $filename;
                    } else {
                        $message = "Error uploading file.";
                    }
                }
            }
            
            if (!$message) {
                $data = [
                    'campaign_id' => $campaign_id,
                    'template_name' => $template_name,
                    'template_content' => $template_content,
                    'template_subject' => $template_subject,
                    'template_status' => $template_status,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                if ($attachment_path) {
                    $data['attachment_path'] = $attachment_path;
                }
                
                $template_id = BaseModelHelper::mysqliCreate($db->db, 'Templates', $data);
            
                if ($template_id) {
                    // Log audit trail
                    BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                        'member_id' => $member_id,
                        'action_type' => 'CREATE_TEMPLATE',
                        'entity_type' => 'Templates',
                        'entity_id' => $template_id,
                        'details' => "Created template: {$template_name}" . ($attachment_path ? " (with attachment)" : ""),
                        'performed_on' => date('Y-m-d H:i:s')
                    ]);
                    
                    $message = "Template created successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error creating template: " . $db->db->error;
                }
            }
        }
    }
}

header("Location: ../templates.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>

