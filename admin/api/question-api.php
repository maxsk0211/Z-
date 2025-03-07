<?php
// question-api.php - /admin/api/question-api.php
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
require_once __DIR__ . '/../../dbcon14.php';

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

// ฟังก์ชันอัปโหลดรูปภาพ
function uploadImage($file, $old_image = null) {
    // ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
    if (!isset($file) || $file['error'] != 0) {
        return null;
    }
    
    // กำหนดโฟลเดอร์สำหรับเก็บไฟล์
    $upload_dir = __DIR__ . '/../../uploads/questions/';
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะไฟล์ JPEG, PNG และ GIF เท่านั้น');
    }
    
    // ตรวจสอบขนาดไฟล์ (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('ขนาดไฟล์ใหญ่เกินไป ต้องไม่เกิน 2MB');
    }
    
    // สร้างชื่อไฟล์ใหม่ เพื่อป้องกันการซ้ำกัน
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'question_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $upload_path = $upload_dir . $new_filename;
    
    // ย้ายไฟล์
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
    }
    
    // ลบรูปภาพเก่าถ้ามี
    if ($old_image && file_exists(__DIR__ . '/../../' . $old_image)) {
        unlink(__DIR__ . '/../../' . $old_image);
    }
    
    // ส่งคืน path สัมพัทธ์
    return 'uploads/questions/' . $new_filename;
}

