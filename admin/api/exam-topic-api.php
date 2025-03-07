<?php
// exam-topic-api.php - สำหรับจัดการหัวข้อข้อสอบ
header('Content-Type: application/json');
session_start();

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../../dbcon.php';

try {
    $conn = getDBConnection();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()]);
    exit;
}

// ฟังก์ชัน Helper
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}

function logActivity($conn, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO admin_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt->execute([$_SESSION['user_id'], $action, $details, $ip]);
}

function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

// รับ action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    // รายการหัวข้อตาม exam_set_id
    case 'list':
        $exam_set_id = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
        
        if ($exam_set_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสชุดข้อสอบไม่ถูกต้อง']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT t.topic_id, t.name, t.description, t.status, t.created_at, t.updated_at,
                       COUNT(q.question_id) AS question_count
                FROM exam_topic t
                LEFT JOIN question q ON t.topic_id = q.topic_id
                WHERE t.exam_set_id = ?
                GROUP BY t.topic_id
                ORDER BY t.name ASC
            ");
            $stmt->execute([$exam_set_id]);
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $topics]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
        
    // ดึงข้อมูลหัวข้อเดียว
    case 'get':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสหัวข้อไม่ถูกต้อง']);
            exit;
        }
        
        try {
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
                echo json_encode(['success' => true, 'data' => $topic]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลหัวข้อ']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // สร้างหัวข้อใหม่
    case 'create':
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        $exam_set_id = isset($_POST['exam_set_id']) ? intval($_POST['exam_set_id']) : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        
        if ($exam_set_id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit;
        }
        
        try {
            // ตรวจสอบว่ามีชุดข้อสอบนี้จริงหรือไม่
            $checkStmt = $conn->prepare("SELECT exam_set_id FROM exam_set WHERE exam_set_id = ?");
            $checkStmt->execute([$exam_set_id]);
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบชุดข้อสอบนี้ในระบบ']);
                exit;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO exam_topic (exam_set_id, name, description, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$exam_set_id, $name, $description, $status, $currentDateTime, $currentDateTime]);
            
            $newTopicId = $conn->lastInsertId();
            
            // บันทึกประวัติ
            logActivity($conn, 'create_topic', "สร้างหัวข้อใหม่: {$name}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'เพิ่มหัวข้อข้อสอบใหม่เรียบร้อยแล้ว',
                'topic_id' => $newTopicId
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // อัปเดตหัวข้อ
    case 'update':
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        
        if ($topic_id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit;
        }
        
        try {
            // ตรวจสอบว่ามีหัวข้อนี้จริงหรือไม่
            $checkStmt = $conn->prepare("SELECT name FROM exam_topic WHERE topic_id = ?");
            $checkStmt->execute([$topic_id]);
            $oldTopic = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldTopic) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบหัวข้อนี้ในระบบ']);
                exit;
            }
            
            $stmt = $conn->prepare("
                UPDATE exam_topic 
                SET name = ?, description = ?, status = ?, updated_at = ? 
                WHERE topic_id = ?
            ");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$name, $description, $status, $currentDateTime, $topic_id]);
            
            // บันทึกประวัติ
            logActivity($conn, 'update_topic', "อัปเดตหัวข้อ '{$oldTopic['name']}' เป็น '{$name}'");
            
            echo json_encode(['success' => true, 'message' => 'อัปเดตหัวข้อข้อสอบเรียบร้อยแล้ว']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // ลบหัวข้อ
    case 'delete':
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
        
        if ($topic_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสหัวข้อไม่ถูกต้อง']);
            exit;
        }
        
        try {
            // ตรวจสอบว่ามีคำถามอยู่ในหัวข้อนี้หรือไม่
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM question WHERE topic_id = ?");
            $checkStmt->execute([$topic_id]);
            $questionCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($questionCount > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ไม่สามารถลบหัวข้อนี้ได้ เนื่องจากมีคำถามอยู่ในหัวข้อนี้'
                ]);
                exit;
            }
            
            // ดึงชื่อหัวข้อเพื่อบันทึกประวัติ
            $nameStmt = $conn->prepare("SELECT name FROM exam_topic WHERE topic_id = ?");
            $nameStmt->execute([$topic_id]);
            $topicName = $nameStmt->fetch(PDO::FETCH_ASSOC)['name'] ?? 'ไม่ทราบชื่อ';
            
            // ลบหัวข้อ
            $stmt = $conn->prepare("DELETE FROM exam_topic WHERE topic_id = ?");
            $stmt->execute([$topic_id]);
            
            // บันทึกประวัติ
            logActivity($conn, 'delete_topic', "ลบหัวข้อ: {$topicName}");
            
            echo json_encode(['success' => true, 'message' => 'ลบหัวข้อข้อสอบเรียบร้อยแล้ว']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'ไม่พบ action ที่ระบุ']);
        break;
}
?>