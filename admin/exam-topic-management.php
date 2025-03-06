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

// ตรวจสอบการรับค่า exam_set_id
$examSetId = isset($_GET['exam_set_id']) ? intval($_GET['exam_set_id']) : 0;

if ($examSetId <= 0) {
    // ถ้าไม่มี exam_set_id ให้ redirect ไปยังหน้าจัดการชุดข้อสอบ
    header('Location: exam-set-management.php');
    exit;
}

// ดึงข้อมูลชุดข้อสอบ
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM exam_set WHERE exam_set_id = ?");
    $stmt->execute([$examSetId]);
    $examSet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$examSet) {
        // ถ้าไม่พบข้อมูลชุดข้อสอบให้ redirect ไปยังหน้าจัดการชุดข้อสอบ
        header('Location: exam-set-management.php');
        exit;
    }
    
    // ดึงข้อมูลสถิติ
    $stmtStats = $conn->prepare("
        SELECT 
            COUNT(DISTINCT t.topic_id) AS total_topics,
            COUNT(DISTINCT q.question_id) AS total_questions
        FROM exam_topic t
        LEFT JOIN question q ON t.topic_id = q.topic_id
        WHERE t.exam_set_id = ?
    ");
    $stmtStats->execute([$examSetId]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    $totalTopics = $stats['total_topics'] ?? 0;
    $totalQuestions = $stats['total_questions'] ?? 0;
    
} catch (PDOException $e) {
    // หากเกิดข้อผิดพลาดให้ redirect ไปยังหน้าจัดการชุดข้อสอบ
    error_log("Error fetching exam set data: " . $e->getMessage());
    header('Location: exam-set-management.php');
    exit;
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

    <title>จัดการหัวข้อและข้อสอบ - <?= htmlspecialchars($examSet['name']) ?> - ระบบสอบออนไลน์</title>

    <meta name="description" content="จัดการหัวข้อและข้อสอบในระบบสอบออนไลน์" />

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
    <!-- <link rel="stylesheet" href="../assets/vendor/libs/animate/animate.min.css" /> -->
    <link rel="stylesheet" href="../assets/vendor/libs/dropzone/dropzone.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/quill/editor.css" />
     <!-- <link rel="stylesheet" href="../assets/vendor/libs/animate/animate.min.css" /> -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" integrity="sha512-c42qTSw/wPZ3/5LBzD+Bw5f7bSF2oxou6wEb+I/lqeaKV5FDIfMvvRp772y4jcJLKuGUOpbJMdg/BTl50fJYAw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    

    <!-- Page CSS -->
    <style>
      body {
        font-family: 'Kanit', sans-serif;
      }
      
      /* Main Layout */
      .content-split {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
      }
      
      .content-left {
        flex: 1;
        min-width: 300px;
      }
      
      .content-right {
        flex: 1.5;
        min-width: 300px;
      }
      
      /* Card Styling */
      .topic-card {
        transition: all 0.3s ease;
        margin-bottom: 16px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
      }
      
      .topic-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
      }
      
      .topic-card.active {
        border-left: 4px solid #5D87FF;
      }
      
      .topic-card-header {
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
      }
      
      .topic-title {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 6px;
      }
      
      .topic-info {
        color: #6c757d;
        font-size: 14px;
        margin-bottom: 10px;
      }
      
      .topic-actions {
        display: flex;
        gap: 8px;
      }
      
      /* Question Form */
      .question-form-card {
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
      }
      
      .form-control-file-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
      }
      
      .form-control-file-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
      }
      
      .image-preview {
        width: 120px;
        height: 80px;
        border-radius: 8px;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
      }
      
      .image-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
      }
      
      .image-preview-placeholder {
        color: #6c757d;
        font-size: 24px;
      }
      
      /* Choice Items */
      .choice-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
      }
      
      .choice-content {
        flex: 1;
      }
      
      .choice-actions {
        display: flex;
        gap: 8px;
      }
      
      .choice-image-preview {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
      }
      
      .choice-image-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
      }
      
      /* Question Cards */
      .questions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
      }
      
      .question-card {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
      }
      
      .question-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
      }
      
      .question-card-header {
        padding: 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      
      .question-number {
        font-weight: 600;
        font-size: 16px;
      }
      
      .question-actions {
        display: flex;
        gap: 8px;
      }
      
      .question-card-body {
        padding: 16px;
      }
      
      .question-content {
        margin-bottom: 16px;
      }
      
      .question-image {
        margin-top: 10px;
        margin-bottom: 16px;
        max-width: 100%;
        border-radius: 8px;
        overflow: hidden;
      }
      
      .question-image img {
        max-width: 100%;
        max-height: 200px;
        object-fit: contain;
      }
      
      .choice-list {
        list-style-type: none;
        padding: 0;
        margin: 0;
      }
      
      .choice-list-item {
        display: flex;
        align-items: center;
        padding: 8px;
        margin-bottom: 8px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
      }
      
      .choice-list-item.correct {
        background-color: #E8F5E9;
        border-color: #2E7D32;
        color: #2E7D32;
      }
      
      .choice-list-item .choice-text {
        flex: 1;
        margin-left: 10px;
      }
      
      .choice-list-item .choice-image {
        width: 30px;
        height: 30px;
        border-radius: 4px;
        overflow: hidden;
        margin-left: 10px;
      }
      
      .choice-list-item .choice-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      
      /* Status Badges */
      .status-badge {
        padding: 5px 8px;
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
      
      /* Tab Navigation (Mobile) */
      .mobile-tabs {
        display: none;
        margin-bottom: 20px;
      }
      
      .tab-btn {
        flex: 1;
        text-align: center;
        padding: 12px;
        font-weight: 500;
        background-color: #f8f9fa;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
      }
      
      .tab-btn.active {
        background-color: #5D87FF;
        color: white;
      }
      
      /* Floating Action Button */
      .floating-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #5D87FF;
        color: white;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        z-index: 1000;
        font-size: 24px;
      }
      
      /* Loading Overlay */
      .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.9);
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
      
      /* Responsive Styles */
      @media (max-width: 992px) {
        .content-split {
          flex-direction: column;
        }
        
        .content-left, .content-right {
          width: 100%;
        }
        
        .mobile-tabs {
          display: flex;
        }
        
        .content-right {
          display: none;
        }
        
        .content-right.active {
          display: block;
        }
        
        .floating-btn {
          display: flex;
        }
      }
      
      @media (max-width: 576px) {
        .questions-grid {
          grid-template-columns: 1fr;
        }
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
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item">
                    <a href="dashboard.php">หน้าหลัก</a>
                  </li>
                  <li class="breadcrumb-item">
                    <a href="exam-set-management.php">จัดการชุดข้อสอบ</a>
                  </li>
                  <li class="breadcrumb-item active"><?= htmlspecialchars($examSet['name']) ?></li>
                </ol>
              </nav>
              
              <!-- Header Card -->
              <div class="card mb-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h4 class="mb-1"><?= htmlspecialchars($examSet['name']) ?></h4>
                      <p class="text-muted mb-0"><?= $examSet['description'] ? htmlspecialchars($examSet['description']) : 'ไม่มีคำอธิบาย' ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <div class="text-center">
                        <h5 class="mb-0"><?= $totalTopics ?></h5>
                        <p class="text-muted mb-0">หัวข้อ</p>
                      </div>
                      <div class="text-center ms-3">
                        <h5 class="mb-0"><?= $totalQuestions ?></h5>
                        <p class="text-muted mb-0">ข้อสอบ</p>
                      </div>
                      <div class="ms-3">
                        <span class="status-badge <?= $examSet['status'] == 1 ? 'status-active' : 'status-inactive' ?>">
                          <?= $examSet['status'] == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน' ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Mobile Tabs (Visible on small screens) -->
              <div class="mobile-tabs">
                <button class="tab-btn active" data-target="topics">หัวข้อการสอบ</button>
                <button class="tab-btn" data-target="questions">จัดการคำถาม</button>
              </div>
              
              <!-- Split Content -->
              <div class="content-split">
                <!-- Left Content - Topics -->
                <div class="content-left" id="topics-section">
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">หัวข้อการสอบ</h5>
                      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                        <i class="ri-add-line me-1"></i> เพิ่มหัวข้อ
                      </button>
                    </div>
                    <div class="card-body" id="topics-container">
                      <!-- Topic cards will be loaded here via AJAX -->
                      <div class="text-center py-5" id="topics-loading">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                        <p class="mt-2">กำลังโหลดข้อมูล...</p>
                      </div>
                      <div id="no-topics-message" style="display: none;">
                        <div class="text-center py-5">
                          <i class="ri-file-list-3-line" style="font-size: 48px; color: #d1d1d1;"></i>
                          <p class="mt-2">ยังไม่มีหัวข้อการสอบ กรุณาเพิ่มหัวข้อ</p>
                          <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                            <i class="ri-add-line me-1"></i> เพิ่มหัวข้อ
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Right Content - Question Management -->
                <div class="content-right" id="questions-section">
                  <div class="card question-form-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0" id="selected-topic-title">จัดการคำถาม</h5>
                      <div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancel-question-btn" style="display: none;">
                          <i class="ri-close-line me-1"></i> ยกเลิก
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="save-question-btn">
                          <i class="ri-save-line me-1"></i> บันทึก
                        </button>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="text-center py-5" id="select-topic-message">
                        <i class="ri-arrow-left-line" style="font-size: 48px; color: #d1d1d1;"></i>
                        <p class="mt-2">กรุณาเลือกหัวข้อจากรายการทางด้านซ้าย</p>
                      </div>
                      
                      <form id="question-form" style="display: none;">
                        <input type="hidden" id="question_id" name="question_id" value="">
                        <input type="hidden" id="topic_id" name="topic_id" value="">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-3">
                          <label for="question_content" class="form-label">เนื้อหาคำถาม <span class="text-danger">*</span></label>
                          <div id="question_content_editor">
                            <!-- Quill editor will be initialized here -->
                          </div>
                          <input type="hidden" id="question_content" name="question_content">
                        </div>
                        
                        <div class="mb-3">
                          <label for="question_image" class="form-label">รูปภาพประกอบคำถาม (ถ้ามี)</label>
                          <div class="row align-items-center">
                            <div class="col-md-6 mb-2 mb-md-0">
                              <div class="form-control-file-wrapper">
                                <button type="button" class="btn btn-outline-primary" id="select-image-btn">
                                  <i class="ri-image-add-line me-1"></i> เลือกรูปภาพ
                                </button>
                                <input type="file" id="question_image" name="question_image" class="form-control-file" accept="image/*">
                              </div>
                              <div class="form-text">ขนาดไฟล์ไม่เกิน 2MB (JPEG, PNG, GIF)</div>
                            </div>
                            <div class="col-md-6 text-md-end">
                              <div class="image-preview" id="question-image-preview">
                                <span class="image-preview-placeholder">
                                  <i class="ri-add-line"></i>
                                </span>
                              </div>
                            </div>
                          </div>
                          <input type="hidden" id="existing_image" name="existing_image" value="">
                          <input type="hidden" id="remove_image" name="remove_image" value="0">
                        </div>
                        
                        
                        
                        <div class="mb-3">
                          <label for="question_score" class="form-label">คะแนน <span class="text-danger">*</span></label>
                          <input type="number" class="form-control" id="question_score" name="question_score" min="0.5" step="0.5" value="1" required>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="mb-3">
                          <label class="form-label">ตัวเลือก <span class="text-danger">*</span></label>
                          <div id="choices-container">
                            <!-- Choices will be added here -->
                          </div>
                          
                          <div class="text-end mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-choice-btn">
                              <i class="ri-add-line me-1"></i> เพิ่มตัวเลือก
                            </button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Questions Display -->
              <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">แสดงข้อสอบทั้งหมด</h5>
                  <div class="btn-group" role="group" aria-label="View Type">
                    <button type="button" class="btn btn-outline-primary active" id="btn-card-view">
                      <i class="ri-layout-grid-line me-1"></i> การ์ด
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btn-table-view">
                      <i class="ri-table-line me-1"></i> ตาราง
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <!-- Filter Options -->
                  <div class="mb-4">
                    <div class="row">
                      <div class="col-md-3 mb-2 mb-md-0">
                        <select class="form-select" id="filter-topic">
                          <option value="">ทุกหัวข้อ</option>
                          <!-- Topics will be loaded here -->
                        </select>
                      </div>
                      <div class="col-md-3 mb-2 mb-md-0">
                        <select class="form-select" id="filter-score">
                          <option value="">ทุกคะแนน</option>
                          <option value="1">1 คะแนน</option>
                          <option value="2">2 คะแนน</option>
                          <option value="3">3 คะแนน</option>
                          <option value="4">4 คะแนน</option>
                          <option value="5">5 คะแนน</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <div class="input-group">
                          <input type="text" class="form-control" id="search-question" placeholder="ค้นหาข้อสอบ...">
                          <button class="btn btn-outline-primary" type="button" id="btn-search">
                            <i class="ri-search-line me-1"></i> ค้นหา
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Card View (Default) -->
                  <div id="card-view" class="questions-grid">
                    <!-- Question cards will be loaded here -->
                    <div class="text-center py-5" id="questions-loading">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                      </div>
                      <p class="mt-2">กำลังโหลดข้อสอบ...</p>
                    </div>
                    <div id="no-questions-message" style="display: none;">
                      <div class="text-center py-5">
                        <i class="ri-question-line" style="font-size: 48px; color: #d1d1d1;"></i>
                        <p class="mt-2">ยังไม่มีข้อสอบในชุดนี้ กรุณาเลือกหัวข้อเพื่อเพิ่มข้อสอบ</p>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Table View (Hidden by default) -->
                  <div id="table-view" style="display: none;">
                    <div class="table-responsive">
                      <table class="table table-bordered table-hover">
                        <thead>
                          <tr>
                            <th width="5%">#</th>
                            <th width="15%">หัวข้อ</th>
                            <th width="40%">คำถาม</th>
                            <th width="15%">รูปภาพ</th>
                            <th width="10%">คะแนน</th>
                            <th width="15%">จัดการ</th>
                          </tr>
                        </thead>
                        <tbody id="questions-table-body">
                          <!-- Question rows will be loaded here -->
                        </tbody>
                      </table>
                    </div>
                  </div>
                  
                  <!-- Pagination -->
                  <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center" id="pagination">
                      <!-- Pagination will be generated dynamically -->
                    </ul>
                  </nav>
                </div>
              </div>
            </div>
            <!-- / Content -->

            <!-- Modal เพิ่มหัวข้อ -->
            <div class="modal fade" id="addTopicModal" tabindex="-1" aria-labelledby="addTopicModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addTopicModalLabel">เพิ่มหัวข้อการสอบใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="addTopicForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" name="exam_set_id" value="<?= $examSetId ?>">
                      
                      <div class="mb-3">
                        <label for="name" class="form-label">ชื่อหัวข้อ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อหัวข้อ</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียด</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                      </div>
                      
                      <div class="mb-3">
                        <label for="status" class="form-label">สถานะ</label>
                        <select class="form-select" id="status" name="status">
                          <option value="1" selected>ใช้งาน</option>
                          <option value="0">ไม่ใช้งาน</option>
                        </select>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveTopicBtn">บันทึก</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal แก้ไขหัวข้อ -->
            <div class="modal fade" id="editTopicModal" tabindex="-1" aria-labelledby="editTopicModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editTopicModalLabel">แก้ไขข้อมูลหัวข้อการสอบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="editTopicForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" id="edit_topic_id" name="topic_id">
                      
                      <div class="mb-3">
                        <label for="edit_name" class="form-label">ชื่อหัวข้อ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อหัวข้อ</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_description" class="form-label">รายละเอียด</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_status" class="form-label">สถานะ</label>
                        <select class="form-select" id="edit_status" name="status">
                          <option value="1">ใช้งาน</option>
                          <option value="0">ไม่ใช้งาน</option>
                        </select>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="updateTopicBtn">บันทึกการเปลี่ยนแปลง</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal ดูรูปภาพขนาดใหญ่ -->
            <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">ดูรูปภาพ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body text-center">
                    <img id="preview-image-large" src="" alt="Preview" class="img-fluid">
                  </div>
                </div>
              </div>
            </div>

            <!-- Floating Action Button (Mobile) -->
            <div class="floating-btn" id="floating-add-btn">
              <i class="ri-add-line"></i>
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
    <script src="../assets/vendor/libs/dropzone/dropzone.js"></script>
    <script src="../assets/vendor/libs/quill/katex.js"></script>
    <script src="../assets/vendor/libs/quill/quill.js"></script>
    
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
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก',
            showClass: {
                popup: 'animate__animated animate__fadeInUp animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutDown animate__faster'
            }
        });
        
        // Function to show loading overlay
        function showLoading() {
            $('#loadingOverlay').addClass('show');
        }
        
        // Function to hide loading overlay
        function hideLoading() {
            $('#loadingOverlay').removeClass('show');
        }
        
        // Initialize Quill Editor for question content
        const quillEditor = new Quill('#question_content_editor', {
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['formula', 'link'],
                    ['clean']
                ]
            },
            placeholder: 'กรอกเนื้อหาคำถาม...',
            theme: 'snow'
        });
        
        // Set content to hidden input when quill editor changes
        quillEditor.on('text-change', function() {
            $('#question_content').val(quillEditor.root.innerHTML);
        });
        
        // Global variables for current state
        let currentTopicId = null;
        let currentQuestionId = null;
        let editingQuestion = false;
        let currentPage = 1;
        let questionsPerPage = 6; // For pagination
        let allQuestions = []; // Array to store all loaded questions
        
        // Load topics on page load
        loadTopics();
        
        // Load questions on page load
        loadQuestions();
        
        // Mobile tabs functionality
        $('.tab-btn').on('click', function() {
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            
            const target = $(this).data('target');
            if (target === 'topics') {
                $('#topics-section').show();
                $('#questions-section').removeClass('active').hide();
            } else {
                $('#topics-section').hide();
                $('#questions-section').addClass('active').show();
            }
        });
        
        // Floating button click (mobile)
        $('#floating-add-btn').on('click', function() {
            $('#addTopicModal').modal('show');
        });
        
        // View toggle between card and table
        $('#btn-card-view').on('click', function() {
            $(this).addClass('active');
            $('#btn-table-view').removeClass('active');
            $('#card-view').show();
            $('#table-view').hide();
        });
        
        $('#btn-table-view').on('click', function() {
            $(this).addClass('active');
            $('#btn-card-view').removeClass('active');
            $('#card-view').hide();
            $('#table-view').show();
        });
        
        // Filter functionality
        $('#filter-topic, #filter-score').on('change', function() {
            applyFilters();
        });
        
        $('#btn-search').on('click', function() {
            applyFilters();
        });
        
        $('#search-question').on('keypress', function(e) {
            if (e.which === 13) {
                applyFilters();
                e.preventDefault();
            }
        });
        
        // Image file input change
        $('#question_image').on('change', function(e) {
            const file = this.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    swalCustom.fire({
                        icon: 'error',
                        title: 'ไฟล์มีขนาดใหญ่เกินไป',
                        text: 'กรุณาเลือกไฟล์ที่มีขนาดไม่เกิน 2MB'
                    });
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#question-image-preview').html(`<img src="${e.target.result}" alt="Preview">`);
                };
                reader.readAsDataURL(file);
                
                // Reset remove image flag since we're adding a new image
                $('#remove_image').val('0');
            }
        });
        
        // Select image button click
        $('#select-image-btn').on('click', function() {
            $('#question_image').click();
        });
        
        // แก้ไขฟังก์ชัน saveQuestion()
