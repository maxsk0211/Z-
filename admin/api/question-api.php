<?php
// question-api.php - สำหรับจัดการคำถามในหัวข้อข้อสอบ
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
    try {
        // ตรวจสอบว่ามีตัวอักษรไทยหรือไม่
        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $details)) {
            // ถ้ามีภาษาไทย ให้ตัดภาษาไทยออกหรือแทนที่ด้วยข้อความภาษาอังกฤษ
            $details = '[Thai text] ' . preg_replace('/[\x{0E00}-\x{0E7F}]/u', '', $details);
        }

        $stmt = $conn->prepare("INSERT INTO admin_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $stmt->execute([$_SESSION['user_id'], $action, $details, $ip]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

// ฟังก์ชันอัปโหลดรูปภาพ
function uploadImage($file, $oldImage = null) {
    if (!isset($file) || $file['error'] != 0) {
        return null;
    }
    
    // กำหนดโฟลเดอร์ - แก้ไขให้อยู่ในขอบเขตที่อนุญาต
    $uploadDir = __DIR__ . '/../../img/question/';
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create upload directory: $uploadDir");
            throw new Exception('ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดไฟล์ได้');
        }
    }
    
    // ตรวจสอบนามสกุลไฟล์
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('รองรับเฉพาะไฟล์ JPEG, PNG และ GIF เท่านั้น');
    }
    
    // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('ขนาดไฟล์ต้องไม่เกิน 2MB');
    }
    
    // สร้างชื่อไฟล์ใหม่
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = 'question_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $uploadPath = $uploadDir . $newFilename;
    
    // อัปโหลดไฟล์
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        error_log("Failed to upload file: {$file['tmp_name']} to $uploadPath");
        throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
    }
    
    // ลบไฟล์เก่า
    if ($oldImage && file_exists(__DIR__ . '/../../' . $oldImage)) {
        unlink(__DIR__ . '/../../' . $oldImage);
    }
    
    return 'img/question/' . $newFilename;
}

