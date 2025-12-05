<?php
require_once "BaseModel.php";

class CampaignGroup extends BaseModel {
    protected $table = "Campaign_Groups";
    protected $primaryKey = "id";

    public function addGroupToCampaign($campaign_id, $group_id) {
        // Check if already exists
        $checkSql = "SELECT * FROM {$this->table} WHERE campaign_id = " . (int)$campaign_id . " AND group_id = " . (int)$group_id;
        $result = $this->db->query($checkSql);
        if ($result && $result->num_rows > 0) {
            return true; // Already exists
        }
        
        // Insert using mysqli
        $sql = "INSERT INTO {$this->table} (campaign_id, group_id) VALUES (" . (int)$campaign_id . ", " . (int)$group_id . ")";
        return $this->db->query($sql);
    }

    public function removeGroupFromCampaign($campaign_id, $group_id) {
        $sql = "DELETE FROM {$this->table} WHERE campaign_id = :campaign_id AND group_id = :group_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":campaign_id", $campaign_id);
        $stmt->bindValue(":group_id", $group_id);
        return $stmt->execute();
    }

    public function getGroupsForCampaign($campaign_id) {
        return $this->getAll(["campaign_id" => $campaign_id]);
    }
}
?>
