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
                      <i class="ri-add-box-line me-1"></i> เพิ่มชุดข้อสอบใหม่
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="addExamSetForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      
                      <div class="mb-3">
                        <label for="name" class="form-label">ชื่อชุดข้อสอบ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อชุดข้อสอบ</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียด</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="อธิบายรายละเอียดของชุดข้อสอบ"></textarea>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="ri-close-line me-1"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" id="saveExamSetBtn">
                      <i class="ri-save-line me-1"></i> บันทึก
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal แก้ไขชุดข้อสอบ -->
            <div class="modal fade" id="editExamSetModal" tabindex="-1" aria-labelledby="editExamSetModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editExamSetModalLabel">
                      <i class="ri-edit-box-line me-1"></i> แก้ไขข้อมูลชุดข้อสอบ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="editExamSetForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" id="edit_exam_set_id" name="exam_set_id">
                      
                      <div class="mb-3">
                        <label for="edit_name" class="form-label">ชื่อชุดข้อสอบ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อชุดข้อสอบ</div>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      <i class="ri-close-line me-1"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" id="updateExamSetBtn">
                      <i class="ri-save-line me-1"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal ดูรายละเอียดชุดข้อสอบ -->
            <div class="modal fade" id="viewExamSetModal" tabindex="-1" aria-labelledby="viewExamSetModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="viewExamSetModalLabel">
                      <i class="ri-file-info-line me-1"></i> รายละเอียดชุดข้อสอบ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">ชื่อชุดข้อสอบ:</div>
                      <div class="col-md-8" id="view_name"></div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">รายละเอียด:</div>
                      <div class="col-md-8" id="view_description">-</div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">จำนวนหัวข้อ:</div>
                      <div class="col-md-8" id="view_topic_count"></div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">จำนวนคำถาม:</div>
                      <div class="col-md-8" id="view_question_count">-</div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">สถานะ:</div>
                      <div class="col-md-8" id="view_status"></div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">สร้างเมื่อ:</div>
                      <div class="col-md-8" id="view_created_at"></div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">สร้างโดย:</div>
                      <div class="col-md-8" id="view_created_by"></div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-4 fw-semibold">แก้ไขล่าสุด:</div>
                      <div class="col-md-8" id="view_updated_at"></div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                      <i class="ri-close-line me-1"></i> ปิด
                    </button>
                    <a href="#" id="view_topics_btn" class="btn btn-info">
                      <i class="ri-list-check-2 me-1"></i> จัดการหัวข้อ
                    </a>
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
    <script src="../assets/vendor/libs/moment/moment.min.js"></script>
    <script src="../assets/vendor/libs/moment/moment-with-locales.min.js"></script>
    
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
        
        // ตั้งค่า moment.js ให้ใช้ภาษาไทย
        moment.locale('th');
        
        // Function to show loading overlay
        function showLoading() {
            $('#loadingOverlay').addClass('show');
        }
        
        // Function to hide loading overlay
        function hideLoading() {
            $('#loadingOverlay').removeClass('show');
        }
        
        // สร้างฟังก์ชันสำหรับกำหนดสีของ badge จำนวนหัวข้อ
        function getCountBadgeClass(count) {
            if (count > 5) return 'high';
            if (count > 0) return 'medium';
            return 'low';
        }
        
        // สร้างฟังก์ชันสำหรับแสดงไอคอนของ badge จำนวนหัวข้อ
        function getCountBadgeIcon(count) {
            if (count > 5) return '<i class="ri-bookmark-3-fill"></i>';
            if (count > 0) return '<i class="ri-bookmark-2-line"></i>';
            return '<i class="ri-bookmark-line"></i>';
        }
        
        // สร้างฟังก์ชันสำหรับเปลี่ยนรูปแบบการแสดงผลวันที่
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = moment(dateString);
            return date.format('D MMM YYYY');
        }
        
        // สร้างฟังก์ชันสำหรับแสดงวันที่แบบ relative time (เช่น 3 วันที่แล้ว)
        function formatRelativeTime(dateString) {
            if (!dateString) return '';
            const date = moment(dateString);
            return date.fromNow();
        }
        
        // Initialize DataTable
        const examSetTable = $('#examSetTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "api/exam-set-api.php?action=list",
                type: "GET",
                dataSrc: function(json) {
                    hideLoading();
                    updateCardView(json.data);
                    return json.data;
                },
                error: function(xhr, error, thrown) {
                    hideLoading();
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                    });
                }
            });
        });
        
        // Edit Exam Set - fetch data and open modal
        $(document).on('click', '.edit-btn', function() {
            const examSetId = $(this).data('id');
            
            showLoading();
            
            $.ajax({
                url: `api/exam-set-api.php?action=get&id=${examSetId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const data = response.data;
                        
                        // Fill form with data
                        $('#edit_exam_set_id').val(data.exam_set_id);
                        $('#edit_name').val(data.name);
                        $('#edit_description').val(data.description || '');
                        $('#edit_status').val(data.status);
                        
                        // Show modal
                        $('#editExamSetModal').modal('show');
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถดึงข้อมูลชุดข้อสอบได้'
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

        // Form validation function
        function validateForm(formId) {
            const form = $(formId);
            let isValid = true;
            
            form.find('input[required], select[required]').each(function() {
                if ($(this).val() === '') {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            return isValid;
        }
        
        // Remove validation styling on input
        $('input, select, textarea').on('focus', function() {
            $(this).removeClass('is-invalid');
        });
        
        // Save new exam set
        $('#saveExamSetBtn').on('click', function() {
            if (validateForm('#addExamSetForm')) {
                const formData = new FormData($('#addExamSetForm')[0]);
                formData.append('action', 'create');
                
                showLoading();
                
                $.ajax({
                    url: 'api/exam-set-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // Close modal and reset form
                            $('#addExamSetModal').modal('hide');
                            $('#addExamSetForm')[0].reset();
                            
                            // Reload exam set table
                            examSetTable.ajax.reload();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'เพิ่มชุดข้อสอบเรียบร้อยแล้ว',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // อัปเดตจำนวนสถิติ
                            refreshStats();
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถเพิ่มชุดข้อสอบได้'
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
        
        // Update exam set
        $('#updateExamSetBtn').on('click', function() {
            if (validateForm('#editExamSetForm')) {
                const formData = new FormData($('#editExamSetForm')[0]);
                formData.append('action', 'update');
                
                showLoading();
                
                $.ajax({
                    url: 'api/exam-set-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // Close modal
                            $('#editExamSetModal').modal('hide');
                            
                            // Reload exam set table
                            examSetTable.ajax.reload();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'อัปเดตข้อมูลชุดข้อสอบเรียบร้อยแล้ว',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // อัปเดตจำนวนสถิติ
                            refreshStats();
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถอัปเดตข้อมูลชุดข้อสอบได้'
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
        
        // Delete exam set
        $(document).on('click', '.delete-btn', function() {
            const examSetId = $(this).data('id');
            const examSetName = $(this).data('name');
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                html: `คุณต้องการลบชุดข้อสอบ <span class="fw-bold">"${examSetName}"</span> ใช่หรือไม่?<br><small class="text-danger">*หากลบแล้วจะไม่สามารถกู้คืนได้</small>`,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> ลบ',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'ยกเลิก',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: 'api/exam-set-api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            exam_set_id: examSetId,
                            csrf_token: '<?= $csrf_token ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // Reload exam set table
                                examSetTable.ajax.reload();
                                
                                // Show success message
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบชุดข้อสอบเรียบร้อยแล้ว',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                
                                // อัปเดตจำนวนสถิติ
                                refreshStats();
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถลบชุดข้อสอบได้'
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
        
        // ฟังก์ชันรีเฟรชข้อมูลสถิติ
        function refreshStats() {
            $.ajax({
                url: 'api/stats-api.php?action=exam_stats',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // อัปเดตตัวเลขใน stats cards
                        const stats = response.data;
                        $('.stats-cards h5').each(function(index) {
                            let value = 0;
                            switch(index) {
                                case 0: value = stats.total_exam_sets; break;
                                case 1: value = stats.active_exam_sets; break;
                                case 2: value = stats.inactive_exam_sets; break;
                                case 3: value = stats.total_topics; break;
                            }
                            $(this).text(value.toLocaleString());
                        });
                    }
                }
            });
        }
        
        // Reset add form when modal is closed
        $('#addExamSetModal').on('hidden.bs.modal', function() {
            $('#addExamSetForm')[0].reset();
            $('#addExamSetForm .is-invalid').removeClass('is-invalid');
        });
        
        // Reset edit form when modal is closed
        $('#editExamSetModal').on('hidden.bs.modal', function() {
            $('#editExamSetForm .is-invalid').removeClass('is-invalid');
        });
        
        // ข้อมูลตัวอย่างที่จะแสดง
        function loadSampleData() {
            // สร้างข้อมูลตัวอย่างสำหรับทดสอบ UI
            const sampleData = [
                {
                    exam_set_id: 1,
                    name: "ชุดข้อสอบวิชาคณิตศาสตร์",
                    description: "ชุดข้อสอบวิชาคณิตศาสตร์สำหรับนักเรียนระดับมัธยมศึกษาตอนปลาย ครอบคลุมเนื้อหาเรื่องพีชคณิต เรขาคณิต และแคลคูลัส",
                    topic_count: 8,
                    status: 1,
                    created_at: "2025-03-01 10:30:00",
                    updated_at: "2025-03-02 15:45:00",
                    created_by_name: "ผู้ดูแลระบบ"
                },
                {
                    exam_set_id: 2,
                    name: "ชุดข้อสอบวิชาวิทยาศาสตร์",
                    description: "ชุดข้อสอบวิชาวิทยาศาสตร์ ครอบคลุมเนื้อหาฟิสิกส์ เคมี และชีววิทยา",
                    topic_count: 5,
                    status: 1,
                    created_at: "2025-03-01 14:20:00",
                    updated_at: "2025-03-01 14:20:00",
                    created_by_name: "ผู้ดูแลระบบ"
                },
                {
                    exam_set_id: 3,
                    name: "ชุดข้อสอบวิชาภาษาอังกฤษ",
                    description: "ชุดข้อสอบวิชาภาษาอังกฤษ ทดสอบความรู้ด้านไวยากรณ์ คำศัพท์ การอ่าน และการเขียน",
                    topic_count: 4,
                    status: 1,
                    created_at: "2025-03-02 09:15:00",
                    updated_at: "2025-03-02 09:15:00",
                    created_by_name: "ผู้ดูแลระบบ"
                },
                {
                    exam_set_id: 4,
                    name: "ชุดข้อสอบวิชาสังคมศึกษา",
                    description: "ชุดข้อสอบวิชาสังคมศึกษา ครอบคลุมเนื้อหาประวัติศาสตร์ ภูมิศาสตร์ และหน้าที่พลเมือง",
                    topic_count: 3,
                    status: 0,
                    created_at: "2025-03-02 16:40:00",
                    updated_at: "2025-03-03 10:20:00",
                    created_by_name: "ผู้ดูแลระบบ"
                },
                {
                    exam_set_id: 5,
                    name: "ชุดข้อสอบทักษะการคิดวิเคราะห์",
                    description: "ชุดข้อสอบทักษะการคิดวิเคราะห์ สำหรับประเมินความสามารถในการแก้ปัญหาและการคิดเชิงวิพากษ์",
                    topic_count: 0,
                    status: 1,
                    created_at: "2025-03-03 13:10:00",
                    updated_at: "2025-03-03 13:10:00",
                    created_by_name: "ผู้ดูแลระบบ"
                }
            ];
            
            // สร้าง mock API response
            const mockResponse = {
                draw: 1,
                recordsTotal: sampleData.length,
                recordsFiltered: sampleData.length,
                data: sampleData
            };
            
            // อัปเดตทั้ง Table View และ Card View
            updateCardView(sampleData);
            
            // แสดงข้อมูลตัวอย่างใน DataTable (เป็นเพียงเดโม ไม่ใช่คำสั่งที่ใช้ได้จริงใน DataTables)
            // ในการใช้งานจริงไม่ต้องใส่ส่วนนี้ เพราะ DataTables ดึงข้อมูลจาก AJAX
            try {
                examSetTable.clear().draw();
                sampleData.forEach(function(data, index) {
                    examSetTable.row.add({
                        "DT_RowIndex": index + 1,
                        ...data
                    }).draw();
                });
            } catch(e) {
                console.log('สามารถดูตัวอย่างได้ในมุมมองการ์ด');
            }
        }
        
        // เรียกใช้ฟังก์ชันโหลดข้อมูลตัวอย่าง (ในกรณีที่ยังไม่มี API จริง)
        // ในการใช้งานจริงให้คอมเมนต์บรรทัดนี้ไว้
        // loadSampleData();
    });
    </script>
  </body>
</html>