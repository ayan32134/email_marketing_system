<?php
require_once "BaseModel.php";

class SMTPSetting extends BaseModel {
    protected $table = "Member_SMTP_Settings";
    protected $primaryKey = "smtp_id";

    public function getSMTPByMember($member_id) {
        // Use direct mysqli query
        $sql = "SELECT smtp_host, smtp_port, smtp_user, smtp_password, encryption, default_from_email, default_from_name 
                FROM {$this->table} WHERE member_id = " . (int)$member_id . " LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Map to expected format
            return [
                'host' => $row['smtp_host'],
                'port' => $row['smtp_port'],
                'username' => $row['smtp_user'],
                'password' => $row['smtp_password'],
                'encryption' => strtolower($row['encryption']),
                'from_email' => $row['default_from_email'],
                'from_name' => $row['default_from_name']
            ];
        }
        return null;
    }
}
?>
