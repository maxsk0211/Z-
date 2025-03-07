<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบประเมินสมรรถนะความรู้วิชาชีพครู</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Materialize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #FFD54F;
            --secondary-color: #9C27B0;
            --accent-color: #FF5252;
            --dark-color: #212121;
            --light-color: #FFFFFF;
            --gray-color: #F5F5F5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--gray-color);
            color: var(--dark-color);
            overflow-x: hidden;
        }
        
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--light-color);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }
        
        .preloader.fade-out {
            opacity: 0;
        }
        
        .preloader .spinner {
            width: 70px;
            text-align: center;
        }
        
        .preloader .spinner > div {
            width: 18px;
            height: 18px;
            background-color: var(--primary-color);
            border-radius: 100%;
            display: inline-block;
            animation: sk-bouncedelay 1.4s infinite ease-in-out both;
            margin: 0 3px;
        }
        
        .preloader .spinner .bounce1 {
            animation-delay: -0.32s;
            background-color: var(--secondary-color);
        }
        
        .preloader .spinner .bounce2 {
            animation-delay: -0.16s;
            background-color: var(--accent-color);
        }
        
        @keyframes sk-bouncedelay {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }
        
        /* ===== HEADER ===== */
        .top-header {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 10px 0;
        }
        
        .top-header .brand-name {
            color: var(--accent-color);
            font-weight: 600;
            margin: 0;
            font-size: 1.2rem;
        }
        
        .top-header .auth-buttons a {
            margin-left: 15px;
            color: var(--light-color);
            transition: color 0.3s ease;
        }
        
        .top-header .auth-buttons a:hover {
            color: var(--primary-color);
        }
        
        /* ===== NAVIGATION ===== */
        .main-nav {
            background-color: var(--primary-color);
            padding: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        .main-nav.scrolled {
            padding: 5px 0;
            background-color: rgba(255, 213, 79, 0.95);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .main-nav .nav-wrapper {
            padding: 0 20px;
        }
        
        .main-nav ul li a {
            color: var(--dark-color);
            font-weight: 500;
            text-transform: none;
            position: relative;
            padding: 0 20px;
            height: 60px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .main-nav ul li a:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--secondary-color);
        }
        
        .main-nav ul li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 3px;
            bottom: 0;
            left: 50%;
            background-color: var(--secondary-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .main-nav ul li a:hover::after {
            width: 70%;
        }
        
        .main-nav ul li.active a::after {
            width: 70%;
        }
        
        .dropdown-content {
            background-color: var(--light-color);
            min-width: 200px;
            border-radius: 4px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            top: 60px !important;
        }
        
        .dropdown-content li > a {
            color: var(--dark-color);
            padding: 14px 20px;
            font-weight: 400;
        }
        
        .dropdown-content li > a:hover {
            background-color: var(--gray-color);
        }
        
        .dropdown-trigger i {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }
        
        .dropdown-trigger.active i {
            transform: rotate(180deg);
        }
        
        /* Mobile Nav */
        .sidenav {
            background-color: var(--light-color);
            width: 280px;
        }
        
        .sidenav li > a {
            color: var(--dark-color);
            font-weight: 500;
            padding: 20px 30px;
        }
        
        .sidenav .subheader {
            color: var(--secondary-color);
            font-weight: 600;
            padding: 20px 30px;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        
        .sidenav-trigger {
            color: var(--dark-color);
            margin: 0;
            height: 60px;
            display: flex;
            align-items: center;
        }
        
        /* ===== HERO SECTION ===== */
        .hero-section {
            position: relative;
            height: 80vh;
            min-height: 500px;
            overflow: hidden;
            background-color: var(--dark-color);
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(156, 39, 176, 0.8), rgba(33, 33, 33, 0.8));
            z-index: 1;
        }
        
        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1632&q=80');
            background-size: cover;
            background-position: center;
            filter: blur(3px);
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--light-color);
            padding: 0 20px;
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin: 20px 0 30px;
            opacity: 0.9;
            max-width: 700px;
            line-height: 1.6;
        }
        
        .hero-btns .btn {
            margin-right: 15px;
            margin-bottom: 15px;
            border-radius: 30px;
            padding: 0 30px;
            height: 45px;
            line-height: 45px;
            text-transform: none;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .hero-btns .btn-primary {
            background-color: var(--primary-color);
            color: var(--dark-color);
        }
        
        .hero-btns .btn-primary:hover {
            background-color: #FFC107;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .hero-btns .btn-secondary {
            background-color: transparent;
            border: 2px solid var(--light-color);
            color: var(--light-color);
        }
        
        .hero-btns .btn-secondary:hover {
            background-color: var(--light-color);
            color: var(--dark-color);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .hero-decoration {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 2;
        }
        
        .wave {
            width: 100%;
            height: 100px;
        }
        
        /* Hero Slider */
        .hero-slider {
            position: absolute;
            top: 0;
            right: 0;
            width: 45%;
            height: 100%;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-slider-content {
            width: 85%;
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .hero-slider-content img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        .slider-dots {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            z-index: 4;
        }
        
        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .slider-dot.active {
            background-color: var(--light-color);
            transform: scale(1.2);
        }
        
        /* Shape Divider */
        .custom-shape-divider-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            z-index: 2;
        }

        .custom-shape-divider-bottom svg {
            position: relative;
            display: block;
            width: calc(100% + 1.3px);
            height: 70px;
        }

        .custom-shape-divider-bottom .shape-fill {
            fill: var(--gray-color);
        }
        
        /* ===== FEATURES SECTION ===== */
        .features-section {
            padding: 80px 0;
            position: relative;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            display: inline-block;
            z-index: 1;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .section-title p {
            font-size: 1.1rem;
            color: #616161;
            margin-top: 15px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .feature-card {
            background-color: var(--light-color);
            border-radius: 10px;
            padding: 30px;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background-color: var(--primary-color);
            opacity: 0.1;
            border-radius: 50%;
            transition: all 0.5s ease;
        }
        
        .feature-card:hover::before {
            transform: scale(5);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            color: var(--primary-color);
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
        }
        
        .feature-title::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-title::after {
            width: 60px;
        }
        
        .feature-description {
            color: #616161;
            line-height: 1.6;
            margin: 0;
        }
        
        /* ===== ABOUT SECTION ===== */
        .about-section {
            padding: 80px 0;
            background-color: var(--light-color);
            position: relative;
            overflow: hidden;
        }
        
        .about-image {
            position: relative;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .about-image img {
            width: 90%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .about-image::before {
            content: '';
            position: absolute;
            width: 80%;
            height: 80%;
            background-color: var(--primary-color);
            border-radius: 20px;
            top: 10%;
            left: 20%;
            z-index: 0;
            opacity: 0.3;
        }
        
        .about-content {
            padding: 30px;
        }
        
        .about-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .about-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .about-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #616161;
            margin-bottom: 30px;
        }
        
        .about-stats {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .stat-item {
            margin-right: 40px;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin: 0;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #616161;
            margin: 5px 0 0;
        }
        
        .about-btn {
            background-color: var(--secondary-color);
            color: var(--light-color);
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 500;
            text-transform: none;
            box-shadow: 0 4px 12px rgba(156, 39, 176, 0.2);
            transition: all 0.3s ease;
        }
        
        .about-btn:hover {
            background-color: #7B1FA2;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(156, 39, 176, 0.3);
        }
        
        /* ===== CALL TO ACTION ===== */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #9C27B0, #673AB7);
            color: var(--light-color);
            position: relative;
            overflow: hidden;
        }
        
        .cta-bg-circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .cta-circle {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .cta-circle-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
        }
        
        .cta-circle-2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            right: -50px;
        }
        
        .cta-circle-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 30%;
            transform: translateY(-50%);
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta-description {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }
        
        .cta-btn {
            background-color: var(--primary-color);
            color: var(--dark-color);
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: 600;
            text-transform: none;
            font-size: 1.1rem;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .cta-btn:hover {
            background-color: #FFC107;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        /* ===== FOOTER ===== */
        .footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 80px 0 30px;
            position: relative;
        }
        
        .footer-logo {
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .footer-logo img {
            height: 60px;
        }
        
        .footer-description {
            color: #BDBDBD;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        
        .footer-social {
            display: flex;
            margin-bottom: 30px;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-right: 10px;
            color: var(--light-color);
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            background-color: var(--primary-color);
            color: var(--dark-color);
            transform: translateY(-3px);
        }
        
        .footer-widget-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-widget-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links li a {
            color: #BDBDBD;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .footer-links li a i {
            margin-right: 8px;
            font-size: 0.8rem;
        }
        
        .footer-links li a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }
        
        .footer-contact li {
            margin-bottom: 15px;
            color: #BDBDBD;
            display: flex;
            align-items: flex-start;
        }
        
        .footer-contact li i {
            color: var(--primary-color);
            margin-right: 10px;
            margin-top: 5px;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            margin-top: 30px;
            text-align: center;
            color: #BDBDBD;
        }
        
        .scrolltop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: var(--secondary-color);
            color: var(--light-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 99;
        }
        
        .scrolltop.show {
            opacity: 1;
            visibility: visible;
        }
        
        .scrolltop:hover {
            background-color: var(--primary-color);
            color: var(--dark-color);
            transform: translateY(-5px);
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .hero-section {
                height: auto;
                padding: 100px 0;
            }
            
            .hero-slider {
                position: relative;
                width: 100%;
                margin-top: 50px;
            }
            
            .hero-slider-content {
                width: 100%;
            }
            
            .slider-dots {
                bottom: -30px;
            }
            
            .feature-card {
                margin-bottom: 30px;
            }
            
            .about-image {
                margin-bottom: 50px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
            
            .cta-title {
                font-size: 1.8rem;
            }
            
            .cta-description {
                font-size: 1rem;
            }
            
            .footer-widget {
                margin-bottom: 40px;
            }
        }
        
        @media (max-width: 576px) {
            .hero-btns .btn {
                display: block;
                width: 100%;
                margin-right: 0;
            }
            
            .stat-item {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner">
            <div class="bounce1"></div>
            <div class="bounce2"></div>
            <div class="bounce3"></div>
        </div>
    </div>

    <!-- Header -->
    <header class="top-header">
        <div class="container">
            <div class="row">
                <div class="col s8 m6">
                    <h1 class="brand-name">ระบบประเมินสมรรถนะวิชาชีพครู</h1>
                </div>
                <div class="col s4 m6 right-align auth-buttons">
                    <a href="#" class="auth-link"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a>
                    <a href="#" class="auth-link"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="nav-wrapper container">
            <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="fas fa-bars"></i></a>
            <ul class="hide-on-med-and-down">
                <li class="active"><a href="#">หน้าหลัก</a></li>
                <li><a class="dropdown-trigger" href="#" data-target="dropdown-announce">ข่าวประชาสัมพันธ์ <i class="fas fa-chevron-down"></i></a></li>
                <li><a class="dropdown-trigger" href="#" data-target="dropdown-exam">ปฏิทินการสอบ <i class="fas fa-chevron-down"></i></a></li>
                <li><a class="dropdown-trigger" href="#" data-target="dropdown-online-exam">สอบออนไลน์ <i class="fas fa-chevron-down"></i></a></li>
                <li><a class="dropdown-trigger" href="#" data-target="dropdown-results">ประกาศผลสอบ <i class="fas fa-chevron-down"></i></a></li>
                <li><a class="dropdown-trigger" href="#" data-target="dropdown-bank">คลังข้อสอบ <i class="fas fa-chevron-down"></i></a></li>
                <li><a href="#">ผู้ดูแลระบบ</a></li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <ul class="sidenav" id="mobile-nav">
        <li class="subheader">เมนูหลัก</li>
        <li><a href="#"><i class="fas fa-home"></i> หน้าหลัก</a></li>
        <li><a href="#"><i class="fas fa-bullhorn"></i> ข่าวประชาสัมพันธ์</a></li>
        <li><a href="#"><i class="fas fa-calendar-alt"></i> ปฏิทินการสอบ</a></li>
        <li><a href="#"><i class="fas fa-laptop"></i> สอบออนไลน์</a></li>
        <li><a href="#"><i class="fas fa-chart-bar"></i> ประกาศผลสอบ</a></li>
        <li><a href="#"><i class="fas fa-book"></i> คลังข้อสอบ</a></li>
        <li><a href="#"><i class="fas fa-user-cog"></i> ผู้ดูแลระบบ</a></li>
        <li class="divider"></li>
        <li class="subheader">บัญชีผู้ใช้</li>
        <li><a href="#"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a></li>
        <li><a href="#"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a></li>
    </ul>

    <!-- Dropdown Navigation -->
    <ul id="dropdown-announce" class="dropdown-content">
        <li><a href="#"><i class="fas fa-newspaper"></i> ข่าวทั้งหมด</a></li>
        <li><a href="#"><i class="fas fa-calendar-check"></i> กิจกรรม</a></li>
        <li><a href="#"><i class="fas fa-file-alt"></i> ประกาศ</a></li>
    </ul>

    <ul id="dropdown-exam" class="dropdown-content">
        <li><a href="#"><i class="fas fa-calendar"></i> ตารางสอบ</a></li>
        <li><a href="#"><i class="fas fa-map-marker-alt"></i> สถานที่สอบ</a></li>
        <li><a href="#"><i class="fas fa-info-circle"></i> ข้อปฏิบัติการสอบ</a></li>
    </ul>

    <ul id="dropdown-online-exam" class="dropdown-content">
        <li><a href="#"><i class="fas fa-pencil-alt"></i> เข้าสอบ</a></li>
        <li><a href="#"><i class="fas fa-history"></i> ประวัติการสอบ</a></li>
        <li><a href="#"><i class="fas fa-desktop"></i> คู่มือการสอบออนไลน์</a></li>
    </ul>

    <ul id="dropdown-results" class="dropdown-content">
        <li><a href="#"><i class="fas fa-list-ol"></i> ผลการสอบล่าสุด</a></li>
        <li><a href="#"><i class="fas fa-certificate"></i> ใบรับรองผล</a></li>
        <li><a href="#"><i class="fas fa-chart-line"></i> สถิติการสอบ</a></li>
    </ul>

    <ul id="dropdown-bank" class="dropdown-content">
        <li><a href="#"><i class="fas fa-book-open"></i> แบบทดสอบ</a></li>
        <li><a href="#"><i class="fas fa-file-alt"></i> เอกสารประกอบ</a></li>
        <li><a href="#"><i class="fas fa-question-circle"></i> คลังข้อสอบเสมือน</a></li>
    </ul>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>

        <div class="container">
            <div class="row">
                <div class="col s12 l7">
                    <div class="hero-content" data-aos="fade-right" data-aos-duration="1000">
                        <h1 class="hero-title">ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมในสถานศึกษา</h1>
                        <p class="hero-subtitle">คณะครุศาสตร์อุตสาหกรรมและเทคโนโลยี มหาวิทยาลัยเทคโนโลยีราชมงคลศรีวิชัย</p>
                        <div class="hero-btns">
                            <a href="#" class="btn btn-primary waves-effect waves-light">เข้าสู่ระบบ</a>
                            <a href="#" class="btn btn-secondary waves-effect waves-light">ดูรายละเอียด</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-slider" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="300">
            <div class="hero-slider-content">
                <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="ระบบสอบออนไลน์" class="responsive-img">
            </div>
        </div>

        <div class="slider-dots">
            <div class="slider-dot active"></div>
            <div class="slider-dot"></div>
            <div class="slider-dot"></div>
        </div>

        <div class="custom-shape-divider-bottom">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>บริการของเรา</h2>
                <p>ระบบการสอบที่ทันสมัยพร้อมการประเมินที่มีประสิทธิภาพสำหรับครูช่างอุตสาหกรรม</p>
            </div>

            <div class="row">
                <div class="col s12 m6 l4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h3 class="feature-title">การสอบออนไลน์</h3>
                        <p class="feature-description">ระบบการสอบออนไลน์ที่ใช้งานง่าย รองรับการทำข้อสอบหลากหลายรูปแบบ พร้อมการประมวลผลที่รวดเร็วและแม่นยำ</p>
                    </div>
                </div>

                <div class="col s12 m6 l4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">การวิเคราะห์ผล</h3>
                        <p class="feature-description">วิเคราะห์ผลการสอบด้วยเครื่องมือที่ทันสมัย ดูพัฒนาการของผู้เรียนและสมรรถนะในแต่ละด้านได้อย่างละเอียด</p>
                    </div>
                </div>

                <div class="col s12 m6 l4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h3 class="feature-title">คลังข้อสอบ</h3>
                        <p class="feature-description">คลังข้อสอบมาตรฐานที่ครอบคลุมสมรรถนะต่างๆ ของครูช่างอุตสาหกรรม สามารถเลือกใช้และปรับแต่งได้ตามต้องการ</p>
                    </div>
                </div>

                <div class="col s12 m6 l4" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h3 class="feature-title">ใบรับรองผล</h3>
                        <p class="feature-description">ออกใบรับรองผลการประเมินสมรรถนะที่ได้มาตรฐาน สามารถนำไปใช้ในการพัฒนาวิชาชีพและการศึกษาต่อได้</p>
                    </div>
                </div>

                <div class="col s12 m6 l4" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">ระบบจัดการผู้ใช้</h3>
                        <p class="feature-description">จัดการผู้ใช้งานได้อย่างมีประสิทธิภาพ กำหนดสิทธิ์การเข้าถึงตามบทบาทหน้าที่ได้อย่างปลอดภัย</p>
                    </div>
                </div>

                <div class="col s12 m6 l4" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="feature-title">ตารางสอบออนไลน์</h3>
                        <p class="feature-description">จัดการตารางสอบได้อย่างยืดหยุ่น กำหนดเวลาเริ่มต้นและสิ้นสุดได้ตามต้องการ พร้อมระบบแจ้งเตือน</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="row">
                <div class="col s12 l5" data-aos="fade-right">
                    <div class="about-image">
                        <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="เกี่ยวกับเรา" class="responsive-img">
                    </div>
                </div>
                <div class="col s12 l7" data-aos="fade-left">
                    <div class="about-content">
                        <h2 class="about-title">เกี่ยวกับระบบประเมินสมรรถนะวิชาชีพครู</h2>
                        <p class="about-description">
                            ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมในสถานศึกษา พัฒนาขึ้นโดยคณะครุศาสตร์อุตสาหกรรมและเทคโนโลยี มหาวิทยาลัยเทคโนโลยีราชมงคลศรีวิชัย เพื่อยกระดับมาตรฐานการประเมินสมรรถนะวิชาชีพครูให้มีประสิทธิภาพและทันสมัย
                        </p>
                        <p class="about-description">
                            ระบบของเรามุ่งเน้นการประเมินสมรรถนะครูอย่างรอบด้าน ทั้งด้านความรู้ ทักษะ และเจตคติ สอดคล้องกับมาตรฐานวิชาชีพครูและบริบทของการจัดการศึกษาอาชีวศึกษาในปัจจุบัน
                        </p>

                        <div class="about-stats">
                            <div class="stat-item">
                                <h3 class="stat-number">3,500+</h3>
                                <p class="stat-label">ผู้ใช้งาน</p>
                            </div>
                            <div class="stat-item">
                                <h3 class="stat-number">120+</h3>
                                <p class="stat-label">สถาบันการศึกษา</p>
                            </div>
                            <div class="stat-item">
                                <h3 class="stat-number">15,000+</h3>
                                <p class="stat-label">การทดสอบ</p>
                            </div>
                        </div>

                        <a href="#" class="btn about-btn waves-effect waves-light">
                            <i class="fas fa-info-circle"></i> ข้อมูลเพิ่มเติม
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="cta-bg-circles">
            <div class="cta-circle cta-circle-1"></div>
            <div class="cta-circle cta-circle-2"></div>
            <div class="cta-circle cta-circle-3"></div>
        </div>

        <div class="container">
            <div class="cta-content" data-aos="zoom-in">
                <h2 class="cta-title">เริ่มต้นประเมินสมรรถนะวิชาชีพครูได้ทันที</h2>
                <p class="cta-description">ร่วมเป็นส่วนหนึ่งในการพัฒนาศักยภาพครูช่างอุตสาหกรรมไทย ด้วยระบบประเมินที่ทันสมัย มาตรฐานสูง ใช้งานง่าย</p>
                <a href="#" class="btn cta-btn waves-effect waves-light">
                    <i class="fas fa-user-plus"></i> สมัครใช้งานฟรี
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col s12 l4">
                    <div class="footer-logo">
                        <h4>ระบบประเมินสมรรถนะวิชาชีพครู</h4>
                    </div>
                    <p class="footer-description">
                        ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมในสถานศึกษา คณะครุศาสตร์อุตสาหกรรมและเทคโนโลยี มหาวิทยาลัยเทคโนโลยีราชมงคลศรีวิชัย
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-line"></i></a>
                    </div>
                </div>

                <div class="col s12 m6 l4">
                    <h4 class="footer-widget-title">ลิงก์ด่วน</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> หน้าหลัก</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> ข่าวประชาสัมพันธ์</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> ปฏิทินการสอบ</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> สอบออนไลน์</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> ประกาศผลสอบ</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> คลังข้อสอบ</a></li>
                    </ul>
                </div>

                <div class="col s12 m6 l4">
                    <h4 class="footer-widget-title">ติดต่อเรา</h4>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>คณะครุศาสตร์อุตสาหกรรมและเทคโนโลยี<br>มหาวิทยาลัยเทคโนโลยีราชมงคลศรีวิชัย<br>เลขที่ 1 ถ.ราชดำเนินนอก ต.บ่อยาง อ.เมือง จ.สงขลา 90000</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>0-7431-7100 ต่อ 3000, 3016</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>ied@rmutsv.ac.th</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมในสถานศึกษา. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll Top Button -->
    <div class="scrolltop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom Script -->
    <script>
        $(document).ready(function(){
            // Initialize Materialize components
            $('.sidenav').sidenav();
            $('.dropdown-trigger').dropdown({
                coverTrigger: false,
                constrainWidth: false,
                hover: true
            });
            
            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Preloader