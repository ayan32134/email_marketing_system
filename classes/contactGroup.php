<?php
require_once "BaseModel.php";

class ContactGroup extends BaseModel {
    protected $table = "ContactGroups";
    protected $primaryKey = "group_id";

    public function getGroupsByMember($member_id) {
        return $this->getAll(["member_id" => $member_id]);
    }
}
?>
