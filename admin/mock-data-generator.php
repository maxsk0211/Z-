<?php
// create_sample_data.php - สร้างข้อมูลตัวอย่างสำหรับทดสอบ
header('Content-Type: text/html; charset=utf-8');

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../dbcon.php';

// ใช้เพื่อสร้างข้อมูลทดสอบสำหรับหน้า exam-topic-management.php
// คำเตือน: ไฟล์นี้จะสร้างข้อมูลตัวอย่างสำหรับทดสอบ ไม่ควรใช้บนเซิร์ฟเวอร์ระบบจริง

try {
    $conn = getDBConnection();
    echo "<h1>กำลังสร้างข้อมูลตัวอย่างสำหรับทดสอบ...</h1>";
    
    // ตรวจสอบว่ามีชุดข้อสอบอยู่แล้วหรือไม่
    $stmt = $conn->query("SELECT * FROM exam_set LIMIT 1");
    $examSetExists = $stmt->fetch();
    
    // ถ้าไม่มีชุดข้อสอบ ให้สร้างชุดข้อสอบใหม่
    $examSetId = 0;
    if (!$examSetExists) {
        echo "<p>สร้างชุดข้อสอบตัวอย่าง...</p>";
        
        $stmt = $conn->prepare("INSERT INTO exam_set (name, description, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
        $currentDateTime = date('Y-m-d H:i:s');
        $stmt->execute(['ข้อสอบครูชุดที่ 1', 'ข้อสอบวิชาชีพครูระดับนักศึกษา', 1, 1, $currentDateTime, $currentDateTime]);
        
        $examSetId = $conn->lastInsertId();
        echo "<p>สร้างชุดข้อสอบเรียบร้อยแล้ว ID: $examSetId</p>";
    } else {
        // ถ้ามีชุดข้อสอบอยู่แล้ว ให้ใช้ ID ของชุดข้อสอบแรก
        $examSetId = $examSetExists['exam_set_id'];
        echo "<p>ใช้ชุดข้อสอบที่มีอยู่แล้ว ID: $examSetId</p>";
    }
    
    // เคลียร์ข้อมูลเดิม (ถ้ามี) เพื่อป้องกันข้อมูลซ้ำซ้อน
    $conn->exec("DELETE FROM choice WHERE question_id IN (SELECT question_id FROM question WHERE topic_id IN (SELECT topic_id FROM exam_topic WHERE exam_set_id = $examSetId))");
    $conn->exec("DELETE FROM question WHERE topic_id IN (SELECT topic_id FROM exam_topic WHERE exam_set_id = $examSetId)");
    $conn->exec("DELETE FROM exam_topic WHERE exam_set_id = $examSetId");
    
    echo "<p>เคลียร์ข้อมูลเดิมเรียบร้อยแล้ว</p>";
    
    // สร้างหัวข้อข้อสอบ
    $topics = [
        [
            'name' => 'ภาษาไทยเพื่ออาชีพครู',
            'description' => 'ภาษาไทยเพื่ออาชีพครูเบื้องต้น',
            'status' => 1
        ],
        [
            'name' => 'จิตวิทยาเพื่ออาชีพครู',
            'description' => 'จิตวิทยาเบื้องต้น',
            'status' => 1
        ],
        [
            'name' => 'การพัฒนาระบบจัดการการสอบ RUTS TEST',
            'description' => 'การพัฒนาระบบสอบออนไลน์',
            'status' => 0
        ]
    ];
    
    $topicIds = [];
    
    foreach ($topics as $topic) {
        $stmt = $conn->prepare("INSERT INTO exam_topic (exam_set_id, name, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
        $currentDateTime = date('Y-m-d H:i:s');
        $stmt->execute([$examSetId, $topic['name'], $topic['description'], $topic['status'], $currentDateTime, $currentDateTime]);
        
        $topicIds[] = $conn->lastInsertId();
    }
    
    echo "<p>สร้างหัวข้อข้อสอบเรียบร้อยแล้ว จำนวน " . count($topicIds) . " หัวข้อ</p>";
    
    // สร้างคำถามและตัวเลือก
    $questions = [
        // คำถามสำหรับหัวข้อที่ 1 (ภาษาไทยเพื่ออาชีพครู)
        [
            'topic_id' => $topicIds[0],
            'content' => '<p>ข้อใดเป็นพยัญชนะในภาษาไทย</p>',
            'score' => 1,
            'status' => 1,
            'choices' => [
                ['content' => 'ก ข ค ง', 'is_correct' => 1],
                ['content' => '1 2 3 4', 'is_correct' => 0],
                ['content' => '∞ ≠ ≤ ≥', 'is_correct' => 0],
                ['content' => '♥ ♦ ♣ ♠', 'is_correct' => 0]
            ]
        ],
        [
            'topic_id' => $topicIds[0],
            'content' => '<p>จงเลือกคำในข้อใดเขียนถูกต้อง</p>',
            'score' => 1,
            'status' => 1,
            'choices' => [
                ['content' => 'กระเพรา', 'is_correct' => 0],
                ['content' => 'กะเพรา', 'is_correct' => 1],
                ['content' => 'กระเพา', 'is_correct' => 0],
                ['content' => 'กะเภา', 'is_correct' => 0]
            ]
        ],
        
        // คำถามสำหรับหัวข้อที่ 2 (จิตวิทยาเพื่ออาชีพครู)
        [
            'topic_id' => $topicIds[1],
            'content' => '<p>วิชาจิตวิทยามีความสำคัญอย่างไรในอาชีพครู</p>',
            'score' => 1,
            'status' => 1,
            'choices' => [
                ['content' => 'ช่วยให้วางแผนการสอนได้ดีขึ้น', 'is_correct' => 0],
                ['content' => 'ช่วยให้เข้าใจพัฒนาการของเด็ก', 'is_correct' => 1],
                ['content' => 'ช่วยให้ออกข้อสอบได้ดี', 'is_correct' => 0],
                ['content' => 'ไม่มีประโยชน์กับอาชีพครู', 'is_correct' => 0]
            ]
        ],
        
        // หัวข้อที่ 3 ไม่มีคำถาม
    ];
    
    foreach ($questions as $question) {
        $stmt = $conn->prepare("INSERT INTO question (topic_id, content, score, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
        $currentDateTime = date('Y-m-d H:i:s');
        $stmt->execute([$question['topic_id'], $question['content'], $question['score'], $question['status'], $currentDateTime, $currentDateTime]);
        
        $questionId = $conn->lastInsertId();
        
        foreach ($question['choices'] as $choice) {
            $stmt = $conn->prepare("INSERT INTO choice (question_id, content, is_correct, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$questionId, $choice['content'], $choice['is_correct'], $currentDateTime, $currentDateTime]);
        }
    }
    
    echo "<p>สร้างคำถามและตัวเลือกเรียบร้อยแล้ว จำนวน " . count($questions) . " คำถาม</p>";
    
    echo "<h2>สร้างข้อมูลตัวอย่างเสร็จสมบูรณ์!</h2>";
    echo "<p>คุณสามารถทดสอบหน้า exam-topic-management.php ด้วยข้อมูลตัวอย่างเหล่านี้ได้แล้ว</p>";
    echo "<p><a href='exam-topic-management.php?exam_set_id=$examSetId' class='btn btn-primary'>ไปยังหน้าจัดการหัวข้อข้อสอบ</a></p>";
    
} catch (PDOException $e) {
    echo "<h1>เกิดข้อผิดพลาด!</h1>";
    echo "<p>ข้อความผิดพลาด: " . $e->getMessage() . "</p>";
}
?>