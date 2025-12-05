<?php
require_once "BaseModel.php";

class Template extends BaseModel {
    protected $table = "templates";
    protected $primaryKey = "template_id";

    public function getTemplatesByCampaign($campaign_id) {
        return $this->getAll(["campaign_id" => $campaign_id]);
    }

    public function getActiveTemplates($member_id) {
        // Templates are linked to campaigns, so get via campaigns
        $sql = "SELECT t.* FROM {$this->table} t 
                JOIN Campaigns c ON t.campaign_id = c.campaign_id 
                WHERE c.member_id = " . (int)$member_id . " AND t.template_status = 'Active'";
        $result = $this->db->query($sql);
        $templates = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $templates[] = $row;
            }
        }
        return $templates;
    }
    
    public function getTemplatesByMember($member_id) {
        // Get all templates for member's campaigns
        $sql = "SELECT t.* FROM {$this->table} t 
                JOIN Campaigns c ON t.campaign_id = c.campaign_id 
                WHERE c.member_id = " . (int)$member_id;
        $result = $this->db->query($sql);
        $templates = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $templates[] = $row;
            }
        }
        return $templates;
    }
}
?>
