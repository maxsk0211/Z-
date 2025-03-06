<?php
// เริ่มต้น session
session_start();

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../dbcon.php';

// สร้าง CSRF token ถ้ายังไม่มี
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ดึงข้อมูลสถิติสำหรับแสดงใน stats cards
try {
    $conn = getDBConnection();
    
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
    
} catch (PDOException $e) {
    $totalExamSets = 0;
    $activeExamSets = 0;
    $inactiveExamSets = 0;
    $totalTopics = 0;
    $recentExamSets = 0;
    error_log("Error fetching stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html
  lang="th"
  class="light-style layout-menu-fixed layout-compact"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="horizontal-menu-template-no-customizer-starter">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>จัดการชุดข้อสอบ - ระบบสอบออนไลน์</title>

    <meta name="description" content="จัดการชุดข้อสอบในระบบสอบออนไลน์" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="../assets/vendor/fonts/remixicon/remixicon.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="../assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/rtl/core.css" />
    <link rel="stylesheet" href="../assets/vendor/css/rtl/theme-default.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/sweetalert2/sweetalert2.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/animate/animate.min.css" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="../assets/css/custom-admin.css" />

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>

  <body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
      <div class="loading-spinner"></div>
    </div>
  
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
      <div class="layout-container">
        <!-- Navbar -->
        <?php include 'navbar.php'; ?>
        <!-- / Navbar -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Menu -->
            <?php include 'menu.php'; ?>
            <!-- / Menu -->

            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
              <!-- Breadcrumb -->
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item">
                    <a href="dashboard.php"><i class="ri-home-line me-1"></i> หน้าหลัก</a>
                  </li>
                  <li class="breadcrumb-item">
                    <a href="javascript:void(0);">จัดการการสอบ</a>
                  </li>
                  <li class="breadcrumb-item active">จัดการชุดข้อสอบ</li>
                </ol>
              </nav>
              
              <!-- Stats Cards Section -->
              <div class="stats-cards animate__animated animate__fadeIn">
                <div class="stat-card bg-white">
                  <div class="stat-icon" style="background-color: rgba(93, 135, 255, 0.1); color: #5D87FF;">
                    <i class="ri-booklet-line"></i>
                  </div>
                  <div class="stat-details">
                    <h5><?= number_format($totalExamSets) ?></h5>
                    <p>ชุดข้อสอบทั้งหมด</p>
                  </div>
                </div>
                <div class="stat-card bg-white">
                  <div class="stat-icon" style="background-color: rgba(19, 222, 185, 0.1); color: #13DEB9;">
                    <i class="ri-check-line"></i>
                  </div>
                  <div class="stat-details">
                    <h5><?= number_format($activeExamSets) ?></h5>
                    <p>ชุดข้อสอบที่ใช้งาน</p>
                  </div>
                </div>
                <div class="stat-card bg-white">
                  <div class="stat-icon" style="background-color: rgba(250, 137, 107, 0.1); color: #FA896B;">
                    <i class="ri-close-line"></i>
                  </div>
                  <div class="stat-details">
                    <h5><?= number_format($inactiveExamSets) ?></h5>
                    <p>ชุดข้อสอบที่ไม่ใช้งาน</p>
                  </div>
                </div>
                <div class="stat-card bg-white">
                  <div class="stat-icon" style="background-color: rgba(100, 120, 225, 0.1); color: #6478E1;">
                    <i class="ri-list-check-2"></i>
                  </div>
                  <div class="stat-details">
                    <h5><?= number_format($totalTopics) ?></h5>
                    <p>หัวข้อการสอบทั้งหมด</p>
                  </div>
                </div>
              </div>
              
              <!-- Filter & View Toggle Section -->
              <div class="row mb-4 animate__animated animate__fadeIn animate__delay-1s">
                <div class="col-md-8 mb-3 mb-md-0">
                  <div class="filter-section">
                    <div class="row align-items-center">
                      <div class="col-md-3 mb-2 mb-md-0">
                        <select id="statusFilter" class="form-select">
                          <option value="">สถานะทั้งหมด</option>
                          <option value="1">ใช้งาน</option>
                          <option value="0">ไม่ใช้งาน</option>
                        </select>
                      </div>
                      <div class="col-md-3 mb-2 mb-md-0">
                        <select id="topicFilter" class="form-select">
                          <option value="">จำนวนหัวข้อทั้งหมด</option>
                          <option value="0">ไม่มีหัวข้อ</option>
                          <option value="1-5">1-5 หัวข้อ</option>
                          <option value="5+">มากกว่า 5 หัวข้อ</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <button id="applyFilter" class="btn btn-primary">
                          <i class="ri-filter-3-line me-1"></i> กรอง
                        </button>
                        <button id="resetFilter" class="btn btn-outline-secondary ms-1">
                          <i class="ri-refresh-line me-1"></i> รีเซ็ต
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 text-md-end">
                  <div class="view-toggle ms-auto d-inline-flex">
                    <button id="tableViewBtn" class="view-toggle-btn active" data-view="table">
                      <i class="ri-table-line me-1"></i> ตาราง
                    </button>
                    <button id="cardViewBtn" class="view-toggle-btn" data-view="card">
                      <i class="ri-layout-grid-line me-1"></i> การ์ด
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Main Card with DataTable -->
              <div class="card datatable-card animate__animated animate__fadeIn animate__delay-2s">
                <div class="datatable-header">
                  <h4><i class="ri-booklet-line"></i> จัดการชุดข้อสอบ</h4>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamSetModal">
                    <i class="ri-add-line me-1"></i> เพิ่มชุดข้อสอบ
                  </button>
                </div>
                <div class="card-body">
                  <!-- Table View -->
                  <div id="tableView" class="table-responsive">
                    <table class="table dt-responsive nowrap w-100" id="examSetTable">
                      <thead>
                        <tr>
                          <th width="5%">#</th>
                          <th width="20%">ชื่อชุดข้อสอบ</th>
                          <th width="25%">รายละเอียด</th>
                          <th width="10%">จำนวนหัวข้อ</th>
                          <th width="10%">สถานะ</th>
                          <th width="10%">สร้างเมื่อ</th>
                          <th width="10%">สร้างโดย</th>
                          <th width="15%">จัดการ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- ข้อมูลจะถูกเพิ่มผ่าน DataTables AJAX -->
                      </tbody>
                    </table>
                  </div>
                  
                  <!-- Card View -->
                  <div id="cardView" class="data-card-view" style="display: none;">
                    <!-- ข้อมูลจะถูกเพิ่มผ่าน JavaScript -->
                  </div>
                </div>
              </div>
            </div>
            <!-- / Content -->

            <!-- Modal เพิ่มชุดข้อสอบ -->
            <div class="modal fade" id="addExamSetModal" tabindex="-1" aria-labelledby="addExamSetModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addExamSetModalLabel">
                      <i class="ri