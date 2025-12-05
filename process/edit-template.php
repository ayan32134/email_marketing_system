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

$template_id = (int)($_GET['id'] ?? 0);
$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $template_id) {
    $template_name = trim($_POST['template_name'] ?? $_POST['template_title'] ?? '');
    $template_content = trim($_POST['template_content'] ?? '');
    $template_subject = trim($_POST['template_subject'] ?? '');
    $template_status = trim($_POST['template_status'] ?? $_POST['status'] ?? 'Active');
    $remove_attachment = isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == '1';
    
    // Verify template exists and belongs to member via campaign
    $template = BaseModelHelper::mysqliFind($db->db, 'Templates', 'template_id', $template_id);
    if (!$template) {
        $message = "Template not found.";
    } else {
        // Verify template's campaign belongs to member
        $campaign = BaseModelHelper::mysqliFind($db->db, 'Campaigns', 'campaign_id', $template['campaign_id']);
        if (!$campaign || $campaign['member_id'] != $member_id) {
            $message = "Access denied.";
        } else {
            $data = [
                'template_name' => $template_name,
                'template_content' => $template_content,
                'template_subject' => $template_subject,
                'template_status' => $template_status
            ];
            
            // Handle attachment removal
            if ($remove_attachment && !empty($template['attachment_path'])) {
                $old_file = '../' . $template['attachment_path'];
                if (file_exists($old_file)) {
                    @unlink($old_file);
                }
                $data['attachment_path'] = null;
            }
            
            // Handle new file upload
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
                    // Remove old file if exists
                    if (!empty($template['attachment_path'])) {
                        $old_file = '../' . $template['attachment_path'];
                        if (file_exists($old_file)) {
                            @unlink($old_file);
                        }
                    }
                    
                    $filename = 'template_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $data['attachment_path'] = 'uploads/attachments/' . $filename;
                    } else {
                        $message = "Error uploading file.";
                    }
                }
            }
            
            if (!$message) {
                if (BaseModelHelper::mysqliUpdate($db->db, 'Templates', 'template_id', $template_id, $data)) {
                    // Log audit trail
                    BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                        'member_id' => $member_id,
                        'action_type' => 'UPDATE_TEMPLATE',
                        'entity_type' => 'Templates',
                        'entity_id' => $template_id,
                        'details' => "Updated template: {$template_name}" . (isset($data['attachment_path']) ? " (attachment updated)" : ""),
                        'performed_on' => date('Y-m-d H:i:s')
                    ]);
                    
                    $message = "Template updated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error updating template: " . $db->db->error;
                }
            }
        }
    }
}

header("Location: ../templates.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>

