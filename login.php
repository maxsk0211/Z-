<?php
// เริ่มต้น session
session_start();

// ตรวจสอบว่ามีการ login อยู่แล้วหรือไม่
if (isset($_SESSION['user_id'])) {
    // ถ้าเป็น admin ให้ไปที่หน้า admin dashboard
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } 
    // ถ้าเป็น student ให้ไปที่หน้า student dashboard
    else if ($_SESSION['user_type'] === 'student') {
        header('Location: student/dashboard.php');
        exit;
    }
}

// สร้าง CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html
  lang="th"
  class="light-style layout-wide customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="assets/"
  data-template="vertical-menu-template">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>ระบบสอบออนไลน์ - เข้าสู่ระบบ</title>

    <meta name="description" content="ระบบสอบออนไลน์ก่อนออกฝึกงาน" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap"
      rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    
    <!-- Custom CSS -->
    <style>
        :root {
            --bs-primary: #5D87FF;
            --bs-primary-rgb: 93, 135, 255;
            --bs-secondary: #49BEFF;
            --bs-success: #13DEB9;
            --bs-info: #539BFF;
            --bs-warning: #FFAE1F;
            --bs-danger: #FA896B;
            --bs-light: #F6F9FC;
            --bs-dark: #2A3547;
            --bs-light-rgb: 246, 249, 252;
            --bs-font-sans-serif: 'Kanit', sans-serif;
            --bs-body-color: #5A607F;
            --bs-body-bg: #F6F9FC;
            --card-bg: #ffffff;
            --bs-border-color: #ebf1f6;
            --bs-border-radius: 0.625rem;
            --bs-border-radius-lg: 0.875rem;
            --bs-box-shadow: 0 0.3rem 0.8rem rgba(0, 0, 0, 0.12);
            --element-shadow: 0 1px 18px 0 rgba(0, 0, 0, 0.12);
        }
        
        body {
            font-family: var(--bs-font-sans-serif);
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            line-height: 1.7;
        }
        
        .authentication-wrapper {
            display: flex;
            flex-basis: 100%;
            min-height: 100vh;
            width: 100%;
        }
        
        .authentication-wrapper .authentication-inner {
            width: 100%;
        }
        
        .authentication-wrapper.authentication-cover {
            align-items: flex-start;
        }
        
        .authentication-wrapper.authentication-cover .authentication-inner {
            height: 100vh;
        }
        
        .authentication-cover .auth-cover-brand {
            position: absolute;
            top: 2.5rem;
            left: 3.5rem;
            z-index: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .authentication-cover .auth-cover-brand:hover {
            transform: translateY(-3px);
        }
        
        .authentication-cover .authentication-image {
            position: absolute;
            z-index: -1;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .auth-cover-illustration {
            z-index: 1;
            max-height: 100%;
            transition: all 0.6s ease-in-out;
        }
        
        .authentication-bg {
            background-color: var(--card-bg);
            border-radius: var(--bs-border-radius-lg);
            box-shadow: var(--bs-box-shadow);
        }
        
        .app-brand-logo {
            display: flex;
            align-items: center;
            font-size: 1.75rem;
            color: var(--bs-primary);
            filter: drop-shadow(0 0 10px rgba(93, 135, 255, 0.5));
            transition: all 0.3s ease;
        }
        
        .app-brand-logo:hover {
            transform: scale(1.05);
        }
        
        .app-brand-text {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--bs-dark);
            margin-left: 0.5rem;
            background: linear-gradient(to right, var(--bs-primary), var(--bs-info));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Form Styling */
        .form-label {
            color: var(--bs-dark);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background-color: var(--bs-light);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 400;
            transition: all 0.2s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
            background-color: #fff;
        }
        
        .form-floating-outline .form-control {
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            padding: 1rem 0.875rem 0.625rem;
            transition: all 0.2s ease-in-out;
            background-color: var(--bs-light);
        }
        
        .form-floating-outline .form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
            background-color: #fff;
        }
        
        .form-floating-outline label {
            padding: 1rem 0.875rem;
            color: #6c757d;
            font-weight: 400;
        }
        
        .form-floating-outline .form-control:focus ~ label,
        .form-floating-outline .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.85;
            transform: scale(0.85) translateY(-0.75rem) translateX(-0.2rem);
            background-color: #fff;
            padding: 0 0.5rem;
            height: auto;
            margin-left: 0.5rem;
            color: var(--bs-primary);
        }
        
        .form-password-toggle .input-group-text {
            cursor: pointer;
            background-color: var(--bs-light);
            border-left: 0;
            border-color: var(--bs-border-color);
            color: #6c757d;
            border-top-right-radius: var(--bs-border-radius);
            border-bottom-right-radius: var(--bs-border-radius);
            transition: all 0.2s ease-in-out;
        }
        
        .form-password-toggle .input-group-text:hover {
            color: var(--bs-primary);
        }
        
        .form-password-toggle .input-group-text i {
            font-size: 1.25rem;
        }
        
        .input-group-merge .form-floating-outline {
            width: 100%;
        }
        
        .input-group-merge .form-floating-outline .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        /* Button Styling */
        .btn {
            border-radius: var(--bs-border-radius);
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-info));
            border: none;
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(var(--bs-primary-rgb), 0.5);
            background: linear-gradient(135deg, var(--bs-info), var(--bs-primary));
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid var(--bs-border-color);
        }
        
        .divider-text {
            padding: 0 1rem;
            color: #8e9aaf;
            font-size: 0.875rem;
        }
        
        /* Social Buttons */
        .btn-icon {
            padding: 0;
            width: 2.75rem;
            height: 2.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
            color: #fff;
            box-shadow: var(--element-shadow);
        }
        
        .btn-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-facebook {
            background-color: #3b5998;
        }
        
        .btn-twitter {
            background-color: #1da1f2;
        }
        
        .btn-github {
            background-color: #333;
        }
        
        .btn-google {
            background-color: #dd4b39;
        }
        
        /* Tabs */
        .nav-pills {
            margin-bottom: 2rem;
            background-color: var(--bs-light);
            border-radius: var(--bs-border-radius-lg);
            padding: 0.25rem;
        }
        
        .nav-pills .nav-link {
            color: var(--bs-body-color);
            border-radius: var(--bs-border-radius);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .nav-pills .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-info));
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.4);
        }
        
        .nav-pills .nav-link:not(.active):hover {
            background-color: rgba(var(--bs-primary-rgb), 0.1);
        }
        
        .tab-content {
            margin-bottom: 2rem;
        }
        
        /* Card Styling */
        .login-card {
            border-radius: var(--bs-border-radius-lg);
            box-shadow: var(--bs-box-shadow);
            background-color: var(--card-bg);
            border: 1px solid var(--bs-border-color);
            overflow: hidden;
        }
        
        /* Form Check */
        .form-check-input {
            width: 1.125rem;
            height: 1.125rem;
            margin-top: 0.25rem;
            border: 1.5px solid var(--bs-border-color);
            transition: all 0.2s ease-in-out;
        }
        
        .form-check-input:checked {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .form-check-label {
            color: var(--bs-body-color);
            cursor: pointer;
        }
        
        /* Links */
        a {
            color: var(--bs-primary);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        a:hover {
            color: var(--bs-info);
            text-decoration: none;
        }
        
        /* Illustrations */
        .illustration-wrapper {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-info));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        /* System Title - แก้ไขปัญหาข้อความไม่ชัด */
        .system-title {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            text-align: center;
            width: 100%;
            padding: 1rem;
        }
        
        .system-title h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: #FFFFFF;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
            letter-spacing: 1px;
            background-color: rgba(0,0,0,0.2);
            padding: 1rem;
            border-radius: 10px;
            display: inline-block;
        }
        
        .system-title p {
            font-size: 1.25rem;
            color: white;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
            max-width: 600px;
            margin: 0 auto;
            background-color: rgba(0,0,0,0.2);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: inline-block;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .auth-cover-brand {
                position: relative;
                top: 0;
                left: 0;
                margin: 1.5rem 0;
                justify-content: center;
            }
            
            .d-none.d-lg-flex {
                display: none !important;
            }
            
            .authentication-bg {
                padding: 2rem 1.5rem;
                border-radius: 0;
                box-shadow: none;
            }
            
            .authentication-wrapper .authentication-inner {
                height: auto;
            }
            
            .system-title h1 {
                font-size: 2.5rem;
            }
            
            .system-title p {
                font-size: 1rem;
            }
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
            border: 3px solid rgba(var(--bs-primary-rgb), 0.2);
            border-radius: 50%;
            border-top-color: var(--bs-primary);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Floating elements */
        .float-element {
            position: absolute;
            z-index: 0;
            border-radius: 50%;
            filter: blur(30px);
            opacity: 0.15;
        }
        
        .float-1 {
            top: 10%;
            left: 10%;
            width: 200px;
            height: 200px;
            background: var(--bs-primary);
            animation: float 6s ease-in-out infinite alternate;
        }
        
        .float-2 {
            bottom: 10%;
            right: 10%;
            width: 300px;
            height: 300px;
            background: var(--bs-info);
            animation: float 8s ease-in-out infinite alternate-reverse;
        }
        
        .float-3 {
            top: 50%;
            left: 50%;
            width: 150px;
            height: 150px;
            background: var(--bs-success);
            animation: float 7s ease-in-out infinite alternate;
            transform: translate(-50%, -50%);
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(20px, 20px) rotate(10deg); }
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
        
        .custom-swal-confirm {
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-info)) !important;
            border-radius: 8px !important;
            padding: 0.75rem 2rem !important;
            font-family: 'Kanit', sans-serif !important;
            font-weight: 500 !important;
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.4) !important;
        }
        
        .custom-swal-confirm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 15px rgba(var(--bs-primary-rgb), 0.5) !important;
        }
        
        .custom-swal-cancel {
            background: #f5f5f5 !important;
            color: #666 !important;
            border-radius: 8px !important;
            padding: 0.75rem 2rem !important;
            font-family: 'Kanit', sans-serif !important;
            font-weight: 500 !important;
            margin-left: 0.75rem !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        }
        
        .custom-swal-icon {
            border: none !important;
            margin-bottom: 1.5rem !important;
        }
        
        .custom-swal-icon.swal2-success .swal2-success-ring {
            border-color: var(--bs-success) !important;
        }
        
        .custom-swal-icon.swal2-success [class^=swal2-success-line] {
            background-color: var(--bs-success) !important;
        }
        
        .custom-swal-icon.swal2-error [class^=swal2-x-mark-line] {
            background-color: var(--bs-danger) !important;
        }
    </style>
  </head>

  <body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Content -->
    <div class="authentication-wrapper authentication-cover">
      <!-- Floating Elements -->
      <div class="float-element float-1"></div>
      <div class="float-element float-2"></div>
      <div class="float-element float-3"></div>
      
      <!-- Logo -->
      <a href="./" class="auth-cover-brand d-flex align-items-center gap-2">
        <span class="app-brand-logo">
          <i class="ri-school-line" style="font-size: 2rem;"></i>
        </span>
        <span class="app-brand-text">ระบบสอบออนไลน์</span>
      </a>
      <!-- /Logo -->
      
      <div class="authentication-inner row m-0">
        <!-- Left Section - Illustration -->
        <div class="d-none d-lg-flex col-lg-7 col-xl-8 p-0">
          <div class="illustration-wrapper w-100 h-100">
            <div id="particles-js" class="particles"></div>
            <!-- เพิ่มข้อความ "ระบบสอบออนไลน์" ที่เห็นได้ชัดเจน -->
            <div class="system-title">
                <h1 class="animated animate__fadeIn">ระบบสอบออนไลน์</h1>
                <p class="animated animate__fadeIn animate__delay-1s">เตรียมความพร้อมก่อนออกฝึกงานด้วยระบบสอบออนไลน์ที่ทันสมัย</p>
            </div>
            <img
              src="assets/img/illustrations/login-illustration.png"
              class="auth-cover-illustration w-75 fade-in"
              alt="สอบออนไลน์"
              onerror="this.src='https://cdn.pixabay.com/photo/2018/01/17/07/06/laptop-3087585_1280.jpg'; this.onerror=null;" />
          </div>
        </div>
        <!-- /Left Section -->

        <!-- Login Form Section -->
        <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center position-relative py-5 px-4 p-sm-5">
          <div class="w-100 mx-auto" style="max-width: 400px;">
            <div class="text-center mb-4 slide-up">
              <h4 class="fw-bold mb-2">ยินดีต้อนรับสู่ระบบสอบออนไลน์ 👋</h4>
              <p class="mb-0">กรุณาเข้าสู่ระบบเพื่อเริ่มใช้งาน</p>
            </div>

            <!-- Tab Menu -->
            <ul class="nav nav-pills mb-4 slide-up" id="pills-tab" role="tablist">
              <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link active w-100" id="student-tab" data-bs-toggle="pill" data-bs-target="#student-login-tab" type="button" role="tab" aria-controls="student-login-tab" aria-selected="true">
                  <i class="ri-user-line me-1"></i> นักเรียน
                </button>
              </li>
              <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link w-100" id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin-login-tab" type="button" role="tab" aria-controls="admin-login-tab" aria-selected="false">
                  <i class="ri-admin-line me-1"></i> ผู้ดูแลระบบ
                </button>
              </li>
            </ul>

            <div class="tab-content slide-up" id="pills-tabContent">
              <!-- Student Login Form -->
              <div class="tab-pane fade show active" id="student-login-tab" role="tabpanel" aria-labelledby="student-tab">
                <div class="login-card p-3 p-sm-4 mb-3">
                  <form id="student-login-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="user_type" value="student">
                    
                    <div class="form-floating form-floating-outline mb-3">
                      <input
                        type="text"
                        class="form-control"
                        id="student-username"
                        name="username"
                        placeholder="ชื่อผู้ใช้งาน"
                        autofocus />
                      <label for="student-username">ชื่อผู้ใช้งาน</label>
                    </div>
                    
                    <div class="mb-3">
                      <div class="form-password-toggle">
                        <div class="input-group input-group-merge">
                          <div class="form-floating form-floating-outline">
                            <input
                              type="password"
                              id="student-password"
                              class="form-control"
                              name="password"
                              placeholder="············"
                              aria-describedby="password" />
                            <label for="student-password">รหัสผ่าน</label>
                          </div>
                          <span class="input-group-text cursor-pointer toggle-password"><i class="ri-eye-off-line"></i></span>
                        </div>
                      </div>
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="student-remember-me" />
                        <label class="form-check-label" for="student-remember-me"> จดจำฉัน </label>
                      </div>
                      <a href="forgot-password.php?type=student" class="fs-sm">
                        ลืมรหัสผ่าน?
                      </a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary d-grid w-100">
                      <i class="ri-login-circle-line me-1"></i> เข้าสู่ระบบ
                    </button>
                  </form>
                </div>
              </div>
              
              <!-- Admin Login Form -->
              <div class="tab-pane fade" id="admin-login-tab" role="tabpanel" aria-labelledby="admin-tab">
                <div class="login-card p-3 p-sm-4 mb-3">
                  <form id="admin-login-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="user_type" value="admin">
                    
                    <div class="form-floating form-floating-outline mb-3">
                      <input
                        type="text"
                        class="form-control"
                        id="admin-username"
                        name="username"
                        placeholder="ชื่อผู้ใช้งาน"
                        autofocus />
                      <label for="admin-username">ชื่อผู้ใช้งาน</label>
                    </div>
                    
                    <div class="mb-3">
                      <div class="form-password-toggle">
                        <div class="input-group input-group-merge">
                          <div class="form-floating form-floating-outline">
                            <input
                              type="password"
                              id="admin-password"
                              class="form-control"
                              name="password"
                              placeholder="············"
                              aria-describedby="password" />
                            <label for="admin-password">รหัสผ่าน</label>
                          </div>
                          <span class="input-group-text cursor-pointer toggle-password"><i class="ri-eye-off-line"></i></span>
                        </div>
                      </div>
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="admin-remember-me" />
                        <label class="form-check-label" for="admin-remember-me"> จดจำฉัน </label>
                      </div>
                      <a href="forgot-password.php?type=admin" class="fs-sm">
                        ลืมรหัสผ่าน?
                      </a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary d-grid w-100">
                      <i class="ri-login-circle-line me-1"></i> เข้าสู่ระบบ
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <div class="divider my-4 slide-up">
              <div class="divider-text">ระบบสอบออนไลน์ก่อนฝึกงาน</div>
            </div>

            <p class="text-center slide-up">
              <small class="text-muted">© <?= date('Y') ?> พัฒนาด้วย PHP 8.3 และ MySQL 5.7</small>
            </p>
          </div>
        </div>
        <!-- /Login Form Section -->
      </div>
    </div>

    <!-- Core JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <!-- Custom JS -->
    <script>
    // Initialize Particles.js
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
            particlesJS('particles-js', {
                "particles": {
                    "number": {
                        "value": 80,
                        "density": {
                            "enable": true,
                            "value_area": 800
                        }
                    },
                    "color": {
                        "value": "#ffffff"
                    },
                    "shape": {
                        "type": "circle",
                        "stroke": {
                            "width": 0,
                            "color": "#000000"
                        },
                        "polygon": {
                            "nb_sides": 5
                        }
                    },
                    "opacity": {
                        "value": 0.5,
                        "random": false,
                        "anim": {
                            "enable": false,
                            "speed": 1,
                            "opacity_min": 0.1,
                            "sync": false
                        }
                    },
                    "size": {
                        "value": 3,
                        "random": true,
                        "anim": {
                            "enable": false,
                            "speed": 40,
                            "size_min": 0.1,
                            "sync": false
                        }
                    },
                    "line_linked": {
                        "enable": true,
                        "distance": 150,
                        "color": "#ffffff",
                        "opacity": 0.4,
                        "width": 1
                    },
                    "move": {
                        "enable": true,
                        "speed": 2,
                        "direction": "none",
                        "random": false,
                        "straight": false,
                        "out_mode": "out",
                        "bounce": false,
                        "attract": {
                            "enable": false,
                            "rotateX": 600,
                            "rotateY": 1200
                        }
                    }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": {
                        "onhover": {
                            "enable": true,
                            "mode": "grab"
                        },
                        "onclick": {
                            "enable": true,
                            "mode": "push"
                        },
                        "resize": true
                    },
                    "modes": {
                        "grab": {
                            "distance": 140,
                            "line_linked": {
                                "opacity": 1
                            }
                        },
                        "bubble": {
                            "distance": 400,
                            "size": 40,
                            "duration": 2,
                            "opacity": 8,
                            "speed": 3
                        },
                        "repulse": {
                            "distance": 200,
                            "duration": 0.4
                        },
                        "push": {
                            "particles_nb": 4
                        },
                        "remove": {
                            "particles_nb": 2
                        }
                    }
                },
                "retina_detect": true
            });
        }
    });
    
    $(document).ready(function() {
        // Custom SweetAlert2 config
        const swalCustom = Swal.mixin({
            customClass: {
                popup: 'custom-swal-popup',
                title: 'custom-swal-title',
                content: 'custom-swal-content',
                confirmButton: 'custom-swal-confirm',
                cancelButton: 'custom-swal-cancel',
                icon: 'custom-swal-icon'
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
        
        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            const input = $(this).closest('.input-group').find('input');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('ri-eye-off-line').addClass('ri-eye-line');
            } else {
                input.attr('type', 'password');
                icon.removeClass('ri-eye-line').addClass('ri-eye-off-line');
            }
        });
        
        // Form validation function
        function validateForm(formId) {
            const form = $(formId);
            let isValid = true;
            
            form.find('input[required]').each(function() {
                if ($(this).val() === '') {
                    isValid = false;
                    $(this).addClass('is-invalid');
                    
                    // Shake animation for empty fields
                    $(this).closest('.form-floating').addClass('animate__animated animate__shakeX');
                    setTimeout(() => {
                        $(this).closest('.form-floating').removeClass('animate__animated animate__shakeX');
                    }, 1000);
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            return isValid;
        }
        
        // Remove validation styling on input
        $('input').on('focus', function() {
            $(this).removeClass('is-invalid');
        });
        
        // Student login form submission
        $('#student-login-form').on('submit', function(e) {
            e.preventDefault();
            
            if (validateForm('#student-login-form')) {
                const formData = new FormData(this);
                
                // Show loading overlay
                showLoading();
                
                // Send data to API
                $.ajax({
                    url: '/api/login-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json', // ระบุว่าต้องการรับข้อมูลเป็น JSON
                    success: function(data) {
                        // Hide loading overlay
                        hideLoading();
                        
                        console.log("API Response:", data);
                        
                        // ตรวจสอบข้อมูลที่ได้รับ
                        if (data && data.success === true) {
                            // Show success message with improved styling
                            swalCustom.fire({
                                icon: 'success',
                                title: 'ยินดีต้อนรับ!',
                                text: 'เข้าสู่ระบบสำเร็จ กำลังนำคุณไปยังหน้าหลัก',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                allowOutsideClick: false,
                                didOpen: () => {
                                    // ทำการ redirect โดยตรงไปยังหน้าที่ต้องการ
                                    setTimeout(() => {
                                        window.location.href = data.redirect;
                                    }, 1500);
                                }
                            });
                        } else {
                            // Show error message with improved styling
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เข้าสู่ระบบไม่สำเร็จ',
                                text: data.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ',
                                confirmButtonText: '<i class="ri-refresh-line me-1"></i> ลองใหม่',
                                showCancelButton: false
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Hide loading overlay
                        hideLoading();
                        
                        console.error("AJAX Error:", status, error, xhr.responseText);
                        
                        // แสดงข้อความแจ้งเตือนความผิดพลาด
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถเชื่อมต่อกับระบบได้ในขณะนี้',
                            confirmButtonText: '<i class="ri-refresh-line me-1"></i> ลองใหม่',
                            showCancelButton: false
                        });
                    }
                });
            }
        });
        
        // Admin login form submission
        $('#admin-login-form').on('submit', function(e) {
            e.preventDefault();
            
            if (validateForm('#admin-login-form')) {
                const formData = new FormData(this);
                
                // Show loading overlay
                showLoading();
                
                // Send data to API
                $.ajax({
                    url: '/api/login-api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json', // ระบุว่าต้องการรับข้อมูลเป็น JSON
                    success: function(data) {
                        // Hide loading overlay
                        hideLoading();
                        
                        console.log("API Response (Admin):", data);
                        
                        // ตรวจสอบข้อมูลที่ได้รับโดยตรง
                        if (data && data.success === true) {
                            // Show success message with improved styling
                            swalCustom.fire({
                                icon: 'success',
                                title: 'ยินดีต้อนรับ!',
                                text: 'เข้าสู่ระบบสำเร็จ กำลังนำคุณไปยังหน้าควบคุม',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                allowOutsideClick: false,
                                didOpen: () => {
                                    // ทำการ redirect โดยตรงไปยังหน้าที่ต้องการ
                                    setTimeout(() => {
                                        window.location.href = data.redirect || '/admin/dashboard.php';
                                    }, 1500);
                                }
                            });
                        } else {
                            // Show error message with improved styling
                            swalCustom.fire({
                                icon: 'error',
                                title: 'เข้าสู่ระบบไม่สำเร็จ',
                                text: data.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ',
                                confirmButtonText: '<i class="ri-refresh-line me-1"></i> ลองใหม่',
                                showCancelButton: false
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Hide loading overlay
                        hideLoading();
                        
                        console.error("AJAX Error (Admin):", status, error, xhr.responseText);
                        
                        // แสดงข้อความแจ้งเตือนความผิดพลาด
                        swalCustom.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถเชื่อมต่อกับระบบได้ในขณะนี้',
                            confirmButtonText: '<i class="ri-refresh-line me-1"></i> ลองใหม่',
                            showCancelButton: false
                        });
                    }
                });
            }
        });
    });
    </script>
  </body>
</html>