function saveQuestion() {
    // ตรวจสอบเนื้อหาของคำถามจาก Quill editor
    if (quillEditor.getText().trim().length === 0) {
        swalCustom.fire({
            icon: 'error',
            title: 'กรุณากรอกเนื้อหาคำถาม',
            text: 'เนื้อหาคำถามไม่สามารถเป็นค่าว่างได้'
        });
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    
    // เพิ่มข้อมูลหลักจากฟอร์มทีละตัว แทนการใช้ $('#question-form')[0]
    formData.append('csrf_token', $('#question-form input[name="csrf_token"]').val());
    formData.append('question_id', $('#question_id').val());
    formData.append('topic_id', $('#topic_id').val());
    formData.append('question_content', quillEditor.root.innerHTML);
    formData.append('question_score', $('#question_score').val());
    formData.append('existing_image', $('#existing_image').val());
    formData.append('remove_image', $('#remove_image').val());
    
    // เพิ่มรูปภาพคำถาม (ถ้ามี)
    if ($('#question_image')[0].files.length > 0) {
        formData.append('question_image', $('#question_image')[0].files[0]);
    }
    
    // เพิ่มข้อมูลตัวเลือก
    $('.choice-item').each(function(index) {
        const choiceId = $(this).find('.choice-content-input').attr('name').match(/choice_content\[(.*?)\]/)[1];
        const choiceContent = $(this).find('.choice-content-input').val();
        const isCorrect = $(this).find('.choice-radio').is(':checked');
        const existingChoiceId = $(this).find('input[name^="choice_id"]').val();
        const removeChoiceImage = $(this).find('.remove-choice-image').val();
        
        formData.append(`choice_content[${choiceId}]`, choiceContent);
        if (isCorrect) {
            formData.append('correct_choice', choiceId);
        }
        if (existingChoiceId) {
            formData.append(`choice_id[${choiceId}]`, existingChoiceId);
        }
        formData.append(`remove_choice_image[${choiceId}]`, removeChoiceImage);
        
        // เพิ่มรูปภาพตัวเลือก (ถ้ามี)
        const choiceImageInput = $(this).find('.choice-image-input')[0];
        if (choiceImageInput.files.length > 0) {
            formData.append(`choice_image[${choiceId}]`, choiceImageInput.files[0]);
        }
    });
    
    // เพิ่ม action ตามสถานะการแก้ไข
    formData.append('action', editingQuestion ? 'update' : 'create');
    
    showLoading();
    
    // Debug output - check formData
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
    }
    
    // ใช้ fetch API แทน jQuery AJAX
    fetch('../admin/api/question-api.php', {  // ตรวจสอบ path อีกครั้ง
        method: 'POST',
        body: formData,
        credentials: 'same-origin'  // ส่ง cookies ไปด้วย
    })
    .then(response => {
        // ตรวจสอบว่าการตอบกลับเป็น JSON หรือไม่
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        throw new Error('Server response is not JSON');
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // Show success message
            swalCustom.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: editingQuestion ? 'อัปเดตคำถามเรียบร้อยแล้ว' : 'เพิ่มคำถามเรียบร้อยแล้ว'
            });
            
            // Reset form for new question
            resetQuestionForm();
            setupNewQuestion();
            
            // Reload questions
            loadQuestions();
        } else {
            swalCustom.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถบันทึกคำถามได้'
            });
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error saving question:', error);
        
        swalCustom.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
            text: 'ไม่สามารถติดต่อกับเซิร์ฟเวอร์ได้ กรุณาตรวจสอบการเชื่อมต่อหรือลองใหม่อีกครั้ง'
        });
    });
}
        
        // Edit topic button click (event delegation)
        $(document).on('click', '.edit-topic-btn', function() {
            const topicId = $(this).data('id');
            
            showLoading();
            
            $.ajax({
                url: `api/exam-topic-api.php?action=get&id=${topicId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const topic = response.data;
                        
                        // Fill form with topic data
                        $('#edit_topic_id').val(topic.topic_id);
                        $('#edit_name').val(topic.name);
                        $('#edit_description').val(topic.description || '');
                        $('#edit_status').val(topic.status);
                        
                        // Show modal
                        $('#editTopicModal').modal('show');
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถดึงข้อมูลหัวข้อการสอบได้'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        });
        
        // Update topic button click
        $('#updateTopicBtn').on('click', function() {
            if (validateForm('#editTopicForm')) {
                const formData = new FormData($('#editTopicForm')[0]);
                formData.append('action', 'update');
                
                showLoading();
                
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
                            // Close modal
                            $('#editTopicModal').modal('hide');
                            
                            // Reload topics
                            loadTopics();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'อัปเดตข้อมูลหัวข้อการสอบเรียบร้อยแล้ว'
                            });
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถอัปเดตข้อมูลหัวข้อการสอบได้'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
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
        
        // Delete topic button click (event delegation)
        $(document).on('click', '.delete-topic-btn', function() {
            const topicId = $(this).data('id');
            const topicName = $(this).data('name');
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: `คุณต้องการลบหัวข้อการสอบ "${topicName}" ใช่หรือไม่?`,
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: 'api/exam-topic-api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            topic_id: topicId,
                            csrf_token: $('#addTopicForm input[name="csrf_token"]').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // Reload topics
                                loadTopics();
                                
                                // Show success message
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบหัวข้อการสอบเรียบร้อยแล้ว'
                                });
                                
                                // If current topic is deleted, reset form
                                if (currentTopicId == topicId) {
                                    resetQuestionForm();
                                }
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถลบหัวข้อการสอบได้'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
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
        
        // Select topic button click (event delegation)
        $(document).on('click', '.select-topic-btn', function() {
            const topicId = $(this).data('id');
            const topicName = $(this).data('name');
            
            // Update UI for selected topic
            $('.topic-card').removeClass('active');
            $(this).closest('.topic-card').addClass('active');
            
            // Set current topic
            currentTopicId = topicId;
            
            // Update form and UI
            $('#topic_id').val(topicId);
            $('#selected-topic-title').text(`จัดการคำถาม: ${topicName}`);
            
            // Show question form
            $('#select-topic-message').hide();
            $('#question-form').show();
            
            // Reset form for new question
            resetQuestionForm();
            setupNewQuestion();
            
            // In mobile view, switch to questions tab
            if ($(window).width() < 992) {
                $('.tab-btn[data-target="questions"]').click();
            }
        });
        
        // Add choice button click
        $('#add-choice-btn').on('click', function() {
            addChoice();
        });
        
        // Remove choice button click (event delegation)
        $(document).on('click', '.remove-choice-btn', function() {
            $(this).closest('.choice-item').remove();
        });
        
        // Choice radio button change (event delegation)
        $(document).on('change', '.choice-radio', function() {
            // Update all other radio buttons
            $('.choice-radio').not(this).prop('checked', false);
        });
        
        // Choice image upload (event delegation)
        $(document).on('change', '.choice-image-input', function(e) {
            const file = this.files[0];
            const choiceItem = $(this).closest('.choice-item');
            
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    swalCustom.fire({
                        icon: 'error',
                        title: 'ไฟล์มีขนาดใหญ่เกินไป',
                        text: 'กรุณาเลือกไฟล์ที่มีขนาดไม่เกิน 2MB'
                    });
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    choiceItem.find('.choice-image-preview').html(`<img src="${e.target.result}" alt="Preview">`);
                };
                reader.readAsDataURL(file);
                
                // Reset remove image flag
                choiceItem.find('.remove-choice-image').val('0');
            }
        });
        
        // Remove choice image button click (event delegation)
        $(document).on('click', '.choice-remove-image-btn', function() {
            const choiceItem = $(this).closest('.choice-item');
            choiceItem.find('.choice-image-preview').html('<span class="image-preview-placeholder"><i class="ri-add-line"></i></span>');
            choiceItem.find('.choice-image-input').val('');
            choiceItem.find('.remove-choice-image').val('1');
        });
        
        // Save question button click
        $('#save-question-btn').on('click', function() {
            if (validateQuestionForm()) {
                saveQuestion();
            }
        });
        
        // Cancel question editing
        $('#cancel-question-btn').on('click', function() {
            resetQuestionForm();
            setupNewQuestion();
        });
        
        // Edit question button click (event delegation)
        $(document).on('click', '.edit-question-btn', function() {
            const questionId = $(this).data('id');
            editQuestion(questionId);
        });
        
        // Delete question button click (event delegation)
        $(document).on('click', '.delete-question-btn', function() {
            const questionId = $(this).data('id');
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: 'คุณต้องการลบคำถามนี้ใช่หรือไม่?',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteQuestion(questionId);
                }
            });
        });
        
        // View image in modal (event delegation)
        $(document).on('click', '.question-image img, .choice-image img', function() {
            const imgSrc = $(this).attr('src');
            $('#preview-image-large').attr('src', imgSrc);
            $('#imagePreviewModal').modal('show');
        });
        
        // Form validation function
        function validateForm(formId) {
            const form = $(formId);
            let isValid = true;
            
            form.find('input[required], select[required], textarea[required]').each(function() {
                if ($(this).val() === '') {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            return isValid;
        }
        
        // Validate question form
        function validateQuestionForm() {
            let isValid = true;
            
            // Check if question content is empty
            const questionContent = quillEditor.getText().trim();
            if (questionContent === '') {
                isValid = false;
                $('#question_content_editor').css('border', '1px solid #dc3545');
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณากรอกเนื้อหาคำถาม',
                    text: 'เนื้อหาคำถามไม่สามารถเป็นค่าว่างได้'
                });
                return false;
            } else {
                $('#question_content_editor').css('border', '');
            }
            
            // Check if question score is valid
            const score = parseFloat($('#question_score').val());
            if (isNaN(score) || score <= 0) {
                isValid = false;
                $('#question_score').addClass('is-invalid');
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณากรอกคะแนนให้ถูกต้อง',
                    text: 'คะแนนต้องเป็นตัวเลขมากกว่า 0'
                });
                return false;
            }
            
            // Check if there are at least 2 choices
            const choiceCount = $('.choice-item').length;
            if (choiceCount < 2) {
                isValid = false;
                swalCustom.fire({
                    icon: 'error',
                    title: 'ต้องมีตัวเลือกอย่างน้อย 2 ตัวเลือก',
                    text: 'กรุณาเพิ่มตัวเลือกให้ครบ'
                });
                return false;
            }
            
            // Check if all choices have content
            let emptyChoiceFound = false;
            $('.choice-content-input').each(function() {
                if ($(this).val().trim() === '') {
                    emptyChoiceFound = true;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (emptyChoiceFound) {
                isValid = false;
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณากรอกเนื้อหาตัวเลือกให้ครบทุกตัวเลือก',
                    text: 'เนื้อหาตัวเลือกไม่สามารถเป็นค่าว่างได้'
                });
                return false;
            }
            
            // Check if one choice is selected as correct
            const hasCorrectChoice = $('.choice-radio:checked').length > 0;
            if (!hasCorrectChoice) {
                isValid = false;
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณาเลือกคำตอบที่ถูกต้อง',
                    text: 'ต้องมีตัวเลือกที่ถูกต้องอย่างน้อย 1 ตัวเลือก'
                });
                return false;
            }
            
            return isValid;
        }
        
        // Remove validation styling on input
        $('input, select, textarea').on('focus', function() {
            $(this).removeClass('is-invalid');
        });
        
        // Load topics from API
        function loadTopics() {
            showLoading();
            
            $.ajax({
                url: `api/exam-topic-api.php?action=list&exam_set_id=<?= $examSetId ?>`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    $('#topics-loading').hide();
                    
                    if (response.success) {
                        const topics = response.data;
                        
                        // Clear topics container
                        $('#topics-container').empty();
                        
                        // Add topics to container
                        if (topics.length > 0) {
                            // Populate filter dropdown
                            $('#filter-topic').empty().append('<option value="">ทุกหัวข้อ</option>');
                            
                            topics.forEach(function(topic) {
                                // Add to topics container
                                const topicCard = createTopicCard(topic);
                                $('#topics-container').append(topicCard);
                                
                                // Add to filter dropdown
                                $('#filter-topic').append(`<option value="${topic.topic_id}">${topic.name}</option>`);
                            });
                        } else {
                            $('#no-topics-message').show();
                        }
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถโหลดข้อมูลหัวข้อการสอบได้'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    $('#topics-loading').hide();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // Create topic card HTML
        function createTopicCard(topic) {
            return `
            <div class="topic-card ${topic.topic_id == currentTopicId ? 'active' : ''}">
                <div class="topic-card-header">
                    <div>
                        <h6 class="topic-title">${topic.name}</h6>
                        <p class="topic-info">มีคำถาม ${topic.question_count} ข้อ | สถานะ: ${topic.status == 1 ? '<span class="status-badge status-active">ใช้งาน</span>' : '<span class="status-badge status-inactive">ไม่ใช้งาน</span>'}</p>
                        <div class="topic-actions">
                            <button type="button" class="btn btn-sm btn-primary select-topic-btn" data-id="${topic.topic_id}" data-name="${topic.name}">
                                <i class="ri-add-line me-1"></i> เลือก
                            </button>
                            <button type="button" class="btn btn-sm btn-info edit-topic-btn" data-id="${topic.topic_id}">
                                <i class="ri-pencil-line me-1"></i> แก้ไข
                            </button>
                            ${parseInt(topic.question_count) === 0 ? 
                                `<button type="button" class="btn btn-sm btn-danger delete-topic-btn" data-id="${topic.topic_id}" data-name="${topic.name}">
                                    <i class="ri-delete-bin-line me-1"></i> ลบ
                                </button>` : 
                                `<button type="button" class="btn btn-sm btn-secondary" disabled title="ไม่สามารถลบได้ เนื่องจากมีคำถามอยู่">
                                    <i class="ri-delete-bin-line me-1"></i> ลบ
                                </button>`
                            }
                        </div>
                    </div>
                </div>
            </div>`;
        }
        
        // Load questions from API
        function loadQuestions() {
            showLoading();
            
            $.ajax({
                url: `api/question-api.php?action=list_by_exam_set&exam_set_id=<?= $examSetId ?>`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    $('#questions-loading').hide();
                    
                    if (response.success) {
                        allQuestions = response.data;
                        
                        // Update UI
                        if (allQuestions.length > 0) {
                            // Display questions with current filters and pagination
                            applyFilters();
                        } else {
                            $('#no-questions-message').show();
                        }
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถโหลดข้อมูลคำถามได้'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    $('#questions-loading').hide();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // Apply filters to questions
        function applyFilters() {
            const topicFilter = $('#filter-topic').val();
            const scoreFilter = $('#filter-score').val();
            const searchQuery = $('#search-question').val().toLowerCase();
            
            // Filter questions
            let filteredQuestions = allQuestions.filter(function(question) {
                let matchesTopic = true;
                let matchesScore = true;
                let matchesSearch = true;
                
                // Topic filter
                if (topicFilter !== '' && question.topic_id != topicFilter) {
                    matchesTopic = false;
                }
                
                // Score filter
                if (scoreFilter !== '' && question.score != scoreFilter) {
                    matchesScore = false;
                }
                
                // Search query
                if (searchQuery !== '') {
                    const content = question.content.toLowerCase();
                    if (!content.includes(searchQuery)) {
                        matchesSearch = false;
                    }
                }
                
                return matchesTopic && matchesScore && matchesSearch;
            });
            
            // Update UI with filtered questions
            displayQuestions(filteredQuestions);
        }
        
        // Display questions with pagination
        function displayQuestions(questions) {
            // Clear containers
            $('#card-view').empty();
            $('#questions-table-body').empty();
            
            if (questions.length === 0) {
                $('#card-view').html(`
                <div class="text-center py-5">
                    <i class="ri-search-line" style="font-size: 48px; color: #d1d1d1;"></i>
                    <p class="mt-2">ไม่พบข้อสอบที่ตรงกับเงื่อนไข</p>
                </div>`);
                return;
            }
            
            // Calculate pagination
            const totalPages = Math.ceil(questions.length / questionsPerPage);
            if (currentPage > totalPages) {
                currentPage = 1;
            }
            
            // Get current page items
            const startIndex = (currentPage - 1) * questionsPerPage;
            const endIndex = Math.min(startIndex + questionsPerPage, questions.length);
            const currentPageItems = questions.slice(startIndex, endIndex);
            
            // Create question cards
            let cardHtml = '<div class="questions-grid">';
            
            currentPageItems.forEach(function(question, index) {
                cardHtml += createQuestionCard(question, startIndex + index + 1);
                
                // Add to table view
                $('#questions-table-body').append(createQuestionTableRow(question, startIndex + index + 1));
            });
            
            cardHtml += '</div>';
            $('#card-view').html(cardHtml);
            
            // Update pagination
            updatePagination(questions.length, questionsPerPage, currentPage);
        }
        
        // Create question card HTML
        function createQuestionCard(question, index) {
            // Process HTML content safely
            const contentElement = document.createElement('div');
            contentElement.innerHTML = question.content;
            const plainText = contentElement.textContent || contentElement.innerText || '';
            const shortContent = plainText.length > 100 ? plainText.substring(0, 100) + '...' : plainText;
            
            // Prepare image HTML if question has an image
            let imageHtml = '';
            if (question.image) {
                imageHtml = `
                <div class="question-image">
                    <img src="uploads/questions/${question.image}" alt="${question.image_description || 'รูปภาพประกอบคำถาม'}" />
                </div>`;
            }
            
            // Create HTML for each choice
            let choicesHtml = '<ul class="choice-list">';
            question.choices.forEach(function(choice, choiceIndex) {
                const choiceClass = choice.is_correct == 1 ? 'correct' : '';
                const choiceLabel = String.fromCharCode(65 + choiceIndex); // A, B, C, D, ...
                
                // Prepare choice image HTML if it exists
                let choiceImageHtml = '';
                if (choice.image) {
                    choiceImageHtml = `
                    <div class="choice-image">
                        <img src="uploads/choices/${choice.image}" alt="${choice.image_description || 'รูปภาพประกอบตัวเลือก'}" />
                    </div>`;
                }
                
                choicesHtml += `
                <li class="choice-list-item ${choiceClass}">
                    <span class="choice-label">${choiceLabel}.</span>
                    <span class="choice-text">${choice.content}</span>
                    ${choiceImageHtml}
                </li>`;
            });
            choicesHtml += '</ul>';
            
            return `
            <div class="question-card">
                <div class="question-card-header">
                    <span class="question-number">ข้อที่ ${index}</span>
                    <div class="question-actions">
                        <button type="button" class="btn btn-sm btn-primary edit-question-btn" data-id="${question.question_id}">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-question-btn" data-id="${question.question_id}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
                <div class="question-card-body">
                    <div class="question-content">
                        ${shortContent}
                    </div>
                    ${imageHtml}
                    ${choicesHtml}
                </div>
            </div>`;
        }
        
        // Create question table row HTML
        function createQuestionTableRow(question, index) {
            // Get topic name from filter dropdown
            const topicName = $(`#filter-topic option[value="${question.topic_id}"]`).text() || 'ไม่ระบุ';
            
            // Process HTML content safely
            const contentElement = document.createElement('div');
            contentElement.innerHTML = question.content;
            const plainText = contentElement.textContent || contentElement.innerText || '';
            const shortContent = plainText.length > 70 ? plainText.substring(0, 70) + '...' : plainText;
            
            // Check if question has image
            let imageCell = 'ไม่มีรูปภาพ';
            if (question.image) {
                imageCell = `<img src="uploads/questions/${question.image}" alt="รูปภาพประกอบ" height="50" class="cursor-pointer" onclick="showImagePreview('uploads/questions/${question.image}')">`;
            }
            
            return `
            <tr>
                <td>${index}</td>
                <td>${topicName}</td>
                <td>${shortContent}</td>
                <td class="text-center">${imageCell}</td>
                <td class="text-center">${question.score}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary edit-question-btn" data-id="${question.question_id}">
                        <i class="ri-pencil-line me-1"></i> แก้ไข
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-question-btn" data-id="${question.question_id}">
                        <i class="ri-delete-bin-line me-1"></i> ลบ
                    </button>
                </td>
            </tr>`;
        }
        
        // Update pagination controls
        function updatePagination(totalItems, itemsPerPage, currentPage) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            let paginationHtml = '';
            
            // Previous button
            paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>`;
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            }
            
            // Next button
            paginationHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>`;
            
            // Update pagination container
            $('#pagination').html(paginationHtml);
            
            // Add event listeners to pagination links
            $('.page-link').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page >= 1 && page <= totalPages) {
                    currentPage = page;
                    applyFilters();
                }
            });
        }
        
        // Reset question form
        function resetQuestionForm() {
            $('#question_id').val('');
            quillEditor.root.innerHTML = '';
            $('#question_content').val('');
            $('#question_score').val('1');
            $('#question_image').val('');
            $('#existing_image').val('');
            $('#remove_image').val('0');
            $('#question_image_description').val('');
            $('#question-image-preview').html('<span class="image-preview-placeholder"><i class="ri-add-line"></i></span>');
            $('#choices-container').empty();
            editingQuestion = false;
            $('#cancel-question-btn').hide();
            currentQuestionId = null;
        }
        
        // Setup for new question
        function setupNewQuestion() {
            // Add default choices
            addChoice();
            addChoice();
        }
        
        // Add new choice to the form
        function addChoice(content = '', isCorrect = false, image = null, imageDescription = '') {
            const choiceIndex = $('.choice-item').length;
            const choiceId = `choice_${Date.now()}_${choiceIndex}`;
            
            let choiceImagePreview = '<span class="image-preview-placeholder"><i class="ri-add-line"></i></span>';
            if (image) {
                choiceImagePreview = `<img src="uploads/choices/${image}" alt="${imageDescription || 'รูปภาพประกอบตัวเลือก'}">`;
            }
            
            const choiceHtml = `
            <div class="choice-item">
                <div class="form-check">
                    <input class="form-check-input choice-radio" type="radio" name="correct_choice" value="${choiceId}" id="radio_${choiceId}" ${isCorrect ? 'checked' : ''}>
                </div>
                <div class="choice-content">
                    <input type="text" class="form-control choice-content-input" name="choice_content[${choiceId}]" value="${content}" placeholder="กรอกเนื้อหาตัวเลือก" required>
                    <input type="hidden" name="choice_id[${choiceId}]" value="">
                    <input type="hidden" class="remove-choice-image" name="remove_choice_image[${choiceId}]" value="0">
                </div>
                <div class="choice-image-preview">
                    ${choiceImagePreview}
                </div>
                <div class="choice-actions">
                    <div class="form-control-file-wrapper">
                        <button type="button" class="btn btn-sm btn-outline-primary choice-image-btn">
                            <i class="ri-image-add-line"></i>
                        </button>
                        <input type="file" class="choice-image-input" name="choice_image[${choiceId}]" accept="image/*">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger choice-remove-image-btn" title="ลบรูปภาพ">
                        <i class="ri-image-close-line"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-choice-btn" title="ลบตัวเลือก">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>`;
            
            $('#choices-container').append(choiceHtml);
            
            // If image exists, show the image
            if (image) {
                $(`#choice_image_${choiceId}`).attr('src', `uploads/choices/${image}`);
            }
        }
        
        // Save question to API
        function saveQuestion() {
            // Prepare form data
            const formData = new FormData($('#question-form')[0]);
            
            // Add question content from Quill editor
            formData.set('question_content', quillEditor.root.innerHTML);
            
            // Add action based on whether we're editing or creating
            formData.append('action', editingQuestion ? 'update' : 'create');
            
            showLoading();
            
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
                        // Show success message
                        swalCustom.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: editingQuestion ? 'อัปเดตคำถามเรียบร้อยแล้ว' : 'เพิ่มคำถามเรียบร้อยแล้ว'
                        });
                        
                        // Reset form for new question
                        resetQuestionForm();
                        setupNewQuestion();
                        
                        // Reload questions
                        loadQuestions();
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถบันทึกคำถามได้'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // Edit question
        function editQuestion(questionId) {
            showLoading();
            
            $.ajax({
                url: `api/question-api.php?action=get&id=${questionId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const question = response.data;
                        
                        // Ensure we're on the right topic
                        currentTopicId = question.topic_id;
                        
                        // Update selected topic in UI
                        $('.topic-card').removeClass('active');
                        $(`.select-topic-btn[data-id="${currentTopicId}"]`).closest('.topic-card').addClass('active');
                        
                        // Get topic name
                        const topicName = $(`.select-topic-btn[data-id="${currentTopicId}"]`).data('name');
                        $('#selected-topic-title').text(`จัดการคำถาม: ${topicName}`);
                        
                        // Show question form
                        $('#select-topic-message').hide();
                        $('#question-form').show();
                        
                        // Fill form with question data
                        $('#question_id').val(question.question_id);
                        $('#topic_id').val(question.topic_id);
                        quillEditor.root.innerHTML = question.content;
                        $('#question_content').val(question.content);
                        $('#question_score').val(question.score);
                        
                        // Handle question image
                        if (question.image) {
                            $('#existing_image').val(question.image);
                            $('#question-image-preview').html(`<img src="uploads/questions/${question.image}" alt="${question.image_description || 'รูปภาพประกอบคำถาม'}">`);
                            $('#question_image_description').val(question.image_description || '');
                        } else {
                            $('#question-image-preview').html('<span class="image-preview-placeholder"><i class="ri-add-line"></i></span>');
                            $('#question_image_description').val('');
                        }
                        
                        // Reset choices container
                        $('#choices-container').empty();
                        
                        // Add choices
                        question.choices.forEach(function(choice) {
                            addChoice(
                                choice.content,
                                choice.is_correct == 1,
                                choice.image,
                                choice.image_description
                            );
                        });
                        
                        // Set editing mode
                        editingQuestion = true;
                        $('#cancel-question-btn').show();
                        currentQuestionId = question.question_id;
                        
                        // Scroll to question form in mobile view
                        if ($(window).width() < 992) {
                            $('.tab-btn[data-target="questions"]').click();
                            $('html, body').animate({
                                scrollTop: $("#questions-section").offset().top - 100
                            }, 500);
                        }
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถดึงข้อมูลคำถามได้'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        }
        
        // Delete question
        function deleteQuestion(questionId) {
            showLoading();
            
            $.ajax({
        url: 'api/question-api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            question_id: questionId,
                            csrf_token: $('#question-form input[name="csrf_token"]').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // Show success message
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบคำถามเรียบร้อยแล้ว'
                                });
                                
                                // Reload questions
                                loadQuestions();
                                
                                // Reload topics (to update question counts)
                                loadTopics();
                                
                                // If current question is deleted, reset form
                                if (currentQuestionId == questionId) {
                                    resetQuestionForm();
                                    setupNewQuestion();
                                }
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถลบคำถามได้'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            hideLoading();
                            
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                            });
                        }
                    });
                }
                
                // Show image preview in modal
                function showImagePreview(src) {
                    $('#preview-image-large').attr('src', src);
                    $('#imagePreviewModal').modal('show');
                }
                
                // Reset modals when closed
                $('#addTopicModal').on('hidden.bs.modal', function() {
                    $('#addTopicForm')[0].reset();
                    $('#addTopicForm .is-invalid').removeClass('is-invalid');
                    $('#addTopicForm input[name="exam_set_id"]').val(<?= $examSetId ?>);
                });
                
                $('#editTopicModal').on('hidden.bs.modal', function() {
                    $('#editTopicForm .is-invalid').removeClass('is-invalid');
                });
            });
            </script>
          </body>
        </html>