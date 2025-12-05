<?php
require_once "BaseModel.php";

class AuditTrail extends BaseModel {
    protected $table = "Audit_Trail";
    protected $primaryKey = "audit_id";

    public function logAction($data) {
        if (!isset($data["performed_on"])) {
            $data["performed_on"] = date("Y-m-d H:i:s");
        }
        // Map old field names to new ones if needed
        if (isset($data['timestamp']) && !isset($data['performed_on'])) {
            $data['performed_on'] = $data['timestamp'];
            unset($data['timestamp']);
        }
        if (isset($data['action_description']) && !isset($data['details'])) {
            $data['details'] = $data['action_description'];
            unset($data['action_description']);
        }
        if (isset($data['related_type']) && !isset($data['entity_type'])) {
            $data['entity_type'] = $data['related_type'];
            unset($data['related_type']);
        }
        if (isset($data['related_id']) && !isset($data['entity_id'])) {
            $data['entity_id'] = $data['related_id'];
            unset($data['related_id']);
        }
        return $this->create($data);
    }
}
?>
