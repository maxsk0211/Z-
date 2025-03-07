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
    <link rel="stylesheet" href="../assets/vendor/libs/dropzone/dropzone.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/quill/editor.css" />
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
        flex: 2;
        min-width: 300px;
      }
      
      /* Card Styling */
      .topic-card {
        transition: all 0.3s ease;
        margin-bottom: 16px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
        position: relative;
      }
      
      .topic-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
      }
      
      .topic-card.active {
        border-left: 4px solid #5D87FF;
      }
      
      .topic-card.active::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background-color: #5D87FF;
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
        color: #2c2c2c;
      }
      
      .topic-info {
        color: #6c757d;
        font-size: 14px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      
      .topic-info i {
        font-size: 16px;
      }
      
      .topic-actions {
        display: flex;
        gap: 8px;
      }
      
      /* Topic Search and Filter */
      .topic-filters {
        display: flex;
        margin-bottom: 1rem;
        gap: 8px;
      }
      
      .topic-search {
        flex: 1;
      }
      
      .topic-sort .dropdown-item.active {
        background-color: #5D87FF;
        color: white;
      }
      
      /* Question Form */
      .modal-lg {
        max-width: 900px;
      }
      
      .question-form-tabs .nav-link {
        font-weight: 500;
        padding: 12px 15px;
      }
      
      .question-form-tabs .nav-link.active {
        background-color: #5D87FF;
        color: white;
        border-radius: 4px;
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
        width: 150px;
        height: 100px;
        border-radius: 8px;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-top: 10px;
        border: 1px dashed #ccc;
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
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        transition: all 0.2s;
      }
      
      .choice-item:hover {
        border-color: #adb5bd;
        background-color: #f8f9fa;
      }
      
      .choice-item.is-correct {
        border-color: #2E7D32;
        background-color: rgba(46, 125, 50, 0.04);
      }
      
      .choice-content {
        flex: 1;
      }
      
      .choice-actions {
        display: flex;
        gap: 8px;
      }
      
      .choice-image-preview {
        width: 50px;
        height: 50px;
        border-radius: 4px;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px dashed #ccc;
      }
      
      .choice-image-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
      }
      
      /* Question Cards */
      .questions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 20px;
      }
      
      .question-card {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
        background-color: white;
        position: relative;
        border: 1px solid #e0e0e0;
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
        background-color: #f8f9fa;
      }
      
      .question-meta {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      
      .question-number {
        font-weight: 600;
        font-size: 16px;
        color: #2c2c2c;
      }
      
      .question-topic {
        font-size: 0.85rem;
        padding: 3px 8px;
        border-radius: 15px;
        background-color: #e0e0ff;
        color: #5D87FF;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
      }
      
      .question-score {
        background-color: #5D87FF;
        color: white;
        padding: 3px 8px;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 500;
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
        max-height: 100px;
        overflow: hidden;
        position: relative;
        line-height: 1.5;
      }
      
      .question-content-full {
        max-height: none;
      }
      
      .question-content:not(.question-content-full)::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 30px;
        background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,1));
      }
      
      .question-image {
        margin: 10px 0 16px;
        max-width: 100%;
        border-radius: 8px;
        overflow: hidden;
        text-align: center;
      }
      
      .question-image img {
        max-width: 100%;
        max-height: 150px;
        object-fit: contain;
        cursor: pointer;
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
        border-radius: 15px;
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
      
      /* Empty States */
      .empty-state {
        text-align: center;
        padding: 2rem;
      }
      
      .empty-state-icon {
        font-size: 3rem;
        color: #cfd8dc;
        margin-bottom: 1rem;
      }
      
      .empty-state-text {
        color: #78909c;
        margin-bottom: 1.5rem;
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
        
        .floating-btn {
          display: flex;
        }
      }
      
      @media (max-width: 768px) {
        .topic-filters {
          flex-direction: column;
        }
        
        .question-meta {
          flex-direction: column;
          align-items: flex-start;
          gap: 5px;
        }
        
        .question-topic, .question-score {
          max-width: none;
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
              <div class="card mb-4 animate__animated animate__fadeIn">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                      <h4 class="mb-1"><?= htmlspecialchars($examSet['name']) ?></h4>
                      <p class="text-muted mb-0"><?= $examSet['description'] ? htmlspecialchars($examSet['description']) : 'ไม่มีคำอธิบาย' ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-2 mt-md-0">
                      <div class="text-center px-3" style="border-right: 1px solid #e0e0e0;">
                        <h5 class="mb-0"><?= $totalTopics ?></h5>
                        <p class="text-muted mb-0">หัวข้อ</p>
                      </div>
                      <div class="text-center px-3" style="border-right: 1px solid #e0e0e0;">
                        <h5 class="mb-0"><?= $totalQuestions ?></h5>
                        <p class="text-muted mb-0">ข้อสอบ</p>
                      </div>
                      <div class="text-center px-3">
                        <span class="status-badge <?= $examSet['status'] == 1 ? 'status-active' : 'status-inactive' ?>">
                          <?= $examSet['status'] == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน' ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Split Content -->
              <div class="content-split">
                <!-- Left Content - Topics -->
                <div class="content-left animate__animated animate__fadeIn animate__delay-1s">
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">หัวข้อการสอบ</h5>
                      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                        <i class="ri-add-line me-1"></i> เพิ่มหัวข้อ
                      </button>
                    </div>
                    <div class="card-body">
                      <!-- Topic Search and Filter -->
                      <div class="topic-filters">
                        <div class="topic-search">
                          <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" class="form-control" id="topic-search-input" placeholder="ค้นหาหัวข้อ...">
                          </div>
                        </div>
                        <div class="dropdown topic-sort">
                          <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="topicSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-sort-asc"></i> เรียงตาม
                          </button>
                          <ul class="dropdown-menu" aria-labelledby="topicSortDropdown">
                            <li><a class="dropdown-item active" href="#" data-sort="name-asc"><i class="ri-sort-asc"></i> ชื่อ (ก-ฮ)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="name-desc"><i class="ri-sort-desc"></i> ชื่อ (ฮ-ก)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="questions-asc"><i class="ri-sort-asc"></i> จำนวนข้อสอบ (น้อย-มาก)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="questions-desc"><i class="ri-sort-desc"></i> จำนวนข้อสอบ (มาก-น้อย)</a></li>
                          </ul>
                        </div>
                      </div>
                      
                      <!-- Topics Container -->
                      <div id="topics-container">
                        <!-- Topic cards will be loaded here via AJAX -->
                        <div class="text-center py-5" id="topics-loading">
                          <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                          </div>
                          <p class="mt-2">กำลังโหลดข้อมูล...</p>
                        </div>
                      </div>
                      
                      <!-- Empty State for Topics -->
                      <div id="no-topics-message" style="display: none;">
                        <div class="empty-state">
                          <div class="empty-state-icon">
                            <i class="ri-file-list-3-line"></i>
                          </div>
                          <p class="empty-state-text">ยังไม่มีหัวข้อการสอบ</p>
                          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                            <i class="ri-add-line me-1"></i> เพิ่มหัวข้อ
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Right Content - Question Management -->
                <div class="content-right animate__animated animate__fadeIn animate__delay-2s">
                  <!-- Questions Display -->
                  <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">ข้อสอบทั้งหมด</h5>
                      <div class="d-flex gap-2">
                        <button type="button" id="add-question-btn" class="btn btn-primary btn-sm">
                          <i class="ri-add-line me-1"></i> เพิ่มข้อสอบ
                        </button>
                        <div class="btn-group btn-group-sm" role="group">
                          <button type="button" class="btn btn-outline-secondary active" id="btn-card-view">
                            <i class="ri-layout-grid-line"></i>
                          </button>
                          <button type="button" class="btn btn-outline-secondary" id="btn-table-view">
                            <i class="ri-table-line"></i>
                          </button>
                        </div>
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
                              <option value="0.5">0.5 คะแนน</option>
                              <option value="1">1 คะแนน</option>
                              <option value="1.5">1.5 คะแนน</option>
                              <option value="2">2 คะแนน</option>
                              <option value="2.5">2.5 คะแนน</option>
                              <option value="3">3 คะแนน</option>
                              <option value="4">4 คะแนน</option>
                              <option value="5">5 คะแนน</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <div class="input-group">
                              <span class="input-group-text"><i class="ri-search-line"></i></span>
                              <input type="text" class="form-control" id="search-question" placeholder="ค้นหาข้อสอบ...">
                              <button class="btn btn-outline-primary" type="button" id="btn-search">
                                ค้นหา
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
                      </div>
                      
                      <!-- Empty State for Questions -->
                      <div id="no-questions-message" style="display: none;">
                        <div class="empty-state">
                          <div class="empty-state-icon">
                            <i class="ri-question-line"></i>
                          </div>
                          <p class="empty-state-text">ยังไม่มีข้อสอบในชุดนี้</p>
                          <button type="button" class="btn btn-primary" id="empty-add-question-btn">
                            <i class="ri-add-line me-1"></i> เพิ่มข้อสอบ
                          </button>
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
                                <th width="10%">รูปภาพ</th>
                                <th width="10%">คะแนน</th>
                                <th width="20%">จัดการ</th>
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
              </div>
            </div>
            <!-- / Content -->

            <!-- Modal เพิ่มหัวข้อ -->
            <div class="modal fade" id="addTopicModal" tabindex="-1" aria-labelledby="addTopicModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addTopicModalLabel">
                      <i class="ri-add-line me-1"></i> เพิ่มหัวข้อการสอบใหม่
                    </h5>
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
                        <div class="d-flex gap-3">
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_active" value="1" checked>
                            <label class="form-check-label" for="status_active">
                              ใช้งาน
                            </label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_inactive" value="0">
                            <label class="form-check-label" for="status_inactive">
                              ไม่ใช้งาน
                            </label>
                          </div>
                        </div>
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
            
            <!-- Modal แก้ไขหัวข้อ -->
            <div class="modal fade" id="editTopicModal" tabindex="-1" aria-labelledby="editTopicModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editTopicModalLabel">
                      <i class="ri-edit-box-line me-1"></i> แก้ไขข้อมูลหัวข้อการสอบ
                    </h5>
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
                        <label class="form-label">สถานะ</label>
                        <div class="d-flex gap-3">
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="edit_status_active" value="1">
                            <label class="form-check-label" for="edit_status_active">
                              ใช้งาน
                            </label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="edit_status_inactive" value="0">
                            <label class="form-check-label" for="edit_status_inactive">
                              ไม่ใช้งาน
                            </label>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="ri-close-line me-1"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" id="updateTopicBtn">
                      <i class="ri-save-line me-1"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal เพิ่ม/แก้ไขข้อสอบ -->
            <div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="questionModalLabel">เพิ่มข้อสอบใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-pills mb-4 question-form-tabs" id="questionFormTabs" role="tablist">
                      <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="question-tab" data-bs-toggle="tab" data-bs-target="#question-content-tab" type="button" role="tab" aria-controls="question-content-tab" aria-selected="true">
                          <i class="ri-question-line me-1"></i> ข้อมูลคำถาม
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link" id="choices-tab" data-bs-toggle="tab" data-bs-target="#choices-content-tab" type="button" role="tab" aria-controls="choices-content-tab" aria-selected="false">
                          <i class="ri-list-check-2 me-1"></i> ตัวเลือกคำตอบ
                        </button>
                      </li>
                    </ul>
                    
                    <!-- Form -->
                    <form id="question-form">
                      <input type="hidden" id="question_id" name="question_id" value="">
                      <input type="hidden" id="topic_id" name="topic_id" value="">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      
                      <!-- Tab Content -->
                      <div class="tab-content" id="questionFormTabContent">
                        <!-- Question Tab -->
                        <div class="tab-pane fade show active" id="question-content-tab" role="tabpanel" aria-labelledby="question-tab">
                          <div class="mb-3">
                            <label for="topic_selector" class="form-label">หัวข้อการสอบ <span class="text-danger">*</span></label>
                            <select class="form-select" id="topic_selector" required>
                              <option value="">-- เลือกหัวข้อ --</option>
                              <!-- Topics will be loaded here -->
                            </select>
                            <div class="form-text">เลือกหัวข้อที่ต้องการเพิ่มข้อสอบ</div>
                          </div>
                          
                          <div class="mb-3">
                            <label for="question_content_editor" class="form-label">เนื้อหาคำถาม <span class="text-danger">*</span></label>
                            <div id="question_content_editor" style="height: 200px;">
                              <!-- Quill editor will be initialized here -->
                            </div>
                            <input type="hidden" id="question_content" name="question_content">
                          </div>
                          
                          <div class="mb-3">
                            <label for="question_image" class="form-label">รูปภาพประกอบคำถาม (ถ้ามี)</label>
                            <div class="row">
                              <div class="col-lg-8 mb-2 mb-lg-0">
                                <div class="input-group">
                                  <span class="input-group-text"><i class="ri-image-add-line"></i></span>
                                  <input type="file" class="form-control" id="question_image" name="question_image" accept="image/*">
                                  <button class="btn btn-outline-danger" type="button" id="remove-image-btn" style="display: none;">
                                    <i class="ri-delete-bin-line"></i>
                                  </button>
                                </div>
                                <div class="form-text">ขนาดไฟล์ไม่เกิน 2MB (JPEG, PNG, GIF)</div>
                              </div>
                              <div class="col-lg-4 text-center">
                                <div class="image-preview mx-auto" id="question-image-preview">
                                  <span class="image-preview-placeholder">
                                    <i class="ri-image-add-line"></i>
                                  </span>
                                </div>
                              </div>
                            </div>
                            <input type="hidden" id="existing_image" name="existing_image" value="">
                            <input type="hidden" id="remove_image" name="remove_image" value="0">
                          </div>
                          
                          <div class="mb-3">
                            <label for="question_score" class="form-label">คะแนน <span class="text-danger">*</span></label>
                            <select class="form-select" id="question_score" name="question_score" required>
                              <option value="0.5">0.5 คะแนน</option>
                              <option value="1" selected>1 คะแนน</option>
                              <option value="1.5">1.5 คะแนน</option>
                              <option value="2">2 คะแนน</option>
                              <option value="2.5">2.5 คะแนน</option>
                              <option value="3">3 คะแนน</option>
                              <option value="4">4 คะแนน</option>
                              <option value="5">5 คะแนน</option>
                            </select>
                          </div>
                        </div>
                        
                        <!-- Choices Tab -->
                        <div class="tab-pane fade" id="choices-content-tab" role="tabpanel" aria-labelledby="choices-tab">
                          <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <label class="form-label">ตัวเลือกคำตอบ <span class="text-danger">*</span></label>
                              <button type="button" class="btn btn-outline-primary btn-sm" id="add-choice-btn">
                                <i class="ri-add-line me-1"></i> เพิ่มตัวเลือก
                              </button>
                            </div>
                            
                            <div id="choices-container">
                              <!-- Choices will be added here -->
                            </div>
                            
                            <div class="form-text mt-3">
                              <i class="ri-information-line me-1"></i> ต้องมีตัวเลือกอย่างน้อย 2 ตัวเลือก และเลือกคำตอบที่ถูกต้อง 1 ข้อ
                            </div>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="ri-close-line me-1"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" id="save-question-btn">
                      <i class="ri-save-line me-1"></i> บันทึกข้อสอบ
                    </button>
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
                    <img id="preview-image-large" src="" alt="Preview" class="img-fluid" style="max-height: 80vh;">
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
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false,
            confirmButtonText: '<i class="ri-check-line me-1"></i> ตกลง',
            cancelButtonText: '<i class="ri-close-line me-1"></i> ยกเลิก',
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
        let questionsPerPage = 8; // Increased from 6 to 8 for more questions per page
        let allQuestions = []; // Array to store all loaded questions
        let choiceCounter = 0; // Counter for unique choice IDs
        
        // Load topics on page load
        loadTopics();
        
        // Load questions on page load
        loadQuestions();
        
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
        
        // Topic search functionality
        $('#topic-search-input').on('input', function() {
            const searchQuery = $(this).val().toLowerCase();
            $('#topics-container .topic-card').each(function() {
                const topicName = $(this).find('.topic-title').text().toLowerCase();
                if (topicName.includes(searchQuery)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Topic sorting
        $('.topic-sort .dropdown-item').on('click', function(e) {
            e.preventDefault();
            
            // Update active state
            $('.topic-sort .dropdown-item').removeClass('active');
            $(this).addClass('active');
            
            const sortBy = $(this).data('sort');
            sortTopics(sortBy);
        });
        
        // Add Question button click
        $('#add-question-btn, #empty-add-question-btn').on('click', function() {
            resetQuestionForm();
            setupNewQuestion();
            $('#questionModalLabel').html('<i class="ri-add-line me-1"></i> เพิ่มข้อสอบใหม่');
            $('#questionModal').modal('show');
        });
        
        // Image file input change
        $('#question_image').on('change', function(e) {
            showImagePreview(this, '#question-image-preview', '#remove-image-btn');
        });
        
        // Remove question image button click
        $('#remove-image-btn').on('click', function() {
            $('#question_image').val('');
            $('#question-image-preview').html('<span class="image-preview-placeholder"><i class="ri-image-add-line"></i></span>');
            $('#remove_image').val('1');
            $(this).hide();
        });
        
        // Topic selector change
        $('#topic_selector').on('change', function() {
            const topicId = $(this).val();
            $('#topic_id').val(topicId);
        });
        
        // Add choice button click
        $('#add-choice-btn').on('click', function() {
            addChoice();
        });
        
        // Save question button click
        $('#save-question-btn').on('click', function() {
            if (validateQuestionForm()) {
                // Save question and move to the first tab for next time
                saveQuestion();
                $('#question-tab').tab('show');
            }
        });
        
        // Tab change event
        $('#questionFormTabs button[data-bs-toggle="tab"]').on('click', function(e) {
            const tabId = $(this).attr('id');
            
            // If moving to choices tab, validate question part first
            if (tabId === 'choices-tab') {
                if (!validateQuestionPart()) {
                    // Prevent tab change
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        });
        
        // Show second tab if moving to choices tab
        $('#questionModal').on('shown.bs.modal', function() {
            // Make sure there are at least two choices
            if ($('#choices-container .choice-item').length < 2) {
                // Add default choices if needed
                if ($('#choices-container .choice-item').length === 0) {
                    addChoice();
                }
                addChoice();
            }
        });
        
        // Edit topic button click (event delegation)
        $(document).on('click', '.edit-topic-btn', function(e) {
            e.stopPropagation();
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
                        
                        // Set the radio button based on status
                        if (topic.status == 1) {
                            $('#edit_status_active').prop('checked', true);
                        } else {
                            $('#edit_status_inactive').prop('checked', true);
                        }
                        
                        // Show modal
                        $('#editTopicModal').modal('show');
                    } else {
                        handleApiError(response, 'ไม่สามารถดึงข้อมูลหัวข้อการสอบได้');
                    }
                },
                error: function() {
                    hideLoading();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        });
        
        // Delete topic button click (event delegation)
        $(document).on('click', '.delete-topic-btn', function(e) {
            e.stopPropagation();
            const topicId = $(this).data('id');
            const topicName = $(this).data('name');
            
            confirmAction(
                'ยืนยันการลบ', 
                `คุณต้องการลบหัวข้อการสอบ <b>"${topicName}"</b> ใช่หรือไม่?<br><small class="text-danger">*หากลบแล้วจะไม่สามารถกู้คืนได้</small>`,
                function() {
                    deleteTopic(topicId);
                }
            );
        });
        
        // Select topic card click (event delegation)
        $(document).on('click', '.topic-card', function() {
            const topicId = $(this).data('id');
            selectTopic(topicId);
        });
        
        // Edit question button click (event delegation)
        $(document).on('click', '.edit-question-btn', function(e) {
            e.stopPropagation();
            const questionId = $(this).data('id');
            editQuestion(questionId);
        });
        
        // Delete question button click (event delegation)
        $(document).on('click', '.delete-question-btn', function(e) {
            e.stopPropagation();
            const questionId = $(this).data('id');
            
            confirmAction(
                'ยืนยันการลบ',
                'คุณต้องการลบคำถามนี้ใช่หรือไม่?<br><small class="text-danger">*หากลบแล้วจะไม่สามารถกู้คืนได้</small>',
                function() {
                    deleteQuestion(questionId);
                }
            );
        });
        
        // View image in modal (event delegation)
        $(document).on('click', '.question-image img, .choice-image img', function(e) {
            e.stopPropagation();
            const imgSrc = $(this).attr('src');
            $('#preview-image-large').attr('src', imgSrc);
            $('#imagePreviewModal').modal('show');
        });
        
        // Choice events (using event delegation)
        $(document).on('change', '.choice-radio', function() {
            // Update UI for all choices
            $('.choice-item').removeClass('is-correct');
            $(this).closest('.choice-item').addClass('is-correct');
        });
        
        $(document).on('change', '.choice-image-input', function() {
            const choiceItem = $(this).closest('.choice-item');
            if (showImagePreview(this, choiceItem.find('.choice-image-preview'), choiceItem.find('.choice-remove-image-btn'))) {
                // Reset remove image flag
                choiceItem.find('.remove-choice-image').val('0');
            }
        });
        
        $(document).on('click', '.choice-remove-image-btn', function(e) {
            e.preventDefault();
            const choiceItem = $(this).closest('.choice-item');
            choiceItem.find('.choice-image-input').val('');
            choiceItem.find('.choice-image-preview').html('<span class="image-preview-placeholder"><i class="ri-add-line"></i></span>');
            choiceItem.find('.remove-choice-image').val('1');
            $(this).hide();
        });
        
        $(document).on('click', '.remove-choice-btn', function(e) {
            e.preventDefault();
            // Don't allow removing if only 2 choices are left
            if ($('#choices-container .choice-item').length <= 2) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'ไม่สามารถลบได้',
                    text: 'ต้องมีตัวเลือกอย่างน้อย 2 ตัวเลือก'
                });
                return;
            }
            
            // Remove the choice item
            $(this).closest('.choice-item').remove();
        });
        
        // Save topic button click
        $('#saveTopicBtn').on('click', function() {
            if (validateForm('#addTopicForm')) {
                saveTopic();
            }
        });
        
        // Update topic button click
        $('#updateTopicBtn').on('click', function() {
            if (validateForm('#editTopicForm')) {
                updateTopic();
            }
        });
        
        // Functions
        
        // Show image preview from input file
        function showImagePreview(input, previewElement, removeButton) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size
                if (file.size > 2 * 1024 * 1024) {
                    swalCustom.fire({
                        icon: 'error',
                        title: 'ไฟล์มีขนาดใหญ่เกินไป',
                        text: 'กรุณาเลือกไฟล์ที่มีขนาดไม่เกิน 2MB'
                    });
                    input.value = '';
                    return false;
                }
                
                // Preview file
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(previewElement).html(`<img src="${e.target.result}" alt="Preview">`);
                    $(removeButton).show();
                };
                reader.readAsDataURL(file);
                return true;
            }
            return false;
        }
        
        // Strip HTML tags and get plain text
        function stripHtml(html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            return doc.body.textContent || '';
        }
        
        // Handle API errors consistently
        function handleApiError(response, defaultMessage) {
            const errorMessage = response && response.message ? response.message : defaultMessage;
            swalCustom.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: errorMessage
            });
        }
        
        // Confirmation dialog
        function confirmAction(title, text, confirmCallback) {
            swalCustom.fire({
                icon: 'warning',
                title: title,
                html: text,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-check-line me-1"></i> ตกลง',
                confirmButtonColor: '#dc3545',
                cancelButtonText: '<i class="ri-close-line me-1"></i> ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed && typeof confirmCallback === 'function') {
                    confirmCallback();
                }
            });
        }
        
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
                            // Populate filter dropdown and topic selector
                            $('#filter-topic').empty().append('<option value="">ทุกหัวข้อ</option>');
                            $('#topic_selector').empty().append('<option value="">-- เลือกหัวข้อ --</option>');
                            
                            topics.forEach(function(topic) {
                                // Add to topics container
                                const topicCard = createTopicCard(topic);
                                $('#topics-container').append(topicCard);
                                
                                // Add to filter dropdown
                                $('#filter-topic').append(`<option value="${topic.topic_id}">${topic.name}</option>`);
                                
                                // Add to topic selector in question form
                                $('#topic_selector').append(`<option value="${topic.topic_id}">${topic.name}</option>`);
                            });
                            
                            // Sort topics initially
                            sortTopics('name-asc');
                            
                            // Hide empty state
                            $('#no-topics-message').hide();
                        } else {
                            $('#no-topics-message').show();
                        }
                    } else {
                        handleApiError(response, 'ไม่สามารถโหลดข้อมูลหัวข้อการสอบได้');
                    }
                },
                error: function() {
                    hideLoading();
                    $('#topics-loading').hide();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Create topic card HTML
        function createTopicCard(topic) {
            // Determine status text and class
            const statusClass = topic.status == 1 ? 'status-active' : 'status-inactive';
            const statusText = topic.status == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
            
            return `
            <div class="topic-card ${topic.topic_id == currentTopicId ? 'active' : ''}" data-id="${topic.topic_id}">
                <div class="topic-card-header">
                    <div>
                        <h6 class="topic-title">${topic.name}</h6>
                        <div class="topic-info">
                            <span><i class="ri-question-line"></i> ${topic.question_count} ข้อ</span>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                        <div class="topic-actions">
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
                            $('#no-questions-message').hide();
                        } else {
                            $('#no-questions-message').show();
                        }
                    } else {
                        handleApiError(response, 'ไม่สามารถโหลดข้อมูลคำถามได้');
                    }
                },
                error: function() {
                    hideLoading();
                    $('#questions-loading').hide();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
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
                    // Extract text content from HTML
                    const tempElement = document.createElement('div');
                    tempElement.innerHTML = question.content;
                    const textContent = tempElement.textContent || tempElement.innerText || '';
                    
                    if (!textContent.toLowerCase().includes(searchQuery)) {
                        matchesSearch = false;
                    }
                }
                
                return matchesTopic && matchesScore && matchesSearch;
            });
            
            // Reset pagination to page 1 when filter changes
            currentPage = 1;
            
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
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="ri-search-line"></i>
                    </div>
                    <p class="empty-state-text">ไม่พบข้อสอบที่ตรงกับเงื่อนไขการค้นหา</p>
                    <button type="button" class="btn btn-outline-secondary" id="reset-filter-btn">
                        <i class="ri-refresh-line me-1"></i> ล้างตัวกรอง
                    </button>
                </div>`);
                
                // Add event handler for reset filter button
                $('#reset-filter-btn').on('click', function() {
                    $('#filter-topic').val('');
                    $('#filter-score').val('');
                    $('#search-question').val('');
                    applyFilters();
                });
                
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
                    <img src="/../../img/question/${question.image}" alt="รูปภาพประกอบคำถาม" loading="lazy" />
                </div>`;
            }
            
            // Get correct choice
            const correctChoice = question.choices.find(choice => choice.is_correct == 1);
            const correctChoiceText = correctChoice ? correctChoice.content : 'ไม่มีคำตอบที่ถูกต้อง';
            
            return `
            <div class="question-card">
                <div class="question-card-header">
                    <div class="question-meta">
                        <span class="question-number">ข้อที่ ${index}</span>
                        <span class="question-topic">${question.topic_name}</span>
                        <span class="question-score">${question.score} คะแนน</span>
                    </div>
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
                        ${question.content}
                    </div>
                    ${imageHtml}
                    <div class="mt-3">
                        <span class="fw-semibold text-success">คำตอบที่ถูกต้อง:</span>
                        <span>${correctChoiceText}</span>
                    </div>
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
            let imageCell = '<span class="text-muted">ไม่มีรูปภาพ</span>';
            if (question.image) {
                imageCell = `<img src="/../../img/question/${question.image}" alt="รูปภาพประกอบ" height="50" class="cursor-pointer" onclick="showImagePreview('/../../img/question/${question.image}')">`;
            }
            
            return `
            <tr>
                <td class="text-center">${index}</td>
                <td>${topicName}</td>
                <td>${shortContent}</td>
                <td class="text-center">${imageCell}</td>
                <td class="text-center">${question.score}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary edit-question-btn mb-1 me-1" data-id="${question.question_id}">
                        <i class="ri-pencil-line me-1"></i> แก้ไข
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-question-btn mb-1" data-id="${question.question_id}">
                        <i class="ri-delete-bin-line me-1"></i> ลบ
                    </button>
                </td>
            </tr>`;
        }
        
        // Update pagination controls
        function updatePagination(totalItems, itemsPerPage, currentPage) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            let paginationHtml = '';
            
            if (totalPages <= 1) {
                $('#pagination').empty();
                return;
            }
            
            // Previous button
            paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>`;
            
            // Page numbers
            const maxPageButtons = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPageButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxPageButtons - 1);
            
            // Adjust if at the end
            if (endPage - startPage + 1 < maxPageButtons) {
                startPage = Math.max(1, endPage - maxPageButtons + 1);
            }
            
            // First page and ellipsis
            if (startPage > 1) {
                paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>`;
                
                if (startPage > 2) {
                    paginationHtml += `
                    <li class="page-item disabled">
                        <a class="page-link" href="#">...</a>
                    </li>`;
                }
            }
            
            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            }
            
            // Ellipsis and last page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHtml += `
                    <li class="page-item disabled">
                        <a class="page-link" href="#">...</a>
                    </li>`;
                }
                
                paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
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
                    
                    // Scroll to top of questions section
                    $('html, body').animate({
                        scrollTop: $("#card-view").offset().top - 100
                    }, 300);
                }
            });
        }
        
        // Reset question form
        function resetQuestionForm() {
            // Reset form fields
            $('#question-form')[0].reset();
            $('#question_id').val('');
            $('#topic_id').val('');
            quillEditor.root.innerHTML = '';
            $('#question_content').val('');
            $('#existing_image').val('');
            $('#remove_image').val('0');
            $('#question-image-preview').html('<span class="image-preview-placeholder"><i class="ri-image-add-line"></i></span>');
            $('#remove-image-btn').hide();
            
            // Clear choices
            $('#choices-container').empty();
            
            // Reset editing state
            editingQuestion = false;
            currentQuestionId = null;
            
            // Reset tabs
            $('#question-tab').tab('show');
        }
        
        // Setup for new question
        function setupNewQuestion() {
            // Add default choices if needed
            if ($('#choices-container .choice-item').length === 0) {
                addChoice();
                addChoice();
            }
            
            // Set currently selected topic if available
            if (currentTopicId) {
                $('#topic_selector').val(currentTopicId);
                $('#topic_id').val(currentTopicId);
            }
        }
        
        // Add new choice to the form
        function addChoice(content = '', isCorrect = false, choiceId = '', imageUrl = null) {
            const uniqueId = choiceId || 'new_' + (++choiceCounter);
            
            // Prepare image preview HTML
            let imagePreviewHtml = '<span class="image-preview-placeholder"><i class="ri-add-line"></i></span>';
            let removeButtonStyle = 'display: none;';
            
            if (imageUrl) {
                imagePreviewHtml = `<img src="/../../img/question/${imageUrl}" alt="รูปภาพประกอบ">`;
                removeButtonStyle = '';
            }
            
            const choiceHtml = `
            <div class="choice-item ${isCorrect ? 'is-correct' : ''}">
                <div class="form-check">
                    <input class="form-check-input choice-radio" type="radio" name="correct_choice" value="${uniqueId}" id="radio_${uniqueId}" ${isCorrect ? 'checked' : ''}>
                </div>
                <div class="choice-content">
                    <input type="text" class="form-control choice-content-input" name="choice_content[${uniqueId}]" value="${content}" placeholder="กรอกเนื้อหาตัวเลือก" required>
                    <input type="hidden" name="choice_id[${uniqueId}]" value="${choiceId}">
                    <input type="hidden" class="remove-choice-image" name="remove_choice_image[${uniqueId}]" value="0">
                </div>
                <div class="choice-image-preview">
                    ${imagePreviewHtml}
                </div>
                <div class="choice-actions">
                    <div class="form-control-file-wrapper me-1">
                        <button type="button" class="btn btn-sm btn-outline-primary">
                            <i class="ri-image-add-line"></i>
                        </button>
                        <input type="file" class="choice-image-input" name="choice_image[${uniqueId}]" accept="image/*">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger choice-remove-image-btn me-1" style="${removeButtonStyle}">
                        <i class="ri-image-close-line"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-choice-btn">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>`;
            
            $('#choices-container').append(choiceHtml);
        }
        
        // Validate question form
        function validateQuestionForm() {
            // Validate question part first
            if (!validateQuestionPart()) {
                // Switch to question tab
                $('#question-tab').tab('show');
                return false;
            }
            
            // Validate choices part
            return validateChoicesPart();
        }
        
        // Validate question part
        function validateQuestionPart() {
            // Check topic selection
            if (!$('#topic_selector').val()) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณาเลือกหัวข้อการสอบ',
                    text: 'ต้องเลือกหัวข้อการสอบก่อนเพิ่มข้อสอบ'
                });
                return false;
            }
            
            // Check if question content is empty
            const questionContent = quillEditor.getText().trim();
            if (questionContent === '') {
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณากรอกเนื้อหาคำถาม',
                    text: 'เนื้อหาคำถามไม่สามารถเป็นค่าว่างได้'
                });
                return false;
            }
            
            // Sync question content with hidden input
            $('#question_content').val(quillEditor.root.innerHTML);
            
            // Save topic_id from selector
            $('#topic_id').val($('#topic_selector').val());
            
            return true;
        }
        
        // Validate choices part
        function validateChoicesPart() {
            // Check if there are at least 2 choices
            const choiceCount = $('#choices-container .choice-item').length;
            if (choiceCount < 2) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'ต้องมีตัวเลือกอย่างน้อย 2 ตัวเลือก',
                    text: 'กรุณาเพิ่มตัวเลือกให้ครบถ้วน'
                });
                return false;
            }

            // Check if there is at least one correct answer
            if (!$('input[name="correct_choice"]:checked').length) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณาเลือกคำตอบที่ถูกต้อง',
                    text: 'ต้องมีคำตอบที่ถูกต้อง 1 ข้อ'
                });
                return false;
            }

            // Check if all choice content fields are filled
            let allFilled = true;
            $('.choice-content-input').each(function() {
                if ($(this).val().trim() === '') {
                    allFilled = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!allFilled) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณากรอกเนื้อหาตัวเลือกให้ครบถ้วน',
                    text: 'เนื้อหาตัวเลือกไม่สามารถเป็นค่าว่างได้'
                });
                return false;
            }

            return true;
        }
        
        // Sort topics based on selected sort criteria
        function sortTopics(sortBy) {
            const topics = $('#topics-container .topic-card').toArray();
            
            topics.sort(function(a, b) {
                const $a = $(a);
                const $b = $(b);
                
                switch(sortBy) {
                    case 'name-asc':
                        return $a.find('.topic-title').text().localeCompare($b.find('.topic-title').text());
                    case 'name-desc':
                        return $b.find('.topic-title').text().localeCompare($a.find('.topic-title').text());
                    case 'questions-asc':
                        return parseInt($a.find('.topic-info span:first-child').text()) - 
                               parseInt($b.find('.topic-info span:first-child').text());
                    case 'questions-desc':
                        return parseInt($b.find('.topic-info span:first-child').text()) - 
                               parseInt($a.find('.topic-info span:first-child').text());
                    default:
                        return 0;
                }
            });
            
            // Reappend sorted topics
            const $container = $('#topics-container');
            $.each(topics, function(i, topic) {
                $container.append(topic);
            });
        }
        
        // Validate form inputs
        function validateForm(formSelector) {
            let isValid = true;
            
            $(formSelector + ' [required]').each(function() {
                if ($(this).val().trim() === '') {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (!isValid) {
                swalCustom.fire({
                    icon: 'error',
                    title: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                    text: 'กรุณากรอกข้อมูลในช่องที่มีเครื่องหมาย * กำกับ'
                });
            }
            
            return isValid;
        }
        
        // Save topic function
        function saveTopic() {
            showLoading();
            
            const formData = new FormData($('#addTopicForm')[0]);
            formData.append('action', 'create');
            
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
                        $('#addTopicModal').modal('hide');
                        
                        swalCustom.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: response.message
                        }).then(() => {
                            // Clear form
                            $('#addTopicForm')[0].reset();
                            
                            // Reload topics
                            loadTopics();
                        });
                    } else {
                        handleApiError(response, 'ไม่สามารถเพิ่มหัวข้อได้');
                    }
                },
                error: function() {
                    hideLoading();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Update topic function
        function updateTopic() {
            showLoading();
            
            const formData = new FormData($('#editTopicForm')[0]);
            formData.append('action', 'update');
            
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
                        $('#editTopicModal').modal('hide');
                        
                        swalCustom.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: response.message
                        }).then(() => {
                            // Reload topics
                            loadTopics();
                        });
                    } else {
                        handleApiError(response, 'ไม่สามารถอัปเดตหัวข้อได้');
                    }
                },
                error: function() {
                    hideLoading();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Delete topic function
        function deleteTopic(topicId) {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('topic_id', topicId);
            formData.append('csrf_token', '<?= $csrf_token ?>');
            
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
                        swalCustom.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: response.message
                        }).then(() => {
                            // Reload topics
                            loadTopics();
                        });
                    } else {
                        handleApiError(response, 'ไม่สามารถลบหัวข้อได้');
                    }
                },
                error: function() {
                    hideLoading();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Select topic function
        function selectTopic(topicId) {
            // Update current topic ID
            currentTopicId = topicId;
            
            // Update UI
            $('.topic-card').removeClass('active');
            $(`.topic-card[data-id="${topicId}"]`).addClass('active');
            
            // Update filter
            $('#filter-topic').val(topicId);
            
            // Apply filter
            applyFilters();
            
            // Scroll to questions section on mobile
            if (window.innerWidth < 992) {
                $('html, body').animate({
                    scrollTop: $(".content-right").offset().top - 70
                }, 500);
            }
        }
        
        // Edit question function
        function editQuestion(questionId) {
            showLoading();
            
            // Reset form
            resetQuestionForm();
            
            // Set editing state
            editingQuestion = true;
            currentQuestionId = questionId;
            
            // Update modal title
            $('#questionModalLabel').html('<i class="ri-edit-box-line me-1"></i> แก้ไขข้อสอบ');
            
            // Fetch question data
            $.ajax({
                url: `api/question-api.php?action=get&id=${questionId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const question = response.data;
                        
                        // Fill form with question data
                        $('#question_id').val(question.question_id);
                        $('#topic_id').val(question.topic_id);
                        $('#topic_selector').val(question.topic_id);
                        quillEditor.root.innerHTML = question.content;
                        $('#question_content').val(question.content);
                        $('#question_score').val(question.score);
                        
                        // Handle image
                        if (question.image) {
                            $('#existing_image').val(question.image);
                            $('#question-image-preview').html(`<img src="../../img/question/${question.image}" alt="Preview">`);
                            $('#remove-image-btn').show();
                        } else {
                            $('#existing_image').val('');
                            $('#question-image-preview').html('<span class="image-preview-placeholder"><i class="ri-image-add-line"></i></span>');
                            $('#remove-image-btn').hide();
                        }
                        
                        // Clear existing choices
                        $('#choices-container').empty();
                        
                        // Add choices
                        question.choices.forEach(function(choice) {
                            addChoice(
                                choice.content,
                                choice.is_correct == 1,
                                choice.choice_id,
                                choice.image
                            );
                        });
                        
                        // Show modal
                        $('#questionModal').modal('show');
                    } else {
                        handleApiError(response, 'ไม่สามารถดึงข้อมูลคำถามได้');
                    }
                },
                error: function() {
                    hideLoading();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Delete question function
        function deleteQuestion(questionId) {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('question_id', questionId);
            formData.append('csrf_token', '<?= $csrf_token ?>');
            
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
                        swalCustom.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: response.message
                        }).then(() => {
                            // Reload questions
                            loadQuestions();
                            
                            // Reload topics (to update question count)
                            loadTopics();
                        });
                    } else {
                        handleApiError(response, 'ไม่สามารถลบคำถามได้');
                    }
                },
                error: function() {
                    hideLoading();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }
        
        // Save question function
        function saveQuestion() {
            showLoading();
            
            // Create form data from the form
            const formData = new FormData($('#question-form')[0]);
            
            // Add question content from Quill editor
            const questionContent = quillEditor.root.innerHTML.trim();
            formData.set('question_content', questionContent);
            
            // Add action based on whether we're editing or creating a new question
            formData.append('action', editingQuestion ? 'update' : 'create');
            
            // Ensure the correct choice is set
            const correctChoice = $('input[name="correct_choice"]:checked').val();
            if (correctChoice) {
                formData.set('correct_choice', correctChoice);
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
                        $('#questionModal').modal('hide');
                        
                        swalCustom.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: response.message
                        }).then(() => {
                            // Reset form
                            resetQuestionForm();
                            
                            // Reload questions
                            loadQuestions();
                            
                            // Reload topics (to update question count)
                            loadTopics();
                        });
                    } else {
                        handleApiError(response, 'ไม่สามารถบันทึกข้อสอบได้');
                    }
                },
                error: function() {
                    hideLoading();
                    handleApiError(null, 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                }
            });
        }

        // Global function to show image preview
        function showImagePreview(imgSrc) {
            $('#preview-image-large').attr('src', imgSrc);
            $('#imagePreviewModal').modal('show');
        }
        
        // Reset form when modals are closed
        $('#addTopicModal').on('hidden.bs.modal', function() {
            $('#addTopicForm')[0].reset();
            $('#addTopicForm .is-invalid').removeClass('is-invalid');
        });
        
        $('#editTopicModal').on('hidden.bs.modal', function() {
            $('#editTopicForm .is-invalid').removeClass('is-invalid');
        });
        
        $('#questionModal').on('hidden.bs.modal', function() {
            resetQuestionForm();
        });
    });
    </script>
  </body>
</html>
