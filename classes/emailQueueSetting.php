<?php
require_once "BaseModel.php";

/**
 * Email queue limits for a member.
 *
 * NOTE: We bypass BaseModel's PDO-style helpers and use mysqli directly,
 * because the rest of the system uses the mysqli connection from dataBase.
 */
class EmailQueueSetting extends BaseModel {
    protected $table = "Email_Queue_Settings";
    protected $primaryKey = "queue_id";

    /**
     * Create queue limits for a member (INSERT).
     */
    public function setLimits($member_id, $max_per_batch, $max_per_hour) {
        $member_id = (int)$member_id;
        $max_per_batch = (int)$max_per_batch;
        $max_per_hour = (int)$max_per_hour;

        $sql = "INSERT INTO {$this->table} (member_id, max_per_batch, max_per_hour) 
                VALUES ({$member_id}, {$max_per_batch}, {$max_per_hour})";
        if ($this->db->query($sql)) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Fetch limits for a member.
     */
    public function getLimits($member_id) {
        $member_id = (int)$member_id;
        $sql = "SELECT * FROM {$this->table} WHERE member_id = {$member_id} LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    /**
     * Update or create queue limits for a member.
     */
    public function updateLimits($member_id, $max_per_batch, $max_per_hour) {
        $member_id = (int)$member_id;
        $max_per_batch = (int)$max_per_batch;
        $max_per_hour = (int)$max_per_hour;

        $existing = $this->getLimits($member_id);
        if ($existing) {
            // Update existing
            $sql = "UPDATE {$this->table} 
                    SET max_per_batch = {$max_per_batch}, max_per_hour = {$max_per_hour} 
                    WHERE member_id = {$member_id}";
            return $this->db->query($sql);
        } else {
            // Create new
            return $this->setLimits($member_id, $max_per_batch, $max_per_hour);
        }
    }
}
?>
