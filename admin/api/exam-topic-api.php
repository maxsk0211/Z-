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
        $stmt = $conn->prepare("INSERT INTO admin_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $stmt->execute([$admin_id, $action, $details, $ip]);
    } catch (PDOException $e) {
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
    // ดึงข้อมูลหัวข้อการสอบทั้งหมดตามชุดข้อสอบ
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
            
            // ดึงข้อมูลหัวข้อและจำนวนคำถาม
            $stmt = $conn->prepare("
                SELECT t.topic_id, t.name, t.description, t.status, t.created_at, t.updated_at,
                       COUNT(q.question_id) AS question_count
                FROM exam_topic t
                LEFT JOIN question q ON t.topic_id = q.topic_id
                WHERE t.exam_set_id = ?
                GROUP BY t.topic_id
                ORDER BY t.name ASC
            ");
            $stmt->execute([$examSetId]);
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $topics
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ดึงข้อมูลหัวข้อการสอบรายการเดียว
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
            
            $stmt = $conn->prepare("
                SELECT t.*, e.name as exam_set_name,
                       (SELECT COUNT(*) FROM question q WHERE q.topic_id = t.topic_id) AS question_count
                FROM exam_topic t
                JOIN exam_set e ON t.exam_set_id = e.exam_set_id
                WHERE t.topic_id = ?
            ");
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
                    'message' => 'ไม่พบข้อมูลหัวข้อการสอบ'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // สร้างหัวข้อการสอบใหม่
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
            $examSetId = isset($_POST['exam_set_id']) ? intval($_POST['exam_set_id']) : 0;
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
            
            // ตรวจสอบว่ามีชุดข้อสอบนี้อยู่จริงหรือไม่
            $stmtCheck = $conn->prepare("SELECT exam_set_id FROM exam_set WHERE exam_set_id = ?");
            $stmtCheck->execute([$examSetId]);
            if (!$stmtCheck->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบชุดข้อสอบที่ระบุ'
                ]);
                exit;
            }
            
            // เพิ่มหัวข้อใหม่
            $stmt = $conn->prepare("INSERT INTO exam_topic (exam_set_id, name, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$examSetId, $name, $description, $status, $currentDateTime, $currentDateTime]);
            
            $newTopicId = $conn->lastInsertId();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'create_topic', "สร้างหัวข้อการสอบใหม่: {$name}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มหัวข้อการสอบใหม่เรียบร้อยแล้ว',
                'topic_id' => $newTopicId
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มหัวข้อการสอบ: ' . $e->getMessage()
            ]);
        }
        break;
        
    // อัปเดตข้อมูลหัวข้อการสอบ
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
            $topicId = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
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
            
            // ตรวจสอบว่ามีหัวข้อนี้อยู่จริงหรือไม่
            $stmt = $conn->prepare("SELECT name, exam_set_id FROM exam_topic WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลหัวข้อการสอบ'
                ]);
                exit;
            }
            
            // อัปเดตข้อมูลหัวข้อการสอบ
            $stmt = $conn->prepare("UPDATE exam_topic SET name = ?, description = ?, status = ?, updated_at = ? WHERE topic_id = ?");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$name, $description, $status, $currentDateTime, $topicId]);
            
            // บันทึกประวัติการทำงาน
            logActivity(
                $conn, 
                'update_topic', 
                "อัปเดตหัวข้อการสอบ: {$topic['name']} เป็น {$name}", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลหัวข้อการสอบเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ลบหัวข้อการสอบ
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
            $topicId = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
            
            if ($topicId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสหัวข้อ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีหัวข้อนี้อยู่จริงหรือไม่
            $stmt = $conn->prepare("SELECT name FROM exam_topic WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลหัวข้อการสอบ'
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
                    'message' => 'ไม่สามารถลบหัวข้อการสอบได้ เนื่องจากมีคำถามอยู่ในหัวข้อนี้'
                ]);
                exit;
            }
            
            // ลบหัวข้อการสอบ
            $stmt = $conn->prepare("DELETE FROM exam_topic WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            
            // บันทึกประวัติการทำงาน
            logActivity(
                $conn, 
                'delete_topic', 
                "ลบหัวข้อการสอบ: {$topic['name']} (ID: {$topicId})", 
                $_SESSION['user_id']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบหัวข้อการสอบเรียบร้อยแล้ว'
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