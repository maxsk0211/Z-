<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบประเมินสมรรถนะวิชาชีพครู</title>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Google Fonts - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Materialize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        /* Variables */
        :root {
            --primary-color: #FDD835;
            --primary-light: #FFFF6B;
            --primary-dark: #C6A700;
            --secondary-color: #455A64;
            --secondary-light: #718792;
            --secondary-dark: #1C313A;
            --accent-color: #FF5722;
            --accent-light: #FF8A65;
            --text-on-primary: #212121;
            --text-on-secondary: #FFFFFF;
            --success-color: #4CAF50;
            --error-color: #F44336;
            --warning-color: #FFC107;
            --info-color: #2196F3;
        }
        
        /* Global Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            overflow-x: hidden;
            background-color: #f5f5f5;
            color: #333;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, var(--secondary-dark), var(--secondary-color));
            color: white;
            padding: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            position: relative;
            z-index: 10;
        }
        
        .header-right {
            text-align: right;
        }
        
        .header-right a {
            margin-left: 15px;
            color: white;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .header-right a:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .header-right i {
            margin-right: 5px;
        }
        
        /* Navigation Bar */
        nav {
            background-color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 5;
        }
        
        nav .nav-wrapper {
            padding: 0 15px;
        }
        
        nav .brand-logo {
            font-weight: 600;
            margin-left: 10px;
        }
        
        nav ul a {
            color: var(--text-on-primary);
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 0 20px;
        }
        
        nav ul a:hover {
            background-color: rgba(0,0,0,0.05);
        }
        
        nav ul a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 3px;
            bottom: 0;
            left: 0;
            background-color: var(--accent-color);
            transition: width 0.3s ease;
        }
        
        nav ul a:hover::after {
            width: 100%;
        }
        
        .dropdown-content {
            min-width: 200px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform-origin: top;
            transition: transform 0.3s ease, opacity 0.3s ease;
            transform: scaleY(0);
            opacity: 0;
            display: block;
            top: 64px !important;
        }
        
        .dropdown-content li > a {
            color: var(--secondary-dark);
            padding: 12px 20px;
            font-weight: 400;
            display: block;
            transition: all 0.3s ease;
        }
        
        .dropdown-content li > a:hover {
            background-color: rgba(0,0,0,0.05);
            padding-left: 25px;
            color: var(--accent-color);
        }
        
        .dropdown-trigger:hover + .dropdown-content,
        .dropdown-content:hover {
            display: block;
            opacity: 1;
            transform: scaleY(1);
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(to bottom, var(--secondary-color), var(--secondary-dark));
            min-height: 80vh;
            padding: 50px 0;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        .hero-content {
            color: white;
            position: relative;
            z-index: 2;
        }
        
        .hero-content h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-content h2 {
            font-size: 1.5rem;
            font-weight: 400;
            margin-top: 0;
            opacity: 0.9;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        
        .hero-buttons {
            margin-top: 2rem;
        }
        
        .hero-buttons .btn {
            margin-right: 1rem;
            margin-bottom: 1rem;
            padding: 0 2rem;
            border-radius: 30px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .hero-buttons .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .btn-start {
            background-color: var(--primary-color);
            color: var(--text-on-primary);
        }
        
        .btn-learn {
            background-color: transparent;
            border: 2px solid white;
        }
        
        .hero-image {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .hero-image img {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));
            transition: all 0.5s ease;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        
        /* Decorative Elements */
        .circle-decoration {
            position: absolute;
            bottom: -250px;
            left: -250px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            opacity: 0.8;
            z-index: 1;
            animation: pulse 8s infinite alternate;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }
            100% {
                transform: scale(1.1);
                opacity: 0.6;
            }
        }
        
        .shape-1 {
            position: absolute;
            top: 10%;
            right: 5%;
            width: 100px;
            height: 100px;
            background-color: var(--accent-color);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            opacity: 0.2;
            z-index: 1;
            animation: morph 10s linear infinite;
        }
        
        .shape-2 {
            position: absolute;
            bottom: 10%;
            right: 15%;
            width: 150px;
            height: 150px;
            background-color: var(--primary-color);
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            opacity: 0.2;
            z-index: 1;
            animation: morph 12s linear infinite alternate;
        }
        
        @keyframes morph {
            0% {
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            }
            50% {
                border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%;
            }
            100% {
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            }
        }
        
        /* Pagination Dots */
        .pagination-dots {
            text-align: center;
            margin-top: 30px;
            position: relative;
            z-index: 3;
        }
        
        .dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dot.active {
            background-color: var(--primary-color);
            transform: scale(1.3);
            box-shadow: 0 0 10px var(--primary-color);
        }
        
        .dot:hover {
            background-color: var(--primary-light);
            transform: scale(1.2);
        }
        
        /* Feature Section */
        .features {
            padding: 80px 0;
            background-color: white;
        }
        
        .features .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .features .section-title h2 {
            font-size: 2.2rem;
            color: var(--secondary-dark);
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .features .section-title h2::after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background-color: var(--primary-color);
            bottom: -10px;
            left: 25%;
        }
        
        .features .section-title p {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .feature-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px 20px;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-align: center;
            z-index: 1;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 50%, rgba(253, 216, 53, 0.1) 50%);
            top: -100%;
            left: -100%;
            z-index: -1;
            transition: all 0.6s ease-in-out;
        }
        
        .feature-card:hover::before {
            top: 0;
            left: 0;
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            box-shadow: 0 5px 15px rgba(253, 216, 53, 0.3);
        }
        
        .feature-icon i {
            font-size: 32px;
            color: var(--text-on-primary);
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            color: var(--secondary-dark);
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .feature-card .btn-flat {
            color: var(--accent-color);
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .feature-card .btn-flat:hover {
            background-color: rgba(255, 87, 34, 0.1);
            color: var(--accent-color);
        }
        
        /* News Section */
        .latest-news {
            padding: 80px 0;
            background-color: #f9f9f9;
            position: relative;
            overflow: hidden;
        }
        
        .news-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(253, 216, 53, 0.1), rgba(255, 255, 255, 0));
            z-index: 0;
        }
        
        .latest-news .section-title {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
            z-index: 1;
        }
        
        .latest-news .section-title h2 {
            font-size: 2.2rem;
            color: var(--secondary-dark);
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .latest-news .section-title h2::after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background-color: var(--primary-color);
            bottom: -10px;
            left: 25%;
        }
        
        .latest-news .section-title p {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .news-card {
            border-radius: 10px;
            overflow: hidden;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .news-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .news-img {
            position: relative;
            overflow: hidden;
            height: 200px;
        }
        
        .news-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.5s ease;
        }
        
        .news-card:hover .news-img img {
            transform: scale(1.1);
        }
        
        .news-date {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--primary-color);
            color: var(--text-on-primary);
            padding: 7px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            z-index: 1;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .news-content {
            padding: 20px;
        }
        
        .news-content h3 {
            font-size: 1.4rem;
            color: var(--secondary-dark);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .news-content p {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .news-content .btn-flat {
            color: var(--accent-color);
            padding: 5px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .news-content .btn-flat:hover {
            background-color: rgba(255, 87, 34, 0.1);
        }
        
        /* Footer */
        footer {
            background-color: var(--secondary-dark);
            color: white;
            padding: 50px 0 20px;
            position: relative;
        }
        
        .footer-content {
            margin-bottom: 30px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .footer-logo i {
            font-size: 36px;
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .footer-logo h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .footer-content p {
            opacity: 0.8;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--primary-color);
            color: var(--text-on-primary);
            transform: translateY(-3px);
        }
        
        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
            color: white;
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
            bottom: 0;
            left: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: white;
            opacity: 0.8;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .footer-links a:hover {
            opacity: 1;
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .contact-info li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .contact-info i {
            margin-right: 10px;
            color: var(--primary-color);
            margin-top: 3px;
        }
        
        .contact-info span {
            opacity: 0.8;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
        }
        
        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--text-on-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            background-color: var(--primary-light);
            transform: translateY(-5px);
        }
        
        /* Preloader */
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: all 0.6s ease-in-out;
        }
        
        .preloader.hide {
            opacity: 0;
            visibility: hidden;
        }
        
        .spinner {
            width: 70px;
            height: 70px;
            position: relative;
            animation: spin 2s linear infinite;
        }
        
        .spinner::before,
        .spinner::after {
            content: '';
            position: absolute;
            border-radius: 50%;
        }
        
        .spinner::before {
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            animation: pulse 1s infinite alternate;
        }
        
        .spinner::after {
            width: 80%;
            height: 80%;
            background-color: white;
            top: 10%;
            left: 10%;
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Mobile Sidenav */
        .sidenav {
            background-color: white;
            width: 250px;
        }
        
        .sidenav li > a {
            color: var(--secondary-dark);
            font-weight: 500;
            padding: 0 32px;
            height: 50px;
            line-height: 50px;
        }
        
        .sidenav li > a:hover {
            background-color: rgba(253, 216, 53, 0.1);
        }
        
        .sidenav li > a > i {
            color: var(--secondary-dark);
            margin-right: 10px;
        }
        
        .sidenav .subheader {
            color: #999;
            font-weight: 500;
        }
        
        .sidenav .divider {
            margin: 10px 0;
        }
        
        /* Responsive Styles */
        @media only screen and (max-width: 992px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content h2 {
                font-size: 1.2rem;
            }
            
            .features, .latest-news {
                padding: 60px 0;
            }
            
            .feature-card {
                margin-bottom: 30px;
            }
            
            .header-right {
                text-align: center;
                margin-top: 10px;
            }
            
            .header-right a {
                margin: 0 5px;
            }
        }
        
        @media only screen and (max-width: 600px) {
            .hero-content h1 {
                font-size: 1.5rem;
            }
            
            .hero-content h2 {
                font-size: 1rem;
            }
            
            .hero-section {
                min-height: 100vh;
                padding: 30px 0;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
            }
            
            .feature-icon i {
                font-size: 24px;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
            
            .back-to-top {
                width: 40px;
                height: 40px;
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s12 m6">
                    <h5 class="white-text">ระบบประเมินสมรรถนะวิชาชีพครู</h5>
                </div>
                <div class="col s12 m6 header-right">
                    <a href="#" class="white-text animate__animated animate__fadeIn animate__delay-1s">
                        <i class="material-icons">person_add</i>สมัครสมาชิก
                    </a>
                    <a href="#" class="white-text animate__animated animate__fadeIn animate__delay-2s">
                        <i class="material-icons">login</i>เข้าสู่ระบบ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="navbar-fixed">
        <nav>
            <div class="container">
                <div class="nav-wrapper">
                    <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
                    <ul class="hide-on-med-and-down">
                        <li class="dropdown-trigger">
                            <a href="#">ข่าวประชาสัมพันธ์<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#"><i class="material-icons left">article</i>ข่าวทั้งหมด</a></li>
                                <li><a href="#"><i class="material-icons left">announcement</i>ประกาศ</a></li>
                                <li><a href="#"><i class="material-icons left">event</i>กิจกรรม</a></li>
                            </ul>
                        </li>
                        <li class="dropdown-trigger">
                            <a href="#">ปฏิทินการสอบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#"><i class="material-icons left">event_note</i>ตารางสอบ</a></li>
                                <li><a href="#"><i class="material-icons left">schedule</i>กำหนดการ</a></li>
                            </ul>
                        </li>
                        <li><a href="#"><i class="material-icons left">edit</i>สอบออนไลน์</a></li>
                        <li class="dropdown-trigger">
                            <a href="#">ประกาศผลสอบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#"><i class="material-icons left">new_releases</i>ผลการสอบล่าสุด</a></li>
                                <li><a href="#"><i class="material-icons left">history</i>ประวัติการสอบ</a></li>
                            </ul>
                        </li>
                        <li class="dropdown-trigger">
                            <a href="#">คลังข้อสอบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#"><i class="material-icons left">school</i>ทดลองทำข้อสอบ</a></li>
                                <li><a href="#"><i class="material-icons left">menu_book</i>แนวข้อสอบ</a></li>
                            </ul>
                        </li>
                        <li class="dropdown-trigger">
                            <a href="#">ผู้ดูแลระบบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#"><i class="material-icons left">settings</i>จัดการข้อมูล</a></li>
                                <li><a href="#"><i class="material-icons left">assessment</i>รายงาน</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <!-- Mobile Sidenav -->
    <ul class="sidenav" id="mobile-nav">
        <li><div class="user-view">
            <div class="background" style="background-color: var(--secondary-color)"></div>
            <a href="#"><i class="material-icons white-text" style="font-size: 48px;">school</i></a>
            <a href="#"><span class="white-text name">ระบบประเมินสมรรถนะวิชาชีพครู</span></a>
            <a href="#"><span class="white-text email">มหาวิทยาลัยเทคโนโลยีราชมงคลศรีวิชัย</span></a>
        </div></li>
        
        <li><a class="subheader">เมนูหลัก</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">home</i>หน้าแรก</a></li>
        <li><div class="divider"></div></li>
        
        <li><a class="subheader">ข่าวสาร</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">article</i>ข่าวทั้งหมด</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">announcement</i>ประกาศ</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">event</i>กิจกรรม</a></li>
        <li><div class="divider"></div></li>
        
        <li><a class="subheader">การสอบ</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">event_note</i>ตารางสอบ</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">schedule</i>กำหนดการ</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">edit</i>สอบออนไลน์</a></li>
        <li><div class="divider"></div></li>
        
        <li><a class="subheader">ผลการสอบ</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">new_releases</i>ผลการสอบล่าสุด</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">history</i>ประวัติการสอบ</a></li>
        <li><div class="divider"></div></li>
        
        <li><a class="subheader">คลังข้อมูล</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">school</i>ทดลองทำข้อสอบ</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">menu_book</i>แนวข้อสอบ</a></li>
        <li><div class="divider"></div></li>
        
        <li><a href="#" class="waves-effect"><i class="material-icons">login</i>เข้าสู่ระบบ</a></li>
        <li><a href="#" class="waves-effect"><i class="material-icons">person_add</i>สมัครสมาชิก</a></li>
    </ul>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="circle-decoration"></div>
        <div class="shape-1"></div>
        <div class="shape-2"></div>
        <div class="container">
            <div class="row">
                <div class="col s12 m6" data-aos="fade-right" data-aos-duration="1000">
                    <div class="hero-content">
                        <h1 class="animate__animated animate__fadeInUp">ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมในสถานศึกษา</h1>
                        <h2 class="animate__animated animate__fadeInUp animate__delay-1s">คณะครุศาสตร์อุตสาหกรรมและเทคโนโลยี<br>มหาวิทยาลัยเทคโนโลยีราชมงคลศรีวิชัย</h2>
                        <div class="hero-buttons animate__animated animate__fadeInUp animate__delay-2s">
                            <a href="#" class="btn btn-start waves-effect waves-light">
                                <i class="material-icons left">play_arrow</i>เริ่มทำแบบทดสอบ
                            </a>
                            <a href="#" class="btn btn-learn waves-effect waves-light">
                                <i class="material-icons left">info</i>เรียนรู้เพิ่มเติม
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col s12 m6" data-aos="fade-left" data-aos-duration="1000">
                    <div class="hero-image">
                        <img src="https://cdn.pixabay.com/photo/2021/11/04/06/27/programmer-6767507_1280.png" alt="ระบบประเมินสมรรถนะวิชาชีพครู" class="responsive-img">
                    </div>
                </div>
            </div>
            <div class="pagination-dots">
                <span class="dot active" data-slide="0"></span>
                <span class="dot" data-slide="1"></span>
                <span class="dot" data-slide="2"></span>
            </div>
        </div>
    </section>

    <!-- Feature Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>คุณสมบัติของระบบ</h2>
                <p>ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมในสถานศึกษา มีคุณสมบัติที่หลากหลายเพื่อช่วยให้การประเมินเป็นไปอย่างมีประสิทธิภาพ</p>
            </div>
            
            <div class="row">
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="material-icons">computer</i>
                        </div>
                        <h3>สอบออนไลน์</h3>
                        <p>ทำแบบทดสอบผ่านระบบออนไลน์ได้ทุกที่ทุกเวลา พร้อมระบบจับเวลาและประมวลผลอัตโนมัติ</p>
                        <a href="#" class="btn-flat waves-effect">อ่านเพิ่มเติม<i class="material-icons right">arrow_forward</i></a>
                    </div>
                </div>
                
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="material-icons">assessment</i>
                        </div>
                        <h3>วิเคราะห์ผลการสอบ</h3>
                        <p>วิเคราะห์ผลการทำแบบทดสอบและแสดงผลสมรรถนะในแต่ละด้านพร้อมคำแนะนำในการพัฒนา</p>
                        <a href="#" class="btn-flat waves-effect">อ่านเพิ่มเติม<i class="material-icons right">arrow_forward</i></a>
                    </div>
                </div>
                
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="material-icons">school</i>
                        </div>
                        <h3>คลังข้อสอบมาตรฐาน</h3>
                        <p>ระบบมีคลังข้อสอบมาตรฐานตามเกณฑ์สมรรถนะวิชาชีพครูช่างอุตสาหกรรมที่ครอบคลุมทุกด้าน</p>
                        <a href="#" class="btn-flat waves-effect">อ่านเพิ่มเติม<i class="material-icons right">arrow_forward</i></a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="material-icons">history</i>
                        </div>
                        <h3>ประวัติการทดสอบ</h3>
                        <p>เก็บประวัติการทำแบบทดสอบทั้งหมด สามารถดูพัฒนาการของตนเองได้อย่างต่อเนื่อง</p>
                        <a href="#" class="btn-flat waves-effect">อ่านเพิ่มเติม<i class="material-icons right">arrow_forward</i></a>
                    </div>
                </div>
                
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="material-icons">security</i>
                        </div>
                        <h3>ความปลอดภัยสูง</h3>
                        <p>ระบบมีความปลอดภัยสูง รักษาความลับของข้อมูลและผลการทดสอบของผู้ใช้งานทุกคน</p>
                        <a href="#" class="btn-flat waves-effect">อ่านเพิ่มเติม<i class="material-icons right">arrow_forward</i></a>
                    </div>
                </div>
                
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="material-icons">devices</i>
                        </div>
                        <h3>รองรับทุกอุปกรณ์</h3>
                        <p>สามารถใช้งานได้บนทุกอุปกรณ์ ทั้งคอมพิวเตอร์ แท็บเล็ต และสมาร์ทโฟน</p>
                        <a href="#" class="btn-flat waves-effect">อ่านเพิ่มเติม<i class="material-icons right">arrow_forward</i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest News Section -->
    <section class="latest-news" id="news">
        <div class="news-background"></div>
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>ข่าวประชาสัมพันธ์ล่าสุด</h2>
                <p>ติดตามข่าวสารและประกาศสำคัญเกี่ยวกับการประเมินสมรรถนะวิชาชีพครูช่างอุตสาหกรรม</p>
            </div>
            
            <div class="row">
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="100">
                    <div class="news-card">
                        <div class="news-img">
                            <img src="https://picsum.photos/id/180/800/600" alt="การอบรมเชิงปฏิบัติการ">
                            <span class="news-date">12 มี.ค. 2566</span>
                        </div>
                        <div class="news-content">
                            <h3>การอบรมเชิงปฏิบัติการ</h3>
                            <p>การอบรมเชิงปฏิบัติการเพื่อพัฒนาทักษะวิชาชีพครูช่างอุตสาหกรรม ในวันที่ 20-22 มีนาคม 2566</p>
                            <a href="#" class="btn-flat waves-effect">อ่านต่อ<i class="material-icons right">arrow_forward</i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="200">
                    <div class="news-card">
                        <div class="news-img">
                            <img src="https://picsum.photos/id/60/800/600" alt="ประกาศรับสมัครทดสอบ">
                            <span class="news-date">5 มี.ค. 2566</span>
                        </div>
                        <div class="news-content">
                            <h3>ประกาศรับสมัครทดสอบ</h3>
                            <p>เปิดรับสมัครผู้เข้าทดสอบสมรรถนะวิชาชีพครูช่างอุตสาหกรรมประจำปี 2566 ตั้งแต่วันนี้ถึง 30 มีนาคม</p>
                            <a href="#" class="btn-flat waves-effect">อ่านต่อ<i class="material-icons right">arrow_forward</i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m4" data-aos="fade-up" data-aos-delay="300">
                    <div class="news-card">
                        <div class="news-img">
                            <img src="https://picsum.photos/id/20/800/600" alt="ประกาศผลการทดสอบ">
                            <span class="news-date">28 ก.พ. 2566</span>
                        </div>
                        <div class="news-content">
                            <h3>ประกาศผลการทดสอบ</h3>
                            <p>ประกาศผลการทดสอบสมรรถนะวิชาชีพครูช่างอุตสาหกรรมรอบเดือนกุมภาพันธ์ 2566</p>
                            <a href="#" class="btn-flat waves-effect">อ่านต่อ<i class="material-icons right">arrow_forward</i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="center-align" style="margin-top: 40px;" data-aos="fade-up">
                <a href="#" class="btn btn-start waves-effect waves-light">
                    <i class="material-icons left">article</i>ดูข่าวทั้งหมด
                </a>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section" style="background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark)); padding: 80px 0; color: white;">
        <div class="container center-align" data-aos="zoom-in">
            <h2 style="font-size: 2.2rem; margin-bottom: 20px;">พร้อมทดสอบสมรรถนะวิชาชีพครูแล้วหรือยัง?</h2>
            <p style="font-size: 1.2rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">
                ลงทะเบียนเข้าร่วมการทดสอบเพื่อประเมินและพัฒนาสมรรถนะวิชาชีพครูช่างอุตสาหกรรมของคุณ
            </p>
            <div>
                <a href="#" class="btn btn-start waves-effect waves-light pulse" style="margin-right: 15px;">
                    <i class="material-icons left">how_to_reg</i>ลงทะเบียนเข้าร่วม
                </a>
                <a href="#" class="btn btn-learn waves-effect waves-light" style="background-color: transparent; border: 2px solid white;">
                    <i class="material-icons left">contact_support</i>สอบถามข้อมูล
                </a>
            </div>
        </div>
    </section>

    <!-- Counter Stats Section -->
    <section class="stats-section" style="padding: 80px 0; background-color: white;">
        <div class="container">
            <div class="row">
                <div class="col s12 m3 center-align" data-aos="fade-up" data-aos-delay="100">
                    <div class="counter-item">
                        <i class="material-icons" style="font-size: 48px; color: var(--primary-color)">people</i>
                        <h3 class="counter" style="font-size: 2.5rem; font-weight: 600; margin: 15px 0;">3,254</h3>
                        <p style="font-size: 1.2rem; color: var(--secondary-dark);">ผู้ใช้งานทั้งหมด</p>
                    </div>
                </div>
                
                <div class="col s12 m3 center-align" data-aos="fade-up" data-aos-delay="200">
                    <div class="counter-item">
                        <i class="material-icons" style="font-size: 48px; color: var(--primary-color)">assignment</i>
                        <h3 class="counter" style="font-size: 2.5rem; font-weight: 600; margin: 15px 0;">12,763</h3>
                        <p style="font-size: 1.2rem; color: var(--secondary-dark);">การทดสอบทั้งหมด</p>
                    </div>
                </div>
                
                <div class="col s12 m3 center-align" data-aos="fade-up" data-aos-delay="300">
                    <div class="counter-item">
                        <i class="material-icons" style="font-size: 48px; color: var(--primary-color)">school</i>
                        <h3 class="counter" style="font-size: 2.5rem; font-weight: 600; margin: 15px 0;">487</h3>
                        <p style="font-size: 1.2rem; color: var(--secondary-dark);">สถานศึกษาทั่วประเทศ</p>
                    </div>
                </div>
                
                <div class="col s12 m3 center-align" data-aos="fade-up" data-aos-delay="400">
                    <div class="counter-item">
                        <i class="material-icons" style="font-size: 48px; color: var(--primary-color)">star</i>
                        <h3 class="counter" style="font-size: 2.5rem; font-weight: 600; margin: 15px 0;">9.8</h3>
                        <p style="font-size: 1.2rem; color: var(--secondary-dark);">คะแนนความพึงพอใจ</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="testimonial-section" style="padding: 80px 0; background-color: #f9f9f9; position: relative; overflow: hidden;">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>เสียงจากผู้ใช้งาน</h2>
                <p>ความคิดเห็นจากครูช่างอุตสาหกรรมที่ใช้งานระบบประเมินสมรรถนะของเรา</p>
            </div>
            
            <div class="carousel carousel-slider center" data-indicators="true" style="height: 350px;">
                <div class="carousel-item" style="background-color: transparent;">
                    <div class="testimonial-card" style="background: white; border-radius: 10px; padding: 30px; max-width: 700px; margin: 0 auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" data-aos="fade-up">
                        <div class="testimonial-content">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <i class="material-icons" style="font-size: 48px; color: var(--primary-color);">format_quote</i>
                            </div>
                            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px; font-style: italic; color: #666;">
                                "ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมนี้ช่วยให้ผมทราบจุดแข็งและจุดที่ต้องพัฒนาของตนเองได้อย่างชัดเจน ทำให้วางแผนพัฒนาตนเองได้ตรงจุด"
                            </p>
                            <div class="testimonial-author" style="display: flex; align-items: center; justify-content: center;">
                                <div class="author-img" style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; margin-right: 15px;">
                                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="ผู้ใช้งาน" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="author-info">
                                    <h5 style="margin: 0; font-weight: 600; color: var(--secondary-dark);">อาจารย์สมชาย ใจดี</h5>
                                    <p style="margin: 5px 0 0; font-size: 0.9rem; color: #999;">วิทยาลัยเทคนิคนครศรีธรรมราช</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="carousel-item" style="background-color: transparent;">
                    <div class="testimonial-card" style="background: white; border-radius: 10px; padding: 30px; max-width: 700px; margin: 0 auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <div class="testimonial-content">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <i class="material-icons" style="font-size: 48px; color: var(--primary-color);">format_quote</i>
                            </div>
                            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px; font-style: italic; color: #666;">
                                "ผมประทับใจกับความสะดวกในการใช้งานและความแม่นยำในการประเมิน ข้อเสนอแนะที่ได้รับมีประโยชน์มากต่อการพัฒนาการสอนของผม"
                            </p>
                            <div class="testimonial-author" style="display: flex; align-items: center; justify-content: center;">
                                <div class="author-img" style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; margin-right: 15px;">
                                    <img src="https://randomuser.me/api/portraits/men/41.jpg" alt="ผู้ใช้งาน" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="author-info">
                                    <h5 style="margin: 0; font-weight: 600; color: var(--secondary-dark);">อาจารย์วิชัย มั่นคง</h5>
                                    <p style="margin: 5px 0 0; font-size: 0.9rem; color: #999;">วิทยาลัยเทคนิคภูเก็ต</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="carousel-item" style="background-color: transparent;">
                    <div class="testimonial-card" style="background: white; border-radius: 10px; padding: 30px; max-width: 700px; margin: 0 auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <div class="testimonial-content">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <i class="material-icons" style="font-size: 48px; color: var(--primary-color);">format_quote</i>
                            </div>
                            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px; font-style: italic; color: #666;">
                                "ระบบนี้เป็นเครื่องมือที่ดีมากในการพัฒนาวิชาชีพครู แบบทดสอบมีมาตรฐาน และช่วยให้ดิฉันเห็นพัฒนาการของตนเองได้อย่างชัดเจน"
                            </p>
                            <div class="testimonial-author" style="display: flex; align-items: center; justify-content: center;">
                                <div class="author-img" style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; margin-right: 15px;">
                                    <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="ผู้ใช้งาน" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="author-info">
                                    <h5 style="margin: 0; font-weight: 600; color: var(--secondary-dark);">อาจารย์สุนันทา พัฒนา</h5>