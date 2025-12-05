<?php
session_start();
require_once '../classes/campaign.php';
require_once '../classes/campaignGroup.php';
require_once '../classes/groupMember.php';
require_once '../classes/contact.php';
require_once '../classes/template.php';
require_once '../classes/emailLog.php';
require_once '../classes/auditTrail.php';
require_once '../classes/smtpSetting.php';
require_once '../classes/emailQueueSetting.php';
require_once '../classes/BaseModelHelper.php';
require_once '../config/Database.php';
 

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

// Initialize models
$campaignModel = new Campaign();
$campaignModel->db = $db->db;

// Initialize campaign-group linking model
$campaignGroupModel = new CampaignGroup();
$campaignGroupModel->db = $db->db;

$groupMemberModel = new GroupMember();
$groupMemberModel->db = $db->db;

$contactModel = new Contact();
$contactModel->db = $db->db;

$templateModel = new Template();
$templateModel->db = $db->db;

$emailLog = new EmailLog();
$emailLog->db = $db->db;

$auditTrail = new AuditTrail();
$auditTrail->db = $db->db;

$smtpSetting = new SMTPSetting();
$smtpSetting->db = $db->db;

$emailQueueSetting = new EmailQueueSetting();
$emailQueueSetting->db = $db->db;

// Check if PHPMailer is available (Composer or manual include)
$phpmailerAvailable = false;

// 1) Try Composer autoload
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
    $phpmailerAvailable = class_exists('PHPMailer\PHPMailer\PHPMailer');
}

