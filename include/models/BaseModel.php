<?php
/**
 * Lớp Model chung cho tất cả các đối tượng
 */
class BaseModel {
    // Tên bảng
    public $table;
    
    // Khóa chính
    public $primaryKey = 'id';
    
    // Kết nối CSDL
    protected $conn;
    
    /**
     * Khởi tạo model
     */
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Lấy tất cả bản ghi
     * @param array $conditions Điều kiện WHERE (optional)
     * @param string $order Cột sắp xếp (optional)
     * @param string $direction Hướng sắp xếp (optional)
     * @param int $limit Giới hạn số bản ghi (optional)
     * @param int $offset Vị trí bắt đầu (optional)
     * @return array Mảng dữ liệu
     */
    public function all($conditions = [], $order = null, $direction = 'ASC', $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Thêm điều kiện WHERE
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $column => $value) {
                $where[] = "$column = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        // Thêm ORDER BY
        if ($order) {
            $sql .= " ORDER BY $order $direction";
        }
        
        // Thêm LIMIT và OFFSET
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
            
            if ($offset) {
                $sql .= " OFFSET ?";
                $params[] = (int)$offset;
            }
        }
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Tìm bản ghi theo ID
     * @param mixed $id Giá trị khóa chính
     * @return array|null Dữ liệu bản ghi hoặc null nếu không tìm thấy
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return fetchRow($sql, [$id]);
    }
    
    /**
     * Lấy một bản ghi theo điều kiện
     * @param array $conditions Điều kiện WHERE
     * @return array|null Dữ liệu bản ghi hoặc null nếu không tìm thấy
     */
    public function findBy($conditions) {
        $where = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $where[] = "$column = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        return fetchRow($sql, $params);
    }
    
    /**
     * Đếm số bản ghi
     * @param array $conditions Điều kiện WHERE (optional)
     * @return int Số bản ghi
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        // Thêm điều kiện WHERE
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $column => $value) {
                $where[] = "$column = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $result = fetchRow($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Tạo bản ghi mới
     * @param array $data Dữ liệu cần thêm
     * @return int|bool ID của bản ghi mới hoặc false nếu lỗi
     */
    public function create($data) {
        return insert($this->table, $data);
    }
    
    /**
     * Cập nhật bản ghi
     * @param mixed $id Giá trị khóa chính
     * @param array $data Dữ liệu cần cập nhật
     * @return bool Kết quả cập nhật
     */
    public function update($id, $data) {
        return update($this->table, $data, "{$this->primaryKey} = ?", [$id]);
    }
    
    /**
     * Xóa bản ghi
     * @param mixed $id Giá trị khóa chính
     * @return bool Kết quả xóa
     */
    public function delete($id) {
        return delete($this->table, "{$this->primaryKey} = ?", [$id]);
    }
    
    /**
     * Thực thi truy vấn tùy chỉnh
     * @param string $sql Câu lệnh SQL
     * @param array $params Tham số
     * @param bool $single Trả về một dòng hay tất cả
     * @return mixed Kết quả truy vấn
     */
    public function query($sql, $params = [], $single = false) {
        if ($single) {
            return fetchRow($sql, $params);
        }
        return fetchAll($sql, $params);
    }
}
?>
