<?php
require_once "BaseModel.php";

class Contact extends BaseModel {
    protected $table = "Contacts";
    protected $primaryKey = "contact_id";

    public function getContactsByMember($member_id) {
        return $this->getAll(["member_id" => $member_id]);
    }

    public function getActiveContacts($member_id) {
        $sql = "SELECT * FROM {$this->table} WHERE member_id = " . (int)$member_id . " AND contact_status = 'Active'";
        $result = $this->db->query($sql);
        $contacts = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $contacts[] = $row;
            }
        }
        return $contacts;
    }
}
?>
