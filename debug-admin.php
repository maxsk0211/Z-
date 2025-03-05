<?php
// debug-admin.php - สคริปต์สำหรับตรวจสอบและแก้ไขปัญหาการ login ของ admin
exit();
// แสดงข้อผิดพลาดทั้งหมด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เชื่อมต่อฐานข้อมูล
require_once 'dbcon.php';

echo "<h2>Debug Admin Login</h2>";

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDBConnection();
    echo "<p style='color:green'>✓ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";
    
    // ตรวจสอบว่ามีตาราง admin หรือไม่
    $tables = $conn->query("SHOW TABLES LIKE 'admin'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color:green'>✓ พบตาราง admin ในฐานข้อมูล</p>";
        
        // ตรวจสอบโครงสร้างตาราง admin
        echo "<h3>โครงสร้างตาราง admin:</h3>";
        echo "<pre>";
        $columns = $conn->query("DESCRIBE admin")->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
        echo "</pre>";
        
        // ตรวจสอบว่ามี user admin หรือไม่
        $stmt = $conn->prepare("SELECT admin_id, username, name, email FROM admin WHERE username = ?");
        $stmt->execute(['admin']);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p style='color:green'>✓ พบข้อมูล admin ในฐานข้อมูล</p>";
            echo "<pre>";
            print_r($user);
            echo "</pre>";
            
            // ทดสอบตรวจสอบรหัสผ่านด้วยรหัส admin123
            $stmt = $conn->prepare("SELECT password FROM admin WHERE username = ?");
            $stmt->execute(['admin']);
            $hash = $stmt->fetchColumn();
            
            echo "<h3>ตรวจสอบรหัสผ่าน:</h3>";
            echo "Stored hash: " . $hash . "<br>";
            $test_password = 'admin123';
            $verify_result = password_verify($test_password, $hash);
            echo "Password verify result for 'admin123': " . ($verify_result ? "✓ ถูกต้อง" : "✗ ไม่ถูกต้อง") . "<br>";
            
            // หากรหัสผ่านไม่ถูกต้อง ให้สร้างรหัสใหม่
            if (!$verify_result) {
                echo "<h3>สร้างรหัสผ่านใหม่สำหรับ admin:</h3>";
                $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
                echo "รหัสผ่านใหม่ที่เข้ารหัสแล้ว: " . $new_hash . "<br>";
                
                // อัปเดตรหัสผ่านใหม่
                $update = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
                $update_result = $update->execute([$new_hash, 'admin']);
                
                if ($update_result) {
                    echo "<p style='color:green'>✓ อัปเดตรหัสผ่านสำเร็จ</p>";
                    echo "<p>ลองเข้าสู่ระบบด้วย:</p>";
                    echo "<ul>";
                    echo "<li>Username: admin</li>";
                    echo "<li>Password: admin123</li>";
                    echo "</ul>";
                } else {
                    echo "<p style='color:red'>✗ อัปเดตรหัสผ่านไม่สำเร็จ</p>";
                }
            }
        } else {
            echo "<p style='color:red'>✗ ไม่พบข้อมูล admin ในฐานข้อมูล</p>";
            
            // เพิ่ม admin ใหม่
            echo "<h3>สร้าง admin ใหม่:</h3>";
            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            
            $insert = $conn->prepare("
                INSERT INTO admin (username, password, name, email) 
                VALUES (?, ?, ?, ?)
            ");
            
            $insert_result = $insert->execute([
                'admin',
                $new_hash,
                'ผู้ดูแลระบบ',
                'admin@example.com'
            ]);
            
            if ($insert_result) {
                echo "<p style='color:green'>✓ สร้าง admin สำเร็จ</p>";
                echo "<p>ลองเข้าสู่ระบบด้วย:</p>";
                echo "<ul>";
                echo "<li>Username: admin</li>";
                echo "<li>Password: admin123</li>";
                echo "</ul>";
            } else {
                echo "<p style='color:red'>✗ สร้าง admin ไม่สำเร็จ</p>";
            }
        }
    } else {
        echo "<p style='color:red'>✗ ไม่พบตาราง admin ในฐานข้อมูล</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}