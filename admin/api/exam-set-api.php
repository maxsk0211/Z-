<?php
// exam-set-api.php - /admin/api/exam-set-api.php
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
    // ดึงข้อมูลชุดข้อสอบทั้งหมด (สำหรับ DataTables)
    case 'list':
        try {
            // ตัวแปรสำหรับ DataTables
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            
            // สร้าง SQL สำหรับการนับจำนวนทั้งหมด
            $countSql = "SELECT COUNT(*) AS total FROM exam_set";
            
            // สร้าง SQL สำหรับการค้นหา
            $searchSql = "";
            $searchParams = [];
            
            if (!empty($search)) {
                $searchSql = " WHERE name LIKE ? OR description LIKE ?";
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
            $sql = "SELECT es.exam_set_id, es.name, es.description, es.status, es.created_at, es.updated_at, 
                   a.name as created_by_name,
                   (SELECT COUNT(*) FROM exam_topic WHERE exam_set_id = es.exam_set_id) as topic_count
                  FROM exam_set es
                  LEFT JOIN admin a ON es.created_by = a.admin_id";
            
            if (!empty($searchSql)) {
                $sql .= $searchSql;
            }
            
            // เพิ่มการเรียงลำดับและการแบ่งหน้า
            $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
            $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
            
            // แปลงลำดับคอลัมน์เป็นชื่อคอลัมน์
            $columns = ['exam_set_id', 'name', 'description', 'topic_count', 'status', 'created_at', 'created_by_name'];
            $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'created_at';
            
            // ปรับปรุงการเรียงลำดับสำหรับคอลัมน์พิเศษ
            if ($orderColumnName === 'topic_count') {
                $sql .= " ORDER BY topic_count " . $orderDir;
            } else if ($orderColumnName === 'created_by_name') {
                $sql .= " ORDER BY a.name " . $orderDir;
            } else {
                $sql .= " ORDER BY es." . $orderColumnName . " " . $orderDir;
            }
            
            $sql .= " LIMIT " . $start . ", " . $length;
            
            // ดึงข้อมูล
            $stmt = $conn->prepare($sql);
            if (!empty($searchParams)) {
                $stmt->execute($searchParams);
            } else {
                $stmt->execute();
            }
            $examSets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // รูปแบบการตอบกลับสำหรับ DataTables
            echo json_encode([
                'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $examSets
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
                'data' => []
            ]);
        }
        break;
        
    // ดึงข้อมูลชุดข้อสอบรายการเดียว
    case 'get':
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสชุดข้อสอบ'
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT * FROM exam_set WHERE exam_set_id = ?");
            $stmt->execute([$id]);
            $examSet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($examSet) {
                echo json_encode([
                    'success' => true,
                    'data' => $examSet
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลชุดข้อสอบ'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // สร้างชุดข้อสอบใหม่
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
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            // ตรวจสอบข้อมูล
            if (empty($name)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกชื่อชุดข้อสอบ'
                ]);
                exit;
            }
            
            // เพิ่มชุดข้อสอบใหม่
            $stmt = $conn->prepare("INSERT INTO exam_set (name, description, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$name, $description, $status, $_SESSION['user_id'], $currentDateTime, $currentDateTime]);
            
            $newExamSetId = $conn->lastInsertId();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'create_exam_set', "สร้างชุดข้อสอบใหม่: {$name}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มชุดข้อสอบใหม่เรียบร้อยแล้ว',
                'exam_set_id' => $newExamSetId
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มชุดข้อสอบ: ' . $e->getMessage()
            ]);
        }
        break;
        
    // อัปเดตชุดข้อสอบ
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
            $examSetId = intval($_POST['exam_set_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            // ตรวจสอบข้อมูล
            if ($examSetId <= 0 || empty($name)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีชุดข้อสอบนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT name FROM exam_set WHERE exam_set_id = ?");
            $stmt->execute([$examSetId]);
            $examSet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$examSet) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลชุดข้อสอบ'
                ]);
                exit;
            }
            
            // อัปเดตข้อมูลชุดข้อสอบ
            $stmt = $conn->prepare("UPDATE exam_set SET name = ?, description = ?, status = ?, updated_at = ? WHERE exam_set_id = ?");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$name, $description, $status, $currentDateTime, $examSetId]);
            
            // บันทึกประวัติการทำงาน
            logActivity(
                $conn, 
                'update_exam_set', 
                "อัปเดตชุดข้อสอบ: {$examSet['name']} เป็น {$name}", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลชุดข้อสอบเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ลบชุดข้อสอบ
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
            $examSetId = intval($_POST['exam_set_id'] ?? 0);
            
            if ($examSetId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสชุดข้อสอบ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีชุดข้อสอบนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT name FROM exam_set WHERE exam_set_id = ?");
            $stmt->execute([$examSetId]);
            $examSet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$examSet) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลชุดข้อสอบ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่าชุดข้อสอบนี้ถูกใช้งานอยู่ในรอบการสอบหรือไม่
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM exam_round_set WHERE exam_set_id = ?");
            $stmt->execute([$examSetId]);
            $usedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($usedCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถลบชุดข้อสอบนี้ได้ เนื่องจากมีการใช้งานในรอบการสอบ'
                ]);
                exit;
            }
            
            // เริ่ม transaction
            $conn->beginTransaction();
            
            // ลบข้อมูลที่เกี่ยวข้องทั้งหมด (choices, questions, topics)
            // 1. ลบตัวเลือกคำถาม
            $stmt = $conn->prepare("
                DELETE c FROM choice c
                INNER JOIN question q ON c.question_id = q.question_id
                INNER JOIN exam_topic et ON q.topic_id = et.topic_id
                WHERE et.exam_set_id = ?
            ");
            $stmt->execute([$examSetId]);
            
            // 2. ลบคำถาม
            $stmt = $conn->prepare("
                DELETE q FROM question q
                INNER JOIN exam_topic et ON q.topic_id = et.topic_id
                WHERE et.exam_set_id = ?
            ");
            $stmt->execute([$examSetId]);
            
            // 3. ลบหัวข้อ
            $stmt = $conn->prepare("DELETE FROM exam_topic WHERE exam_set_id = ?");
            $stmt->execute([$examSetId]);
            
            // 4. ลบชุดข้อสอบ
            $stmt = $conn->prepare("DELETE FROM exam_set WHERE exam_set_id = ?");
            $stmt->execute([$examSetId]);
            
            // บันทึกการเปลี่ยนแปลง
            $conn->commit();
            
            // บันทึกประวัติการทำงาน
            logActivity(
                $conn, 
                'delete_exam_set', 
                "ลบชุดข้อสอบ: {$examSet['name']} (ID: {$examSetId})", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบชุดข้อสอบเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            // ยกเลิกการเปลี่ยนแปลงหากเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
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