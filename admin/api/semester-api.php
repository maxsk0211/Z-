<?php
// semester-api.php - /api/semester-api.php1
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

// ฟังก์ชันตรวจสอบว่าเทอมถูกใช้งานอยู่หรือไม่ (ในตาราง student)
function isSemesterInUse($conn, $semesterId) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM student WHERE semester_id = ?");
        $stmt->execute([$semesterId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Error checking semester usage: " . $e->getMessage());
        return true; // ให้ถือว่าใช้งานอยู่ในกรณีที่มีข้อผิดพลาด (เพื่อความปลอดภัย)
    }
}

// รับค่า action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// ตรวจสอบค่า action และประมวลผลตามค่าที่รับมา
switch ($action) {
    // ดึงข้อมูลเทอมทั้งหมด (สำหรับ DataTables)
    case 'list':
        try {
            // ตัวแปรสำหรับ DataTables
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            
            // สร้าง SQL สำหรับการนับจำนวนทั้งหมด
            $countSql = "SELECT COUNT(*) AS total FROM semester";
            
            // สร้าง SQL สำหรับการค้นหา
            $searchSql = "";
            $searchParams = [];
            
            if (!empty($search)) {
                $searchSql = " WHERE year LIKE ? OR term LIKE ?";
                $searchValue = "%{$search}%";
                $searchParams = [$searchValue, $searchValue];
                
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
            
            // สร้าง SQL สำหรับการดึงข้อมูล
            $sql = "SELECT semester_id, year, term, status, created_at, updated_at FROM semester" . $searchSql;
            
            // เพิ่มการเรียงลำดับและการแบ่งหน้า
            $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 1; // เริ่มต้นเรียงตามปีการศึกษา
            $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
            
            // แปลงลำดับคอลัมน์เป็นชื่อคอลัมน์
            $columns = ['semester_id', 'year', 'term', 'status', 'created_at'];
            $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'year';
            
            $sql .= " ORDER BY " . $orderColumnName . " " . $orderDir;
            $sql .= " LIMIT " . $start . ", " . $length;
            
            // ดึงข้อมูล
            $stmt = $conn->prepare($sql);
            if (!empty($searchParams)) {
                $stmt->execute($searchParams);
            } else {
                $stmt->execute();
            }
            $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // รูปแบบการตอบกลับสำหรับ DataTables
            echo json_encode([
                'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $semesters
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
                'data' => []
            ]);
        }
        break;
        
    // ดึงข้อมูลเทอมรายการเดียว
    case 'get':
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสเทอม'
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT semester_id, year, term, status, created_at, updated_at FROM semester WHERE semester_id = ?");
            $stmt->execute([$id]);
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($semester) {
                echo json_encode([
                    'success' => true,
                    'data' => $semester
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลเทอม'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // สร้างเทอมใหม่
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
            $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
            $term = isset($_POST['term']) ? intval($_POST['term']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            // ตรวจสอบข้อมูล
            if ($year < 2500 || $year > 2599) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ปีการศึกษาไม่ถูกต้อง (2500-2599)'
                ]);
                exit;
            }
            
            if ($term < 1 || $term > 3) {
                echo json_encode([
                    'success' => false,
                    'message' => 'เทอมไม่ถูกต้อง (1-3)'
                ]);
                exit;
            }
            
            // ตรวจสอบข้อมูลซ้ำ
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM semester WHERE year = ? AND term = ?");
            $stmt->execute([$year, $term]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'เทอมนี้มีอยู่ในระบบแล้ว'
                ]);
                exit;
            }
            
            // เพิ่มเทอมใหม่
            $stmt = $conn->prepare("INSERT INTO semester (year, term, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$year, $term, $status, $currentDateTime, $currentDateTime]);
            
            $newSemesterId = $conn->lastInsertId();
            
            // แปลงข้อมูลเทอมเป็นข้อความ
            $termText = '';
            if ($term == 1) {
                $termText = 'เทอม 1';
            } else if ($term == 2) {
                $termText = 'เทอม 2';
            } else if ($term == 3) {
                $termText = 'ภาคฤดูร้อน';
            }
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'create_semester', "สร้างเทอมใหม่: ปีการศึกษา {$year} {$termText}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มเทอมใหม่เรียบร้อยแล้ว',
                'semester_id' => $newSemesterId
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มเทอม: ' . $e->getMessage()
            ]);
        }
        break;
        
    // อัปเดตข้อมูลเทอม
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
            $semesterId = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
            $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
            $term = isset($_POST['term']) ? intval($_POST['term']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            // ตรวจสอบข้อมูล
            if ($semesterId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสเทอม'
                ]);
                exit;
            }
            
            if ($year < 2500 || $year > 2599) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ปีการศึกษาไม่ถูกต้อง (2500-2599)'
                ]);
                exit;
            }
            
            if ($term < 1 || $term > 3) {
                echo json_encode([
                    'success' => false,
                    'message' => 'เทอมไม่ถูกต้อง (1-3)'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีเทอมนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT year, term FROM semester WHERE semester_id = ?");
            $stmt->execute([$semesterId]);
            $existingSemester = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingSemester) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลเทอม'
                ]);
                exit;
            }
            
            // ตรวจสอบข้อมูลซ้ำ (เฉพาะกรณีที่เปลี่ยนปีการศึกษาหรือเทอม)
            if ($existingSemester['year'] != $year || $existingSemester['term'] != $term) {
                $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM semester WHERE year = ? AND term = ? AND semester_id != ?");
                $stmt->execute([$year, $term, $semesterId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'เทอมนี้มีอยู่ในระบบแล้ว'
                    ]);
                    exit;
                }
            }
            
            // อัปเดตข้อมูลเทอม
            $stmt = $conn->prepare("UPDATE semester SET year = ?, term = ?, status = ?, updated_at = ? WHERE semester_id = ?");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$year, $term, $status, $currentDateTime, $semesterId]);
            
            // แปลงข้อมูลเทอมเป็นข้อความ
            $termText = '';
            if ($term == 1) {
                $termText = 'เทอม 1';
            } else if ($term == 2) {
                $termText = 'เทอม 2';
            } else if ($term == 3) {
                $termText = 'ภาคฤดูร้อน';
            }
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'update_semester', "อัปเดตข้อมูลเทอม: ปีการศึกษา {$year} {$termText}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลเทอมเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // เปลี่ยนสถานะเทอม
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
            $semesterId = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            if ($semesterId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสเทอม'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีเทอมนี้อยู่หรือไม่
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
            
            // อัปเดตสถานะเทอม
            $stmt = $conn->prepare("UPDATE semester SET status = ?, updated_at = ? WHERE semester_id = ?");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$status, $currentDateTime, $semesterId]);
            
            // แปลงข้อมูลเทอมเป็นข้อความ
            $termText = '';
            if ($semester['term'] == 1) {
                $termText = 'เทอม 1';
            } else if ($semester['term'] == 2) {
                $termText = 'เทอม 2';
            } else if ($semester['term'] == 3) {
                $termText = 'ภาคฤดูร้อน';
            }
            
            $statusText = $status == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'toggle_semester_status', "เปลี่ยนสถานะเทอม: ปีการศึกษา {$semester['year']} {$termText} เป็น {$statusText}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => "เปลี่ยนสถานะเทอมเป็น \"$statusText\" เรียบร้อยแล้ว"
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ลบเทอม
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
            $semesterId = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
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
            
            if ($semesterId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสเทอม'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีเทอมนี้อยู่หรือไม่
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
            
            // ตรวจสอบว่าเทอมนี้ถูกใช้งานอยู่หรือไม่
            if (isSemesterInUse($conn, $semesterId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถลบเทอมนี้ได้ เนื่องจากมีนักเรียนอ้างอิงถึง'
                ]);
                exit;
            }
            
            // แปลงข้อมูลเทอมเป็นข้อความสำหรับบันทึกประวัติ
            $termText = '';
            if ($semester['term'] == 1) {
                $termText = 'เทอม 1';
            } else if ($semester['term'] == 2) {
                $termText = 'เทอม 2';
            } else if ($semester['term'] == 3) {
                $termText = 'ภาคฤดูร้อน';
            }
            
            // บันทึกประวัติการทำงานก่อนลบ
            logActivity(
                $conn, 
                'delete_semester', 
                "ลบเทอม: ปีการศึกษา {$semester['year']} {$termText}", 
                $_SESSION['user_id']
            );
            
            // ลบเทอม
            $stmt = $conn->prepare("DELETE FROM semester WHERE semester_id = ?");
            $stmt->execute([$semesterId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบเทอมเรียบร้อยแล้ว'
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