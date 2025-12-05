<?php
if (file_exists(__DIR__ . '/../config/Database.php')) {
    require_once __DIR__ . '/../config/Database.php';
} else {
    require_once __DIR__ . './config/Database.php';
}
class BaseModel extends dataBase {
    protected $table;
    protected $primaryKey = "id";

    public function create($data) {
        $cols = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    // UPDATE
    public function update($id, $data) {
        $sets = [];
        foreach ($data as $key => $val) {
            $sets[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE {$this->table} SET " . implode(", ", $sets) . " WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(":id", $id);
        return $stmt->execute();
    }

    // DELETE
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindValue(":id", $id);
        return $stmt->execute();
    }

    // GET ONE
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // GET ALL (with optional conditions)
    public function getAll($conditions = []) {
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $col => $val) {
                $clauses[] = "$col = :$col";
            }
            $sql .= " WHERE " . implode(" AND ", $clauses);
        }
        $stmt = $this->db->prepare($sql);
        foreach ($conditions as $col => $val) {
            $stmt->bindValue(":$col", $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
