<?php
// login-api.php - /api/login-api.php
header('Content-Type: application/json');

// เริ่มต้น session
session_start();

// ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// ตรวจสอบ CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token'
    ]);
    exit;
}

// รับค่าจากฟอร์ม
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

// ตรวจสอบว่าข้อมูลที่ส่งมาครบหรือไม่
if (empty($username) || empty($password) || empty($user_type)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
    ]);
    exit;
}

// ทำความสะอาดข้อมูล
$username = htmlspecialchars(trim($username));

// เชื่อมต่อฐานข้อมูล - แก้ไขเส้นทางตามโครงสร้างไฟล์ใหม่
require_once __DIR__ . '/../dbcon.php';

try {
    $conn = getDBConnection();
    
    // ตรวจสอบประเภทผู้ใช้
    if ($user_type === 'student') {
        // ตรวจสอบข้อมูลนักเรียน
        $stmt = $conn->prepare("SELECT student_id, student_code, username, password, firstname, lastname, status 
                              FROM student WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // ไม่พบข้อมูลผู้ใช้
            echo json_encode([
                'success' => false,
                'error_code' => 'user_not_found',
                'message' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'
            ]);
            exit;
        }
        
        // ตรวจสอบสถานะบัญชี
        if ($user['status'] != 1) {
            echo json_encode([
                'success' => false,
                'error_code' => 'account_disabled',
                'message' => 'บัญชีผู้ใช้นี้ถูกปิดการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'
            ]);
            exit;
        }
        
        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            // สร้าง session
            $_SESSION['user_id'] = $user['student_id'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['student_code'] = $user['student_code'];
            
            // บันทึกเวลาเข้าสู่ระบบล่าสุด (ถ้ามีคอลัมน์นี้)
            if ($conn->query("SHOW COLUMNS FROM student LIKE 'last_login'")->rowCount() > 0) {
                $loginStmt = $conn->prepare("UPDATE student SET last_login = NOW() WHERE student_id = ?");
                $loginStmt->execute([$user['student_id']]);
            }
            
            // สร้าง CSRF token ใหม่
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // ตอบกลับเป็น JSON ที่ถูกต้อง
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'redirect' => '/student/dashboard.php'
            ]);
            exit;
        } else {
            // รหัสผ่านไม่ถูกต้อง
            echo json_encode([
                'success' => false,
                'error_code' => 'invalid_password',
                'message' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'
            ]);
            exit;
        }
    } else if ($user_type === 'admin') {
        // ตรวจสอบข้อมูลผู้ดูแลระบบ
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // ไม่พบข้อมูลผู้ใช้
            echo json_encode([
                'success' => false,
                'error_code' => 'user_not_found',
                'message' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'
            ]);
            exit;
        }
        
        // ตรวจสอบสถานะบัญชี
        if ($user['status'] != 1) {
            echo json_encode([
                'success' => false,
                'error_code' => 'account_disabled',
                'message' => 'บัญชีผู้ดูแลระบบนี้ถูกปิดการใช้งาน กรุณาติดต่อผู้ดูแลระบบหลัก'
            ]);
            exit;
        }
        
        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            // สร้าง session
            $_SESSION['user_id'] = $user['admin_id'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            
            // บันทึกเวลาเข้าสู่ระบบล่าสุด (ถ้ามีคอลัมน์นี้)
            if ($conn->query("SHOW COLUMNS FROM admin LIKE 'last_login'")->rowCount() > 0) {
                $loginStmt = $conn->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                $loginStmt->execute([$user['admin_id']]);
            }
            
            // สร้าง CSRF token ใหม่
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // ตอบกลับเป็น JSON ที่ถูกต้อง
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'redirect' => '/admin/dashboard.php'
            ]);
            exit;
        } else {
            // รหัสผ่านไม่ถูกต้อง
            echo json_encode([
                'success' => false,
                'error_code' => 'invalid_password',
                'message' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'
            ]);
            exit;
        }
    } else {
        // ประเภทผู้ใช้ไม่ถูกต้อง
        echo json_encode([
            'success' => false,
            'error_code' => 'invalid_user_type',
            'message' => 'ประเภทผู้ใช้งานไม่ถูกต้อง'
        ]);
        exit;
    }
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database Error: " . $e->getMessage());
    
    // ตอบกลับเป็น JSON ที่ถูกต้อง
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error_code' => 'database_error',
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล: ' . $e->getMessage()
    ]);
    exit;
}