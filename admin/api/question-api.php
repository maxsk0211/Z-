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

// ฟังก์ชันสำหรับอัปโหลดไฟล์รูปภาพ
function uploadImage($file, $targetDir) {
    // ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
    if (!isset($file) || $file['error'] != 0) {
        return null;
    }
    
    // ตรวจสอบชนิดของไฟล์
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('ชนิดของไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น');
    }
    
    // ตรวจสอบขนาดของไฟล์ (ไม่เกิน 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('ไฟล์มีขนาดใหญ่เกินไป กรุณาอัปโหลดไฟล์ที่มีขนาดไม่เกิน 2MB');
    }
    
    // สร้างไดเร็กทอรีถ้ายังไม่มี
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำซ้อน
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetPath = $targetDir . '/' . $newFileName;
    
    // อัปโหลดไฟล์
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
    }
    
    return $newFileName;
}

// รับค่า action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// ตรวจสอบค่า action และประมวลผลตามค่าที่รับมา
switch ($action) {
    // ดึงข้อมูลคำถามทั้งหมดตามหัวข้อ
    case 'list_by_topic':
        try {
            $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
            
            if ($topicId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสหัวข้อ'
                ]);
                exit;
            }
            
            // ดึงข้อมูลคำถาม
            $stmt = $conn->prepare("
                SELECT q.question_id, q.topic_id, q.content, q.image,  q.score, q.status, q.created_at, q.updated_at
                FROM question q
                WHERE q.topic_id = ?
                ORDER BY q.question_id ASC
            ");
            $stmt->execute([$topicId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงข้อมูลตัวเลือกของแต่ละคำถาม
            foreach ($questions as &$question) {
                $stmtChoices = $conn->prepare("
                    SELECT choice_id, question_id, content, image, is_correct
                    FROM choice
                    WHERE question_id = ?
                    ORDER BY choice_id ASC
                ");
                $stmtChoices->execute([$question['question_id']]);
                $question['choices'] = $stmtChoices->fetchAll(PDO::FETCH_ASSOC);
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
        
    // ดึงข้อมูลคำถามทั้งหมดตามชุดข้อสอบ
    case 'list_by_exam_set':
        try {
            $examSetId = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
            
            if ($examSetId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสชุดข้อสอบ'
                ]);
                exit;
            }
            
            // ดึงข้อมูลคำถาม
            $stmt = $conn->prepare("
                SELECT q.question_id, q.topic_id, q.content, q.image, q.score, q.status, q.created_at, q.updated_at,
                       t.name as topic_name
                FROM question q
                JOIN exam_topic t ON q.topic_id = t.topic_id
                WHERE t.exam_set_id = ?
                ORDER BY t.name ASC, q.question_id ASC
            ");
            $stmt->execute([$examSetId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงข้อมูลตัวเลือกของแต่ละคำถาม
            foreach ($questions as &$question) {
                $stmtChoices = $conn->prepare("
                    SELECT choice_id, question_id, content, image, is_correct
                    FROM choice
                    WHERE question_id = ?
                    ORDER BY choice_id ASC
                ");
                $stmtChoices->execute([$question['question_id']]);
                $question['choices'] = $stmtChoices->fetchAll(PDO::FETCH_ASSOC);
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
                SELECT question_id, topic_id, content, image, score, status, created_at, updated_at
                FROM question
                WHERE question_id = ?
            ");
            $stmt->execute([$id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($question) {
                // ดึงข้อมูลตัวเลือก
                $stmtChoices = $conn->prepare("
                    SELECT choice_id, question_id, content, image, is_correct
                    FROM choice
                    WHERE question_id = ?
                    ORDER BY choice_id ASC
                ");
                $stmtChoices->execute([$id]);
                $question['choices'] = $stmtChoices->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $question
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลคำถาม'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
            ]);
        }
        break;
        
        
        // question-api.php - แก้ไขส่วนที่เกี่ยวข้องกับคำอธิบายรูปภาพ
        
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
                $topicId = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
                $content = $_POST['question_content'] ?? '';
                $score = isset($_POST['question_score']) ? floatval($_POST['question_score']) : 1;
                // ลบการรับค่า imageDescription
                // $imageDescription = $_POST['question_image_description'] ?? null;
                
                // ตรวจสอบข้อมูล
                if ($topicId <= 0 || empty($content)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
                    ]);
                    exit;
                }
                
                // ตรวจสอบว่ามีหัวข้อนี้อยู่จริงหรือไม่
                $stmtCheck = $conn->prepare("SELECT topic_id FROM exam_topic WHERE topic_id = ?");
                $stmtCheck->execute([$topicId]);
                if (!$stmtCheck->fetch()) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ไม่พบหัวข้อที่ระบุ'
                    ]);
                    exit;
                }
                
                // ตรวจสอบและอัปโหลดรูปภาพคำถาม (ถ้ามี)
                $questionImage = null;
                if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] == 0) {
                    $targetDir = __DIR__ . '/../../uploads/questions';
                    try {
                        $questionImage = uploadImage($_FILES['question_image'], $targetDir);
                    } catch (Exception $e) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพคำถาม: ' . $e->getMessage()
                        ]);
                        exit;
                    }
                }
                
                // เริ่มต้น transaction
                $conn->beginTransaction();
                
                // สร้างคำถามใหม่ - แก้ไข SQL ให้ไม่มี image_description
                $stmt = $conn->prepare("
                    INSERT INTO question (topic_id, content, image, score, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 1, ?, ?)
                ");
                $currentDateTime = getCurrentDateTime();
                $stmt->execute([$topicId, $content, $questionImage, $score, $currentDateTime, $currentDateTime]);
                
                $questionId = $conn->lastInsertId();
                
                // ตรวจสอบตัวเลือก
                $choiceContents = $_POST['choice_content'] ?? [];
                $correctChoice = $_POST['correct_choice'] ?? '';
                
                if (empty($choiceContents) || count($choiceContents) < 2) {
                    $conn->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => 'กรุณาเพิ่มตัวเลือกอย่างน้อย 2 ตัวเลือก'
                    ]);
                    exit;
                }
                
                if (empty($correctChoice)) {
                    $conn->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => 'กรุณาเลือกคำตอบที่ถูกต้อง'
                    ]);
                    exit;
                }
                
                // เพิ่มตัวเลือก
                foreach ($choiceContents as $choiceId => $choiceContent) {
                    if (empty($choiceContent)) continue;
                    
                    // ตรวจสอบว่าเป็นคำตอบที่ถูกต้องหรือไม่
                    $isCorrect = ($choiceId === $correctChoice) ? 1 : 0;
                    
                    // ตรวจสอบและอัปโหลดรูปภาพตัวเลือก (ถ้ามี)
                    $choiceImage = null;
                    // ลบการรับค่า choiceImageDescription 
                    // $choiceImageDescription = $_POST['choice_image_description'][$choiceId] ?? null;
                    
                    if (isset($_FILES['choice_image']['name'][$choiceId]) && $_FILES['choice_image']['error'][$choiceId] == 0) {
                        $targetDir = __DIR__ . '/../../uploads/choices';
                        
                        // สร้าง $_FILES array สำหรับรูปภาพตัวเลือกปัจจุบัน
                        $tempFile = [
                            'name' => $_FILES['choice_image']['name'][$choiceId],
                            'type' => $_FILES['choice_image']['type'][$choiceId],
                            'tmp_name' => $_FILES['choice_image']['tmp_name'][$choiceId],
                            'error' => $_FILES['choice_image']['error'][$choiceId],
                            'size' => $_FILES['choice_image']['size'][$choiceId]
                        ];
                        
                        try {
                            $choiceImage = uploadImage($tempFile, $targetDir);
                        } catch (Exception $e) {
                            $conn->rollBack();
                            echo json_encode([
                                'success' => false,
                                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพตัวเลือก: ' . $e->getMessage()
                            ]);
                            exit;
                        }
                    }
                    
                    // เพิ่มตัวเลือก - แก้ไข SQL ให้ไม่มี image_description
                    $stmtChoice = $conn->prepare("
                        INSERT INTO choice (question_id, content, image, is_correct, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmtChoice->execute([$questionId, $choiceContent, $choiceImage, $isCorrect, $currentDateTime, $currentDateTime]);
                }
                
                // บันทึก transaction
                $conn->commit();
                
                // บันทึกประวัติการทำงาน
                logActivity($conn, 'create_question', "สร้างคำถามใหม่: {$content}", $_SESSION['user_id']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'เพิ่มคำถามเรียบร้อยแล้ว',
                    'question_id' => $questionId
                ]);
            } catch (PDOException $e) {
                // ยกเลิก transaction ในกรณีเกิดข้อผิดพลาด
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
            $questionId = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
            $topicId = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
            $content = $_POST['question_content'] ?? '';
            $score = isset($_POST['question_score']) ? floatval($_POST['question_score']) : 1;
            $existingImage = $_POST['existing_image'] ?? '';
            $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';
            // $imageDescription = $_POST['question_image_description'] ?? null;
            
            // ตรวจสอบข้อมูล
            if ($questionId <= 0 || $topicId <= 0 || empty($content)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีคำถามนี้อยู่จริงหรือไม่
            $stmtCheck = $conn->prepare("SELECT question_id, image FROM question WHERE question_id = ?");
            $stmtCheck->execute([$questionId]);
            $existingQuestion = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingQuestion) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคำถามที่ระบุ'
                ]);
                exit;
            }
            
            // ตรวจสอบและอัปโหลดรูปภาพคำถาม (ถ้ามี)
            $questionImage = $existingImage;
            
            if ($removeImage) {
                // ลบรูปภาพเดิม
                $questionImage = null;
                
                // ลบไฟล์รูปภาพ (ถ้ามี)
                if (!empty($existingQuestion['image'])) {
                    $imagePath = __DIR__ . '/../../uploads/questions/' . $existingQuestion['image'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            } elseif (isset($_FILES['question_image']) && $_FILES['question_image']['error'] == 0) {
                // อัปโหลดรูปภาพใหม่
                $targetDir = __DIR__ . '/../../uploads/questions';
                try {
                    $questionImage = uploadImage($_FILES['question_image'], $targetDir);
                    
                    // ลบรูปภาพเดิม (ถ้ามี)
                    if (!empty($existingQuestion['image'])) {
                        $imagePath = __DIR__ . '/../../uploads/questions/' . $existingQuestion['image'];
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพคำถาม: ' . $e->getMessage()
                    ]);
                    exit;
                }
            }
            
            // เริ่มต้น transaction
            $conn->beginTransaction();
            
            // อัปเดตข้อมูลคำถาม
            $stmt = $conn->prepare("
                UPDATE question
                SET topic_id = ?, content = ?, image = ?, score = ?, updated_at = ?
                WHERE question_id = ?
            ");
            $currentDateTime = getCurrentDateTime();
            $stmt->execute([$topicId, $content, $questionImage, $imageDescription, $score, $currentDateTime, $questionId]);
            
            // ตรวจสอบตัวเลือก
            $choiceContents = $_POST['choice_content'] ?? [];
            $choiceIds = $_POST['choice_id'] ?? [];
            $correctChoice = $_POST['correct_choice'] ?? '';
            
            if (empty($choiceContents) || count($choiceContents) < 2) {
                $conn->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาเพิ่มตัวเลือกอย่างน้อย 2 ตัวเลือก'
                ]);
                exit;
            }
            
            if (empty($correctChoice)) {
                $conn->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาเลือกคำตอบที่ถูกต้อง'
                ]);
                exit;
            }
            
            // ดึงตัวเลือกเดิม
            $stmtExistingChoices = $conn->prepare("SELECT choice_id, image FROM choice WHERE question_id = ?");
            $stmtExistingChoices->execute([$questionId]);
            $existingChoices = $stmtExistingChoices->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // เก็บ choice_id ทั้งหมดที่จะเพิ่มหรืออัปเดต เพื่อใช้ในการลบตัวเลือกที่ไม่ได้ใช้
            $processedChoiceIds = [];
            
            // อัปเดตหรือเพิ่มตัวเลือก
            foreach ($choiceContents as $tempChoiceId => $choiceContent) {
                if (empty($choiceContent)) continue;
                
                // ตรวจสอบว่าเป็นคำตอบที่ถูกต้องหรือไม่
                $isCorrect = ($tempChoiceId === $correctChoice) ? 1 : 0;
                
                // ตัวเลือกเดิมหรือใหม่
                $existingChoiceId = $choiceIds[$tempChoiceId] ?? null;
                $removeChoiceImage = isset($_POST['remove_choice_image'][$tempChoiceId]) && $_POST['remove_choice_image'][$tempChoiceId] == '1';
                // $choiceImageDescription = $_POST['choice_image_description'][$tempChoiceId] ?? null;
                
                // ตรวจสอบและอัปโหลดรูปภาพตัวเลือก (ถ้ามี)
                $choiceImage = isset($existingChoices[$existingChoiceId]) ? $existingChoices[$existingChoiceId] : null;
                
                if ($removeChoiceImage) {
                    // ลบรูปภาพเดิม
                    $choiceImage = null;
                    
                    // ลบไฟล์รูปภาพ (ถ้ามี)
                    if (!empty($existingChoices[$existingChoiceId])) {
                        $imagePath = __DIR__ . '/../../uploads/choices/' . $existingChoices[$existingChoiceId];
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                } elseif (isset($_FILES['choice_image']['name'][$tempChoiceId]) && $_FILES['choice_image']['error'][$tempChoiceId] == 0) {
                    // อัปโหลดรูปภาพใหม่
                    $targetDir = __DIR__ . '/../../uploads/choices';
                    
                    // สร้าง $_FILES array สำหรับรูปภาพตัวเลือกปัจจุบัน
                    $tempFile = [
                        'name' => $_FILES['choice_image']['name'][$tempChoiceId],
                        'type' => $_FILES['choice_image']['type'][$tempChoiceId],
                        'tmp_name' => $_FILES['choice_image']['tmp_name'][$tempChoiceId],
                        'error' => $_FILES['choice_image']['error'][$tempChoiceId],
                        'size' => $_FILES['choice_image']['size'][$tempChoiceId]
                    ];
                    
                    try {
                        $choiceImage = uploadImage($tempFile, $targetDir);
                        
                        // ลบรูปภาพเดิม (ถ้ามี)
                        if (!empty($existingChoices[$existingChoiceId])) {
                            $imagePath = __DIR__ . '/../../uploads/choices/' . $existingChoices[$existingChoiceId];
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        }
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo json_encode([
                            'success' => false,
                            'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพตัวเลือก: ' . $e->getMessage()
                        ]);
                        exit;
                    }
                }
                
                if (!empty($existingChoiceId)) {
                    // อัปเดตตัวเลือกเดิม
                    $stmtUpdateChoice = $conn->prepare("
                        UPDATE choice
                        SET content = ?, image = ?, is_correct = ?, updated_at = ?
                        WHERE choice_id = ?
                    ");
                    $stmtUpdateChoice->execute([$choiceContent, $choiceImage, $choiceImageDescription, $isCorrect, $currentDateTime, $existingChoiceId]);
                    
                    $processedChoiceIds[] = $existingChoiceId;
                } else {
                    // เพิ่มตัวเลือกใหม่
                    $stmtInsertChoice = $conn->prepare("
                        INSERT INTO choice (question_id, content, image, is_correct, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsertChoice->execute([$questionId, $choiceContent, $choiceImage, $choiceImageDescription, $isCorrect, $currentDateTime, $currentDateTime]);
                    
                    $processedChoiceIds[] = $conn->lastInsertId();
                }
            }
            
            // ลบตัวเลือกที่ไม่ได้ใช้
            if (!empty($processedChoiceIds)) {
                $placeholders = str_repeat('?,', count($processedChoiceIds) - 1) . '?';
                $stmtDeleteChoices = $conn->prepare("
                    DELETE FROM choice
                    WHERE question_id = ? AND choice_id NOT IN ({$placeholders})
                ");
                
                $params = array_merge([$questionId], $processedChoiceIds);
                $stmtDeleteChoices->execute($params);
            }
            
            // บันทึก transaction
            $conn->commit();
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'update_question', "อัปเดตคำถามรหัส: {$questionId}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตคำถามเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            // ยกเลิก transaction ในกรณีเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตคำถาม: ' . $e->getMessage()
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
            $questionId = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
            
            if ($questionId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสคำถาม'
                ]);
                exit;
            }
            
            // ตรวจสอบว่ามีคำถามนี้อยู่จริงหรือไม่
            $stmtCheck = $conn->prepare("SELECT question_id, content, image FROM question WHERE question_id = ?");
            $stmtCheck->execute([$questionId]);
            $question = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$question) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคำถามที่ระบุ'
                ]);
                exit;
            }
            
            // เริ่มต้น transaction
            $conn->beginTransaction();
            
            // ดึงข้อมูลตัวเลือกเพื่อลบรูปภาพ
            $stmtChoices = $conn->prepare("SELECT choice_id, image FROM choice WHERE question_id = ?");
            $stmtChoices->execute([$questionId]);
            $choices = $stmtChoices->fetchAll(PDO::FETCH_ASSOC);
            
            // ลบตัวเลือกของคำถาม
            $stmtDeleteChoices = $conn->prepare("DELETE FROM choice WHERE question_id = ?");
            $stmtDeleteChoices->execute([$questionId]);
            
            // ลบคำถาม
            $stmtDeleteQuestion = $conn->prepare("DELETE FROM question WHERE question_id = ?");
            $stmtDeleteQuestion->execute([$questionId]);
            
            // บันทึก transaction
            $conn->commit();
            
            // ลบไฟล์รูปภาพคำถาม (ถ้ามี)
            if (!empty($question['image'])) {
                $imagePath = __DIR__ . '/../../uploads/questions/' . $question['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            // ลบไฟล์รูปภาพตัวเลือก (ถ้ามี)
            foreach ($choices as $choice) {
                if (!empty($choice['image'])) {
                    $imagePath = __DIR__ . '/../../uploads/choices/' . $choice['image'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }
            
            // บันทึกประวัติการทำงาน
            logActivity($conn, 'delete_question', "ลบคำถามรหัส: {$questionId}", $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบคำถามเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            // ยกเลิก transaction ในกรณีเกิดข้อผิดพลาด
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบคำถาม: ' . $e->getMessage()
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