<?php
/**
 * Base Model Class
 * Provides common database operations for all models
 */

class BaseModel {
    protected $db;
    protected $table;
    
    public function __construct($database, $table) {
        $this->db = $database;
        $this->table = $table;
    }
    
    /**
     * Get all records from table
     */
    public function getAll($limit = null, $offset = 0) {
        $query = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        
        if ($limit) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get record by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Find records by column
     */
    public function findBy($column, $value) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
        $stmt = $this->db->prepare($query);
        
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->db->insert_id];
        }
        
        return ['success' => false, 'error' => $stmt->error];
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $updateFields = [];
        $types = '';
        $values = [];
        
        foreach ($data as $column => $value) {
            $updateFields[] = "{$column} = ?";
            $types .= 's';
            $values[] = $value;
        }
        
        $types .= 'i';
        $values[] = $id;
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => $stmt->error];
    }
    
    /**
     * Soft delete record
     */
    public function delete($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => $stmt->error];
    }
    
    /**
     * Count records
     */
    public function count() {
        $result = $this->db->query("SELECT COUNT(*) as count FROM {$this->table} WHERE deleted_at IS NULL");
        $data = $result->fetch_assoc();
        return $data['count'];
    }
    
    /**
     * Execute custom query
     */
    public function query($sql, $params = [], $types = '') {
        if (empty($params)) {
            return $this->db->query($sql);
        }
        
        $stmt = $this->db->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
}