// 2) If still not available, try manual PHPMailer sources under classes/phpmailer/src
if (!$phpmailerAvailable) {
    $phpmailerBase = dirname(__DIR__) . '/classes/phpmailer/src';
    $phpMailerFile = $phpmailerBase . '/PHPMailer.php';
    $smtpFile      = $phpmailerBase . '/SMTP.php';
    $exceptionFile = $phpmailerBase . '/Exception.php';

    if (file_exists($phpMailerFile) && file_exists($smtpFile) && file_exists($exceptionFile)) {
        require_once $exceptionFile;
        require_once $smtpFile;
        require_once $phpMailerFile;
        $phpmailerAvailable = class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int)$_POST['campaign_id'];
    $template_id = (int)$_POST['template_id'];
    $group_ids = $_POST['group_ids'] ?? [];
    $email_subject = trim($_POST['email_subject'] ?? '');

    // Validation
    if (!$campaign_id || !$template_id || empty($group_ids) || !$email_subject) {
        $message = "Please fill all required fields.";
    } else {
        // Verify campaign belongs to member (using mysqli helper)
        $campaign = BaseModelHelper::mysqliFind($db->db, 'Campaigns', 'campaign_id', $campaign_id);
        if (!$campaign || $campaign['member_id'] != $member_id) {
            $message = "Invalid campaign or access denied.";
        } else {
            // Get template (using mysqli helper) - templates are linked via campaigns
            $template = BaseModelHelper::mysqliFind($db->db, 'Templates', 'template_id', $template_id);
            if (!$template) {
                $message = "Invalid template.";
            } else {
                // Verify template belongs to a campaign owned by this member
                $templateCampaign = BaseModelHelper::mysqliFind($db->db, 'Campaigns', 'campaign_id', $template['campaign_id']);
                if (!$templateCampaign || $templateCampaign['member_id'] != $member_id) {
                    $message = "Invalid template or access denied.";
                    $template = null;
                }
            }
            
            if ($template) {
                    // Get SMTP settings
                    $smtpConfig = $smtpSetting->getSMTPByMember($member_id);
                    if (!$smtpConfig) {
                        $message = "SMTP settings not configured. Please configure SMTP settings first.";
                    } else {
                    // Collect all contacts from selected groups
                    $contactsToSend = [];
                    foreach ($group_ids as $group_id) {
                        // Link group to campaign
                        try {
                            $campaignGroupModel->addGroupToCampaign($campaign_id, $group_id);
                        } catch (Exception $e) {
                            // Group might already be linked, continue
                        }

                        // Get contacts in this group (using mysqli helper)
                        $groupMembers = BaseModelHelper::mysqliGetAll($db->db, 'Group_Members', ['group_id' => $group_id]);
                        foreach ($groupMembers as $gm) {
                            $contact = BaseModelHelper::mysqliFind($db->db, 'Contacts', 'contact_id', $gm['contact_id']);
                            if ($contact && $contact['member_id'] == $member_id && $contact['contact_status'] == 'Active') {
                                // Avoid duplicates
                                $key = $contact['contact_id'];
                                if (!isset($contactsToSend[$key])) {
                                    $contactsToSend[$key] = $contact;
                                }
                            }
                        }
                    }

                    if (empty($contactsToSend)) {
                        $message = "No active contacts found in selected groups.";
                    } else {
                        // Check email queue limits
                        $queueLimits = $emailQueueSetting->getLimits($member_id);
                        $maxPerBatch = $queueLimits ? $queueLimits['max_per_batch'] : 100; // Default 100
                        $maxPerHour = $queueLimits ? $queueLimits['max_per_hour'] : 1000; // Default 1000
                        
                        // Check hourly limit - count emails sent in last hour
                        $hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
                        $hourlyCountSql = "SELECT COUNT(*) as count FROM Email_Log 
                                          WHERE campaign_id IN (
                                              SELECT campaign_id FROM Campaigns WHERE member_id = " . (int)$member_id . "
                                          ) AND sent_on >= '$hourAgo' AND delivery_status = 'Sent'";
                        $hourlyResult = $db->db->query($hourlyCountSql);
                        $hourlyCount = 0;
                        if ($hourlyResult && $hourlyResult->num_rows > 0) {
                            $hourlyRow = $hourlyResult->fetch_assoc();
                            $hourlyCount = (int)$hourlyRow['count'];
                        }
                        
                        // Check if hourly limit exceeded
                        if ($hourlyCount >= $maxPerHour) {
                            $message = "Hourly email limit reached ({$maxPerHour} emails/hour). Please try again later.";
                        } else {
                            // Limit contacts to batch size
                            $totalContacts = count($contactsToSend);
                            $contactsToSendArray = array_values($contactsToSend);
                            $batchSize = min($maxPerBatch, $totalContacts, ($maxPerHour - $hourlyCount));
                            
                            if ($batchSize < $totalContacts) {
                                $contactsToSendArray = array_slice($contactsToSendArray, 0, $batchSize);
                                $message = "Sending batch of {$batchSize} emails (limit: {$maxPerBatch} per batch, {$maxPerHour} per hour). Remaining emails will be queued.";
                            }
                            
                            $sentCount = 0;
                            $failedCount = 0;
                            $firstErrorMessage = '';
                            $emailContent = $template['template_content'] ?? '';

                            // Log audit trail for campaign send start (using mysqli helper)
                            BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                                'member_id' => $member_id,
                                'action_type' => 'SEND_CAMPAIGN_START',
                                'entity_type' => 'Campaigns',
                                'entity_id' => $campaign_id,
                                'details' => "Started sending campaign: {$campaign['campaign_name']} to " . count($contactsToSend) . " contacts",
                                'performed_on' => date('Y-m-d H:i:s')
                            ]);

                            // Send emails
                            if ($phpmailerAvailable) {
                                // Resolve and validate "From" address once
                                $fromEmail = $smtpConfig['from_email'] ?? '';
                                if (empty($fromEmail)) {
                                    $fromEmail = $smtpConfig['username'] ?? '';
                                }
                                $fromName = $smtpConfig['from_name'] ?? 'Email Marketing System';

                                // If From email is not a valid email address, abort before sending
                                if (empty($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                                    $message = "Invalid 'From' email address configured in your SMTP settings. "
                                             . "Please go to SMTP Settings and set a valid email address for either "
                                             . "'Default From Email' or ensure your SMTP username is a full email (e.g. user@example.com).";
                                    $messageType = 'error';
                                } else {
                                    foreach ($contactsToSendArray as $contact) {
                                    try {
                                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                                        $mail->isSMTP();
                                        $mail->Host = $smtpConfig['host'];
                                        $mail->Port = (int)$smtpConfig['port'];
                                        $mail->SMTPAuth = !empty($smtpConfig['username']);
                                        $mail->Username = $smtpConfig['username'];
                                        $mail->Password = $smtpConfig['password'];

                                        // Map stored encryption to PHPMailer constants / values
                                        $enc = strtolower($smtpConfig['encryption'] ?? '');
                                        if ($enc === 'tls') {
                                            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                                        } elseif ($enc === 'ssl') {
                                            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                                        } else {
                                            // 'none' or anything else -> no encryption
                                            $mail->SMTPSecure = '';
                                        }

                                        $mail->CharSet = 'UTF-8';

                                        // Use validated From address / name
                                        $mail->setFrom($fromEmail, $fromName);
                                        // Use template subject if available
                                        if (!empty($template['template_subject'])) {
                                            $mail->Subject = $template['template_subject'];
                                        } else {
                                            $mail->Subject = $email_subject;
                                        }
                                        $mail->addAddress($contact['email'], $contact['first_name'] . ' ' . $contact['last_name']);
                                        $mail->Body = $emailContent;
                                        $mail->isHTML(true);

                                        if ($mail->send()) {
                                            $sentCount++;
                                            $status = 'sent';
                                            $logMessage = 'Email sent successfully';
                                        } else {
                                            $failedCount++;
                                            $status = 'failed';
                                            $logMessage = 'Email send failed: ' . $mail->ErrorInfo;
                                        }
                                    } catch (Exception $e) {
                                        $failedCount++;
                                        $status = 'failed';
                                        $logMessage = 'Email send error: ' . $e->getMessage();
                                    }

                                    // Capture the first error so we can show it to the user if everything fails
                                    if ($status === 'failed' && $firstErrorMessage === '') {
                                        $firstErrorMessage = $logMessage;
                                    }

                                    // Log email send (using mysqli helper) - map status to delivery_status
                                    $deliveryStatus = ($status === 'sent') ? 'Sent' : (($status === 'failed') ? 'Failed' : 'Queued');
                                    BaseModelHelper::mysqliCreate($db->db, 'Email_Log', [
                                        'campaign_id' => $campaign_id,
                                        'contact_id' => $contact['contact_id'],
                                        'template_id' => $template_id,
                                        'group_id' => $group_id,
                                        'delivery_status' => $deliveryStatus,
                                        'error_message' => ($status === 'failed') ? $logMessage : null,
                                        'sent_on' => date('Y-m-d H:i:s')
                                    ]);
                                }
                              }
                            } else {
                                // PHPMailer not available: do NOT pretend emails were sent.
                                // Just log as queued and inform the user in the redirect message.
                                foreach ($contactsToSendArray as $contact) {
                                    BaseModelHelper::mysqliCreate($db->db, 'Email_Log', [
                                        'campaign_id' => $campaign_id,
                                        'contact_id' => $contact['contact_id'],
                                        'template_id' => $template_id,
                                        'group_id' => $group_id,
                                        'delivery_status' => 'Queued',
                                        'error_message' => 'PHPMailer not installed (vendor/autoload.php missing). Email queued but not actually sent.',
                                        'sent_on' => date('Y-m-d H:i:s')
                                    ]);
                                }

                                $message = "Emails could not be sent because the PHPMailer library was not found. "
                                         . "Please either install it via Composer in this project root "
                                         . "(`composer require phpmailer/phpmailer` so that vendor/autoload.php exists) "
                                         . "or copy the PHPMailer src files (PHPMailer.php, SMTP.php, Exception.php) "
                                         . "into classes/phpmailer/src/. Then try sending the campaign again.";
                                $messageType = 'error';
                            }

                            // Log audit trail for campaign send completion (using mysqli helper)
                            BaseModelHelper::mysqliCreate($db->db, 'Audit_Trail', [
                                'member_id' => $member_id,
                                'action_type' => 'SEND_CAMPAIGN_COMPLETE',
                                'entity_type' => 'Campaigns',
                                'entity_id' => $campaign_id,
                                'details' => "Completed sending campaign: {$campaign['campaign_name']}. Sent: {$sentCount}, Failed: {$failedCount}" . ($batchSize < $totalContacts ? " (Queued: " . ($totalContacts - $batchSize) . ")" : ""),
                                'performed_on' => date('Y-m-d H:i:s')
                            ]);

                            // Update campaign status to active if it was draft (using mysqli helper)
                            if ($campaign['campaign_status'] == 'Draft') {
                                BaseModelHelper::mysqliUpdate($db->db, 'Campaigns', 'campaign_id', $campaign_id, ['campaign_status' => 'Active']);
                            }

                            // Only show success/error if PHPMailer was available and we attempted real sends
                            if ($phpmailerAvailable) {
                                if ($sentCount > 0) {
                                    // At least some emails sent
                                    $message = "Campaign processed. Emails sent: {$sentCount}" . ($failedCount > 0 ? ", Failed: {$failedCount}" : "") . ($batchSize < $totalContacts ? " (Queued: " . ($totalContacts - $batchSize) . ")" : "");
                                    $messageType = 'success';
                                } else {
                                    // All emails failed
                                    $fallbackError = $firstErrorMessage ?: 'Unknown error. Please check your SMTP credentials and server configuration.';
                                    $message = "Campaign processed but all emails failed to send. Last error: {$fallbackError}";
                                    $messageType = 'error';
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Redirect back with message
header("Location: ../campaign.php?message=" . urlencode($message) . "&type=" . $messageType);
exit;
?>

