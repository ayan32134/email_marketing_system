<?php
require_once "BaseModel.php";

class EmailLog extends BaseModel {
    protected $table = "Email_Log";
    protected $primaryKey = "email_log_id";

    public function logEmail($data) {
        if (!isset($data["sent_on"])) {
            $data["sent_on"] = date("Y-m-d H:i:s");
        }
        // Map 'status' to 'delivery_status' if needed
        if (isset($data['status']) && !isset($data['delivery_status'])) {
            $data['delivery_status'] = $data['status'];
            unset($data['status']);
        }
        return $this->create($data);
    }
    
    public function getEmailsByCampaign($campaign_id) {
        return $this->getAll(["campaign_id" => $campaign_id]);
    }
    
    public function getFailedEmails($campaign_id = null) {
        $conditions = ["delivery_status" => "Failed"];
        if ($campaign_id) {
            $conditions["campaign_id"] = $campaign_id;
        }
        return $this->getAll($conditions);
    }
}
?>
