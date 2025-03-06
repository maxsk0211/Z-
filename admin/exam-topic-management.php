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
} catch (PDOException $e) {
    // หากเกิดข้อผิดพลาดให้ redirect ไปยังหน้าจัดการชุดข้อสอบ
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

    <title>จัดการหัวข้อการสอบ - <?= htmlspecialchars($examSet['name']) ?> - ระบบสอบออนไลน์</title>

    <meta name="description" content="จัดการหัวข้อการสอบในระบบสอบออนไลน์" />

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
    <link rel="stylesheet" href="../assets/vendor/lib/sweetalert2/sweetalert2.css" />

    <!-- Page CSS -->
    <style>
      body {
        font-family: 'Kanit', sans-serif;
      }
      
      .btn-add-topic {
        position: absolute;
        right: 20px;
        top: 20px;
        z-index: 10;
      }
      
      .action-btn {
        margin: 0 3px;
        padding: 6px 12px;
        font-size: 0.8rem;
      }
      
      .status-badge {
        padding: 5px 8px;
        border-radius: 5px;
        font-size: 0.8rem;
      }
      
      .status-active {
        background-color: #E8F5E9;
        color: #2E7D32;
      }
      
      .status-inactive {
        background-color: #FFEBEE;
        color: #C62828;
      }
      
      .question-count-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        background-color: #EEF2FF;
        color: #5D87FF;
        font-weight: 500;
      }
      
      .breadcrumb-item a {
        text-decoration: none;
      }
      
      /* Custom SweetAlert2 Styling */
      .custom-swal-popup {
        border-radius: 15px !important;
        padding: 1.5rem !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
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
      
      /* Loading indicator */
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
              <!-- / Breadcrumb -->
              
              <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                  <h4 class="mb-0">จัดการหัวข้อการสอบ - <?= htmlspecialchars($examSet['name']) ?></h4>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                    <i class="ri-add-line me-1"></i> เพิ่มหัวข้อการสอบ
                  </button>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover dt-responsive nowrap" id="topicTable">
                      <thead>
                        <tr>
                          <th width="5%">#</th>
                          <th width="25%">ชื่อหัวข้อ</th>
                          <th width="35%">รายละเอียด</th>
                          <th width="10%">จำนวนคำถาม</th>
                          <th width="10%">สถานะ</th>
                          <th width="15%">จัดการ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- ข้อมูลจะถูกเพิ่มผ่าน DataTables AJAX -->
                      </tbody>
                    </table>
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
                    <h5 class="modal-title" id="addTopicModalLabel">เพิ่มหัวข้อการสอบใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="addTopicForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" name="exam_set_id" value="<?= $examSetId ?>">
                      
                      <div class="mb-3">
                        <label for="name" class="form-label">ชื่อหัวข้อ</label>
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
                        <label for="edit_name" class="form-label">ชื่อหัวข้อ</label>
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
        
        // Function to show loading overlay
        function showLoading() {
            $('#loadingOverlay').addClass('show');
        }
        
        // Function to hide loading overlay
        function hideLoading() {
            $('#loadingOverlay').removeClass('show');
        }
        
        // Initialize DataTable
        const topicTable = $('#topicTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "api/exam-topic-api.php?action=list&exam_set_id=<?= $examSetId ?>",
                type: "GET",
                dataSrc: function(json) {
                    hideLoading();
                    return json.data;
                },
                error: function(xhr, error, thrown) {
                    hideLoading();
                    swalCustom.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดข้อมูลหัวข้อการสอบได้'
                    });
                }
            },
            columns: [
                { data: null, render: function(data, type, row, meta) {
                    return meta.row + 1;
                }},
                { data: "name" },
                { data: "description", render: function(data) {
                    return data ? data : '-';
                }},
                { data: "question_count", render: function(data) {
                    return `<span class="question-count-badge">${data}</span>`;
                }},
                { data: "status", render: function(data) {
                    if (data == 1) {
                        return '<span class="status-badge status-active">ใช้งาน</span>';
                    } else {
                        return '<span class="status-badge status-inactive">ไม่ใช้งาน</span>';
                    }
                }},
                { data: null, render: function(data, type, row) {
                    let buttons = `
                        <button type="button" class="btn btn-sm btn-primary action-btn edit-btn" data-id="${row.topic_id}" title="แก้ไข">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <a href="question-management.php?topic_id=${row.topic_id}" class="btn btn-sm btn-info action-btn" title="จัดการคำถาม">
                            <i class="ri-question-line"></i>
                        </a>
                    `;
                    
                    // เพิ่มปุ่มลบเฉพาะหัวข้อที่ไม่มีคำถาม
                    if (parseInt(row.question_count) === 0) {
                        buttons += `
                            <button type="button" class="btn btn-sm btn-danger action-btn delete-btn" data-id="${row.topic_id}" data-name="${row.name}" title="ลบ">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        `;
                    } else {
                        buttons += `
                            <button type="button" class="btn btn-sm btn-secondary action-btn" disabled title="ไม่สามารถลบได้ มีคำถามอยู่">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        `;
                    }
                    
                    return buttons;
                }, orderable: false }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
            },
            responsive: true,
            dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>t<"row"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="ri-file-excel-2-line"></i> Excel',
                    className: 'btn btn-success me-2',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="ri-printer-line"></i> พิมพ์',
                    className: 'btn btn-info me-2',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                }
            ],
            order: [[0, 'asc']] // Sort by first column by default
        });
        
        // Show loading on initial data load
        showLoading();
        
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
        
        // Save new topic
        $('#saveTopicBtn').on('click', function() {
            if (validateForm('#addTopicForm')) {
                const formData = new FormData($('#addTopicForm')[0]);
                formData.append('action', 'create');
                
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
                            // Close modal and reset form
                            $('#addTopicModal').modal('hide');
                            $('#addTopicForm')[0].reset();
                            $('#addTopicForm input[name="exam_set_id"]').val(<?= $examSetId ?>);
                            
                            // Reload topic table
                            topicTable.ajax.reload();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'เพิ่มหัวข้อการสอบเรียบร้อยแล้ว'
                            });
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถเพิ่มหัวข้อการสอบได้'
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
        
        // Edit topic - fetch data and open modal
        $(document).on('click', '.edit-btn', function() {
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
                        $('#edit_description').val(topic.description);
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
        
        // Update topic
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
                            
                            // Reload topic table
                            topicTable.ajax.reload();
                            
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
        
        // Delete topic
        $(document).on('click', '.delete-btn', function() {
            const topicId = $(this).data('id');
            const topicName = $(this).data('name');
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: `คุณต้องการลบหัวข้อการสอบ "${topicName}" ใช่หรือไม่?`,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> ลบ',
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
                            csrf_token: '<?= $csrf_token ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // Reload topic table
                                topicTable.ajax.reload();
                                
                                // Show success message
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบหัวข้อการสอบเรียบร้อยแล้ว'
                                });
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
        
        // Reset add topic form when modal is closed
        $('#addTopicModal').on('hidden.bs.modal', function() {
            $('#addTopicForm')[0].reset();
            $('#addTopicForm .is-invalid').removeClass('is-invalid');
            $('#addTopicForm input[name="exam_set_id"]').val(<?= $examSetId ?>);
        });
        
        // Reset edit topic form when modal is closed
        $('#editTopicModal').on('hidden.bs.modal', function() {
            $('#editTopicForm .is-invalid').removeClass('is-invalid');
        });
    });
    </script>
  </body>
</html>