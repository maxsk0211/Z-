<?php
// เริ่มต้น session
session_start();

// ถ้ายังไม่ได้เข้าสู่ระบบให้กลับไปที่หน้า login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// ฟังก์ชันตรวจสอบ token และทำการออกจากระบบ
function logout() {
    // ลบข้อมูล session ทั้งหมด
    $_SESSION = array();
    
    // ลบ session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // ทำลาย session
    session_destroy();
    
    // กลับไปที่หน้า login
    header('Location: login.php');
    exit;
}

// ถ้าเป็นการเรียกผ่าน POST (เช่น กดปุ่ม logout)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        logout();
    } else {
        // ถ้า token ไม่ตรงกัน ให้แสดงข้อความผิดพลาด
        die("Invalid security token");
    }
} 
// ถ้าเป็นการเรียกผ่าน GET (เช่น คลิกลิงก์ logout)
else {
    // บันทึกประเภทผู้ใช้ก่อนทำ logout เพื่อใช้ในการเปลี่ยนเส้นทาง
    $user_type = $_SESSION['user_type'];
    $user_name = $_SESSION['name'] ?? 'User';
    
    // ทำ logout
    logout();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกจากระบบ</title>
    
    <!-- Materialize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Google Font (Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f5f5f5;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        
        main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-container {
            max-width: 500px;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .logout-icon {
            font-size: 4rem;
            color: #4285f4;
            margin-bottom: 1rem;
        }
        
        .btn-login {
            background-color: #4285f4;
            margin-top: 1.5rem;
            border-radius: 4px;
        }
        
        .btn-login:hover {
            background-color: #3367d6;
        }
        
        .logout-form {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <main>
        <div class="container">
            <div class="logout-container">
                <i class="material-icons logout-icon">exit_to_app</i>
                <h4>ออกจากระบบ</h4>
                
                <p>คุณต้องการออกจากระบบใช่หรือไม่?</p>
                
                <form class="logout-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <button type="submit" class="btn waves-effect waves-light btn-login">
                        ยืนยันออกจากระบบ
                        <i class="material-icons right">send</i>
                    </button>
                </form>
                
                <p class="logout-links">
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                        <a href="admin/dashboard.php">กลับไปที่หน้าหลักผู้ดูแลระบบ</a>
                    <?php else: ?>
                        <a href="student/dashboard.php">กลับไปที่หน้าหลักนักเรียน</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </main>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>