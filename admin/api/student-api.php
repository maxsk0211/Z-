<?php
// student-api.php - /api/student-api.php111
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

// ตรวจสอบว่าสามารถสร้างตาราง student ได้หรือไม่ (ถ้ายังไม่มี)
function ensureStudentTableExists($conn) {
    try {
        $checkTable = $conn->query("SHOW TABLES LIKE 'student'");
        
        if ($checkTable->rowCount() === 0) {
            $conn->exec("CREATE TABLE `student` (
                `student_id` INT NOT NULL AUTO_INCREMENT,
                `student_code` VARCHAR(20) NOT NULL,
                `username` VARCHAR(50) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `firstname` VARCHAR(100) NOT NULL,
                `lastname` VARCHAR(100) NOT NULL,
                `email` VARCHAR(100) NULL,
                `phone` VARCHAR(20) NULL,
                `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=ไม่ใช้งาน, 1=ใช้งาน',
                `semester_id` INT NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`student_id`),
                UNIQUE INDEX `student_code_UNIQUE` (`student_code` ASC),
                UNIQUE INDEX `username_UNIQUE` (`username` ASC),
                INDEX `fk_student_semester_idx` (`semester_id` ASC),
                CONSTRAINT `fk_student_semester`
                    FOREIGN KEY (`semester_id`)
                    REFERENCES `semester` (`semester_id`)
                    ON DELETE NO ACTION
                    ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            return true;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error creating student table: " . $e->getMessage());
        return false;
    }
}

// รับค่า action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// ตรวจสอบค่า action และประมวลผลตามค่าที่รับมา
switch ($action) {
    // ดึงข้อมูลนักเรียนทั้งหมดตามเทอม (สำหรับ DataTables)
    case 'list':
        try {
            // ตรวจสอบ semester_id
            $semesterId = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;
            
            if ($semesterId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุรหัสเทอม',
                    'data' => []
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีตาราง student หรือไม่
            if (!ensureStudentTableExists($conn)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถสร้างตารางนักเรียนได้',
                    'data' => []
                ]);
                exit;
            }
            
            // ตัวแปรสำหรับ DataTables
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            
            // สร้าง SQL สำหรับการนับจำนวนทั้งหมด
            $countSql = "SELECT COUNT(*) AS total FROM student WHERE semester_id = ?";
            
            // สร้าง SQL สำหรับการค้นหา
            $searchSql = "";
            $searchParams = [$semesterId];
            
            if (!empty($search)) {
                $searchSql = " AND (student_code LIKE ? OR username LIKE ? OR firstname LIKE ? OR lastname LIKE ?)";
                $searchValue = "%{$search}%";
                $searchParams = array_merge($searchParams, [$searchValue, $searchValue, $searchValue, $searchValue]);
                
                $countSql .= $searchSql;
            }
            
            // นับจำนวนทั้งหมด
            $stmtCount = $conn->prepare($countSql);
            $stmtCount->execute($searchParams);
            $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            $filteredRecords = $totalRecords;
            
            // สร้าง SQL สำหรับการดึงข้อมูล
            $sql = "SELECT student_id, student_code, username, firstname, lastname, email, phone, status, created_at, updated_at FROM student WHERE semester_id = ?" . $searchSql;
            
            // เพิ่มการเรียงลำดับและการแบ่งหน้า
            $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
            $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
            
            // แปลงลำดับคอลัมน์เป็นชื่อคอลัมน์
            $columns = ['student_id', 'student_code', 'username', 'firstname', 'status', 'created_at'];
            $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'created_at';
            
            $sql .= " ORDER BY " . $orderColumnName . " " . $orderDir;
            $sql .= " LIMIT " . $start . ", " . $length;
            
            // ดึงข้อมูล
            $stmt = $conn->prepare($sql);
            $stmt->execute($searchParams);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // รูปแบบการตอบกลับสำหรับ DataTables
            echo json_encode([
                'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $students
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
                'data' => []
            ]);
        }
        break;
        
    // ดึงข้อมูลนักเรียนรายบุคคล
    case 'get':
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสนักเรียน'
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT student_id, student_code, username, firstname, lastname, email, phone, status FROM student WHERE student_id = ?");
            $stmt->execute([$id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                echo json_encode([
                    'success' => true,
                    'data' => $student
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // สร้างนักเรียนใหม่
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
            // ตรวจสอบว่ามีตาราง student หรือไม่
            if (!ensureStudentTableExists($conn)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถสร้างตารางนักเรียนได้'
                ]);
                exit;
            }
            
            // รับค่าจากฟอร์ม
            $student_code = trim($_POST['student_code'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            $semester_id = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
            
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($student_code) || empty($username) || empty($password) || empty($firstname) || empty($lastname) || $semester_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
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
            
            // ตรวจสอบว่าเทอมมีอยู่จริงหรือไม่
            $stmt = $conn->prepare("SELECT semester_id FROM semester WHERE semester_id = ?");
            $stmt->execute([$semester_id]);
            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลเทอมที่ระบุ'
                ]);
                exit;
            }
            
            // ตรวจสอบ student_code ซ้ำ
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM student WHERE student_code = ?");
            $stmt->execute([$student_code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'รหัสนักเรียนนี้มีในระบบแล้ว'
                ]);
                exit;
            }
            
            // ตรวจสอบ username ซ้ำ
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM student WHERE username = ?");
            $stmt->execute([$username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว'
                ]);
                exit;
            }
            
            // เข้ารหัสรหัสผ่าน
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // เพิ่มนักเรียนใหม่
            $stmt = $conn->prepare("INSERT INTO student (student_code, username, password, firstname, lastname, email, phone, status, semester_id, created_at, updated_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([
                $student_code, $username, $hashedPassword, $firstname, $lastname, $email, $phone, $status, $semester_id, $currentDateTime, $currentDateTime
            ]);
            
            $newStudentId = $conn->lastInsertId();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'create_student', "เพิ่มนักเรียนใหม่: {$firstname} {$lastname} (รหัส: {$student_code})", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มนักเรียนใหม่เรียบร้อยแล้ว',
                'student_id' => $newStudentId
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มนักเรียน: ' . $e->getMessage()
            ]);
        }
        break;
        
    // อัปเดตข้อมูลนักเรียน
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
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $student_code = trim($_POST['student_code'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            $change_password = isset($_POST['change_password']) && $_POST['change_password'] === '1';
            
            // ตรวจสอบข้อมูลที่จำเป็น
            if ($student_id <= 0 || empty($student_code) || empty($username) || empty($firstname) || empty($lastname)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
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
            
            // ตรวจสอบว่ามีนักเรียนคนนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT student_code, username FROM student WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ]);
                exit;
            }
            
            // ตรวจสอบ student_code ซ้ำ (เฉพาะกรณีที่เปลี่ยน)
            if ($student_code !== $student['student_code']) {
                $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM student WHERE student_code = ? AND student_id != ?");
                $stmt->execute([$student_code, $student_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'รหัสนักเรียนนี้มีในระบบแล้ว'
                    ]);
                    exit;
                }
            }
            
            // ตรวจสอบ username ซ้ำ (เฉพาะกรณีที่เปลี่ยน)
            if ($username !== $student['username']) {
                $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM student WHERE username = ? AND student_id != ?");
                $stmt->execute([$username, $student_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว'
                    ]);
                    exit;
                }
            }
            
            // เตรียม SQL สำหรับอัปเดต
            $currentDateTime = getCurrentDateTime();
            
            if ($change_password) {
                $password = $_POST['password'] ?? '';
                
                if (empty($password)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'กรุณากรอกรหัสผ่านใหม่'
                    ]);
                    exit;
                }
                
                // เข้ารหัสรหัสผ่านใหม่
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // อัปเดตข้อมูลพร้อมรหัสผ่าน
                $stmt = $conn->prepare("UPDATE student SET 
                                      student_code = ?, 
                                      username = ?, 
                                      password = ?,
                                      firstname = ?, 
                                      lastname = ?, 
                                      email = ?, 
                                      phone = ?, 
                                      status = ?, 
                                      updated_at = ? 
                                      WHERE student_id = ?");
                $stmt->execute([
                    $student_code, $username, $hashedPassword, $firstname, $lastname, $email, $phone, $status, $currentDateTime, $student_id
                ]);
                
                // บันทึกประวัติการทำงาน
                logActivity($conn, 'update_student_with_password', "อัปเดตข้อมูลนักเรียนพร้อมรหัสผ่าน: {$firstname} {$lastname} (รหัส: {$student_code})", $_SESSION['user_id']);
            } else {
                // อัปเดตข้อมูลโดยไม่เปลี่ยนรหัสผ่าน
                $stmt = $conn->prepare("UPDATE student SET 
                                      student_code = ?, 
                                      username = ?, 
                                      firstname = ?, 
                                      lastname = ?, 
                                      email = ?, 
                                      phone = ?, 
                                      status = ?, 
                                      updated_at = ? 
                                      WHERE student_id = ?");
                $stmt->execute([
                    $student_code, $username, $firstname, $lastname, $email, $phone, $status, $currentDateTime, $student_id
                ]);
                
                // บันทึกประวัติการทำงาน
                logActivity($conn, 'update_student', "อัปเดตข้อมูลนักเรียน: {$firstname} {$lastname} (รหัส: {$student_code})", $_SESSION['user_id']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลนักเรียนเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // เปลี่ยนสถานะนักเรียน
    case 'toggle-status':
        // ตรวจสอบ CSRF token
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token'
            ]);
            exit;
        }
        
        try {
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            if ($student_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสนักเรียน'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีนักเรียนคนนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT student_code, firstname, lastname FROM student WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ]);
                exit;
            }
            
            // อัปเดตสถานะนักเรียน
            $stmt = $conn->prepare("UPDATE student SET status = ?, updated_at = ? WHERE student_id = ?");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$status, $currentDateTime, $student_id]);
            
            // บันทึกประวัติการทำงาน
            $statusText = $status == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
            logActivity(
                $conn, 
                'toggle_student_status', 
                "เปลี่ยนสถานะนักเรียน: {$student['firstname']} {$student['lastname']} (รหัส: {$student['student_code']}) เป็น {$statusText}", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => "เปลี่ยนสถานะนักเรียนเป็น \"$statusText\" เรียบร้อยแล้ว"
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ลบนักเรียน
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
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $adminId = $_SESSION['user_id'];
            
            // ตรวจสอบรหัสผ่าน
            if (empty($password)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกรหัสผ่านเพื่อยืนยัน'
                ]);
                exit;
            }
            
            // ดึงรหัสผ่านของผู้ดูแลระบบจากฐานข้อมูล
            $stmt = $conn->prepare("SELECT password FROM admin WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ดูแลระบบ'
                ]);
                exit;
            }
            
            // ตรวจสอบความถูกต้องของรหัสผ่าน
            if (!password_verify($password, $admin['password'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'รหัสผ่านไม่ถูกต้อง'
                ]);
                exit;
            }
            
            if ($student_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสนักเรียน'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีนักเรียนคนนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT student_code, firstname, lastname FROM student WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ]);
                exit;
            }
            
            // บันทึกประวัติการทำงานก่อนลบ
            logActivity(
                $conn, 
                'delete_student', 
                "ลบนักเรียน: {$student['firstname']} {$student['lastname']} (รหัส: {$student['student_code']})", 
                $_SESSION['user_id']
            );
            
            // ลบนักเรียน
            $stmt = $conn->prepare("DELETE FROM student WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบนักเรียนเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // นำเข้าข้อมูลนักเรียนจากไฟล์ CSV
    case 'import-csv':
        // ตรวจสอบ CSRF token
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token'
            ]);
            exit;
        }
        
        try {
            // ตรวจสอบว่ามีไฟล์ถูกอัปโหลดหรือไม่
            if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาอัปโหลดไฟล์ CSV'
                ]);
                exit;
            }
            
            // ตรวจสอบว่าเป็นไฟล์ CSV หรือไม่
            $file_info = pathinfo($_FILES['csvFile']['name']);
            if (strtolower($file_info['extension']) !== 'csv') {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาอัปโหลดไฟล์ CSV เท่านั้น'
                ]);
                exit;
            }
            
            // ตรวจสอบค่า semester_id
            $semester_id = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
            if ($semester_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุรหัสเทอม'
                ]);
                exit;
            }
            
            // ตรวจสอบว่าเทอมมีอยู่จริงหรือไม่
            $stmt = $conn->prepare("SELECT semester_id FROM semester WHERE semester_id = ?");
            $stmt->execute([$semester_id]);
            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลเทอมที่ระบุ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีตาราง student หรือไม่
            if (!ensureStudentTableExists($conn)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถสร้างตารางนักเรียนได้'
                ]);
                exit;
            }
            
            // อ่านไฟล์ CSV
            $file = $_FILES['csvFile']['tmp_name'];
            $handle = fopen($file, 'r');
            
            if ($handle === false) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถอ่านไฟล์ CSV ได้'
                ]);
                exit;
            }
            
            // อ่านบรรทัดแรก (header)
            $headers = fgetcsv($handle);
            
            // ตรวจสอบ headers ที่จำเป็น
            $requiredHeaders = ['student_code', 'username', 'password', 'firstname', 'lastname'];
            $missingHeaders = [];
            
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    $missingHeaders[] = $requiredHeader;
                }
            }
            
            if (!empty($missingHeaders)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไฟล์ CSV ไม่มีคอลัมน์ที่จำเป็น: ' . implode(', ', $missingHeaders)
                ]);
                exit;
            }
            
            // เตรียมตัวแปรสำหรับการนำเข้า
            $imported = 0;
            $errors = 0;
            $duplicates = 0;
            $currentDateTime = getCurrentDateTime();
            
            // ดึงรายการ student_code และ username ที่มีอยู่แล้ว
            $stmt = $conn->prepare("SELECT student_code, username FROM student");
            $stmt->execute();
            $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $existingStudentCodes = [];
            $existingUsernames = [];
            
            foreach ($existingData as $existing) {
                $existingStudentCodes[] = strtolower($existing['student_code']);
                $existingUsernames[] = strtolower($existing['username']);
            }
            
            // เริ่มการนำเข้าข้อมูล
            $conn->beginTransaction();
            
            // เตรียม SQL สำหรับการเพิ่มข้อมูล
            $insert_sql = "INSERT INTO student (student_code, username, password, firstname, lastname, email, phone, status, semester_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            // อ่านข้อมูลจาก CSV และนำเข้า
            while (($data = fgetcsv($handle)) !== false) {
                // แปลงข้อมูลเป็น associative array
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = isset($data[$index]) ? trim($data[$index]) : '';
                }
                
                // ตรวจสอบข้อมูลที่จำเป็น
                $isValid = true;
                foreach ($requiredHeaders as $requiredHeader) {
                    if (empty($rowData[$requiredHeader])) {
                        $isValid = false;
                        break;
                    }
                }
                
                if (!$isValid) {
                    $errors++;
                    continue;
                }
                
                // ตรวจสอบข้อมูลซ้ำ
                if (in_array(strtolower($rowData['student_code']), $existingStudentCodes) || 
                    in_array(strtolower($rowData['username']), $existingUsernames)) {
                    $duplicates++;
                    continue;
                }
                
                // เข้ารหัสรหัสผ่าน
                $hashedPassword = password_hash($rowData['password'], PASSWORD_DEFAULT);
                
                // กำหนดค่าสถานะ (ถ้าไม่มีให้เป็น 1)
                $status = isset($rowData['status']) ? intval($rowData['status']) : 1;
                
                // เพิ่มข้อมูลลงฐานข้อมูล
                try {
                    $insert_stmt->execute([
                        $rowData['student_code'],
                        $rowData['username'],
                        $hashedPassword,
                        $rowData['firstname'],
                        $rowData['lastname'],
                        $rowData['email'] ?? null,
                        $rowData['phone'] ?? null,
                        $status,
                        $semester_id,
                        $currentDateTime,
                        $currentDateTime
                    ]);
                    
                    // เพิ่มรายการที่นำเข้าแล้วลงในรายการที่มีอยู่
                    $existingStudentCodes[] = strtolower($rowData['student_code']);
                    $existingUsernames[] = strtolower($rowData['username']);
                    
                    $imported++;
                } catch (PDOException $e) {
                    $errors++;
                }
            }
            
            // ปิดไฟล์ CSV
            fclose($handle);
            
            // ยืนยันการนำเข้า
            $conn->commit();
            
            // บันทึกประวัติการทำงาน
            logActivity(
                $conn, 
                'import_students_csv', 
                "นำเข้านักเรียนจากไฟล์ CSV: สำเร็จ {$imported} รายการ, ข้อมูลซ้ำ {$duplicates} รายการ, ผิดพลาด {$errors} รายการ", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว',
                'imported' => $imported,
                'duplicates' => $duplicates,
                'errors' => $errors
            ]);
        } catch (PDOException $e) {
            // ยกเลิกการนำเข้าในกรณีที่มีข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ดาวน์โหลดไฟล์ตัวอย่าง CSV
    case 'download-template':
        try {
            // กำหนด header สำหรับดาวน์โหลดไฟล์ CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="student_template.csv"');
            
            // สร้างไฟล์ CSV
            $output = fopen('php://output', 'w');
            
            // เพิ่ม BOM (Byte Order Mark) สำหรับรองรับภาษาไทยใน Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // เพิ่ม header ของไฟล์ CSV
            fputcsv($output, ['student_code', 'username', 'password', 'firstname', 'lastname', 'email', 'phone', 'status']);
            
            // เพิ่มข้อมูลตัวอย่าง
            fputcsv($output, ['6201234567', 'student1', 'password123', 'สมชาย', 'ใจดี', 'somchai@example.com', '0891234567', '1']);
            fputcsv($output, ['6201234568', 'student2', 'password123', 'สมหญิง', 'รักเรียน', 'somying@example.com', '0891234568', '1']);
            
            // ปิดไฟล์
            fclose($output);
            exit;
        } catch (Exception $e) {
            // กลับไปแสดงหน้าเว็บในกรณีที่มีข้อผิดพลาด
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างไฟล์ตัวอย่าง: ' . $e->getMessage()
            ]);
        }
        break;
        
        // เพิ่มฟังก์ชันส่งออกข้อมูลนักเรียนเป็น CSV
        case 'export-csv':
            try {
                // ตรวจสอบ semester_id
                $semesterId = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;
                
                if ($semesterId <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'กรุณาระบุรหัสเทอม'
                    ]);
                    exit;
                }
                
                // ดึงข้อมูลเทอม
                $stmt = $conn->prepare("SELECT year, term FROM semester WHERE semester_id = ?");
                $stmt->execute([$semesterId]);
                $semester = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$semester) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ไม่พบข้อมูลเทอม'
                    ]);
                    exit;
                }
                
                // แปลงเทอมเป็นข้อความ
                $termText = '';
                if ($semester['term'] == 1) {
                    $termText = 'เทอม1';
                } else if ($semester['term'] == 2) {
                    $termText = 'เทอม2';
                } else if ($semester['term'] == 3) {
                    $termText = 'ฤดูร้อน';
                }
                
                // ชื่อไฟล์ส่งออก
                $filename = "นักเรียน_ปี{$semester['year']}_{$termText}_" . date('Y-m-d') . ".csv";
                
                // กำหนด header สำหรับดาวน์โหลดไฟล์ CSV
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                // สร้างไฟล์ CSV
                $output = fopen('php://output', 'w');
                
                // เพิ่ม BOM (Byte Order Mark) สำหรับรองรับภาษาไทยใน Excel
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // เพิ่ม header ของไฟล์ CSV
                fputcsv($output, ['รหัสนักศึกษา', 'ชื่อผู้ใช้', 'ชื่อจริง', 'นามสกุล', 'อีเมล', 'เบอร์โทรศัพท์', 'สถานะ']);
                
                // ดึงข้อมูลนักเรียนในเทอมนั้น
                $stmt = $conn->prepare("SELECT student_code, username, firstname, lastname, email, phone, status FROM student WHERE semester_id = ? ORDER BY student_code ASC");
                $stmt->execute([$semesterId]);
                
                // บันทึกประวัติการทำงาน
                logActivity(
                    $conn, 
                    'export_students_csv', 
                    "ส่งออกข้อมูลนักเรียนเป็น CSV: ปีการศึกษา {$semester['year']} {$termText}", 
                    $_SESSION['user_id']
                );
                
                // เพิ่มข้อมูลนักเรียนในไฟล์ CSV
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // แปลงสถานะเป็นข้อความ
                    $statusText = ($row['status'] == 1) ? 'ใช้งาน' : 'ไม่ใช้งาน';
                    
                    // เขียนข้อมูลลงไฟล์ CSV
                    fputcsv($output, [
                        $row['student_code'],
                        $row['username'],
                        $row['firstname'],
                        $row['lastname'],
                        $row['email'] ?? '',
                        $row['phone'] ?? '',
                        $statusText
                    ]);
                }
                
                // ปิดไฟล์
                fclose($output);
                exit;
            } catch (PDOException $e) {
                // กลับไปแสดงหน้าเว็บในกรณีที่มีข้อผิดพลาด
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในการส่งออกข้อมูล: ' . $e->getMessage()
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