// รับ action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    // รายการคำถามตามหัวข้อ
    case 'list_by_topic':
        $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
        
        if ($topic_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสหัวข้อไม่ถูกต้อง']);
            exit;
        }
        
        try {
            // ตรวจสอบว่ามีหัวข้อนี้อยู่จริง
            $checkStmt = $conn->prepare("SELECT topic_id FROM exam_topic WHERE topic_id = ?");
            $checkStmt->execute([$topic_id]);
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบหัวข้อนี้ในระบบ']);
                exit;
            }
            
            // ดึงข้อมูลคำถาม
            $stmt = $conn->prepare("
                SELECT question_id, content, image, score, status, topic_id
                FROM question
                WHERE topic_id = ?
                ORDER BY question_id ASC
            ");
            $stmt->execute([$topic_id]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงตัวเลือกของแต่ละคำถาม
            foreach ($questions as &$question) {
                $choiceStmt = $conn->prepare("
                    SELECT choice_id, content, is_correct
                    FROM choice
                    WHERE question_id = ?
                    ORDER BY choice_id ASC
                ");
                $choiceStmt->execute([$question['question_id']]);
                $question['choices'] = $choiceStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(['success' => true, 'data' => $questions]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // รายการคำถามตามชุดข้อสอบ
    case 'list_by_exam_set':
        $exam_set_id = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
        
        if ($exam_set_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสชุดข้อสอบไม่ถูกต้อง']);
            exit;
        }
        
        try {
            // ดึงข้อมูลคำถามทั้งหมดในชุดข้อสอบ
            $stmt = $conn->prepare("
                SELECT q.question_id, q.content, q.image, q.score, q.status, 
                       q.topic_id, t.name as topic_name
                FROM question q
                JOIN exam_topic t ON q.topic_id = t.topic_id
                WHERE t.exam_set_id = ?
                ORDER BY t.name ASC, q.question_id ASC
            ");
            $stmt->execute([$exam_set_id]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงตัวเลือกของแต่ละคำถาม
            foreach ($questions as &$question) {
                $choiceStmt = $conn->prepare("
                    SELECT choice_id, content, is_correct
                    FROM choice
                    WHERE question_id = ?
                    ORDER BY choice_id ASC
                ");
                $choiceStmt->execute([$question['question_id']]);
                $question['choices'] = $choiceStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(['success' => true, 'data' => $questions]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // ดึงข้อมูลคำถามเดียว
    case 'get':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสคำถามไม่ถูกต้อง']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT q.*, t.name as topic_name
                FROM question q
                JOIN exam_topic t ON q.topic_id = t.topic_id
                WHERE q.question_id = ?
            ");
            $stmt->execute([$id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$question) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลคำถาม']);
                exit;
            }
            
            // ดึงตัวเลือก
            $choiceStmt = $conn->prepare("
                SELECT *
                FROM choice
                WHERE question_id = ?
                ORDER BY choice_id ASC
            ");
            $choiceStmt->execute([$id]);
            $question['choices'] = $choiceStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $question]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // สร้างคำถามใหม่
    case 'create':
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
        $content = $_POST['content'] ?? '';
        $score = isset($_POST['score']) ? floatval($_POST['score']) : 1;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        $choices = isset($_POST['choices']) ? $_POST['choices'] : [];
        
        // ตรวจสอบข้อมูล
        if ($topic_id <= 0 || empty($content) || empty($choices)) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit;
        }
        
        // ตรวจสอบว่ามีอย่างน้อยหนึ่งตัวเลือกที่เป็นคำตอบถูกต้อง
        $hasCorrectAnswer = false;
        foreach ($choices as $choice) {
            if (isset($choice['is_correct']) && $choice['is_correct'] == 1) {
                $hasCorrectAnswer = true;
                break;
            }
        }
        
        if (!$hasCorrectAnswer) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุตัวเลือกที่เป็นคำตอบที่ถูกต้อง']);
            exit;
        }
        
        try {
            // เริ่ม transaction
            $conn->beginTransaction();
            
            // อัปโหลดรูปภาพ (ถ้ามี)
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                try {
                    $imagePath = uploadImage($_FILES['image']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'อัปโหลดรูปภาพไม่สำเร็จ: ' . $e->getMessage()]);
                    exit;
                }
            }
            
            // สร้างคำถามใหม่
            $stmt = $conn->prepare("
                INSERT INTO question (topic_id, content, image, score, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$topic_id, $content, $imagePath, $score, $status, $currentDateTime, $currentDateTime]);
            
            $question_id = $conn->lastInsertId();
            
            // เพิ่มตัวเลือก
            foreach ($choices as $choice) {
                if (empty($choice['content'])) continue;
                
                $stmt = $conn->prepare("
                    INSERT INTO choice (question_id, content, is_correct, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $is_correct = isset($choice['is_correct']) ? intval($choice['is_correct']) : 0;
                $stmt->execute([$question_id, $choice['content'], $is_correct, $currentDateTime, $currentDateTime]);
            }
            
            // บันทึก transaction
            $conn->commit();
            
            // บันทึกประวัติ
            logActivity($conn, 'create_question', "สร้างคำถามใหม่: " . substr($content, 0, 50) . (strlen($content) > 50 ? '...' : ''));
            
            echo json_encode([
                'success' => true, 
                'message' => 'เพิ่มคำถามใหม่เรียบร้อยแล้ว', 
                'question_id' => $question_id
            ]);
        } catch (PDOException $e) {
            // ยกเลิก transaction ในกรณีเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // อัปเดตคำถาม
    case 'update':
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
        $content = $_POST['content'] ?? '';
        $score = isset($_POST['score']) ? floatval($_POST['score']) : 1;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        $choices = isset($_POST['choices']) ? $_POST['choices'] : [];
        $remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] == 'on';
        
        if ($question_id <= 0 || $topic_id <= 0 || empty($content) || empty($choices)) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit;
        }
        
        // ตรวจสอบว่ามีอย่างน้อยหนึ่งตัวเลือกที่เป็นคำตอบถูกต้อง
        $hasCorrectAnswer = false;
        foreach ($choices as $choice) {
            if (isset($choice['is_correct']) && $choice['is_correct'] == 1) {
                $hasCorrectAnswer = true;
                break;
            }
        }
        
        if (!$hasCorrectAnswer) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุตัวเลือกที่เป็นคำตอบที่ถูกต้อง']);
            exit;
        }
        
        try {
            // ตรวจสอบว่ามีคำถามนี้อยู่จริง
            $checkStmt = $conn->prepare("SELECT image FROM question WHERE question_id = ?");
            $checkStmt->execute([$question_id]);
            $oldQuestion = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldQuestion) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบคำถามนี้ในระบบ']);
                exit;
            }
            
            // เริ่ม transaction
            $conn->beginTransaction();
            
            // จัดการรูปภาพ
            $imagePath = $oldQuestion['image'];
            
            if ($remove_image) {
                // ลบรูปภาพเก่า
                if ($imagePath && file_exists(__DIR__ . '/../../' . $imagePath)) {
                    unlink(__DIR__ . '/../../' . $imagePath);
                }
                $imagePath = null;
            } else if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                // อัปโหลดรูปภาพใหม่
                try {
                    $imagePath = uploadImage($_FILES['image'], $imagePath);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'อัปโหลดรูปภาพไม่สำเร็จ: ' . $e->getMessage()]);
                    exit;
                }
            }
            
            // อัปเดตคำถาม
            $stmt = $conn->prepare("
                UPDATE question
                SET topic_id = ?, content = ?, image = ?, score = ?, status = ?, updated_at = ?
                WHERE question_id = ?
            ");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$topic_id, $content, $imagePath, $score, $status, $currentDateTime, $question_id]);
            
            // ลบตัวเลือกเดิม
            $stmt = $conn->prepare("DELETE FROM choice WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // เพิ่มตัวเลือกใหม่
            foreach ($choices as $choice) {
                if (empty($choice['content'])) continue;
                
                $stmt = $conn->prepare("
                    INSERT INTO choice (question_id, content, is_correct, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $is_correct = isset($choice['is_correct']) ? intval($choice['is_correct']) : 0;
                $stmt->execute([$question_id, $choice['content'], $is_correct, $currentDateTime, $currentDateTime]);
            }
            
            // บันทึก transaction
            $conn->commit();
            
            // บันทึกประวัติ
            logActivity($conn, 'update_question', "อัปเดตคำถามรหัส: {$question_id}");
            
            echo json_encode(['success' => true, 'message' => 'อัปเดตคำถามเรียบร้อยแล้ว']);
        } catch (PDOException $e) {
            // ยกเลิก transaction ในกรณีเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // ลบคำถาม
    case 'delete':
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        
        if ($question_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสคำถามไม่ถูกต้อง']);
            exit;
        }
        
        try {
            // ตรวจสอบว่ามีคำถามนี้อยู่จริง
            $checkStmt = $conn->prepare("SELECT image FROM question WHERE question_id = ?");
            $checkStmt->execute([$question_id]);
            $question = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$question) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบคำถามนี้ในระบบ']);
                exit;
            }
            
            // เริ่ม transaction
            $conn->beginTransaction();
            
            // ลบตัวเลือก
            $stmt = $conn->prepare("DELETE FROM choice WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // ลบคำถาม
            $stmt = $conn->prepare("DELETE FROM question WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // ลบรูปภาพประกอบ (ถ้ามี)
            if ($question['image'] && file_exists(__DIR__ . '/../../' . $question['image'])) {
                unlink(__DIR__ . '/../../' . $question['image']);
            }
            
            // บันทึก transaction
            $conn->commit();
            
            // บันทึกประวัติ
            logActivity($conn, 'delete_question', "ลบคำถามรหัส: {$question_id}");
            
            echo json_encode(['success' => true, 'message' => 'ลบคำถามเรียบร้อยแล้ว']);
        } catch (PDOException $e) {
            // ยกเลิก transaction ในกรณีเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'ไม่พบ action ที่ระบุ']);
        break;
}
?>