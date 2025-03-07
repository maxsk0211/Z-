<?php
// admin-api.php - /api/admin-api.php1 
header('Content-Type: application/json');

// เริ่มต้น session
session_start();

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึง'
    ]);
    exit;
}

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../../dbcon.php';

// สร้างการเชื่อมต่อกับฐานข้อมูล
try {
    $conn = getDBConnection();
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้: ' . $e->getMessage()
    ]);
    exit;
}

// ฟังก์ชันวันที่และเวลาปัจจุบัน
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

// ฟังก์ชันบันทึกประวัติการทำงาน (Audit Log)
function logActivity($conn, $action, $details, $admin_id) {
    try {
        // ตรวจสอบว่ามีตาราง admin_log หรือไม่
        $checkTable = $conn->query("SHOW TABLES LIKE 'admin_log'");
        
        // ถ้าไม่มีตาราง admin_log ให้สร้างตาราง
        if ($checkTable->rowCount() === 0) {
            $conn->exec("CREATE TABLE `admin_log` (
                `log_id` INT NOT NULL AUTO_INCREMENT,
                `admin_id` INT NOT NULL,
                `action` VARCHAR(50) NOT NULL,
                `details` TEXT NULL,
                `ip_address` VARCHAR(45) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`log_id`),
                INDEX `fk_admin_log_admin_idx` (`admin_id` ASC),
                CONSTRAINT `fk_admin_log_admin`
                    FOREIGN KEY (`admin_id`)
                    REFERENCES `admin` (`admin_id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }
        
        // บันทึกประวัติการทำงาน
        $stmt = $conn->prepare("INSERT INTO admin_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $stmt->execute([$admin_id, $action, $details, $ip]);
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาดลงใน log
        error_log("Error logging activity: " . $e->getMessage());
        // ไม่ต้อง exit เพื่อให้การทำงานอื่นยังทำงานต่อได้
    }
}

// ฟังก์ชันตรวจสอบ CSRF token
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// รับค่า action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// ตรวจสอบค่า action และประมวลผลตามค่าที่รับมา
switch ($action) {
    // ดึงข้อมูลผู้ดูแลระบบทั้งหมด (สำหรับ DataTables)
    case 'list':
        try {
            // ตัวแปรสำหรับ DataTables
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            
            // สร้าง SQL สำหรับการนับจำนวนทั้งหมด
            $countSql = "SELECT COUNT(*) AS total FROM admin";
            
            // สร้าง SQL สำหรับการค้นหา
            $searchSql = "";
            $searchParams = [];
            
            if (!empty($search)) {
                $searchSql = " WHERE username LIKE ? OR name LIKE ? OR email LIKE ?";
                $searchValue = "%{$search}%";
                $searchParams = [$searchValue, $searchValue, $searchValue];
                
                $countSql .= $searchSql;
            }
            
            // นับจำนวนทั้งหมด
            $stmtCount = $conn->prepare($countSql);
            if (!empty($searchParams)) {
                $stmtCount->execute($searchParams);
            } else {
                $stmtCount->execute();
            }
            $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            $filteredRecords = $totalRecords;
            
            // สร้าง SQL สำหรับการดึงข้อมูล - ดึงค่า status จริงจากฐานข้อมูล
            $sql = "SELECT admin_id, username, name, email, created_at, updated_at, 
               status, -- ใช้ค่า status จริงจากฐานข้อมูล
               (CASE WHEN admin_id = ? THEN 1 ELSE 0 END) AS is_current_user
              FROM admin" . $searchSql;
            
            // เพิ่มการเรียงลำดับและการแบ่งหน้า
            $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
            $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
            
            // แปลงลำดับคอลัมน์เป็นชื่อคอลัมน์
            $columns = ['admin_id', 'username', 'name', 'email', 'created_at', 'updated_at'];
            $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'created_at';
            
            $sql .= " ORDER BY " . $orderColumnName . " " . $orderDir;
            $sql .= " LIMIT " . $start . ", " . $length;
            
            // ดึงข้อมูล
            $stmt = $conn->prepare($sql);
            $params = array_merge([$_SESSION['user_id']], $searchParams);
            $stmt->execute($params);
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // รูปแบบการตอบกลับสำหรับ DataTables
            echo json_encode([
                'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $admins
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
                'data' => []
            ]);
        }
        break;
        
    // ดึงข้อมูลผู้ดูแลระบบรายบุคคล
    case 'get':
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสผู้ดูแลระบบ'
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT admin_id, username, name, email, created_at, updated_at, 1 AS status FROM admin WHERE admin_id = ?");
            $stmt->execute([$id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo json_encode([
                    'success' => true,
                    'data' => $admin
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ดูแลระบบ'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // สร้างผู้ดูแลระบบใหม่
    case 'create':
        // ตรวจสอบ CSRF token
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token'
            ]);
            exit;
        }
        
        try {
            // รับค่าจากฟอร์ม
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            
            // ตรวจสอบข้อมูล
            if (empty($username) || empty($password) || empty($name)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
                ]);
                exit;
            }
            
            if ($password !== $confirmPassword) {
                echo json_encode([
                    'success' => false,
                    'message' => 'รหัสผ่านไม่ตรงกัน'
                ]);
                exit;
            }
            
            // ตรวจสอบความยาวของรหัสผ่าน
            if (strlen($password) < 6) {
                echo json_encode([
                    'success' => false,
                    'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร'
                ]);
                exit;
            }
            
            // ตรวจสอบรูปแบบอีเมล
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'รูปแบบอีเมลไม่ถูกต้อง'
                ]);
                exit;
            }
            
            // ตรวจสอบชื่อผู้ใช้ซ้ำ
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ชื่อผู้ใช้นี้มีผู้ใช้งานในระบบแล้ว'
                ]);
                exit;
            }
            
            // เข้ารหัสรหัสผ่าน
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // เพิ่มผู้ดูแลระบบใหม่
            $stmt = $conn->prepare("INSERT INTO admin (username, password, name, email, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$username, $hashedPassword, $name, $email, $currentDateTime, $currentDateTime]);
            
            $newAdminId = $conn->lastInsertId();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'create_admin', "สร้างผู้ดูแลระบบใหม่: {$username}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มผู้ดูแลระบบใหม่เรียบร้อยแล้ว',
                'admin_id' => $newAdminId
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มผู้ดูแลระบบ: ' . $e->getMessage()
            ]);
        }
        break;
        
        // อัปเดตข้อมูลผู้ดูแลระบบ
        case 'update':
            // ตรวจสอบ CSRF token
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid security token'
                ]);
                exit;
            }
            
            try {
                // รับค่าจากฟอร์ม
                $adminId = intval($_POST['admin_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
                $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
                $changePassword = isset($_POST['change_password']) && $_POST['change_password'] === '1';
                
                // ตรวจสอบข้อมูล
                if ($adminId <= 0 || empty($name)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ข้อมูลไม่ถูกต้อง'
                    ]);
                    exit;
                }
                
                // ตรวจสอบรูปแบบอีเมล
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'รูปแบบอีเมลไม่ถูกต้อง'
                    ]);
                    exit;
                }
                
                // ป้องกันการยกเลิกการใช้งานตัวเอง
                if ($adminId === $_SESSION['user_id'] && $status === 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ไม่สามารถยกเลิกการใช้งานบัญชีของตัวเองได้'
                    ]);
                    exit;
                }
                
                // ตรวจสอบว่ามีการเปลี่ยนรหัสผ่านหรือไม่
                if ($changePassword) {
                    $password = $_POST['password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';
                    
                    if (empty($password)) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'กรุณากรอกรหัสผ่าน'
                        ]);
                        exit;
                    }
                    
                    if ($password !== $confirmPassword) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'รหัสผ่านไม่ตรงกัน'
                        ]);
                        exit;
                    }
                    
                    // ตรวจสอบความยาวของรหัสผ่าน
                    if (strlen($password) < 6) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร'
                        ]);
                        exit;
                    }
                }
                
                // ตรวจสอบว่ามีผู้ดูแลระบบรายนี้อยู่หรือไม่
                $stmt = $conn->prepare("SELECT username FROM admin WHERE admin_id = ?");
                $stmt->execute([$adminId]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$admin) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ไม่พบข้อมูลผู้ดูแลระบบ'
                    ]);
                    exit;
                }
                
                // อัปเดตข้อมูลผู้ดูแลระบบ
                $currentDateTime = getCurrentDateTime();
                
                if ($changePassword) {
                    // เข้ารหัสรหัสผ่านใหม่
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // อัปเดตข้อมูลรวมถึงรหัสผ่านและสถานะ
                    $stmt = $conn->prepare("UPDATE admin SET name = ?, email = ?, password = ?, status = ?, updated_at = ? WHERE admin_id = ?");
                    $stmt->execute([$name, $email, $hashedPassword, $status, $currentDateTime, $adminId]);
                    
                    // บันทึกประวัติการทำงาน
                    logActivity(
                        $conn, 
                        'update_admin_with_password', 
                        "อัปเดตข้อมูลผู้ดูแลระบบรวมถึงรหัสผ่านและสถานะ: {$admin['username']}", 
                        $_SESSION['user_id']
                    );
                } else {
                    // อัปเดตข้อมูลไม่รวมรหัสผ่าน แต่รวมสถานะ
                    $stmt = $conn->prepare("UPDATE admin SET name = ?, email = ?, status = ?, updated_at = ? WHERE admin_id = ?");
                    $stmt->execute([$name, $email, $status, $currentDateTime, $adminId]);
                    
                    // บันทึกประวัติการทำงาน
                    logActivity(
                        $conn, 
                        'update_admin', 
                        "อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: {$admin['username']}", 
                        $_SESSION['user_id']
                    );
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'อัปเดตข้อมูลผู้ดูแลระบบเรียบร้อยแล้ว'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
                ]);
            }
            break;
        
    // ลบผู้ดูแลระบบ
    case 'delete':
        // ตรวจสอบ CSRF token
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token'
            ]);
            exit;
        }
        
        try {
            $adminId = intval($_POST['admin_id'] ?? 0);
            
            if ($adminId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสผู้ดูแลระบบ'
                ]);
                exit;
            }
            
            // ป้องกันการลบตัวเอง
            if ($adminId === $_SESSION['user_id']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถลบบัญชีของตัวเองได้'
                ]);
                exit;
            }
            
            // ป้องกันการลบ admin_id = 1 (แอดมินหลักของระบบ)
            if ($adminId === 1) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถลบแอดมินหลักของระบบได้'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีผู้ดูแลระบบรายนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT username FROM admin WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ดูแลระบบ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีผู้ดูแลระบบเหลืออยู่อย่างน้อย 1 คนหลังจากลบแล้ว
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM admin");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] <= 1) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถลบผู้ดูแลระบบทั้งหมดได้'
                ]);
                exit;
            }
            
            // บันทึกประวัติการทำงานก่อนลบ
            logActivity(
                $conn, 
                'delete_admin', 
                "ลบผู้ดูแลระบบ: {$admin['username']} (ID: {$adminId})", 
                $_SESSION['user_id']
            );
            
            // ลบผู้ดูแลระบบ
            $stmt = $conn->prepare("DELETE FROM admin WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบผู้ดูแลระบบเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}