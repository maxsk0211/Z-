<?php
// stats-api.php - สำหรับดึงข้อมูลสถิติต่างๆ
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

// รับ action จาก request
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    // สถิติของชุดข้อสอบ
    case 'exam_stats':
        try {
            // จำนวนชุดข้อสอบทั้งหมด
            $totalExamSets = $conn->query("SELECT COUNT(*) FROM exam_set")->fetchColumn();
            
            // จำนวนชุดข้อสอบที่ใช้งาน
            $activeExamSets = $conn->query("SELECT COUNT(*) FROM exam_set WHERE status = 1")->fetchColumn();
            
            // จำนวนชุดข้อสอบที่ไม่ใช้งาน
            $inactiveExamSets = $totalExamSets - $activeExamSets;
            
            // จำนวนหัวข้อทั้งหมด
            $totalTopics = $conn->query("SELECT COUNT(*) FROM exam_topic")->fetchColumn();
            
            // จำนวนคำถามทั้งหมด
            $totalQuestions = $conn->query("SELECT COUNT(*) FROM question")->fetchColumn();
            
            // จำนวนชุดข้อสอบที่สร้างในรอบ 7 วัน
            $recentExamSets = $conn->query(
                "SELECT COUNT(*) FROM exam_set WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_exam_sets' => intval($totalExamSets),
                    'active_exam_sets' => intval($activeExamSets),
                    'inactive_exam_sets' => intval($inactiveExamSets),
                    'total_topics' => intval($totalTopics),
                    'total_questions' => intval($totalQuestions),
                    'recent_exam_sets' => intval($recentExamSets)
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    // สถิติของหัวข้อข้อสอบ
    case 'topic_stats':
        $exam_set_id = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
        
        if ($exam_set_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'รหัสชุดข้อสอบไม่ถูกต้อง']);
            exit;
        }
        
        try {
            // ข้อมูลชุดข้อสอบ
            $examSet = $conn->prepare("SELECT name FROM exam_set WHERE exam_set_id = ?");
            $examSet->execute([$exam_set_id]);
            $examSetName = $examSet->fetchColumn();
            
            if (!$examSetName) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบชุดข้อสอบนี้ในระบบ']);
                exit;
            }
            
            // จำนวนหัวข้อทั้งหมด
            $totalTopics = $conn->prepare(
                "SELECT COUNT(*) FROM exam_topic WHERE exam_set_id = ?"
            );
            $totalTopics->execute([$exam_set_id]);
            $totalTopicsCount = $totalTopics->fetchColumn();
            
            // จำนวนหัวข้อที่ใช้งาน
            $activeTopics = $conn->prepare(
                "SELECT COUNT(*) FROM exam_topic WHERE exam_set_id = ? AND status = 1"
            );
            $activeTopics->execute([$exam_set_id]);
            $activeTopicsCount = $activeTopics->fetchColumn();
            
            // จำนวนหัวข้อที่ไม่ใช้งาน
            $inactiveTopicsCount = $totalTopicsCount - $activeTopicsCount;
            
            // จำนวนคำถามทั้งหมด
            $totalQuestions = $conn->prepare("
                SELECT COUNT(q.question_id) 
                FROM question q
                JOIN exam_topic t ON q.topic_id = t.topic_id
                WHERE t.exam_set_id = ?
            ");
            $totalQuestions->execute([$exam_set_id]);
            $totalQuestionsCount = $totalQuestions->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'exam_set_id' => $exam_set_id,
                    'exam_set_name' => $examSetName,
                    'total_topics' => intval($totalTopicsCount),
                    'active_topics' => intval($activeTopicsCount),
                    'inactive_topics' => intval($inactiveTopicsCount),
                    'total_questions' => intval($totalQuestionsCount)
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'ไม่พบ action ที่ระบุ']);
        break;
}
?>