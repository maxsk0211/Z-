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

// ตรวจสอบว่ามีการส่ง exam_set_id มาหรือไม่
$exam_set_id = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;
if ($exam_set_id <= 0) {
    header('Location: exam-set-management.php');
    exit;
}

// ดึงข้อมูลชุดข้อสอบเพื่อแสดงชื่อ
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT name FROM exam_set WHERE exam_set_id = ?");
    $stmt->execute([$exam_set_id]);
    $exam_set = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam_set) {
        header('Location: exam-set-management.php');
        exit;
    }
    
    $exam_set_name = $exam_set['name'];
} catch (PDOException $e) {
    $exam_set_name = 'ไม่พบชื่อชุดข้อสอบ';
    error_log("Error fetching exam set: " . $e->getMessage());
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

    <title>จัดการหัวข้อข้อสอบ - <?= htmlspecialchars($exam_set_name) ?> - ระบบสอบออนไลน์</title>

    <meta name="description" content="จัดการหัวข้อข้อสอบในระบบสอบออนไลน์" />

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
    <!-- <link rel="stylesheet" href="../assets/vendor/libs/summernote/summernote-bs5.min.css" /> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs5.min.css" integrity="sha512-rDHV59PgRefDUbMm2lSjvf0ZhXZy3wgROFyao0JxZPGho3oOuWejq/ELx0FOZJpgaE5QovVtRN65Y3rrb7JhdQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" integrity="sha512-c42qTSw/wPZ3/5LBzD+Bw5f7bSF2oxou6wEb+I/lqeaKV5FDIfMvvRp772y4jcJLKuGUOpbJMdg/BTl50fJYAw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Page CSS -->
    <style>
      body {
        font-family: 'Kanit', sans-serif;
      }
      
      /* Split layout */
      .split-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
      }
      
      .split-left {
        flex: 1;
        min-width: 320px;
        max-width: 450px;
      }
      
      .split-right {
        flex: 2;
        min-width: 320px;
      }
      
      @media (max-width: 991.98px) {
        .split-left, .split-right {
          flex: 100%;
          max-width: 100%;
        }
      }
      
      /* Topic list */
      .topic-list {
        height: calc(100vh - 300px);
        overflow-y: auto;
        padding-right: 5px;
      }
      
      .topic-card {
        margin-bottom: 1rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
        border-left: 4px solid transparent;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      }
      
      .topic-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      }
      
      .topic-card.active {
        border-left-color: #5D87FF;
        background-color: rgba(93, 135, 255, 0.05);
      }
      
      .topic-card .card-body {
        padding: 1rem;
      }
      
      .topic-title {
        font-size: 1.1rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      
      .topic-description {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 40px;
      }
      
      .topic-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.75rem;
        font-size: 0.85rem;
      }
      
      .question-count {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 30px;
        font-size: 0.75rem;
        background-color: rgba(93, 135, 255, 0.1);
        color: #5D87FF;
      }
      
      .topic-actions {
        display: flex;
        gap: 0.5rem;
      }
      
      .topic-actions button {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        border-radius: 5px;
      }
      
      /* Status badges */
      .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 5px;
        font-size: 0.8rem;
        font-weight: 500;
      }
      
      .status-active {
        background-color: #E8F5E9;
        color: #2E7D32;
      }
      
      .status-inactive {
        background-color: #FFEBEE;
        color: #C62828;
      }
      
      /* Question Panel */
      .question-panel {
        height: calc(100vh - 300px);
        overflow-y: auto;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      }
      
      .question-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        background-color: #fff;
        z-index: 10;
      }
      
      .question-panel-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0;
      }
      
      .question-item {
        padding: 1rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        transition: all 0.2s ease;
      }
      
      .question-item:hover {
        background-color: rgba(0,0,0,0.01);
      }
      
      .question-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #E3F2FD;
        color: #1565C0;
        font-weight: 500;
        margin-right: 10px;
      }
      
      .question-content {
        font-weight: 500;
        margin-bottom: 0.5rem;
      }
      
      .choice-list {
        padding-left: 40px;
        margin-bottom: 0.5rem;
      }
      
      .choice-item {
        margin-bottom: 0.25rem;
        display: flex;
        align-items: flex-start;
      }
      
      .choice-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid #E0E0E0;
        margin-right: 8px;
      }
      
      .choice-correct {
        background-color: #E8F5E9;
        border-color: #2E7D32;
        color: #2E7D32;
      }
      
      .question-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0.5rem;
        padding-left: 40px;
        font-size: 0.9rem;
        color: #6c757d;
      }
      
      .question-actions {
        display: flex;
        gap: 0.5rem;
      }
      
      .question-actions button {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        border-radius: 5px;
      }
      
      .no-topics-placeholder, .no-topic-selected-placeholder, .no-questions-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1rem;
        text-align: center;
        color: #6c757d;
        height: 100%;
      }
      
      .placeholder-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #E0E0E0;
      }
      
      .placeholder-text {
        font-size: 1.1rem;
        margin-bottom: 1rem;
      }
      
      .placeholder-subtext {
        font-size: 0.9rem;
        max-width: 300px;
        margin-bottom: 1.5rem;
      }
      
      /* Cards */
      .card-header {
        padding: 1.2rem 1.5rem;
        background-color: rgba(93, 135, 255, 0.05);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      }
      
      .card-action-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0;
      }
      
      /* Loading overlay */
      .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        visibility: hidden;
        opacity: 0;
        transition: all 0.3s ease;
      }
      
      .loading-overlay.show {
        visibility: visible;
        opacity: 1;
      }
      
      .loading-spinner {
        width: 50px;
        height: 50px;
        border: 3px solid rgba(93, 135, 255, 0.2);
        border-radius: 50%;
        border-top-color: #5D87FF;
        animation: spin 1s linear infinite;
      }
      
      @keyframes spin {
        to { transform: rotate(360deg); }
      }
      
      /* Summernote custom styling */
      .note-editor.note-frame {
        border-color: #E0E0E0;
        border-radius: 0.375rem;
      }
      
      .note-editor.note-frame .note-statusbar {
        border-top-color: #E0E0E0;
      }
      
      .note-editor.note-frame .note-editing-area .note-editable {
        min-height: 120px;
        font-family: 'Kanit', sans-serif;
      }
      
      /* Form controls */
      .form-label {
        font-weight: 500;
      }
      
      .required-indicator {
        color: #C62828;
        margin-left: 3px;
      }
      
      /* Choice management in modal */
      .choice-container {
        margin-bottom: 1rem;
        border: 1px solid #E0E0E0;
        border-radius: 0.375rem;
        padding: 1rem;
      }
      
      .choice-row {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1rem;
      }
      
      .choice-row:last-child {
        margin-bottom: 0;
      }
      
      .choice-label-col {
        flex: 0 0 40px;
        text-align: center;
      }
      
      .choice-content-col {
        flex: 1;
      }
      
      .choice-correct-col {
        flex: 0 0 80px;
        text-align: center;
        padding-top: 0.5rem;
      }
      
      .choice-remove-col {
        flex: 0 0 50px;
        text-align: center;
        padding-top: 0.5rem;
      }
      
      /* ปุ่มเพิ่มตัวเลือก */
      .add-choice-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        background-color: rgba(93, 135, 255, 0.1);
        color: #5D87FF;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      
      .add-choice-btn:hover {
        background-color: rgba(93, 135, 255, 0.2);
      }
      
      .add-choice-btn i {
        margin-right: 0.5rem;
      }
      
      /* Custom SweetAlert2 Styling */
      .custom-swal-popup {
        border-radius: 15px !important;
        padding: 1.5rem !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        font-family: 'Kanit', sans-serif !important;
      }
      
      .custom-swal-title {
        font-family: 'Kanit', sans-serif !important;
        font-size: 1.75rem !important;
        font-weight: 600 !important;
        margin-bottom: 0.75rem !important;
      }
      
      .custom-swal-content {
        font-family: 'Kanit', sans-serif !important;
        font-size: 1.1rem !important;
        margin-bottom: 1rem !important;
      }
      
      /* Fixed action button for mobile */
      .fab-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 999;
        display: none;
      }
      
      .fab-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #5D87FF;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        cursor: pointer;
        transition: all 0.3s ease;
      }
      
      .fab-button:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 15px rgba(0,0,0,0.3);
      }
      
      @media (max-width: 767.98px) {
        .fab-container {
          display: block;
        }
      }
      
      /* Data tables customization */
      .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.5rem;
      }
      
      .dataTables_wrapper .dataTables_filter input {
        border-radius: 0.375rem;
        border: 1px solid #E0E0E0;
        padding: 0.5rem;
      }
      
      .dataTables_wrapper .dataTables_length select {
        border-radius: 0.375rem;
        border: 1px solid #E0E0E0;
        padding: 0.5rem;
      }
      
      /* Box container animations */
      .animate-box {
        animation-duration: 0.5s;
      }
      
      /* Responsive adjustments */
      @media (max-width: 767.98px) {
        .topic-list,
        .question-panel {
          height: auto;
          max-height: 500px;
        }
        
        .card-header {
          padding: 1rem;
        }
        
        .choice-row {
          flex-wrap: wrap;
        }
        
        .choice-correct-col,
        .choice-remove-col {
          flex: 0 0 50%;
          text-align: left;
          padding-top: 0.5rem;
          margin-top: 0.5rem;
        }
      }
      
      /* Custom tooltip */
      .tooltip-inner {
        max-width: 200px;
        padding: 0.5rem;
        font-family: 'Kanit', sans-serif;
        font-size: 0.8rem;
        background-color: #333;
        border-radius: 0.375rem;
      }
    </style>

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
              <div class="mb-4">
                <nav aria-label="breadcrumb">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                      <a href="dashboard.php"><i class="ri-home-line me-1"></i> หน้าหลัก</a>
                    </li>
                    <li class="breadcrumb-item">
                      <a href="exam-set-management.php">จัดการชุดข้อสอบ</a>
                    </li>
                    <li class="breadcrumb-item active">จัดการหัวข้อข้อสอบ: <?= htmlspecialchars($exam_set_name) ?></li>
                  </ol>
                </nav>
                <h4 class="fw-bold mb-0 d-flex align-items-center">
                  <i class="ri-list-check-2 me-2"></i> 
                  จัดการหัวข้อข้อสอบ: <?= htmlspecialchars($exam_set_name) ?>
                </h4>
              </div>
              
              <!-- Split Layout -->
              <div class="split-container">
                <!-- ส่วนซ้าย - หัวข้อข้อสอบ -->
                <div class="split-left animate__animated animate__fadeIn">
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="card-action-title mb-0">หัวข้อข้อสอบ</h5>
                      <button type="button" class="btn btn-primary btn-sm" id="addTopicBtn">
                        <i class="ri-add-line me-1"></i> เพิ่มหัวข้อ
                      </button>
                    </div>
                    <div class="card-body p-0">
                      <div class="topic-list" id="topicList">
                        <!-- รายการหัวข้อจะถูกเพิ่มผ่าน JavaScript -->
                        <div class="no-topics-placeholder" id="noTopicsPlaceholder">
                          <i class="ri-list-check-2 placeholder-icon"></i>
                          <p class="placeholder-text">ยังไม่มีหัวข้อข้อสอบ</p>
                          <p class="placeholder-subtext">คลิกปุ่ม "เพิ่มหัวข้อ" เพื่อเริ่มสร้างหัวข้อข้อสอบใหม่</p>
                          <button type="button" class="btn btn-primary" id="addTopicBtnPlaceholder">
                            <i class="ri-add-line me-1"></i> เพิ่มหัวข้อ
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- ส่วนขวา - คำถามในหัวข้อ -->
                <div class="split-right animate__animated animate__fadeIn animate__delay-1s">
                  <div class="card mb-4">
                    <div class="question-panel" id="questionPanel">
                      <div class="no-topic-selected-placeholder" id="noTopicSelectedPlaceholder">
                        <i class="ri-question-mark placeholder-icon"></i>
                        <p class="placeholder-text">กรุณาเลือกหัวข้อข้อสอบ</p>
                        <p class="placeholder-subtext">เลือกหัวข้อข้อสอบจากรายการด้านซ้ายเพื่อดูคำถามในหัวข้อนั้น</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- รายการข้อสอบทั้งหมด -->
              <div class="card animate__animated animate__fadeIn animate__delay-2s">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="card-action-title mb-0">รายการคำถามทั้งหมดในชุดข้อสอบนี้</h5>
                  <div>
                    <a href="exam-set-management.php" class="btn btn-outline-secondary btn-sm me-2">
                      <i class="ri-arrow-left-line me-1"></i> กลับไปยังชุดข้อสอบ
                    </a>
                    <button type="button" class="btn btn-info btn-sm" id="exportQuestionsBtn">
                      <i class="ri-file-excel-2-line me-1"></i> ส่งออก
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="allQuestionsTable">
                      <thead>
                        <tr>
                          <th width="5%">#</th>
                          <th width="20%">หัวข้อ</th>
                          <th width="30%">คำถาม</th>
                          <th width="15%">ตัวเลือก</th>
                          <th width="10%">คำตอบที่ถูก</th>
                          <th width="10%">คะแนน</th>
                          <th width="10%">จัดการ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- ข้อมูลจะถูกเพิ่มผ่าน JavaScript -->
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <!-- / Content -->
            
            <!-- Fixed Action Button (for mobile) -->
            <div class="fab-container">
              <div class="fab-button" id="addQuestionFabBtn">
                <i class="ri-add-line"></i>
              </div>
            </div>

            <!-- Modal เพิ่ม/แก้ไขหัวข้อข้อสอบ -->
            <div class="modal fade" id="topicModal" tabindex="-1" aria-labelledby="topicModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="topicModalLabel">เพิ่มหัวข้อข้อสอบใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="topicForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" name="exam_set_id" value="<?= $exam_set_id ?>">
                      <input type="hidden" id="topic_id" name="topic_id" value="0">
                      <input type="hidden" id="action" name="action" value="create">
                      
                      <div class="mb-3">
                        <label for="name" class="form-label">ชื่อหัวข้อ<span class="required-indicator">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">กรุณาระบุชื่อหัวข้อ</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียด</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                      </div>
                      
                      <div class="mb-3">
                        <label for="status" class="form-label">สถานะ</label>
                        <select class="form-select" id="status" name="status">
                          <option value="1">ใช้งาน</option>
                          <option value="0">ไม่ใช้งาน</option>
                        </select>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="ri-close-line me-1"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" id="saveTopicBtn">
                      <i class="ri-save-line me-1"></i> บันทึก
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal เพิ่ม/แก้ไขคำถาม -->
            <div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="questionModalLabel">เพิ่มคำถามใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="questionForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" id="question_topic_id" name="topic_id" value="0">
                      <input type="hidden" id="question_id" name="question_id" value="0">
                      <input type="hidden" id="question_action" name="action" value="create">
                      
                      <div class="mb-3">
                        <label for="content" class="form-label">เนื้อหาคำถาม<span class="required-indicator">*</span></label>
                        <textarea class="form-control" id="content" name="content" required></textarea>
                        <div class="invalid-feedback">กรุณาระบุเนื้อหาคำถาม</div>
                      </div>
                      
                      <div class="mb-3">
                        <label class="form-label">รูปภาพประกอบคำถาม (ถ้ามี)</label>
                        <div class="input-group">
                          <input type="file" class="form-control" id="image" name="image" accept="image/*">
                          <button class="btn btn-outline-secondary" type="button" id="clearImageBtn">ล้างค่า</button>
                        </div>
                        <small class="text-muted">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 2MB</small>
                        <div id="currentImageContainer" class="mt-2 d-none">
                          <p class="mb-1">รูปภาพปัจจุบัน:</p>
                          <img id="currentImage" src="" alt="รูปภาพประกอบคำถาม" class="img-fluid img-thumbnail" style="max-height: 150px;">
                          <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="removeImage" name="remove_image">
                            <label class="form-check-label" for="removeImage">ลบรูปภาพนี้</label>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label class="form-label">ตัวเลือกคำตอบ<span class="required-indicator">*</span></label>
                        <div class="choice-container" id="choiceContainer">
                          <!-- Choice items will be added here dynamically -->
                        </div>
                        <button type="button" class="add-choice-btn" id="addChoiceBtn">
                          <i class="ri-add-line"></i> เพิ่มตัวเลือก
                        </button>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="score" class="form-label">คะแนน</label>
                          <input type="number" class="form-control" id="score" name="score" min="0" step="0.5" value="1">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                          <label for="question_status" class="form-label">สถานะ</label>
                          <select class="form-select" id="question_status" name="status">
                            <option value="1">ใช้งาน</option>
                            <option value="0">ไม่ใช้งาน</option>
                          </select>
                        </div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="ri-close-line me-1"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" id="saveQuestionBtn">
                      <i class="ri-save-line me-1"></i> บันทึก
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <?php include 'footer.php'; ?>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!--/ Content wrapper -->
        </div>
        <!--/ Layout container -->
      </div>
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
    
    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
    
    <!--/ Layout wrapper -->

    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/libs/hammer/hammer.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    
    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="../assets/vendor/libs/sweetalert2/sweetalert2.js"></script>
    <!-- <script src="../assets/vendor/libs/summernote/summernote-bs5.min.js"></script> -->
    
    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>
    
    <!-- Page JS -->
    <script>
    $(document).ready(function() {
        // Custom SweetAlert2 config
        const swalCustom = Swal.mixin({
            customClass: {
                popup: 'custom-swal-popup',
                title: 'custom-swal-title',
                content: 'custom-swal-content'
            },
            buttonsStyling: true,
            confirmButtonText: '<i class="ri-check-line me-1"></i> ตกลง',
            cancelButtonText: '<i class="ri-close-line me-1"></i> ยกเลิก',
            showClass: {
                popup: 'animate__animated animate__fadeInUp animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutDown animate__faster'
            }
        });
        
        // Base URL ของระบบ
        const baseUrl = '../';
        
        // Exam Set ID
        const examSetId = <?= $exam_set_id ?>;
        
        // รายการตัวเลือกในแบบฟอร์มคำถาม
        let choiceLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        
        // Current Topic ID
        let currentTopicId = 0;
        let currentTopicName = '';
        
        // Function to show loading overlay
        function showLoading() {
            $('#loadingOverlay').addClass('show');
        }
        
        // Function to hide loading overlay
        function hideLoading() {
            $('#loadingOverlay').removeClass('show');
        }
        
        // Initialize rich text editor
        $('#content').summernote({
            placeholder: 'เขียนคำถามที่นี่...',
            height: 150,
            toolbar: [
              ['style', ['style']],
              ['font', ['bold', 'underline', 'clear']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph']],
              ['insert', ['link']],
              ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    // แจ้งเตือนให้ใช้ช่องอัปโหลดรูปภาพแทน
                    swalCustom.fire({
                        icon: 'info',
                        title: 'ใช้ช่องอัปโหลดรูปภาพด้านล่าง',
                        text: 'กรุณาใช้ช่องอัปโหลดรูปภาพประกอบคำถามที่อยู่ด้านล่างแทน'
                    });
                }
            }
        });
        
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // โหลดรายการหัวข้อข้อสอบทั้งหมด
        function loadTopics() {
            showLoading();
            
            $.ajax({
                url: 'api/exam-topic-api.php?action=list&exam_set_id=' + examSetId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const topics = response.data;
                        
                        // ตรวจสอบว่ามีหัวข้อหรือไม่
                        if (topics.length === 0) {
                            $('#noTopicsPlaceholder').show();
                            $('#topicList').html('');
                        } else {
                            $('#noTopicsPlaceholder').hide();
                            
                            let html = '';
                            
                            topics.forEach(function(topic) {
                                const statusBadgeClass = topic.status == 1 ? 'status-active' : 'status-inactive';
                                const statusText = topic.status == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
                                
                                html += `
                                <div class="topic-card card ${currentTopicId === parseInt(topic.topic_id) ? 'active' : ''}" 
                                     data-id="${topic.topic_id}" data-name="${topic.name}">
                                    <div class="card-body">
                                        <h5 class="topic-title">${topic.name}</h5>
                                        <p class="topic-description">${topic.description || '-'}</p>
                                        <div class="topic-footer">
                                            <div>
                                                <span class="question-count"><i class="ri-question-line me-1"></i>${topic.question_count} คำถาม</span>
                                                <span class="status-badge ${statusBadgeClass} ms-2">${statusText}</span>
                                            </div>
                                            <div class="topic-actions">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-topic-btn"
                                                        data-id="${topic.topic_id}"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-title="แก้ไขหัวข้อ">
                                                    <i class="ri-pencil-line"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-topic-btn"
                                                        data-id="${topic.topic_id}"
                                                        data-name="${topic.name}"
                                                        data-question-count="${topic.question_count}"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-title="ลบหัวข้อ">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                `;
                            });
                            
                            $('#topicList').html(html);
                            
                            // Reinitialize tooltips
                            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                                return new bootstrap.Tooltip(tooltipTriggerEl);
                            });
                            
                            // ถ้ามีการเลือกหัวข้อไว้แล้วให้โหลดคำถามในหัวข้อนั้น
                            if (currentTopicId > 0) {
                                loadQuestions(currentTopicId, currentTopicName);
                            }
                        }
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถโหลดรายการหัวข้อข้อสอบได้'
                        });
                    }
                },
                error: function() {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // โหลดคำถามในหัวข้อที่เลือก
        function loadQuestions(topicId, topicName) {
            showLoading();
            
            currentTopicId = topicId;
            currentTopicName = topicName;
            
            $.ajax({
                url: 'api/question-api.php?action=list_by_topic&topic_id=' + topicId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const questions = response.data;
                        
                        let html = `
                        <div class="question-header">
                            <h5 class="question-panel-title">คำถามในหัวข้อ: ${topicName}</h5>
                            <button type="button" class="btn btn-primary add-question-btn" data-topic-id="${topicId}">
                                <i class="ri-add-line me-1"></i> เพิ่มคำถาม
                            </button>
                        </div>
                        `;
                        
                        if (questions.length === 0) {
                            html += `
                            <div class="no-questions-placeholder">
                                <i class="ri-question-mark placeholder-icon"></i>
                                <p class="placeholder-text">ยังไม่มีคำถามในหัวข้อนี้</p>
                                <p class="placeholder-subtext">คลิกปุ่ม "เพิ่มคำถาม" เพื่อเริ่มสร้างคำถามใหม่</p>
                                <button type="button" class="btn btn-primary add-question-btn" data-topic-id="${topicId}">
                                    <i class="ri-add-line me-1"></i> เพิ่มคำถาม
                                </button>
                            </div>
                            `;
                        } else {
                            for (let i = 0; i < questions.length; i++) {
                                const question = questions[i];
                                const questionNumber = i + 1;
                                const statusBadgeClass = question.status == 1 ? 'status-active' : 'status-inactive';
                                const statusText = question.status == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
                                
                                html += `
                                <div class="question-item">
                                    <div class="d-flex align-items-start">
                                        <span class="question-number">${questionNumber}</span>
                                        <div class="flex-grow-1">
                                            <div class="question-content">${question.content}</div>
                                        </div>
                                    </div>
                                    ${question.image ? `<div class="ms-4 mt-2 mb-2"><img src="${baseUrl + question.image}" alt="รูปภาพประกอบคำถาม" class="img-fluid img-thumbnail" style="max-height: 150px;"></div>` : ''}
                                    <div class="choice-list">
                                `;
                                
                                for (let j = 0; j < question.choices.length; j++) {
                                    const choice = question.choices[j];
                                    const choiceLetter = choiceLetters[j];
                                    
                                    html += `
                                    <div class="choice-item">
                                        <span class="choice-label ${choice.is_correct == 1 ? 'choice-correct' : ''}">${choiceLetter}</span>
                                        <div>${choice.content}</div>
                                    </div>
                                    `;
                                }
                                
                                html += `
                                    </div>
                                    <div class="question-meta">
                                        <div>
                                            <span>คะแนน: ${question.score} คะแนน</span>
                                            <span class="ms-3 status-badge ${statusBadgeClass}">${statusText}</span>
                                        </div>
                                        <div class="question-actions">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-question-btn"
                                                    data-id="${question.question_id}"
                                                    data-topic-id="${topicId}"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-title="แก้ไขคำถาม">
                                                <i class="ri-pencil-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-question-btn"
                                                    data-id="${question.question_id}"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-title="ลบคำถาม">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                `;
                            }
                        }
                        
                        $('#questionPanel').html(html);
                        
                        // Reinitialize tooltips
                        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                        
                        // ไฮไลท์หัวข้อที่เลือก
                        $('.topic-card').removeClass('active');
                        $(`.topic-card[data-id="${topicId}"]`).addClass('active');
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถโหลดรายการคำถามได้'
                        });
                    }
                },
                error: function() {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // โหลดคำถามทั้งหมดในชุดข้อสอบ
        function loadAllQuestions() {
            showLoading();
            
            $.ajax({
                url: 'api/question-api.php?action=list_by_exam_set&exam_set_id=' + examSetId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        // ตรวจสอบว่าตาราง DataTable ถูกสร้างไว้แล้วหรือไม่
                        if ($.fn.DataTable.isDataTable('#allQuestionsTable')) {
                            $('#allQuestionsTable').DataTable().destroy();
                        }
                        
                        // สร้างตาราง DataTable ใหม่
                        $('#allQuestionsTable').DataTable({
                            data: response.data,
                            columns: [
                                { 
                                    data: null, 
                                    render: function(data, type, row, meta) {
                                        return meta.row + 1;
                                    }
                                },
                                { 
                                    data: 'topic_name',
                                    render: function(data, type, row) {
                                        return data || '-';
                                    }
                                },
                                { 
                                    data: 'content',
                                    render: function(data, type, row) {
                                        if (type === 'display') {
                                            // Remove HTML tags for display
                                            return $('<div>').html(data).text().substring(0, 100) + (data.length > 100 ? '...' : '');
                                        }
                                        return data;
                                    }
                                },
                                { 
                                    data: 'choices',
                                    render: function(data, type, row) {
                                        if (type === 'display') {
                                            return data.length + ' ตัวเลือก';
                                        }
                                        return data.length;
                                    }
                                },
                                { 
                                    data: 'choices',
                                    render: function(data, type, row) {
                                        if (type === 'display') {
                                            for (let i = 0; i < data.length; i++) {
                                                if (data[i].is_correct == 1) {
                                                    return choiceLetters[i] || '-';
                                                }
                                            }
                                            return '-';
                                        }
                                        return '-';
                                    }
                                },
                                { 
                                    data: 'score',
                                    render: function(data, type, row) {
                                        return data || '0';
                                    }
                                },
                                { 
                                    data: null,
                                    render: function(data, type, row) {
                                        if (type === 'display') {
                                            return `
                                            <div class="d-flex">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-question-btn me-1"
                                                        data-id="${row.question_id}"
                                                        data-topic-id="${row.topic_id}">
                                                    <i class="ri-pencil-line"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-question-btn"
                                                        data-id="${row.question_id}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                            `;
                                        }
                                        return null;
                                    },
                                    orderable: false
                                }
                            ],
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
                            },
                            responsive: true,
                            dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>t<"row"<"col-md-6"i><"col-md-6"p>>',
                            buttons: [
                                {
                                    extend: 'excel',
                                    text: '<i class="ri-file-excel-2-line me-1"></i> Excel',
                                    className: 'btn btn-success btn-sm me-2',
                                    exportOptions: {
                                        columns: [0, 1, 2, 3, 4, 5]
                                    }
                                },
                                {
                                    extend: 'print',
                                    text: '<i class="ri-printer-line me-1"></i> พิมพ์',
                                    className: 'btn btn-info btn-sm me-2',
                                    exportOptions: {
                                        columns: [0, 1, 2, 3, 4, 5]
                                    }
                                }
                            ],
                            order: [[0, 'asc']]
                        });
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถโหลดรายการคำถามได้'
                        });
                    }
                },
                error: function() {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // เพิ่มตัวเลือกในแบบฟอร์มคำถาม
        function addChoiceField(index, content = '', isCorrect = false) {
            const letter = choiceLetters[index] || '';
            
            const html = `
            <div class="choice-row" data-index="${index}">
                <div class="choice-label-col">
                    <span class="choice-label">${letter}</span>
                </div>
                <div class="choice-content-col">
                    <input type="text" class="form-control choice-input" name="choices[${index}][content]" 
                           placeholder="ตัวเลือก ${letter}" value="${content}">
                    <input type="hidden" name="choices[${index}][is_correct]" class="choice-correct-input" value="${isCorrect ? '1' : '0'}">
                </div>
                <div class="choice-correct-col">
                    <div class="form-check">
                        <input class="form-check-input choice-radio" type="radio" name="correct_choice" 
                               value="${index}" id="choice_${index}" ${isCorrect ? 'checked' : ''}>
                        <label class="form-check-label" for="choice_${index}">
                            ถูกต้อง
                        </label>
                    </div>
                </div>
                <div class="choice-remove-col">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-choice-btn">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>
            `;
            
            $('#choiceContainer').append(html);
        }
        
        // รีเซ็ตฟอร์มหัวข้อ
        function resetTopicForm() {
            $('#topic_id').val('0');
            $('#action').val('create');
            $('#name').val('');
            $('#description').val('');
            $('#status').val('1');
            
            $('#topicModalLabel').text('เพิ่มหัวข้อข้อสอบใหม่');
            $('#topicForm .is-invalid').removeClass('is-invalid');
        }
        
        // รีเซ็ตฟอร์มคำถาม
        function resetQuestionForm() {
            $('#question_id').val('0');
            $('#question_action').val('create');
            $('#content').summernote('code', '');
            $('#image').val('');
            $('#score').val('1');
            $('#question_status').val('1');
            $('#currentImageContainer').addClass('d-none');
            $('#currentImage').attr('src', '');
            $('#removeImage').prop('checked', false);
            
            $('#choiceContainer').empty();
            
            // เพิ่มตัวเลือกเริ่มต้น 4 ตัวเลือก
            for (let i = 0; i < 4; i++) {
                addChoiceField(i);
            }
            
            $('#questionModalLabel').text('เพิ่มคำถามใหม่');
            $('#questionForm .is-invalid').removeClass('is-invalid');
        }
        
        // ตรวจสอบความถูกต้องของฟอร์มหัวข้อ
        function validateTopicForm() {
            let isValid = true;
            
            // ตรวจสอบชื่อหัวข้อ
            if ($('#name').val().trim() === '') {
                $('#name').addClass('is-invalid');
                isValid = false;
            } else {
                $('#name').removeClass('is-invalid');
            }
            
            return isValid;
        }
        
        // ตรวจสอบความถูกต้องของฟอร์มคำถาม
        function validateQuestionForm() {
            let isValid = true;
            
            // ตรวจสอบเนื้อหาคำถาม
            const content = $('#content').summernote('code').trim();
            if (content === '' || content === '<p><br></p>') {
                $('#content').next('.note-editor').addClass('is-invalid');
                isValid = false;
            } else {
                $('#content').next('.note-editor').removeClass('is-invalid');
            }
            
            // ตรวจสอบว่ามีตัวเลือกอย่างน้อย 2 ตัว
            if ($('.choice-row').length < 2) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณาระบุตัวเลือกอย่างน้อย 2 ตัวเลือก'
                });
                isValid = false;
            }
            
            // ตรวจสอบว่ามีการเลือกคำตอบที่ถูกต้อง
            if ($('.choice-radio:checked').length === 0) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณาเลือกคำตอบที่ถูกต้อง'
                });
                isValid = false;
            }
            
            // ตรวจสอบว่าแต่ละตัวเลือกมีเนื้อหา
            $('.choice-input').each(function() {
                if ($(this).val().trim() === '') {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            return isValid;
        }
        
        // Event Handlers
        
        // คลิกปุ่มเพิ่มหัวข้อ
        $('#addTopicBtn, #addTopicBtnPlaceholder').on('click', function() {
            resetTopicForm();
            $('#topicModal').modal('show');
        });
        
        // คลิกปุ่มบันทึกหัวข้อ
        $('#saveTopicBtn').on('click', function() {
            if (validateTopicForm()) {
                showLoading();
                
                const formData = new FormData($('#topicForm')[0]);
                
                $.ajax({
                    url: 'api/exam-topic-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // ปิด Modal
                            $('#topicModal').modal('hide');
                            
                            // แสดงข้อความสำเร็จ
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: formData.get('action') === 'create' ? 'เพิ่มหัวข้อข้อสอบใหม่เรียบร้อยแล้ว' : 'อัปเดตข้อมูลหัวข้อข้อสอบเรียบร้อยแล้ว',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // โหลดรายการหัวข้อใหม่
                            loadTopics();
                            loadAllQuestions();
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถบันทึกข้อมูลได้'
                            });
                        }
                    },
                    error: function() {
                        hideLoading();
                        
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                        });
                    }
                });
            }
        });
        
        // คลิกปุ่มแก้ไขหัวข้อ
        $(document).on('click', '.edit-topic-btn', function(e) {
            e.stopPropagation();
            
            const topicId = $(this).data('id');
            
            showLoading();
            
            $.ajax({
                url: 'api/exam-topic-api.php?action=get&id=' + topicId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const topic = response.data;
                        
                        // รีเซ็ตฟอร์มก่อน
                        resetTopicForm();
                        
                        // ใส่ข้อมูลเดิมลงในฟอร์ม
                        $('#topic_id').val(topic.topic_id);
                        $('#action').val('update');
                        $('#name').val(topic.name);
                        $('#description').val(topic.description || '');
                        $('#status').val(topic.status);
                        
                        // เปลี่ยนชื่อ Modal
                        $('#topicModalLabel').text('แก้ไขหัวข้อข้อสอบ');
                        
                        // แสดง Modal
                        $('#topicModal').modal('show');
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถโหลดข้อมูลหัวข้อได้'
                        });
                    }
                },
                error: function() {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        });
        
        // คลิกปุ่มลบหัวข้อ
        $(document).on('click', '.delete-topic-btn', function(e) {
            e.stopPropagation();
            
            const topicId = $(this).data('id');
            const topicName = $(this).data('name');
            const questionCount = $(this).data('question-count');
            
            if (parseInt(questionCount) > 0) {
                swalCustom.fire({
                    icon: 'warning',
                    title: 'ไม่สามารถลบได้',
                    text: `หัวข้อ "${topicName}" มีคำถามอยู่ ${questionCount} ข้อ กรุณาลบคำถามทั้งหมดก่อนลบหัวข้อ`
                });
                return;
            }
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                html: `คุณต้องการลบหัวข้อ <span class="fw-bold">"${topicName}"</span> ใช่หรือไม่?<br><small class="text-danger">*หากลบแล้วจะไม่สามารถกู้คืนได้</small>`,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> ลบ',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'ยกเลิก',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: 'api/exam-topic-api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            topic_id: topicId,
                            csrf_token: $('input[name="csrf_token"]').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // แสดงข้อความสำเร็จ
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบหัวข้อข้อสอบเรียบร้อยแล้ว',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                
                                // ถ้าหัวข้อที่ลบเป็นหัวข้อที่กำลังเลือกอยู่ ให้รีเซ็ต
                                if (currentTopicId === topicId) {
                                    currentTopicId = 0;
                                    currentTopicName = '';
                                    $('#questionPanel').html(`
                                    <div class="no-topic-selected-placeholder" id="noTopicSelectedPlaceholder">
                                        <i class="ri-question-mark placeholder-icon"></i>
                                        <p class="placeholder-text">กรุณาเลือกหัวข้อข้อสอบ</p>
                                        <p class="placeholder-subtext">เลือกหัวข้อข้อสอบจากรายการด้านซ้ายเพื่อดูคำถามในหัวข้อนั้น</p>
                                    </div>
                                    `);
                                }
                                
                                // โหลดรายการหัวข้อใหม่
                                loadTopics();
                                loadAllQuestions();
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถลบหัวข้อได้'
                                });
                            }
                        },
                        error: function() {
                            hideLoading();
                            
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                            });
                        }
                    });
                }
            });
        });
        
        // คลิกที่หัวข้อเพื่อดูคำถาม
        $(document).on('click', '.topic-card', function() {
            const topicId = $(this).data('id');
            const topicName = $(this).data('name');
            
            if (currentTopicId !== topicId) {
                loadQuestions(topicId, topicName);
            }
        });
        
        // คลิกปุ่มเพิ่มคำถาม
        $(document).on('click', '.add-question-btn, #addQuestionFabBtn', function() {
            const topicId = $(this).data('topic-id') || currentTopicId;
            
            if (topicId > 0) {
                $('#question_topic_id').val(topicId);
                resetQuestionForm();
                $('#questionModal').modal('show');
            } else {
                swalCustom.fire({
                    icon: 'warning',
                    title: 'กรุณาเลือกหัวข้อ',
                    text: 'กรุณาเลือกหัวข้อข้อสอบที่ต้องการเพิ่มคำถามก่อน'
                });
            }
        });
        
        // คลิกปุ่มเพิ่มตัวเลือก
        $('#addChoiceBtn').on('click', function() {
            const index = $('.choice-row').length;
            
            if (index < 10) { // จำกัดไม่เกิน 10 ตัวเลือก
                addChoiceField(index);
            } else {
                swalCustom.fire({
                    icon: 'warning',
                    title: 'ข้อจำกัด',
                    text: 'ไม่สามารถเพิ่มตัวเลือกได้มากกว่า 10 ตัวเลือก'
                });
            }
        });
        
        // คลิกปุ่มลบตัวเลือก
        $(document).on('click', '.remove-choice-btn', function() {
            const choiceRow = $(this).closest('.choice-row');
            
            if ($('.choice-row').length > 2) { // ต้องมีอย่างน้อย 2 ตัวเลือก
                choiceRow.remove();
                
                // รีเซ็ตลำดับและ index ใหม่
                $('.choice-row').each(function(idx) {
                    const letter = choiceLetters[idx];
                    $(this).attr('data-index', idx);
                    $(this).find('.choice-label').text(letter);
                    $(this).find('.choice-input').attr('name', `choices[${idx}][content]`).attr('placeholder', `ตัวเลือก ${letter}`);
                    $(this).find('.choice-correct-input').attr('name', `choices[${idx}][is_correct]`);
                    $(this).find('.choice-radio').val(idx).attr('id', `choice_${idx}`);
                    $(this).find('.form-check-label').attr('for', `choice_${idx}`);
                });
            } else {
                swalCustom.fire({
                    icon: 'warning',
                    title: 'ข้อจำกัด',
                    text: 'ต้องมีตัวเลือกอย่างน้อย 2 ตัวเลือก'
                });
            }
        });
        
        // เลือกคำตอบที่ถูกต้อง
        $(document).on('change', '.choice-radio', function() {
            // รีเซ็ตทุกตัวเลือกให้เป็นคำตอบที่ไม่ถูกต้อง
            $('.choice-correct-input').val('0');
            
            // กำหนดตัวเลือกที่เลือกให้เป็นคำตอบที่ถูกต้อง
            const index = $(this).val();
            $(`input[name="choices[${index}][is_correct]"]`).val('1');
        });
        
        // ปุ่มล้างรูปภาพ
        $('#clearImageBtn').on('click', function() {
            $('#image').val('');
        });
        
        // คลิกปุ่มบันทึกคำถาม
        $('#saveQuestionBtn').on('click', function() {
            if (validateQuestionForm()) {
                showLoading();
                
                const formData = new FormData($('#questionForm')[0]);
                
                // ตรวจสอบว่ามีการอัปโหลดรูปภาพหรือไม่
                if ($('#image')[0].files.length > 0) {
                    const file = $('#image')[0].files[0];
                    
                    // ตรวจสอบขนาดไฟล์
                    if (file.size > 2 * 1024 * 1024) { // 2MB
                        hideLoading();
                        
                        swalCustom.fire({
                            icon: 'error',
                            title: 'ไฟล์มีขนาดใหญ่เกินไป',
                            text: 'กรุณาอัปโหลดไฟล์ขนาดไม่เกิน 2MB'
                        });
                        
                        return;
                    }
                    
                    // ตรวจสอบประเภทไฟล์
                    const fileType = file.type.toLowerCase();
                    if (fileType !== 'image/jpeg' && fileType !== 'image/png' && fileType !== 'image/gif') {
                        hideLoading();
                        
                        swalCustom.fire({
                            icon: 'error',
                            title: 'ประเภทไฟล์ไม่ถูกต้อง',
                            text: 'กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น (JPEG, PNG, GIF)'
                        });
                        
                        return;
                    }
                }
                
                $.ajax({
                    url: 'api/question-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // ปิด Modal
                            $('#questionModal').modal('hide');
                            
                            // แสดงข้อความสำเร็จ
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: formData.get('action') === 'create' ? 'เพิ่มคำถามใหม่เรียบร้อยแล้ว' : 'อัปเดตข้อมูลคำถามเรียบร้อยแล้ว',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // โหลดคำถามใหม่
                            loadQuestions(currentTopicId, currentTopicName);
                            loadTopics(); // โหลดหัวข้อใหม่เพื่ออัปเดตจำนวนคำถาม
                            loadAllQuestions();
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถบันทึกข้อมูลได้'
                            });
                        }
                    },
                    error: function() {
                        hideLoading();
                        
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                        });
                    }
                });
            }
        });
        
        // คลิกปุ่มแก้ไขคำถาม
        $(document).on('click', '.edit-question-btn', function() {
            const questionId = $(this).data('id');
            const topicId = $(this).data('topic-id');
            
            showLoading();
            
            $.ajax({
                url: 'api/question-api.php?action=get&id=' + questionId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const question = response.data;
                        
                        // รีเซ็ตฟอร์มก่อน
                        resetQuestionForm();
                        
                        // ใส่ข้อมูลเดิมลงในฟอร์ม
                        $('#question_id').val(question.question_id);
                        $('#question_topic_id').val(question.topic_id);
                        $('#question_action').val('update');
                        $('#content').summernote('code', question.content);
                        $('#score').val(question.score);
                        $('#question_status').val(question.status);
                        
                        // ตรวจสอบว่ามีรูปภาพหรือไม่
                        if (question.image) {
                            $('#currentImageContainer').removeClass('d-none');
                            $('#currentImage').attr('src', baseUrl + question.image);
                        }
                        
                        // ใส่ตัวเลือก
                        $('#choiceContainer').empty();
                        if (question.choices && question.choices.length > 0) {
                            for (let i = 0; i < question.choices.length; i++) {
                                const choice = question.choices[i];
                                addChoiceField(i, choice.content, choice.is_correct == 1);
                            }
                        } else {
                            // ถ้าไม่มีตัวเลือก ให้เพิ่มตัวเลือกเริ่มต้น 4 ตัวเลือก
                            for (let i = 0; i < 4; i++) {
                                addChoiceField(i);
                            }
                        }
                        
                        // เปลี่ยนชื่อ Modal
                        $('#questionModalLabel').text('แก้ไขคำถาม');
                        
                        // แสดง Modal
                        $('#questionModal').modal('show');
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถโหลดข้อมูลคำถามได้'
                        });
                    }
                },
                error: function() {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        });
        
        // คลิกปุ่มลบคำถาม
        $(document).on('click', '.delete-question-btn', function() {
            const questionId = $(this).data('id');
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                html: 'คุณต้องการลบคำถามนี้ใช่หรือไม่?<br><small class="text-danger">*หากลบแล้วจะไม่สามารถกู้คืนได้</small>',
                showCancelButton: true,
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> ลบ',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'ยกเลิก',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: 'api/question-api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            question_id: questionId,
                            csrf_token: $('input[name="csrf_token"]').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // แสดงข้อความสำเร็จ
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบคำถามเรียบร้อยแล้ว',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                
                                // โหลดคำถามใหม่
                                if (currentTopicId > 0) {
                                    loadQuestions(currentTopicId, currentTopicName);
                                }
                                loadTopics(); // โหลดหัวข้อใหม่เพื่ออัปเดตจำนวนคำถาม
                                loadAllQuestions();
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถลบคำถามได้'
                                });
                            }
                        },
                        error: function() {
                            hideLoading();
                            
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                            });
                        }
                    });
                }
            });
        });
        
        // ปุ่มส่งออก
        $('#exportQuestionsBtn').on('click', function() {
            if ($.fn.DataTable.isDataTable('#allQuestionsTable')) {
                $('#allQuestionsTable').DataTable().button(0).trigger();
            }
        });
        
        // ลงทะเบียน Event Listener สำหรับอินพุตในฟอร์ม
        $('#name, #description, #status').on('input change', function() {
            $(this).removeClass('is-invalid');
        });
        
        $('#content').on('summernote.change', function() {
            $(this).next('.note-editor').removeClass('is-invalid');
        });
        
        $(document).on('input', '.choice-input', function() {
            $(this).removeClass('is-invalid');
        });
        
        // เมื่อปิด Modal ให้รีเซ็ตฟอร์ม
        $('#topicModal').on('hidden.bs.modal', function() {
            resetTopicForm();
        });
        
        $('#questionModal').on('hidden.bs.modal', function() {
            resetQuestionForm();
        });
        
        // แสดงสถานะการโหลดข้อมูล
        showLoading();
        
        // เริ่มโหลดข้อมูลจากฐานข้อมูลเมื่อโหลดหน้าเสร็จ
        console.log('เริ่มดึงข้อมูลจากฐานข้อมูล...');
        
        // โหลดข้อมูลหัวข้อข้อสอบ (exam_topic) ทั้งหมด
        loadTopics();
        
        // โหลดข้อมูลคำถาม (question) ทั้งหมดในชุดข้อสอบ
        loadAllQuestions();
        
        // แสดงแบบ Placeholder เริ่มต้น
        $('#questionPanel').html(`
        <div class="no-topic-selected-placeholder" id="noTopicSelectedPlaceholder">
            <i class="ri-question-mark placeholder-icon"></i>
            <p class="placeholder-text">กรุณาเลือกหัวข้อข้อสอบ</p>
            <p class="placeholder-subtext">เลือกหัวข้อข้อสอบจากรายการด้านซ้ายเพื่อดูคำถามในหัวข้อนั้น</p>
        </div>
        `);
    });