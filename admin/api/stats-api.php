<?php
// stats-api.php - /admin/api/stats-api.php
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

// รับค่า action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ตรวจสอบค่า action และประมวลผลตามค่าที่รับมา
switch ($action) {
    // ดึงข้อมูลสถิติเกี่ยวกับชุดข้อสอบ
    case 'exam_stats':
        try {
            // จำนวนชุดข้อสอบทั้งหมด
            $stmtTotal = $conn->query("SELECT COUNT(*) FROM exam_set");
            $totalExamSets = $stmtTotal->fetchColumn();
            
            // จำนวนชุดข้อสอบที่ใช้งานอยู่
            $stmtActive = $conn->query("SELECT COUNT(*) FROM exam_set WHERE status = 1");
            $activeExamSets = $stmtActive->fetchColumn();
            
            // จำนวนชุดข้อสอบที่ไม่ใช้งาน
            $inactiveExamSets = $totalExamSets - $activeExamSets;
            
            // จำนวนหัวข้อทั้งหมด
            $stmtTopics = $conn->query("SELECT COUNT(*) FROM exam_topic");
            $totalTopics = $stmtTopics->fetchColumn();
            
            // ชุดข้อสอบที่สร้างล่าสุด
            $stmtRecent = $conn->query("SELECT COUNT(*) FROM exam_set WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $recentExamSets = $stmtRecent->fetchColumn();
            
            // จำนวนคำถามทั้งหมด
            $stmtQuestions = $conn->query("SELECT COUNT(*) FROM question");
            $totalQuestions = $stmtQuestions->fetchColumn();
            
            // จำนวนรอบการสอบที่กำลังดำเนินการ
            $stmtActiveRounds = $conn->query("SELECT COUNT(*) FROM exam_round WHERE status = 2"); // สถานะ 2 = กำลังสอบ
            $activeRounds = $stmtActiveRounds->fetchColumn();
            
            // ส่งข้อมูลกลับ
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_exam_sets' => intval($totalExamSets),
                    'active_exam_sets' => intval($activeExamSets),
                    'inactive_exam_sets' => intval($inactiveExamSets),
                    'total_topics' => intval($totalTopics),
                    'recent_exam_sets' => intval($recentExamSets),
                    'total_questions' => intval($totalQuestions),
                    'active_rounds' => intval($activeRounds)
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ: ' . $e->getMessage()
            ]);
        }
        break;
        
    // ดึงข้อมูลสถิติเกี่ยวกับหัวข้อการสอบ (สำหรับหน้า exam-topic-management.php)
    case 'topic_stats':
        try {
            // รับค่า exam_set_id จาก request
            $examSetId = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
            
            if ($examSetId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบรหัสชุดข้อสอบ'
                ]);
                exit;
            }
            
            // จำนวนหัวข้อทั้งหมดในชุดข้อสอบนี้
            $stmtTopics = $conn->prepare("SELECT COUNT(*) FROM exam_topic WHERE exam_set_id = ?");
            $stmtTopics->execute([$examSetId]);
            $totalTopics = $stmtTopics->fetchColumn();
            
            // จำนวนหัวข้อที่ใช้งานอยู่
            $stmtActiveTopics = $conn->prepare("SELECT COUNT(*) FROM exam_topic WHERE exam_set_id = ? AND status = 1");
            $stmtActiveTopics->execute([$examSetId]);
            $activeTopics = $stmtActiveTopics->fetchColumn();
            
            // จำนวนหัวข้อที่ไม่ใช้งาน
            $inactiveTopics = $totalTopics - $activeTopics;
            
            // จำนวนคำถามทั้งหมดในชุดข้อสอบนี้
            $stmtQuestions = $conn->prepare("
                SELECT COUNT(*) FROM question q
                JOIN exam_topic t ON q.topic_id = t.topic_id
                WHERE t.exam_set_id = ?
            ");
            $stmtQuestions->execute([$examSetId]);
            $totalQuestions = $stmtQuestions->fetchColumn();
            
            // ข้อมูลเกี่ยวกับชุดข้อสอบ
            $stmtExamSet = $conn->prepare("SELECT name FROM exam_set WHERE exam_set_id = ?");
            $stmtExamSet->execute([$examSetId]);
            $examSetName = $stmtExamSet->fetchColumn();
            
            // ส่งข้อมูลกลับ
            echo json_encode([
                'success' => true,
                'data' => [
                    'exam_set_id' => $examSetId,
                    'exam_set_name' => $examSetName,
                    'total_topics' => intval($totalTopics),
                    'active_topics' => intval($activeTopics),
                    'inactive_topics' => intval($inactiveTopics),
                    'total_questions' => intval($totalQuestions)
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ: ' . $e->getMessage()
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