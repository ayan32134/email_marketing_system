<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
require_once "BaseModel.php";

class Admin extends BaseModel {
    public $table = "Admins";
    protected $primaryKey = "admin_id";

    // ✅ Dynamic SQL builder
public function buildSQL($table, $data = [], $action = 'select', $where = []) {
    if (empty($table)) {
        return false; // table is required
    }

    // only insert or update require $data
    if (($action === 'insert' || $action === 'update') && empty($data)) {
        return false;
    }

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
            if (empty($where)) return false; // DELETE **must have a WHERE clause**
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


    // ✅ Additional Admin methods
    public function getCampaignMetrics() {
        $sql = "SELECT c.member_id, COUNT(e.id) AS total_sent
                FROM campaigns c
                JOIN email_log e ON c.campaign_id = e.campaign_id
                GROUP BY c.member_id";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function auditTrail() {
        $sql = "SELECT * FROM audit_trail ORDER BY timestamp DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
