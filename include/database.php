<?php
/**
 * Kết nối cơ sở dữ liệu cải tiến với xử lý lỗi tốt hơn
 */

// Đảm bảo đã load config
require_once 'config.php';

// Khởi tạo biến kết nối toàn cục
$conn = null;

// Hàm kết nối cơ sở dữ liệu
function connectDB() {
    global $conn;
    
    // Nếu đã kết nối, trả về kết nối hiện tại
    if ($conn !== null) {
        return $conn;
    }
    
    // Thực hiện kết nối
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Kiểm tra lỗi kết nối
        if ($conn->connect_error) {
            throw new Exception("Lỗi kết nối cơ sở dữ liệu: " . $conn->connect_error);
        }
        
        // Thiết lập charset
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        // Ghi log lỗi
        error_log($e->getMessage());
        
        // Hiển thị lỗi nếu đang ở chế độ debug
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo '<div class="alert alert-danger">Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau.</div>';
        }
        
        // Kết thúc script nếu không thể kết nối
        die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng liên hệ quản trị viên.");
    }
}

// Thực hiện kết nối
$conn = connectDB();

/**
 * Hàm thực thi truy vấn an toàn (prepared statement)
 * 
 * @param string $sql Câu lệnh SQL
 * @param array $params Mảng tham số (nếu có)
 * @param string $types Kiểu dữ liệu của các tham số (s: string, i: integer, d: double, b: blob)
 * @return mysqli_result|bool Kết quả truy vấn hoặc false nếu lỗi
 */
function executeQuery($sql, $params = [], $types = '') {
    global $conn;
    
    // Chuẩn bị câu lệnh
    $stmt = $conn->prepare($sql);
    
    // Kiểm tra lỗi
    if (!$stmt) {
        error_log("Lỗi chuẩn bị truy vấn: " . $conn->error . " - SQL: " . $sql);
        return false;
    }
    
    // Bind tham số nếu có
    if (!empty($params)) {
        // Tự động tạo chuỗi types nếu không được cung cấp
        if (empty($types)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                elseif (is_string($param)) $types .= 's';
                else $types .= 'b';
            }
        }
        
        // Đảm bảo số lượng kiểu và tham số khớp nhau
        if (strlen($types) !== count($params)) {
            error_log("Số lượng kiểu dữ liệu và tham số không khớp");
            return false;
        }
        
        // Tạo mảng tham chiếu
        $refs = [];
        $refs[] = &$types;
        
        for ($i = 0; $i < count($params); $i++) {
            $refs[] = &$params[$i];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    
    // Thực thi
    $result = $stmt->execute();
    
    // Kiểm tra lỗi thực thi
    if (!$result) {
        error_log("Lỗi thực thi truy vấn: " . $stmt->error . " - SQL: " . $sql);
        $stmt->close();
        return false;
    }
    
    // Lấy kết quả nếu là truy vấn SELECT
    if ($stmt->field_count > 0) {
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    
    // Trả về true nếu là INSERT, UPDATE, DELETE thành công
    $stmt->close();
    return true;
}

/**
 * Hàm thực thi truy vấn và trả về một dòng kết quả
 * 
 * @param string $sql Câu lệnh SQL
 * @param array $params Mảng tham số
 * @param string $types Kiểu dữ liệu của các tham số
 * @return array|null Một dòng kết quả hoặc null nếu không có
 */
function fetchRow($sql, $params = [], $types = '') {
    $result = executeQuery($sql, $params, $types);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Hàm thực thi truy vấn và trả về tất cả các dòng kết quả
 * 
 * @param string $sql Câu lệnh SQL
 * @param array $params Mảng tham số
 * @param string $types Kiểu dữ liệu của các tham số
 * @return array Mảng kết quả
 */
function fetchAll($sql, $params = [], $types = '') {
    $result = executeQuery($sql, $params, $types);
    $rows = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Hàm thêm dữ liệu vào bảng
 * 
 * @param string $table Tên bảng
 * @param array $data Mảng dữ liệu (key: tên cột, value: giá trị)
 * @return int|bool ID của bản ghi mới hoặc false nếu lỗi
 */
function insert($table, $data) {
    global $conn;
    
    $columns = array_keys($data);
    $values = array_values($data);
    $placeholders = array_fill(0, count($values), '?');
    
    $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $result = executeQuery($sql, $values);
    
    if ($result) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Hàm cập nhật dữ liệu trong bảng
 * 
 * @param string $table Tên bảng
 * @param array $data Mảng dữ liệu cần cập nhật (key: tên cột, value: giá trị)
 * @param string $condition Điều kiện WHERE
 * @param array $params Mảng tham số cho điều kiện
 * @return bool Kết quả cập nhật
 */
function update($table, $data, $condition, $params = []) {
    $set = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $set[] = "$column = ?";
        $values[] = $value;
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $condition";
    
    // Kết hợp mảng tham số
    $all_params = array_merge($values, $params);
    
    return executeQuery($sql, $all_params);
}

/**
 * Hàm xóa dữ liệu từ bảng
 * 
 * @param string $table Tên bảng
 * @param string $condition Điều kiện WHERE
 * @param array $params Mảng tham số cho điều kiện
 * @return bool Kết quả xóa
 */
function delete($table, $condition, $params = []) {
    $sql = "DELETE FROM $table WHERE $condition";
    
    return executeQuery($sql, $params);
}

?>
