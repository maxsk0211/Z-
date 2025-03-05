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

// ดึงข้อมูลเทอมที่มีสถานะใช้งาน
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT semester_id, year, term FROM semester WHERE status = 1 ORDER BY year DESC, term ASC");
    $stmt->execute();
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // จัดการข้อผิดพลาด
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลเทอม: ' . $e->getMessage();
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

    <title>จัดการข้อมูลนักเรียน - ระบบสอบออนไลน์</title>

    <meta name="description" content="จัดการข้อมูลนักเรียนในระบบสอบออนไลน์" />

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

      .semester-selector {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      }

      .semester-selector label {
        font-weight: 500;
        margin-right: 10px;
      }

      .student-action-buttons {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 15px;
        gap: 10px;
      }

      .import-info {
        background-color: #EFF6FF;
        border-left: 3px solid #3B82F6;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 0 5px 5px 0;
      }

      .required-field::after {
        content: ' *';
        color: #ef4444;
      }

      .preview-table {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 15px;
      }

      .error-row {
        background-color: #FFEBEE !important;
      }

      .success-row {
        background-color: #E8F5E9 !important;
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
              <h4 class="py-3 mb-4">จัดการข้อมูลนักเรียน</h4>

              <!-- เลือกเทอม -->
              <div class="card mb-4">
                <div class="card-body semester-selector">
                  <div class="row">
                    <div class="col-md-6">
                      <label for="semesterSelect" class="form-label">เลือกเทอม:</label>
                      <select id="semesterSelect" class="form-select w-100">
                        <option value="">-- กรุณาเลือกเทอม --</option>
                        <?php if (isset($semesters) && !empty($semesters)): ?>
                          <?php foreach ($semesters as $semester): ?>
                            <?php 
                            $termText = '';
                            if ($semester['term'] == 1) {
                                $termText = 'เทอม 1';
                            } else if ($semester['term'] == 2) {
                                $termText = 'เทอม 2';
                            } else if ($semester['term'] == 3) {
                                $termText = 'ภาคฤดูร้อน';
                            }
                            ?>
                            <option value="<?= $semester['semester_id'] ?>">
                              ปีการศึกษา <?= $semester['year'] ?> <?= $termText ?>
                            </option>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <option value="" disabled>ไม่พบข้อมูลเทอม</option>
                        <?php endif; ?>
                      </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button type="button" id="loadStudentBtn" class="btn btn-primary" disabled>
                        <i class="ri-search-line me-1"></i> แสดงข้อมูลนักเรียน
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ข้อมูลนักเรียน -->
              <div class="card" id="studentDataCard" style="display: none;">
                <div class="card-header">
                  <h5 class="mb-0">รายชื่อนักเรียน</h5>
                </div>
                <div class="card-body">
                  <div class="student-action-buttons">
                    <a href="api/student-api.php?action=export-csv&semester_id=<?= htmlspecialchars($semester['semester_id'] ?? '') ?>" id="exportCSVBtn" class="btn btn-secondary" style="display: none;">
                      <i class="ri-file-download-line me-1"></i> ส่งออก CSV
                    </a>
                    <button type="button" class="btn btn-success" id="importStudentBtn">
                      <i class="ri-file-upload-line me-1"></i> นำเข้าไฟล์ CSV
                    </button>
                    <button type="button" class="btn btn-primary" id="addStudentBtn">
                      <i class="ri-user-add-line me-1"></i> เพิ่มนักเรียนรายบุคคล
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-hover dt-responsive nowrap" id="studentTable">
                      <thead>
                        <tr>
                          <th width="5%">#</th>
                          <th width="15%">รหัสนักเรียน</th>
                          <th width="15%">ชื่อผู้ใช้</th>
                          <th width="25%">ชื่อ-นามสกุล</th>
                          <th width="10%">สถานะ</th>
                          <th width="10%">วันที่สร้าง</th>
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

              <!-- คำแนะนำ -->
              <div class="card mt-4" id="studentGuidance">
                <div class="card-body">
                  <h5 class="card-title">คำแนะนำการใช้งาน</h5>
                  <div class="alert alert-info">
                    <h6><i class="ri-information-line me-1"></i> กรุณาเลือกเทอมก่อนเริ่มใช้งาน</h6>
                    <p>คุณสามารถจัดการข้อมูลนักเรียนได้หลังจากเลือกเทอมเรียบร้อยแล้ว โดยมีขั้นตอนดังนี้:</p>
                    <ol>
                      <li>เลือกเทอมจากรายการด้านบน</li>
                      <li>คลิกปุ่ม "แสดงข้อมูลนักเรียน"</li>
                      <li>เพิ่มนักเรียนด้วยการนำเข้าไฟล์ CSV หรือเพิ่มรายบุคคล</li>
                    </ol>
                  </div>
                </div>
              </div>
            </div>
            <!-- / Content -->

            <!-- Modal เพิ่มนักเรียนรายบุคคล -->
            <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">เพิ่มนักเรียนรายบุคคล</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="addStudentForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" name="semester_id" id="current_semester_id" value="">
                      
                      <div class="mb-3">
                        <label for="student_code" class="form-label required-field">รหัสนักเรียน</label>
                        <input type="text" class="form-control" id="student_code" name="student_code" required>
                        <div class="invalid-feedback">กรุณากรอกรหัสนักเรียน</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="username" class="form-label required-field">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อผู้ใช้</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="password" class="form-label required-field">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">กรุณากรอกรหัสผ่าน</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="firstname" class="form-label required-field">ชื่อจริง</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อจริง</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="lastname" class="form-label required-field">นามสกุล</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                        <div class="invalid-feedback">กรุณากรอกนามสกุล</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="email" class="form-label">อีเมล (ไม่บังคับ)</label>
                        <input type="email" class="form-control" id="email" name="email">
                        <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="phone" class="form-label">เบอร์โทรศัพท์ (ไม่บังคับ)</label>
                        <input type="text" class="form-control" id="phone" name="phone">
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
                    <button type="button" class="btn btn-primary" id="saveStudentBtn">บันทึก</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Modal แก้ไขนักเรียน -->
            <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">แก้ไขข้อมูลนักเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form id="editStudentForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" id="edit_student_id" name="student_id">
                      
                      <div class="mb-3">
                        <label for="edit_student_code" class="form-label required-field">รหัสนักเรียน</label>
                        <input type="text" class="form-control" id="edit_student_code" name="student_code" required>
                        <div class="invalid-feedback">กรุณากรอกรหัสนักเรียน</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_username" class="form-label required-field">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อผู้ใช้</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_firstname" class="form-label required-field">ชื่อจริง</label>
                        <input type="text" class="form-control" id="edit_firstname" name="firstname" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อจริง</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_lastname" class="form-label required-field">นามสกุล</label>
                        <input type="text" class="form-control" id="edit_lastname" name="lastname" required>
                        <div class="invalid-feedback">กรุณากรอกนามสกุล</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_email" class="form-label">อีเมล (ไม่บังคับ)</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                        <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="edit_phone" class="form-label">เบอร์โทรศัพท์ (ไม่บังคับ)</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone">
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
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="updateStudentBtn">บันทึกการเปลี่ยนแปลง</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal นำเข้าไฟล์ CSV -->
            <div class="modal fade" id="importCSVModal" tabindex="-1" aria-labelledby="importCSVModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="importCSVModalLabel">นำเข้านักเรียนจากไฟล์ CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="import-info mb-3">
                      <h6 class="mb-2"><i class="ri-information-line me-1"></i> คำแนะนำ:</h6>
                      <p class="mb-2">ไฟล์ CSV ต้องมีคอลัมน์ดังต่อไปนี้:</p>
                      <div class="mb-2">
                        <span class="badge bg-primary me-1">student_code</span>
                        <span class="badge bg-primary me-1">username</span>
                        <span class="badge bg-primary me-1">password</span>
                        <span class="badge bg-primary me-1">firstname</span>
                        <span class="badge bg-primary me-1">lastname</span>
                        <span class="badge bg-secondary me-1">email (ไม่บังคับ)</span>
                        <span class="badge bg-secondary me-1">phone (ไม่บังคับ)</span>
                        <span class="badge bg-secondary me-1">status (ไม่บังคับ, ค่าเริ่มต้น = 1)</span>
                      </div>
                      <div class="d-flex justify-content-end">
                        <a href="api/student-api.php?action=download-template" class="btn btn-sm btn-outline-secondary">
                          <i class="ri-download-line me-1"></i> ดาวน์โหลดไฟล์ตัวอย่าง
                        </a>
                      </div>
                    </div>

                    <form id="importCSVForm">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" name="semester_id" id="import_semester_id" value="">
                      
                      <div class="mb-3">
                        <label for="csvFile" class="form-label">เลือกไฟล์ CSV</label>
                        <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                        <div class="invalid-feedback">กรุณาเลือกไฟล์ CSV</div>
                      </div>
                    </form>

                    <div id="csvPreviewContainer" style="display: none;">
                      <h6 class="mb-2">ตัวอย่างข้อมูล</h6>
                      <div class="preview-table">
                        <table class="table table-bordered table-striped table-sm" id="csvPreviewTable">
                          <thead>
                            <tr>
                              <!-- Header จะถูกเพิ่มด้วย JavaScript -->
                            </tr>
                          </thead>
                          <tbody>
                            <!-- ข้อมูลจะถูกเพิ่มด้วย JavaScript -->
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div id="csvImportStatus" class="alert alert-info" style="display: none;">
                      <i class="ri-information-line me-1"></i> <span id="csvImportStatusText">กำลังตรวจสอบข้อมูล...</span>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="confirmImportBtn" disabled>นำเข้าข้อมูล</button>
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
    <script src="../assets/vendor/libs/papaparse/papaparse.min.js"></script>
    
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
	    
	    // DataTable สำหรับข้อมูลนักเรียน
	    let studentTable;
	    let selectedSemesterId = '';
	    
	    // เมื่อเลือกเทอม
	    $('#semesterSelect').on('change', function() {
	        const semesterId = $(this).val();
	        if (semesterId) {
	            $('#loadStudentBtn').prop('disabled', false);
	        } else {
	            $('#loadStudentBtn').prop('disabled', true);
	        }
	    });
	    
	    // เมื่อคลิกปุ่มโหลดข้อมูลนักเรียน
	    $('#loadStudentBtn').on('click', function() {
	        selectedSemesterId = $('#semesterSelect').val();
	        if (selectedSemesterId) {
	            showLoading();
	            
			    // ซ่อนคำแนะนำ และแสดงการ์ดข้อมูลนักเรียน
			    $('#studentGuidance').hide();
			    $('#studentDataCard').show();
			    
			    // กำหนดค่า semester_id ให้กับฟอร์ม
			    $('#current_semester_id').val(selectedSemesterId);
			    $('#import_semester_id').val(selectedSemesterId);
			    
			    // แสดงปุ่มส่งออก CSV และอัปเดตลิงก์
			    $('#exportCSVBtn').show().attr('href', `api/student-api.php?action=export-csv&semester_id=${selectedSemesterId}`);
			    
			    // ถ้ามีตาราง DataTable อยู่แล้ว ให้ทำลายก่อน
			    if ($.fn.dataTable.isDataTable('#studentTable')) {
			        studentTable.destroy();
			    }

	            // สร้างตาราง DataTable ใหม่
	            studentTable = $('#studentTable').DataTable({
	                processing: true,
	                serverSide: true,
					 ajax: {
		                url: `api/student-api.php?action=list&semester_id=${selectedSemesterId}`,
		                type: "GET",
		                dataSrc: function(json) {
		                    hideLoading();
		                    
		                    // แสดงสถิตินักเรียน
		                    let statsElement = $('#studentStats');
		                    if (statsElement.length === 0) {
		                        statsElement = $('<div id="studentStats" class="alert alert-info mt-3 mb-3">' +
		                                        '<strong><i class="ri-information-line me-1"></i> สถิติข้อมูลนักเรียน:</strong> ' +
		                                        '<span id="studentCount"></span>' +
		                                        '</div>');
		                        statsElement.insertBefore('.student-action-buttons');
		                    }
		                    $('#studentCount').text(`จำนวนนักเรียนทั้งหมด ${json.recordsTotal} คน`);
		                    
		                    return json.data;
		                },
		                error: function(xhr, error, thrown) {
		                    hideLoading();
		                    swalCustom.fire({
		                        icon: 'error',
		                        title: 'เกิดข้อผิดพลาด',
		                        text: 'ไม่สามารถโหลดข้อมูลนักเรียนได้'
		                    });
		                }
		            },
	                columns: [
	                    { data: null, render: function(data, type, row, meta) {
	                        return meta.row + 1;
	                    }},
	                    { data: "student_code" },
	                    { data: "username" },
	                    { data: null, render: function(data, type, row) {
	                        return row.firstname + ' ' + row.lastname;
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
	                            month: 'short',
	                            day: 'numeric'
	                        });
	                    }},
	                    { data: null, render: function(data, type, row) {
	                        return `<button type="button" class="btn btn-sm btn-primary action-btn edit-btn" data-id="${row.student_id}">
	                                    <i class="ri-pencil-line"></i>
	                                </button>
	                                <button type="button" class="btn btn-sm btn-danger action-btn delete-btn" data-id="${row.student_id}" data-name="${row.firstname} ${row.lastname}">
	                                    <i class="ri-delete-bin-line"></i>
	                                </button>
	                                <button type="button" class="btn btn-sm ${row.status == 1 ? 'btn-warning' : 'btn-success'} action-btn toggle-btn" data-id="${row.student_id}" data-status="${row.status}">
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
	                ]
	            });
	        }
	    });
	    
	    // Toggle เปลี่ยนรหัสผ่าน
	    $('#changePasswordCheck').on('change', function() {
	        if ($(this).is(':checked')) {
	            $('#passwordFields').slideDown();
	            $('#edit_password').attr('required', true);
	        } else {
	            $('#passwordFields').slideUp();
	            $('#edit_password').attr('required', false);
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
	    
	    // แสดง Modal เพิ่มนักเรียนรายบุคคล
	    $('#addStudentBtn').on('click', function() {
	        $('#addStudentForm')[0].reset();
	        $('#current_semester_id').val(selectedSemesterId);
	        $('#addStudentModal').modal('show');
	    });
	    
	    // แสดง Modal นำเข้า CSV
	    $('#importStudentBtn').on('click', function() {
	        $('#importCSVForm')[0].reset();
	        $('#import_semester_id').val(selectedSemesterId);
	        $('#csvPreviewContainer').hide();
	        $('#csvImportStatus').hide();
	        $('#confirmImportBtn').prop('disabled', true);
	        $('#importCSVModal').modal('show');
	    });
	    
	    // Remove validation styling on input
	    $('input, select').on('focus', function() {
	        $(this).removeClass('is-invalid');
	    });
	    
	    // บันทึกข้อมูลนักเรียนรายบุคคล
	    $('#saveStudentBtn').on('click', function() {
	        if (validateForm('#addStudentForm')) {
	            const formData = new FormData($('#addStudentForm')[0]);
	            formData.append('action', 'create');
	            
	            showLoading();
	            
	            $.ajax({
	                url: 'api/student-api.php',
	                type: 'POST',
	                data: formData,
	                processData: false,
	                contentType: false,
	                dataType: 'json',
	                success: function(response) {
	                    hideLoading();
	                    
	                    if (response.success) {
	                        // Close modal and reset form
	                        $('#addStudentModal').modal('hide');
	                        $('#addStudentForm')[0].reset();
	                        
	                        // Reload student table
	                        studentTable.ajax.reload();
	                        
	                        // Show success message
	                        swalCustom.fire({
	                            icon: 'success',
	                            title: 'สำเร็จ!',
	                            text: 'เพิ่มนักเรียนเรียบร้อยแล้ว'
	                        });
	                    } else {
	                        swalCustom.fire({
	                            icon: 'error',
	                            title: 'เกิดข้อผิดพลาด',
	                            text: response.message || 'ไม่สามารถเพิ่มนักเรียนได้'
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
	    
	    // ดึงข้อมูลนักเรียนเพื่อแก้ไข
	    $(document).on('click', '.edit-btn', function() {
	        const studentId = $(this).data('id');
	        
	        showLoading();
	        
	        $.ajax({
	            url: `api/student-api.php?action=get&id=${studentId}`,
	            type: 'GET',
	            dataType: 'json',
	            success: function(response) {
	                hideLoading();
	                
	                if (response.success) {
	                    const student = response.data;
	                    
	                    // กรอกข้อมูลลงในฟอร์ม
	                    $('#edit_student_id').val(student.student_id);
	                    $('#edit_student_code').val(student.student_code);
	                    $('#edit_username').val(student.username);
	                    $('#edit_firstname').val(student.firstname);
	                    $('#edit_lastname').val(student.lastname);
	                    $('#edit_email').val(student.email);
	                    $('#edit_phone').val(student.phone);
	                    $('#edit_status').val(student.status);
	                    
	                    // Reset password fields
	                    $('#changePasswordCheck').prop('checked', false);
	                    $('#passwordFields').hide();
	                    $('#edit_password').val('');
	                    
	                    // Show modal
	                    $('#editStudentModal').modal('show');
	                } else {
	                    swalCustom.fire({
	                        icon: 'error',
	                        title: 'เกิดข้อผิดพลาด',
	                        text: response.message || 'ไม่สามารถดึงข้อมูลนักเรียนได้'
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
	    
	    // อัปเดตข้อมูลนักเรียน
	    $('#updateStudentBtn').on('click', function() {
	        if (validateForm('#editStudentForm')) {
	            const formData = new FormData($('#editStudentForm')[0]);
	            formData.append('action', 'update');
	            formData.append('change_password', $('#changePasswordCheck').is(':checked') ? '1' : '0');
	            
	            showLoading();
	            
	            $.ajax({
	                url: 'api/student-api.php',
	                type: 'POST',
	                data: formData,
	                processData: false,
	                contentType: false,
	                dataType: 'json',
	                success: function(response) {
	                    hideLoading();
	                    
	                    if (response.success) {
	                        // Close modal
	                        $('#editStudentModal').modal('hide');
	                        
	                        // Reload student table
	                        studentTable.ajax.reload();
	                        
	                        // Show success message
	                        swalCustom.fire({
	                            icon: 'success',
	                            title: 'สำเร็จ!',
	                            text: 'อัปเดตข้อมูลนักเรียนเรียบร้อยแล้ว'
	                        });
	                    } else {
	                        swalCustom.fire({
	                            icon: 'error',
	                            title: 'เกิดข้อผิดพลาด',
	                            text: response.message || 'ไม่สามารถอัปเดตข้อมูลนักเรียนได้'
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
	    
	    // Toggle สถานะนักเรียน
	    $(document).on('click', '.toggle-btn', function() {
	        const studentId = $(this).data('id');
	        const currentStatus = $(this).data('status');
	        const newStatus = currentStatus == 1 ? 0 : 1;
	        const statusText = newStatus == 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
	        
	        swalCustom.fire({
	            icon: 'question',
	            title: 'ยืนยันการเปลี่ยนสถานะ',
	            text: `คุณต้องการเปลี่ยนสถานะนักเรียนคนนี้เป็น "${statusText}" ใช่หรือไม่?`,
	            showCancelButton: true,
	            confirmButtonText: '<i class="ri-check-line me-1"></i> ยืนยัน',
	            confirmButtonColor: '#4caf50',
	            cancelButtonText: 'ยกเลิก'
	        }).then((result) => {
	            if (result.isConfirmed) {
	                showLoading();
	                
	                $.ajax({
	                    url: 'api/student-api.php',
	                    type: 'POST',
	                    data: {
	                        action: 'toggle-status',
	                        student_id: studentId,
	                        status: newStatus,
	                        csrf_token: '<?= $csrf_token ?>'
	                    },
	                    dataType: 'json',
	                    success: function(response) {
	                        hideLoading();
	                        
	                        if (response.success) {
	                            // Reload student table
	                            studentTable.ajax.reload();
	                            
	                            // Show success message
	                            swalCustom.fire({
	                                icon: 'success',
	                                title: 'สำเร็จ!',
	                                text: `เปลี่ยนสถานะนักเรียนเป็น "${statusText}" เรียบร้อยแล้ว`
	                            });
	                        } else {
	                            swalCustom.fire({
	                                icon: 'error',
	                                title: 'เกิดข้อผิดพลาด',
	                                text: response.message || 'ไม่สามารถเปลี่ยนสถานะนักเรียนได้'
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
	    
	    // ลบนักเรียน
	    $(document).on('click', '.delete-btn', function() {
	        const studentId = $(this).data('id');
	        const studentName = $(this).data('name');
	        
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
	            text: `คุณต้องการลบนักเรียน "${studentName}" ใช่หรือไม่?`,
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
	                    url: 'api/student-api.php',
	                    type: 'POST',
	                    data: {
	                        action: 'delete',
	                        student_id: studentId,
	                        password: result.value,
	                        csrf_token: '<?= $csrf_token ?>'
	                    },
	                    dataType: 'json',
	                    success: function(response) {
	                        hideLoading();
	                        
	                        if (response.success) {
	                            // Reload student table
	                            studentTable.ajax.reload();
	                            
	                            // Show success message
	                            swalCustom.fire({
	                                icon: 'success',
	                                title: 'สำเร็จ!',
	                                text: 'ลบนักเรียนเรียบร้อยแล้ว'
	                            });
	                        } else {
	                            swalCustom.fire({
	                                icon: 'error',
	                                title: 'เกิดข้อผิดพลาด',
	                                text: response.message || 'ไม่สามารถลบนักเรียนได้'
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

	    // อัปโหลดไฟล์ CSV
	    $('#csvFile').on('change', function(e) {
	        const file = e.target.files[0];
	        if (file) {
	            $('#csvPreviewContainer').hide();
	            $('#csvImportStatus').show();
	            $('#csvImportStatusText').text('กำลังตรวจสอบข้อมูล...');
	            $('#confirmImportBtn').prop('disabled', true);
	            
	            // ใช้ PapaParse อ่านไฟล์ CSV
	            Papa.parse(file, {
	                header: true,
	                skipEmptyLines: true,
	                complete: function(results) {
	                    // ตรวจสอบว่ามีข้อมูลหรือไม่
	                    if (results.data && results.data.length > 0) {
	                        const headers = results.meta.fields;
	                        const requiredFields = ['student_code', 'username', 'password', 'firstname', 'lastname'];
	                        
	                        // ตรวจสอบ header ที่จำเป็น
	                        const missingFields = requiredFields.filter(field => !headers.includes(field));
	                        
	                        if (missingFields.length > 0) {
	                            $('#csvImportStatusText').html(`<span class="text-danger">พบข้อผิดพลาด: ไม่พบคอลัมน์ที่จำเป็น: ${missingFields.join(', ')}</span>`);
	                            return;
	                        }
	                        
	                        // ตรวจสอบข้อมูลในแต่ละแถว
	                        let hasError = false;
	                        let errorCount = 0;
	                        let validRows = 0;
	                        
	                        // สร้าง HTML สำหรับ header และเตรียมตาราง preview
	                        let headerHTML = '<tr>';
	                        headers.forEach(header => {
	                            headerHTML += `<th>${header}</th>`;
	                        });
	                        headerHTML += '<th>สถานะ</th>';
	                        headerHTML += '</tr>';
	                        
	                        // สร้าง HTML สำหรับข้อมูล
	                        let bodyHTML = '';
	                        results.data.forEach((row, index) => {
	                            let rowIsValid = true;
	                            let rowErrors = [];
	                            
	                            // ตรวจสอบฟิลด์ที่จำเป็น
	                            requiredFields.forEach(field => {
	                                if (!row[field] || row[field].trim() === '') {
	                                    rowIsValid = false;
	                                    rowErrors.push(`ไม่พบข้อมูล ${field}`);
	                                }
	                            });
	                            
	                            // สร้าง HTML แถวข้อมูล
	                            let rowClass = rowIsValid ? '' : 'error-row';
	                            bodyHTML += `<tr class="${rowClass}">`;
	                            headers.forEach(header => {
	                                bodyHTML += `<td>${row[header] || ''}</td>`;
	                            });
	                            
	                            // เพิ่มคอลัมน์สถานะ
	                            if (rowIsValid) {
	                                bodyHTML += '<td><span class="badge bg-success">ผ่าน</span></td>';
	                                validRows++;
	                            } else {
	                                bodyHTML += `<td><span class="badge bg-danger" title="${rowErrors.join(', ')}">ไม่ผ่าน</span></td>`;
	                                errorCount++;
	                                hasError = true;
	                            }
	                            
	                            bodyHTML += '</tr>';
	                        });
	                        
	                        // แสดงตัวอย่างข้อมูล
	                        $('#csvPreviewTable thead').html(headerHTML);
	                        $('#csvPreviewTable tbody').html(bodyHTML);
	                        $('#csvPreviewContainer').show();
	                        
	                        // อัปเดตสถานะ
	                        if (hasError) {
	                            $('#csvImportStatusText').html(`<span class="text-warning">พบข้อมูลที่ไม่ถูกต้อง ${errorCount} รายการ จากทั้งหมด ${results.data.length} รายการ</span>`);
	                            $('#confirmImportBtn').prop('disabled', true);
	                        } else {
	                            $('#csvImportStatusText').html(`<span class="text-success">ข้อมูลถูกต้องทั้งหมด ${validRows} รายการ พร้อมนำเข้า</span>`);
	                            $('#confirmImportBtn').prop('disabled', false);
	                        }
	                    } else {
	                        $('#csvImportStatusText').html('<span class="text-danger">ไม่พบข้อมูลในไฟล์ CSV</span>');
	                    }
	                },
	                error: function(error) {
	                    $('#csvImportStatusText').html(`<span class="text-danger">เกิดข้อผิดพลาดในการอ่านไฟล์: ${error}</span>`);
	                }
	            });
	        }
	    });

	    // ยืนยันการนำเข้าไฟล์ CSV
	    $('#confirmImportBtn').on('click', function() {
	        const formData = new FormData($('#importCSVForm')[0]);
	        formData.append('action', 'import-csv');
	        
	        showLoading();
	        
	        $.ajax({
	            url: 'api/student-api.php',
	            type: 'POST',
	            data: formData,
	            processData: false,
	            contentType: false,
	            dataType: 'json',
	            success: function(response) {
	                hideLoading();
	                
	                if (response.success) {
	                    // Close modal and reset form
	                    $('#importCSVModal').modal('hide');
	                    $('#importCSVForm')[0].reset();
	                    
	                    // Reload student table
	                    studentTable.ajax.reload();
	                    
	                    // Show success message with import stats
	                    swalCustom.fire({
	                        icon: 'success',
	                        title: 'สำเร็จ!',
	                        html: `นำเข้าข้อมูลเรียบร้อยแล้ว<br>
	                              <ul class="text-left mt-3">
	                                <li>นำเข้าสำเร็จ: ${response.imported} รายการ</li>
	                                ${response.duplicates > 0 ? `<li>ข้อมูลซ้ำ: ${response.duplicates} รายการ</li>` : ''}
	                                ${response.errors > 0 ? `<li>ผิดพลาด: ${response.errors} รายการ</li>` : ''}
	                              </ul>`
	                    });
	                } else {
	                    swalCustom.fire({
	                        icon: 'error',
	                        title: 'เกิดข้อผิดพลาด',
	                        text: response.message || 'ไม่สามารถนำเข้าข้อมูลได้'
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
		
	    
	    // เพิ่มสถิติข้อมูลนักเรียนในหน้า
	    function updateStudentStats(totalRecords) {
	        // หาหรือสร้าง element สำหรับแสดงสถิติ
	        let statsElement = $('#studentStats');
	        if (statsElement.length === 0) {
	            // สร้าง element ใหม่ถ้าไม่มี
	            statsElement = $('<div id="studentStats" class="alert alert-info mt-3 mb-3">' +
	                             '<strong><i class="ri-information-line me-1"></i> สถิติข้อมูลนักเรียน:</strong> ' +
	                             '<span id="studentCount"></span>' +
	                             '</div>');
	            statsElement.insertBefore('.student-action-buttons');
	        }
	        
	        // อัปเดตจำนวนนักเรียน
	        $('#studentCount').text(`จำนวนนักเรียนทั้งหมด ${totalRecords} คน`);
	    }
	    


	    
	    // Reset add student form when modal is closed
	    $('#addStudentModal').on('hidden.bs.modal', function() {
	        $('#addStudentForm')[0].reset();
	        $('#addStudentForm .is-invalid').removeClass('is-invalid');
	    });
	    
	    // Reset edit student form when modal is closed
	    $('#editStudentModal').on('hidden.bs.modal', function() {
	        $('#editStudentForm .is-invalid').removeClass('is-invalid');
	    });
	    
	    // Reset import CSV form when modal is closed
	    $('#importCSVModal').on('hidden.bs.modal', function() {
	        $('#importCSVForm')[0].reset();
	        $('#csvPreviewContainer').hide();
	        $('#csvImportStatus').hide();
	    });
	});
	</script>
  </body>
</html>