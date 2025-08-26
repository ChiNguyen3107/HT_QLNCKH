<?php
session_start();
require 'include/connect.php';
$conn->set_charset('utf8mb4');

/**
 * Kiểm tra mật khẩu với nhiều định dạng:
 * - bcrypt/argon2: password_verify
 * - sha256 hex (64 ký tự)
 * - md5 hex (32 ký tự)
 * - plain text (fallback, hạn chế dùng)
 */
function verify_password_mixed(string $raw, ?string $stored): bool {
    if ($stored === null) return false;
    $stored = trim((string)$stored);

    // bcrypt / argon2
    if (preg_match('/^(\$(2y|2b|2a)\$|\$argon2(id|i|d)\$)/', $stored)) {
        return password_verify($raw, $stored);
    }
    // sha-256 hex
    if (preg_match('/^[0-9a-f]{64}$/i', $stored)) {
        return hash('sha256', $raw) === strtolower($stored);
    }
    // md5 hex
    if (preg_match('/^[0-9a-f]{32}$/i', $stored)) {
        return md5($raw) === strtolower($stored);
    }
    // plain text (không khuyến nghị)
    return hash_equals($stored, $raw);
}

/** Sau khi đăng nhập đúng, nếu hash cũ không phải bcrypt/argon2 thì nâng cấp lên bcrypt */
function maybe_rehash_bcrypt(mysqli $conn, string $table, string $id_col, string $id_val,
                             string $pass_col, string $raw, string $stored): void {
    if (!preg_match('/^\$2[aby]\$|\$argon2(id|i|d)\$/', $stored)) {
        $new = password_hash($raw, PASSWORD_DEFAULT);
        $sql = "UPDATE {$table} SET {$pass_col}=? WHERE {$id_col}=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $new, $id_val);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // ===== 1) Bảng user =====
    if ($stmt = $conn->prepare("SELECT * FROM user WHERE USERNAME = ?")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($u = $rs->fetch_assoc()) {
            if (verify_password_mixed($password, $u['PASSWORD'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $u['USER_ID'] ?? $u['USERNAME'];
                $_SESSION['username']  = $u['USERNAME'];
                $_SESSION['role']      = $u['ROLE'];
                $_SESSION['user_name'] = $u['NAME'] ?? $u['USERNAME'];

                maybe_rehash_bcrypt($conn, 'user', 'USERNAME', $u['USERNAME'], 'PASSWORD', $password, $u['PASSWORD']);

                switch ($u['ROLE']) {
                    case 'admin':
                        header("Location: /NLNganh/view/admin/admin_dashboard.php"); break;
                    case 'teacher':
                        header("Location: /NLNganh/view/teacher/teacher_dashboard.php"); break;
                    case 'student':
                        header("Location: /NLNganh/view/student/student_dashboard.php"); break;
                    case 'research_manager':
                        $_SESSION['manager_id'] = $u['USER_ID'] ?: $u['USERNAME'];
                        header("Location: /NLNganh/view/research/research_dashboard.php"); break;
                    default:
                        header("Location: /NLNganh/index.php"); break;
                }
                exit;
            }
        }
        $stmt->close();
    }

    // ===== 2) Bảng sinh_vien (đăng nhập bằng mã SV hoặc email) =====
    if ($stmt = $conn->prepare("SELECT * FROM sinh_vien WHERE SV_MASV = ? OR SV_EMAIL = ?")) {
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($sv = $rs->fetch_assoc()) {
            if (verify_password_mixed($password, $sv['SV_MATKHAU'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $sv['SV_MASV'];
                $_SESSION['username']  = $sv['SV_EMAIL'];
                $_SESSION['role']      = 'student';
                $_SESSION['user_name'] = $sv['SV_HOSV'].' '.$sv['SV_TENSV'];

                maybe_rehash_bcrypt($conn, 'sinh_vien', 'SV_MASV', $sv['SV_MASV'], 'SV_MATKHAU', $password, $sv['SV_MATKHAU']);

                header("Location: /NLNganh/view/student/student_dashboard.php");
                exit;
            }
        }
        $stmt->close();
    }

    // ===== 3) Bảng giang_vien (đăng nhập bằng mã GV hoặc email) =====
    if ($stmt = $conn->prepare("SELECT * FROM giang_vien WHERE GV_MAGV = ? OR GV_EMAIL = ?")) {
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($gv = $rs->fetch_assoc()) {
            if (verify_password_mixed($password, $gv['GV_MATKHAU'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $gv['GV_MAGV'];
                $_SESSION['username']  = $gv['GV_EMAIL'];
                $_SESSION['role']      = 'teacher';
                $_SESSION['user_name'] = $gv['GV_HOGV'].' '.$gv['GV_TENGV'];

                maybe_rehash_bcrypt($conn, 'giang_vien', 'GV_MAGV', $gv['GV_MAGV'], 'GV_MATKHAU', $password, $gv['GV_MATKHAU']);

                header("Location: /NLNganh/view/teacher/teacher_dashboard.php");
                exit;
            }
        }
        $stmt->close();
    }

    // ===== Thất bại =====
    header("Location: login.php?error=invalid_credentials");
    exit;
}
