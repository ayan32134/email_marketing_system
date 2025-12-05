<?php
require_once "BaseModel.php";

class Campaign extends BaseModel {
    protected $table = "Campaigns";
    protected $primaryKey = "campaign_id";

    public function getCampaignsByMember($member_id) {
        return $this->getAll(["member_id" => $member_id]);
    }

    public function getCampaignById($campaign_id) {
        return $this->find($campaign_id);
    }

    public function updateCampaignStatus($campaign_id, $status) {
        return $this->update($campaign_id, ["campaign_status" => $status]);
    }
}
?>
