<?php
require_once "BaseModel.php";

class Member extends BaseModel {
    public $table = "Members";
    protected $primaryKey = "member_id";

    // Reuse buildSQL from Admin
    public function buildSQL($table, $data = [], $action = 'select', $where = []) {
        if (empty($table)) return false;
        if (($action === 'insert' || $action === 'update') && empty($data)) return false;

        switch ($action) {
            case 'insert':
                $cols = implode(', ', array_keys($data));
                $vals = implode("', '", array_map('addslashes', array_values($data)));
                $sql = "INSERT INTO {$table} ({$cols}) VALUES ('{$vals}')";
                break;

            case 'update':
                $setParts = [];
                foreach ($data as $col => $val) {
                    $setParts[] = "{$col} = '" . addslashes($val) . "'";
                }
                $setClause = implode(', ', $setParts);
                $whereClause = '';
                if (!empty($where)) {
                    $whereParts = [];
                    foreach ($where as $col => $val) {
                        $whereParts[] = "{$col} = '" . addslashes($val) . "'";
                    }
                    $whereClause = ' WHERE ' . implode(' AND ', $whereParts);
                }
                $sql = "UPDATE {$table} SET {$setClause}{$whereClause}";
                break;

            case 'delete':
                if (empty($where)) return false;
                $whereParts = [];
                foreach ($where as $col => $val) {
                    $whereParts[] = "{$col} = '" . addslashes($val) . "'";
                }
                $whereClause = ' WHERE ' . implode(' AND ', $whereParts);
                $sql = "DELETE FROM {$table}{$whereClause}";
                break;

            case 'select':
            default:
                $whereClause = '';
                if (!empty($where)) {
                    $whereParts = [];
                    foreach ($where as $col => $val) {
                        $whereParts[] = "{$col} = '" . addslashes($val) . "'";
                    }
                    $whereClause = ' WHERE ' . implode(' AND ', $whereParts);
                }
                $sql = "SELECT * FROM {$table}{$whereClause}";
                break;
        }

        return $sql;
    }

    // Example: fetch all members
    public function getAllMembers() {
        $sql = $this->buildSQL($this->table);
        $stmt = $this->db->query($sql);
        return $stmt->fetch_all(MYSQLI_ASSOC);
    }

    // Example: login verification
    public function verifyLogin($email, $password) {
        $sql = $this->buildSQL($this->table, ['member_email' => $email], 'select');
        $result = $this->db->query($sql);
        if ($result->num_rows === 1) {
            $member = $result->fetch_assoc();
            if (password_verify($password, $member['password_hash'])) {
                return $member;
            }
        }
        return false;
    }
}
?>
