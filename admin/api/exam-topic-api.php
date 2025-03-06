<?php
// exam-topic-api.php - /admin/api/exam-topic-api.php
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
    // ดึงข้อมูลชุดข้อสอบ
    case 'get_exam_set':
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
        
    // ดึงข้อมูลหัวข้อทั้งหมดในชุดข้อสอบ (สำหรับ DataTables)
    case 'list':
        try {
            $examSetId = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
            
            if ($examSetId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสชุดข้อสอบ'
                ]);
                exit;
            }
            
            // ตัวแปรสำหรับ DataTables
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            
            // สร้าง SQL สำหรับการนับจำนวนทั้งหมด
            $countSql = "SELECT COUNT(*) AS total FROM exam_topic WHERE exam_set_id = ?";
            
            // สร้าง SQL สำหรับการค้นหา
            $searchSql = "";
            $searchParams = [$examSetId];
            
            if (!empty($search)) {
                $searchSql = " AND (name LIKE ? OR description LIKE ?)";
                $searchValue = "%{$search}%";
                $searchParams = array_merge($searchParams, [$searchValue, $searchValue]);
                
                $countSql .= $searchSql;
            }
            
            // นับจำนวนทั้งหมด
            $stmtCount = $conn->prepare($countSql);
            $stmtCount->execute($searchParams);
            $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            $filteredRecords = $totalRecords;
            
            // สร้าง SQL สำหรับการดึงข้อมูล
            $sql = "SELECT et.topic_id, et.name, et.description, et.status, et.created_at, et.updated_at,
                   (SELECT COUNT(*) FROM question WHERE topic_id = et.topic_id) as question_count
                  FROM exam_topic et
                  WHERE et.exam_set_id = ?";
            
            if (!empty($searchSql)) {
                $sql .= $searchSql;
            }
            
            // เพิ่มการเรียงลำดับและการแบ่งหน้า
            $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
            $orderDir = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
            
            // แปลงลำดับคอลัมน์เป็นชื่อคอลัมน์
            $columns = ['topic_id', 'name', 'description', 'question_count', 'status', 'created_at'];
            $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'created_at';
            
            // ปรับปรุงการเรียงลำดับสำหรับคอลัมน์พิเศษ
            if ($orderColumnName === 'question_count') {
                $sql .= " ORDER BY question_count " . $orderDir;
            } else {
                $sql .= " ORDER BY et." . $orderColumnName . " " . $orderDir;
            }
            
            $sql .= " LIMIT " . $start . ", " . $length;
            
            // ดึงข้อมูล
            $stmt = $conn->prepare($sql);
            $stmt->execute($searchParams);
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // รูปแบบการตอบกลับสำหรับ DataTables
            echo json_encode([
                'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $topics
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
                'data' => []
            ]);
        }
        break;
        
    // ดึงข้อมูลหัวข้อรายการเดียว
    case 'get':
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสหัวข้อ'
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT * FROM exam_topic WHERE topic_id = ?");
            $stmt->execute([$id]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($topic) {
                echo json_encode([
                    'success' => true,
                    'data' => $topic
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลหัวข้อ'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // สร้างหัวข้อใหม่
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
            $examSetId = intval($_POST['exam_set_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            // ตรวจสอบข้อมูล
            if ($examSetId <= 0 || empty($name)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
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
            
            // เพิ่มหัวข้อใหม่
            $stmt = $conn->prepare("INSERT INTO exam_topic (exam_set_id, name, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$examSetId, $name, $description, $status, $currentDateTime, $currentDateTime]);
            
            $newTopicId = $conn->lastInsertId();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'create_exam_topic', "สร้างหัวข้อใหม่: {$name} (ชุดข้อสอบ: {$examSet['name']})", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มหัวข้อใหม่เรียบร้อยแล้ว',
                'topic_id' => $newTopicId
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มหัวข้อ: ' . $e->getMessage()
            ]);
        }
        break;
        
    // อัปเดตหัวข้อ
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
            $topicId = intval($_POST['topic_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            // ตรวจสอบข้อมูล
            if ($topicId <= 0 || empty($name)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีหัวข้อนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT t.name, s.name as exam_set_name 
                                   FROM exam_topic t 
                                   JOIN exam_set s ON t.exam_set_id = s.exam_set_id 
                                   WHERE t.topic_id = ?");
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลหัวข้อ'
                ]);
                exit;
            }
            
            // อัปเดตข้อมูลหัวข้อ
            $stmt = $conn->prepare("UPDATE exam_topic SET name = ?, description = ?, status = ?, updated_at = ? WHERE topic_id = ?");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$name, $description, $status, $currentDateTime, $topicId]);
            
            // บันทึกประวัติการทำงาน
            logActivity(
                $conn, 
                'update_exam_topic', 
                "อัปเดตหัวข้อ: {$topic['name']} เป็น {$name} (ชุดข้อสอบ: {$topic['exam_set_name']})", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลหัวข้อเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ลบหัวข้อ
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
            $topicId = intval($_POST['topic_id'] ?? 0);
            
            if ($topicId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสหัวข้อ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีหัวข้อนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT t.name, s.name as exam_set_name 
                                   FROM exam_topic t 
                                   JOIN exam_set s ON t.exam_set_id = s.exam_set_id 
                                   WHERE t.topic_id = ?");
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลหัวข้อ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีคำถามในหัวข้อนี้หรือไม่
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM question WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            $questionCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($questionCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถลบหัวข้อนี้ได้ เนื่องจากมีคำถามอยู่ในหัวข้อนี้'
                ]);
                exit;
            }
            
            // ลบหัวข้อ
            $stmt = $conn->prepare("DELETE FROM exam_topic WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            
            // บันทึกประวัติการทำงาน
            logActivity(
                $conn, 
                'delete_exam_topic', 
                "ลบหัวข้อ: {$topic['name']} (ชุดข้อสอบ: {$topic['exam_set_name']})", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบหัวข้อเรียบร้อยแล้ว'
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