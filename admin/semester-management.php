<?php
// เริ่มต้น session1
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

    <title>จัดการเทอม - ระบบสอบออนไลน์</title>

    <meta name="description" content="จัดการข้อมูลเทอมในระบบสอบออนไลน์" />

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

    <!-- Page CSS -->
    <style>
      body {
        font-family: 'Kanit', sans-serif;
      }
      
      .btn-add-semester {
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
              <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                  <h4 class="mb-0">จัดการเทอม</h4>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSemesterModal">
                    <i class="ri-add-line me-1"></i> เพิ่มเทอมใหม่
                  </button>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover dt-responsive nowrap" id="semesterTable">
                      <thead>
                        <tr>
                          <th width="5%">#</th>
                          <th width="20%">ปีการศึกษา</th>
                          <th width="20%">เทอม</th>
                          <th width="15%">สถานะ</th>
                          <th width="20%">วันที่สร้าง</th>
                          <th width="20%">จัดการ</th>
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

            <!-- Modal เพิ่มเทอม -->
            <div class="modal fade" id="addSemesterModal" tabindex="-1" aria-labelledby="addSemesterModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addSemesterModalLabel">เพิ่มเทอมใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="addSemesterForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      
                      <div class="mb-3">
                        <label for="year" class="form-label">ปีการศึกษา</label>
                        <input type="number" class="form-control" id="year" name="year" min="2500" max="2599" required>
                        <div class="invalid-feedback">กรุณากรอกปีการศึกษาให้ถูกต้อง (2500-2599)</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="term" class="form-label">เทอม</label>
                        <select class="form-select" id="term" name="term" required>
                          <option value="">เลือกเทอม</option>
                          <option value="1">เทอม 1</option>
                          <option value="2">เทอม 2</option>
                          <option value="3">ภาคฤดูร้อน</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกเทอม</div>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveSemesterBtn">บันทึก</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal แก้ไขเทอม -->
            <div class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editSemesterModalLabel">แก้ไขข้อมูลเทอม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="editSemesterForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" id="edit_semester_id" name="semester_id">
                      
                      <div class="mb-3">
                        <label for="edit_year" class="form-label">ปีการศึกษา</label>
                        <input type="number" class="form-control" id="edit_year" name="year" min="2500" max="2599" required>
                        <div class="invalid-feedback">กรุณากรอกปีการศึกษาให้ถูกต้อง (2500-2599)</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_term" class="form-label">เทอม</label>
                        <select class="form-select" id="edit_term" name="term" required>
                          <option value="">เลือกเทอม</option>
                          <option value="1">เทอม 1</option>
                          <option value="2">เทอม 2</option>
                          <option value="3">ภาคฤดูร้อน</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกเทอม</div>
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
                    <button type="button" class="btn btn-primary" id="updateSemesterBtn">บันทึกการเปลี่ยนแปลง</button>
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
        const semesterTable = $('#semesterTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "api/semester-api.php?action=list",
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
                        text: 'ไม่สามารถโหลดข้อมูลเทอมได้'
                    });
                }
            },
            columns: [
                { data: null, render: function(data, type, row, meta) {
                    return meta.row + 1;
                }},
                { data: "year" },
                { data: "term", render: function(data) {
                    if (data == 1) {
                        return 'เทอม 1';
                    } else if (data == 2) {
                        return 'เทอม 2';
                    } else if (data == 3) {
                        return 'ภาคฤดูร้อน';
                    }
                    return 'ไม่ระบุ';
                }},
                { data: "status", render: function(data) {
                    if (data == 1) {
                        return '<span class="status-badge status-active">ใช้งาน</span>';
                    } else {
                        return '<span class="status-badge status-inactive">ไม่ใช้งาน</span>';
                    }
                }},
                { data: "created_at", render: function(data) {
                    // Format date to display in Thai locale
                    const date = new Date(data);
                    return date.toLocaleDateString('th-TH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }},
                { data: null, render: function(data, type, row) {
                    return `<button type="button" class="btn btn-sm btn-primary action-btn edit-btn" data-id="${row.semester_id}">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger action-btn delete-btn" data-id="${row.semester_id}" data-year="${row.year}" data-term="${row.term}">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm ${row.status == 1 ? 'btn-warning' : 'btn-success'} action-btn toggle-btn" data-id="${row.semester_id}" data-status="${row.status}">
                                <i class="ri-${row.status == 1 ? 'eye-off' : 'eye'}-line"></i>
                            </button>`;
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
            order: [[1, 'desc'], [2, 'asc']] // เรียงตามปีการศึกษา (มากไปน้อย) และเทอม (น้อยไปมาก)
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
            
            // ตรวจสอบปีการศึกษา
            const yearInput = form.find('input[name="year"]');
            const year = parseInt(yearInput.val());
            if (isNaN(year) || year < 2500 || year > 2599) {
                isValid = false;
                yearInput.addClass('is-invalid');
            }
            
            return isValid;
        }
        
        // Remove validation styling on input
        $('input, select').on('focus', function() {
            $(this).removeClass('is-invalid');
        });
        
        // Save new semester
        $('#saveSemesterBtn').on('click', function() {
            if (validateForm('#addSemesterForm')) {
                const formData = new FormData($('#addSemesterForm')[0]);
                formData.append('action', 'create');
                
                showLoading();
                
                $.ajax({
                    url: 'api/semester-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // Close modal and reset form
                            $('#addSemesterModal').modal('hide');
                            $('#addSemesterForm')[0].reset();
                            
                            // Reload semester table
                            semesterTable.ajax.reload();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'เพิ่มเทอมเรียบร้อยแล้ว'
                            });
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถเพิ่มเทอมได้'
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
        
        // Edit semester - fetch data and open modal
        $(document).on('click', '.edit-btn', function() {
            const semesterId = $(this).data('id');
            
            showLoading();
            
            $.ajax({
                url: `api/semester-api.php?action=get&id=${semesterId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const semester = response.data;
                        
                        // Fill form with semester data
                        $('#edit_semester_id').val(semester.semester_id);
                        $('#edit_year').val(semester.year);
                        $('#edit_term').val(semester.term);
                        $('#edit_status').val(semester.status);
                        
                        // Show modal
                        $('#editSemesterModal').modal('show');
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถดึงข้อมูลเทอมได้'
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
        
        // Update semester
        $('#updateSemesterBtn').on('click', function() {
            if (validateForm('#editSemesterForm')) {
                const formData = new FormData($('#editSemesterForm')[0]);
                formData.append('action', 'update');
                
                showLoading();
                
                $.ajax({
                    url: 'api/semester-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // Close modal
                            $('#editSemesterModal').modal('hide');
                            
                            // Reload semester table
                            semesterTable.ajax.reload();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'อัปเดตข้อมูลเทอมเรียบร้อยแล้ว'
                            });
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถอัปเดตข้อมูลเทอมได้'
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
        
        // Toggle semester status
        $(document).on('click', '.toggle-btn', function() {
            const semesterId = $(this).data('id');
            const currentStatus = $(this).data('status');
            const newStatus = currentStatus == 1 ? 0 : 1;
            const statusText = newStatus == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
            
            swalCustom.fire({
                icon: 'question',
                title: 'ยืนยันการเปลี่ยนสถานะ',
                text: `คุณต้องการเปลี่ยนสถานะเทอมนี้เป็น "${statusText}" ใช่หรือไม่?`,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-check-line me-1"></i> ยืนยัน',
                confirmButtonColor: '#4caf50',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: 'api/semester-api.php',
                        type: 'POST',
                        data: {
                            action: 'toggle-status',
                            semester_id: semesterId,
                            status: newStatus,
                            csrf_token: '<?= $csrf_token ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // Reload semester table
                                semesterTable.ajax.reload();
                                
                                // Show success message
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: `เปลี่ยนสถานะเทอมเป็น "${statusText}" เรียบร้อยแล้ว`
                                });
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถเปลี่ยนสถานะเทอมได้'
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
        
        // Delete semester
        $(document).on('click', '.delete-btn', function() {
            const semesterId = $(this).data('id');
            const year = $(this).data('year');
            const term = $(this).data('term');
            let termText = '';
            
            if (term == 1) {
                termText = 'เทอม 1';
            } else if (term == 2) {
                termText = 'เทอม 2';
            } else if (term == 3) {
                termText = 'ภาคฤดูร้อน';
            }
            
            // สร้าง HTML สำหรับฟอร์มยืนยันรหัสผ่าน
            const passwordFormHtml = `
                <form id="confirmPasswordForm" class="mt-3">
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">กรุณายืนยันรหัสผ่านของท่าน</label>
                        <input type="password" class="form-control" id="confirmPassword" placeholder="รหัสผ่านปัจจุบัน" required>
                        <div class="invalid-feedback">กรุณากรอกรหัสผ่าน</div>
                    </div>
                </form>
            `;
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: `คุณต้องการลบเทอม "${year} ${termText}" ใช่หรือไม่?`,
                footer: '<span class="text-warning"><i class="ri-alert-line me-1"></i> หากมีนักเรียนอ้างอิงถึงเทอมนี้ จะไม่สามารถลบได้</span>',
                html: passwordFormHtml,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> ลบ',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'ยกเลิก',
                preConfirm: () => {
                    const password = document.getElementById('confirmPassword').value;
                    if (!password) {
                        Swal.showValidationMessage('กรุณากรอกรหัสผ่านเพื่อยืนยัน');
                        return false;
                    }
                    return password;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: 'api/semester-api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            semester_id: semesterId,
                            password: result.value,
                            csrf_token: '<?= $csrf_token ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // Reload semester table
                                semesterTable.ajax.reload();
                                
                                // Show success message
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบเทอมเรียบร้อยแล้ว'
                                });
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถลบเทอมได้'
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
        
        // Reset add semester form when modal is closed
        $('#addSemesterModal').on('hidden.bs.modal', function() {
            $('#addSemesterForm')[0].reset();
            $('#addSemesterForm .is-invalid').removeClass('is-invalid');
        });
        
        // Reset edit semester form when modal is closed
        $('#editSemesterModal').on('hidden.bs.modal', function() {
            $('#editSemesterForm .is-invalid').removeClass('is-invalid');
        });
    });
    </script>
  </body>
</html>