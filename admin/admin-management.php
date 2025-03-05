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

    <title>จัดการผู้ดูแลระบบ - ระบบสอบออนไลน์</title>

    <meta name="description" content="จัดการผู้ดูแลระบบในระบบสอบออนไลน์" />

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
      
      .btn-add-admin {
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
                  <h4 class="mb-0">จัดการผู้ดูแลระบบ</h4>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="ri-user-add-line me-1"></i> เพิ่มผู้ดูแลระบบ
                  </button>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover dt-responsive nowrap" id="adminTable">
                      <thead>
                        <tr>
                          <th width="5%">#</th>
                          <th width="15%">ชื่อผู้ใช้</th>
                          <th width="25%">ชื่อ-นามสกุล</th>
                          <th width="20%">อีเมล</th>
                          <th width="15%">วันที่สร้าง</th>
                          <th width="10%">สถานะ</th>
                          <th width="10%">จัดการ</th>
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

            <!-- Modal เพิ่มผู้ดูแลระบบ -->
            <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addAdminModalLabel">เพิ่มผู้ดูแลระบบใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="addAdminForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      
                      <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อผู้ใช้</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">กรุณากรอกรหัสผ่าน</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">รหัสผ่านไม่ตรงกัน</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อ-นามสกุล</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="email" name="email">
                        <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveAdminBtn">บันทึก</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal แก้ไขผู้ดูแลระบบ -->
            <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editAdminModalLabel">แก้ไขข้อมูลผู้ดูแลระบบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="editAdminForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" id="edit_admin_id" name="admin_id">
                      
                      <div class="mb-3">
                        <label for="edit_username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="edit_username" name="username" readonly>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อ-นามสกุล</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                        <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_status" class="form-label">สถานะ</label>
                        <select class="form-select" id="edit_status" name="status">
                          <option value="1">ใช้งาน</option>
                          <option value="0">ไม่ใช้งาน</option>
                        </select>
                      </div>
                      
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="changePasswordCheck">
                        <label class="form-check-label" for="changePasswordCheck">
                          เปลี่ยนรหัสผ่าน
                        </label>
                      </div>
                      
                      <div id="passwordFields" style="display: none;">
                        <div class="mb-3">
                          <label for="edit_password" class="form-label">รหัสผ่านใหม่</label>
                          <input type="password" class="form-control" id="edit_password" name="password">
                          <div class="invalid-feedback">กรุณากรอกรหัสผ่าน</div>
                        </div>
                        
                        <div class="mb-3">
                          <label for="edit_confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                          <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password">
                          <div class="invalid-feedback">รหัสผ่านไม่ตรงกัน</div>
                        </div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="updateAdminBtn">บันทึกการเปลี่ยนแปลง</button>
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
        const adminTable = $('#adminTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "api/admin-api.php?action=list",
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
                        text: 'ไม่สามารถโหลดข้อมูลผู้ดูแลระบบได้'
                    });
                }
            },
            columns: [
                { data: null, render: function(data, type, row, meta) {
                    return meta.row + 1;
                }},
                { data: "username" },
                { data: "name" },
                { data: "email", render: function(data) {
                    return data || '-';
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
                { data: "status", render: function(data) {
                    if (data == 1) {
                        return '<span class="status-badge status-active">ใช้งาน</span>';
                    } else {
                        return '<span class="status-badge status-inactive">ไม่ใช้งาน</span>';
                    }
                }},
                { data: null, render: function(data, type, row) {
                    // Don't allow to edit or delete current user or main admin (admin_id = 1)
                    if (row.admin_id == <?= $_SESSION['user_id'] ?>) {
                        // แสดงเฉพาะปุ่มแก้ไขสำหรับตัวเอง
                        return `<button type="button" class="btn btn-sm btn-primary action-btn edit-btn" data-id="${row.admin_id}">
                                    <i class="ri-pencil-line"></i>
                                </button>`;
                    } else if (row.admin_id == 1) {
                        // แสดงเฉพาะปุ่มแก้ไขสำหรับ admin_id = 1 (แอดมินหลัก)
                        return `<button type="button" class="btn btn-sm btn-primary action-btn edit-btn" data-id="${row.admin_id}">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger action-btn" disabled title="ไม่สามารถลบแอดมินหลักได้">
                                    <i class="ri-delete-bin-line"></i>
                                </button>`;
                    } else {
                        // แสดงทั้งปุ่มแก้ไขและลบสำหรับแอดมินคนอื่นๆ
                        return `<button type="button" class="btn btn-sm btn-primary action-btn edit-btn" data-id="${row.admin_id}">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger action-btn delete-btn" data-id="${row.admin_id}" data-name="${row.name}">
                                    <i class="ri-delete-bin-line"></i>
                                </button>`;
                    }
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
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="ri-printer-line"></i> พิมพ์',
                    className: 'btn btn-info me-2',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                }
            ],
            order: [[4, 'desc']] // Sort by created_at column by default
        });
        
        // Show loading on initial data load
        showLoading();
        
        // Toggle change password fields
        $('#changePasswordCheck').on('change', function() {
            if ($(this).is(':checked')) {
                $('#passwordFields').slideDown();
                $('#edit_password, #edit_confirm_password').attr('required', true);
            } else {
                $('#passwordFields').slideUp();
                $('#edit_password, #edit_confirm_password').attr('required', false);
            }
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
            
            // Check if passwords match
            if (formId === '#addAdminForm') {
                if ($('#password').val() !== $('#confirm_password').val()) {
                    $('#confirm_password').addClass('is-invalid');
                    isValid = false;
                }
            } else if (formId === '#editAdminForm' && $('#changePasswordCheck').is(':checked')) {
                if ($('#edit_password').val() !== $('#edit_confirm_password').val()) {
                    $('#edit_confirm_password').addClass('is-invalid');
                    isValid = false;
                }
            }
            
            // Validate email format if not empty
            const emailInput = form.find('input[type="email"]');
            if (emailInput.val() !== '') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.val())) {
                    emailInput.addClass('is-invalid');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        // Remove validation styling on input
        $('input, select').on('focus', function() {
            $(this).removeClass('is-invalid');
        });
        
        // Save new admin
        $('#saveAdminBtn').on('click', function() {
            if (validateForm('#addAdminForm')) {
                const formData = new FormData($('#addAdminForm')[0]);
                formData.append('action', 'create');
                
                showLoading();
                
                $.ajax({
                    url: 'api/admin-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // Close modal and reset form
                            $('#addAdminModal').modal('hide');
                            $('#addAdminForm')[0].reset();
                            
                            // Reload admin table
                            adminTable.ajax.reload();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'เพิ่มผู้ดูแลระบบเรียบร้อยแล้ว'
                            });
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถเพิ่มผู้ดูแลระบบได้'
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
        
        // Edit admin - fetch data and open modal
        $(document).on('click', '.edit-btn', function() {
            const adminId = $(this).data('id');
            
            showLoading();
            
            $.ajax({
                url: `api/admin-api.php?action=get&id=${adminId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        const admin = response.data;
                        
                        // Fill form with admin data
                        $('#edit_admin_id').val(admin.admin_id);
                        $('#edit_username').val(admin.username);
                        $('#edit_name').val(admin.name);
                        $('#edit_email').val(admin.email);
                        $('#edit_status').val(admin.status);
                        
                        // Reset password fields
                        $('#changePasswordCheck').prop('checked', false);
                        $('#passwordFields').hide();
                        $('#edit_password, #edit_confirm_password').val('');
                        
                        // Show modal
                        $('#editAdminModal').modal('show');
                    } else {
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message || 'ไม่สามารถดึงข้อมูลผู้ดูแลระบบได้'
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
        
        // Update admin
        $('#updateAdminBtn').on('click', function() {
            if (validateForm('#editAdminForm')) {
                const formData = new FormData($('#editAdminForm')[0]);
                formData.append('action', 'update');
                formData.append('change_password', $('#changePasswordCheck').is(':checked') ? '1' : '0');
                
                showLoading();
                
                $.ajax({
                    url: 'api/admin-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();
                        
                        if (response.success) {
                            // Close modal
                            $('#editAdminModal').modal('hide');
                            
                            // Reload admin table
                            adminTable.ajax.reload();
                            
                            // Show success message
                            swalCustom.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'อัปเดตข้อมูลผู้ดูแลระบบเรียบร้อยแล้ว'
                            });
                        } else {
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: response.message || 'ไม่สามารถอัปเดตข้อมูลผู้ดูแลระบบได้'
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
        
        // Delete admin
        $(document).on('click', '.delete-btn', function() {
            const adminId = $(this).data('id');
            const adminName = $(this).data('name');
            
            swalCustom.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: `คุณต้องการลบผู้ดูแลระบบ "${adminName}" ใช่หรือไม่?`,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> ลบ',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    $.ajax({
                        url: 'api/admin-api.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            admin_id: adminId,
                            csrf_token: '<?= $csrf_token ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success) {
                                // Reload admin table
                                adminTable.ajax.reload();
                                
                                // Show success message
                                swalCustom.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: 'ลบผู้ดูแลระบบเรียบร้อยแล้ว'
                                });
                            } else {
                                swalCustom.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message || 'ไม่สามารถลบผู้ดูแลระบบได้'
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
        
        // Reset add admin form when modal is closed
        $('#addAdminModal').on('hidden.bs.modal', function() {
            $('#addAdminForm')[0].reset();
            $('#addAdminForm .is-invalid').removeClass('is-invalid');
        });
        
        // Reset edit admin form when modal is closed
        $('#editAdminModal').on('hidden.bs.modal', function() {
            $('#editAdminForm .is-invalid').removeClass('is-invalid');
        });
    });
    </script>
  </body>
</html>