<?php
// database.php - /assets/config/database.php

// ข้อมูลการเชื่อมต่อฐานข้อมูล
$db_config = [
    'host' => 'localhost',
    'dbname' => 'ftp_ruts_test_ddns_net',
    'username' => 'ftp_ruts_test_ddns_net',
    'password' => 'eea4eaff3d922',
    'charset' => 'utf8mb4'
];

// ฟังก์ชั่นเชื่อมต่อฐานข้อมูล
function getDBConnection() {
    global $db_config;
    
    try {
        $conn = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}",
            $db_config['username'],
            $db_config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
    // ตั้งค่า character set เป็น UTF-8
    $conn->exec("SET NAMES utf8mb4");
    $conn->exec("SET CHARACTER SET utf8mb4");
    $conn->exec("SET character_set_connection=utf8mb4");
    
        return $conn;
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาดลงใน log
        error_log("Database Connection Error: " . $e->getMessage());
        
        // กรณีอยู่ในโหมด development ให้แสดงข้อผิดพลาด
        if (defined('DEV_MODE') && DEV_MODE) {
            die("Database Connection Error: " . $e->getMessage());
        } else {
            die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
        }
    }
}