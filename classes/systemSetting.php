<?php
require_once "BaseModel.php";

class SystemSetting extends BaseModel {
    protected $table = "System_Settings";
    protected $primaryKey = "id";

    public function getSetting($key) {
        $sql = "SELECT value FROM {$this->table} WHERE name = '" . $this->db->real_escape_string($key) . "' LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['value'];
        }
        return null;
    }
    
    public function updateSetting($key, $value) {
        $sql = "UPDATE {$this->table} SET value = '" . $this->db->real_escape_string($value) . "' 
                WHERE name = '" . $this->db->real_escape_string($key) . "'";
        return $this->db->query($sql);
    }
}
?>
