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
    
    <style>
        /* Custom styles */
        :root {
            --primary-color: #FDD835;
            --primary-light: #FFFF6B;
            --primary-dark: #C6A700;
            --secondary-color: #455A64;
            --secondary-light: #718792;
            --secondary-dark: #1C313A;
            --text-on-primary: #000000;
            --text-on-secondary: #FFFFFF;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            overflow-x: hidden;
        }
        
        nav {
            background-color: var(--primary-color);
            box-shadow: none;
        }
        
        nav ul a {
            color: var(--text-on-primary);
            font-weight: 500;
        }
        
        .dropdown-content li > a {
            color: var(--secondary-dark);
        }
        
        .header-section {
            background-color: var(--secondary-color);
            color: white;
            padding: 10px 0;
        }
        
        .header-right {
            text-align: right;
        }
        
        .header-right a {
            margin-left: 15px;
            color: white;
        }
        
        .hero-section {
            background-color: var(--secondary-color);
            min-height: 80vh;
            padding: 50px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            color: white;
            position: relative;
            z-index: 2;
        }
        
        .hero-content h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .hero-content h2 {
            font-size: 1.5rem;
            font-weight: 400;
            margin-top: 0;
        }
        
        .hero-image {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .hero-image img {
            max-width: 100%;
            height: auto;
        }
        
        .circle-decoration {
            position: absolute;
            bottom: -250px;
            left: -250px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background-color: var(--primary-color);
            opacity: 0.8;
            z-index: 1;
        }
        
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
        }
        
        .dot.active {
            background-color: var(--primary-color);
        }
        
        /* Dropdown hover styles */
        .dropdown-trigger:hover + .dropdown-content,
        .dropdown-content:hover {
            display: block;
            opacity: 1;
            transform: scaleY(1);
        }
        
        .dropdown-content {
            transform-origin: top;
            transition: all 0.3s ease;
            transform: scaleY(0);
            opacity: 0;
            display: block;
            position: absolute;
        }
        
        /* Button styles */
        .btn-custom {
            background-color: var(--primary-color);
            color: var(--text-on-primary);
        }
        
        .btn-custom:hover {
            background-color: var(--primary-light);
        }
        
        /* Navigation active state */
        nav ul li.active {
            background-color: rgba(0,0,0,0.1);
        }
        
        /* Responsive adjustments */
        @media only screen and (max-width: 992px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content h2 {
                font-size: 1.2rem;
            }
            
            .sidenav-trigger {
                color: var(--text-on-primary);
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
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s12 m6">
                    <h5 class="white-text">ระบบประเมินสมรรถนะวิชาชีพครู</h5>
                </div>
                <div class="col s12 m6 header-right">
                    <a href="#" class="white-text"><i class="material-icons left">person_add</i>สมัครสมาชิก</a>
                    <a href="#" class="white-text"><i class="material-icons left">login</i>เข้าสู่ระบบ</a>
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
                                <li><a href="#">ข่าวทั้งหมด</a></li>
                                <li><a href="#">ประกาศ</a></li>
                                <li><a href="#">กิจกรรม</a></li>
                            </ul>
                        </li>
                        <li class="dropdown-trigger">
                            <a href="#">ปฏิทินการสอบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#">ตารางสอบ</a></li>
                                <li><a href="#">กำหนดการ</a></li>
                            </ul>
                        </li>
                        <li><a href="#">สอบออนไลน์</a></li>
                        <li class="dropdown-trigger">
                            <a href="#">ประกาศผลสอบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#">ผลการสอบล่าสุด</a></li>
                                <li><a href="#">ประวัติการสอบ</a></li>
                            </ul>
                        </li>
                        <li class="dropdown-trigger">
                            <a href="#">คลังข้อสอบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#">ทดลองทำข้อสอบ</a></li>
                                <li><a href="#">แนวข้อสอบ</a></li>
                            </ul>
                        </li>
                        <li class="dropdown-trigger">
                            <a href="#">ผู้ดูแลระบบ<i class="material-icons right">arrow_drop_down</i></a>
                            <ul class="dropdown-content">
                                <li><a href="#">จัดการข้อมูล</a></li>
                                <li><a href="#">รายงาน</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <!-- Mobile Sidenav -->
    <ul class="sidenav" id="mobile-nav">
        <li><a href="#" class="dropdown-trigger" data-target="news-mobile">ข่าวประชาสัมพันธ์<i class="material-icons right">arrow_drop_down</i></a></li>
        <ul id="news-mobile" class="dropdown-content">
            <li><a href="#">ข่าวทั้งหมด</a></li>
            <li><a href="#">ประกาศ</a></li>
            <li><a href="#">กิจกรรม</a></li>
        </ul>
        
        <li><a href="#" class="dropdown-trigger" data-target="calendar-mobile">ปฏิทินการสอบ<i class="material-icons right">arrow_drop_down</i></a></li>
        <ul id="calendar-mobile" class="dropdown-content">
            <li><a href="#">ตารางสอบ</a></li>
            <li><a href="#">กำหนดการ</a></li>
        </ul>
        
        <li><a href="#">สอบออนไลน์</a></li>
        
        <li><a href="#" class="dropdown-trigger" data-target="results-mobile">ประกาศผลสอบ<i class="material-icons right">arrow_drop_down</i></a></li>
        <ul id="results-mobile" class="dropdown-content">
            <li><a href="#">ผลการสอบล่าสุด</a></li>
            <li><a href="#">ประวัติการสอบ</a></li>
        </ul>
        
        <li><a href="#" class="dropdown-trigger" data-target="bank-mobile">คลังข้อสอบ<i class="material-icons right">arrow_drop_down</i></a></li>
        <ul id="bank-mobile" class="dropdown-content">
            <li><a href="#">ทดลองทำข้อสอบ</a></li>
            <li><a href="#">แนวข้อสอบ</a></li>
        </ul>
        
        <li><a href="#" class="dropdown-trigger" data-target="admin-mobile">ผู้ดูแลระบบ<i class="material-icons right">arrow_drop_down</i></a></li>
        <ul id="admin-mobile" class="dropdown-content">
            <li><a href="#">จัดการข้อมูล</a></li>
            <li><a href="#">รายงาน</a></li>
        </ul>
    </ul>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="circle-decoration"></div>
        <div class="container">
            <div class="row">
                <div class="col s12 m6">
                    <div class="hero-content">
                        <h1>ระบบประเมินสมรรถนะความรู้วิชาชีพครูช่างอุตสาหกรรมในสถานศึกษา</h1>
                        <h2>คณะครุศาสตร์อุตสาหกรรมและเทคโนโลยี<br>มหาวิทยาลัยเทคโนโลยีราชมงคลศรีวิชัย</h2>
                    </div>
                </div>
                <div class="col s12 m6">
                    <div class="hero-image">
                        <img src="https://cdn.pixabay.com/photo/2021/11/04/06/27/programmer-6767507_1280.png" alt="ระบบประเมินสมรรถนะวิชาชีพครู">
                    </div>
                </div>
            </div>
            <div class="pagination-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </section>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Materialize components
            var elems = document.querySelectorAll('.sidenav');
            var instances = M.Sidenav.init(elems);
            
            var dropdowns = document.querySelectorAll('.dropdown-trigger');
            var dropdownInstances = M.Dropdown.init(dropdowns, {
                coverTrigger: false,
                constrainWidth: false,
                hover: true
            });
            
            // Dot pagination functionality
            const dots = document.querySelectorAll('.dot');
            dots.forEach(dot => {
                dot.addEventListener('click', function() {
                    dots.forEach(d => d.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>