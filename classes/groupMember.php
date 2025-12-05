<?php
require_once "BaseModel.php";

class GroupMember extends BaseModel {
    protected $table = "Group_Members";
    protected $primaryKey = "id";

    public function addContactToGroup($group_id, $contact_id) {
        return $this->create([
            "group_id" => $group_id,
            "contact_id" => $contact_id
        ]);
    }

    public function removeContactFromGroup($group_id, $contact_id) {
        $sql = "DELETE FROM {$this->table} WHERE group_id = " . (int)$group_id . " AND contact_id = " . (int)$contact_id;
        return $this->db->query($sql);
    }

    public function getContactsInGroup($group_id) {
        return $this->getAll(["group_id" => $group_id]);
    }
}
?>