// รับค่า action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// ตรวจสอบค่า action และประมวลผลตามค่าที่รับมา
switch ($action) {
    // ดึงข้อมูลคำถามตามหัวข้อ
    case 'list_by_topic':
        try {
            $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
            
            if ($topic_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสหัวข้อข้อสอบ'
                ]);
                exit;
            }
            
            // ดึงข้อมูลคำถาม
            $stmt = $conn->prepare("
                SELECT q.question_id, q.content, q.image, q.score, q.status, q.topic_id
                FROM question q
                WHERE q.topic_id = ?
                ORDER BY q.question_id ASC
            ");
            $stmt->execute([$topic_id]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงตัวเลือกของแต่ละคำถาม
            foreach ($questions as &$question) {
                $stmt = $conn->prepare("
                    SELECT choice_id, content, is_correct
                    FROM choice
                    WHERE question_id = ?
                    ORDER BY choice_id ASC
                ");
                $stmt->execute([$question['question_id']]);
                $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $questions
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ดึงข้อมูลคำถามตามชุดข้อสอบ
    case 'list_by_exam_set':
        try {
            $exam_set_id = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
            
            if ($exam_set_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสชุดข้อสอบ'
                ]);
                exit;
            }
            
            // ดึงข้อมูลคำถาม
            $stmt = $conn->prepare("
                SELECT q.question_id, q.content, q.image, q.score, q.status, q.topic_id, t.name as topic_name
                FROM question q
                JOIN exam_topic t ON q.topic_id = t.topic_id
                WHERE t.exam_set_id = ?
                ORDER BY t.name ASC, q.question_id ASC
            ");
            $stmt->execute([$exam_set_id]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงตัวเลือกของแต่ละคำถาม
            foreach ($questions as &$question) {
                $stmt = $conn->prepare("
                    SELECT choice_id, content, is_correct
                    FROM choice
                    WHERE question_id = ?
                    ORDER BY choice_id ASC
                ");
                $stmt->execute([$question['question_id']]);
                $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $questions
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ดึงข้อมูลคำถามรายการเดียว
    case 'get':
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสคำถาม'
                ]);
                exit;
            }
            
            // ดึงข้อมูลคำถาม
            $stmt = $conn->prepare("
                SELECT q.*, t.name as topic_name
                FROM question q
                JOIN exam_topic t ON q.topic_id = t.topic_id
                WHERE q.question_id = ?
            ");
            $stmt->execute([$id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$question) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลคำถาม'
                ]);
                exit;
            }
            
            // ดึงตัวเลือก
            $stmt = $conn->prepare("
                SELECT *
                FROM choice
                WHERE question_id = ?
                ORDER BY choice_id ASC
            ");
            $stmt->execute([$id]);
            $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $question
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // สร้างคำถามใหม่
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
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
            $content = $_POST['content'] ?? '';
            $score = isset($_POST['score']) ? floatval($_POST['score']) : 1;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            $choices = isset($_POST['choices']) ? $_POST['choices'] : [];
            
            // ตรวจสอบข้อมูล
            if ($topic_id <= 0 || empty($content) || empty($choices)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีหัวข้อนี้อยู่จริงหรือไม่
            $stmt = $conn->prepare("SELECT topic_id FROM exam_topic WHERE topic_id = ?");
            $stmt->execute([$topic_id]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบหัวข้อข้อสอบที่ระบุ'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีคำตอบที่ถูกต้องอย่างน้อย 1 ข้อหรือไม่
            $has_correct_answer = false;
            foreach ($choices as $choice) {
                if (isset($choice['is_correct']) && $choice['is_correct'] == 1) {
                    $has_correct_answer = true;
                    break;
                }
            }
            
            if (!$has_correct_answer) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุคำตอบที่ถูกต้องอย่างน้อย 1 ข้อ'
                ]);
                exit;
            }
            
            // อัปโหลดรูปภาพ (ถ้ามี)
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                try {
                    $image_path = uploadImage($_FILES['image']);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: ' . $e->getMessage()
                    ]);
                    exit;
                }
            }
            
            // เริ่ม transaction
            $conn->beginTransaction();
            
            // เพิ่มคำถามใหม่
            $stmt = $conn->prepare("
                INSERT INTO question (topic_id, content, image, score, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$topic_id, $content, $image_path, $score, $status, $currentDateTime, $currentDateTime]);
            
            $question_id = $conn->lastInsertId();
            
            // เพิ่มตัวเลือก
            foreach ($choices as $choice) {
                if (empty($choice['content'])) continue;
                
                $stmt = $conn->prepare("
                    INSERT INTO choice (question_id, content, is_correct, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $is_correct = isset($choice['is_correct']) ? (int)$choice['is_correct'] : 0;
                $stmt->execute([$question_id, $choice['content'], $is_correct, $currentDateTime, $currentDateTime]);
            }
            
            // บันทึกการเปลี่ยนแปลง
            $conn->commit();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'create_question', "สร้างคำถามใหม่: {$content}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มคำถามใหม่เรียบร้อยแล้ว',
                'question_id' => $question_id
            ]);
        } catch (PDOException $e) {
            // ยกเลิกการเปลี่ยนแปลงหากเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มคำถาม: ' . $e->getMessage()
            ]);
        }
        break;
        
    // อัปเดตข้อมูลคำถาม
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
            $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
            $content = $_POST['content'] ?? '';
            $score = isset($_POST['score']) ? floatval($_POST['score']) : 1;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            $choices = isset($_POST['choices']) ? $_POST['choices'] : [];
            $remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] == 'on';
            
            // ตรวจสอบข้อมูล
            if ($question_id <= 0 || $topic_id <= 0 || empty($content) || empty($choices)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีคำถามนี้อยู่จริงหรือไม่
            $stmt = $conn->prepare("SELECT content, image FROM question WHERE question_id = ?");
            $stmt->execute([$question_id]);
            $current_question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current_question) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลคำถาม'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีคำตอบที่ถูกต้องอย่างน้อย 1 ข้อหรือไม่
            $has_correct_answer = false;
            foreach ($choices as $choice) {
                if (isset($choice['is_correct']) && $choice['is_correct'] == 1) {
                    $has_correct_answer = true;
                    break;
                }
            }
            
            if (!$has_correct_answer) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุคำตอบที่ถูกต้องอย่างน้อย 1 ข้อ'
                ]);
                exit;
            }
            
            // เริ่ม transaction
            $conn->beginTransaction();
            
            // อัปโหลดรูปภาพใหม่ (ถ้ามี)
            $image_path = $current_question['image'];
            
            if ($remove_image) {
                // ลบรูปภาพเก่า
                if ($current_question['image'] && file_exists(__DIR__ . '/../../' . $current_question['image'])) {
                    unlink(__DIR__ . '/../../' . $current_question['image']);
                }
                $image_path = null;
            } else if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                try {
                    $image_path = uploadImage($_FILES['image'], $current_question['image']);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: ' . $e->getMessage()
                    ]);
                    exit;
                }
            }
            
            // อัปเดตข้อมูลคำถาม
            $stmt = $conn->prepare("
                UPDATE question
                SET topic_id = ?, content = ?, image = ?, score = ?, status = ?, updated_at = ?
                WHERE question_id = ?
            ");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$topic_id, $content, $image_path, $score, $status, $currentDateTime, $question_id]);
            
            // ลบตัวเลือกเดิมทั้งหมด
            $stmt = $conn->prepare("DELETE FROM choice WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // เพิ่มตัวเลือกใหม่
            foreach ($choices as $choice) {
                if (empty($choice['content'])) continue;
                
                $stmt = $conn->prepare("
                    INSERT INTO choice (question_id, content, is_correct, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $is_correct = isset($choice['is_correct']) ? (int)$choice['is_correct'] : 0;
                $stmt->execute([$question_id, $choice['content'], $is_correct, $currentDateTime, $currentDateTime]);
            }
            
            // บันทึกการเปลี่ยนแปลง
            $conn->commit();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'update_question', "อัปเดตคำถามรหัส: {$question_id}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลคำถามเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            // ยกเลิกการเปลี่ยนแปลงหากเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ลบคำถาม
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
            $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
            
            if ($question_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสคำถาม'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีคำถามนี้อยู่จริงหรือไม่ และดึงข้อมูลรูปภาพ
            $stmt = $conn->prepare("SELECT content, image FROM question WHERE question_id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$question) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลคำถาม'
                ]);
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
            
            // ลบรูปภาพ (ถ้ามี)
            if ($question['image'] && file_exists(__DIR__ . '/../../' . $question['image'])) {
                unlink(__DIR__ . '/../../' . $question['image']);
            }
            
            // บันทึกการเปลี่ยนแปลง
            $conn->commit();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'delete_question', "ลบคำถามรหัส: {$question_id}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบคำถามเรียบร้อยแล้ว'
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