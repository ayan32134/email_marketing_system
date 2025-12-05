<?php
/**
 * Helper methods for BaseModel to work with mysqli
 * Since BaseModel uses PDO syntax but database is mysqli, these helpers bridge the gap
 */
class BaseModelHelper {
    
    /**
     * Execute a SELECT query and return results as associative array
     */
    public static function mysqliFind($db, $table, $primaryKey, $id) {
        $id = (int)$id;
        $sql = "SELECT * FROM {$table} WHERE {$primaryKey} = {$id}";
        $result = $db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    /**
     * Execute a SELECT query with conditions
     * Checks if columns exist before using them in WHERE clause
     */
    public static function mysqliGetAll($db, $table, $conditions = []) {
        $sql = "SELECT * FROM {$table}";
        if (!empty($conditions)) {
            // First, get table columns to verify they exist
            $columns_result = $db->query("SHOW COLUMNS FROM {$table}");
            $existing_columns = [];
            if ($columns_result) {
                while ($col = $columns_result->fetch_assoc()) {
                    $existing_columns[] = $col['Field'];
                }
            }
            
            $whereParts = [];
            foreach ($conditions as $col => $val) {
                // Only add condition if column exists
                if (in_array($col, $existing_columns)) {
                    $escapedVal = $db->real_escape_string($val);
                    $whereParts[] = "{$col} = '{$escapedVal}'";
                }
            }
            
            if (!empty($whereParts)) {
                $sql .= " WHERE " . implode(" AND ", $whereParts);
            }
        }
        $result = $db->query($sql);
        $rows = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
    
    /**
     * Execute an INSERT query
     */
    public static function mysqliCreate($db, $table, $data) {
        // First, get table columns to check which ones exist
        $columns_result = $db->query("SHOW COLUMNS FROM {$table}");
        $existing_columns = [];
        if ($columns_result) {
            while ($col = $columns_result->fetch_assoc()) {
                $existing_columns[] = $col['Field'];
            }
        }
        
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            // Only include columns that exist in the table
            if (!in_array($col, $existing_columns)) {
                continue;
            }
            
            $cols[] = $col;
            // Handle NULL values properly
            if ($val === null || $val === '') {
                $vals[] = "NULL";
            } else {
                $vals[] = "'" . $db->real_escape_string($val) . "'";
            }
        }
        
        if (empty($cols)) {
            return false;
        }
        
        $sql = "INSERT INTO {$table} (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
        if ($db->query($sql)) {
            return $db->insert_id;
        }
        return false;
    }
    
    /**
     * Execute an UPDATE query
     */
    public static function mysqliUpdate($db, $table, $primaryKey, $id, $data) {
        // First, get table columns to check which ones exist
        $columns_result = $db->query("SHOW COLUMNS FROM {$table}");
        $existing_columns = [];
        if ($columns_result) {
            while ($col = $columns_result->fetch_assoc()) {
                $existing_columns[] = $col['Field'];
            }
        }
        
        $sets = [];
        foreach ($data as $col => $val) {
            // Only update columns that exist in the table
            if (!in_array($col, $existing_columns)) {
                continue;
            }
            
            // Handle NULL values properly
            if ($val === null || $val === '') {
                $sets[] = "{$col} = NULL";
            } else {
                $escapedVal = $db->real_escape_string($val);
                $sets[] = "{$col} = '{$escapedVal}'";
            }
        }
        
        if (empty($sets)) {
            return false;
        }
        
        $id = (int)$id;
        $sql = "UPDATE {$table} SET " . implode(", ", $sets) . " WHERE {$primaryKey} = {$id}";
        return $db->query($sql);
    }
    
    /**
     * Execute a DELETE query
     */
    public static function mysqliDelete($db, $table, $primaryKey, $id) {
        $id = (int)$id;
        $sql = "DELETE FROM {$table} WHERE {$primaryKey} = {$id}";
        return $db->query($sql);
    }
}
?>

