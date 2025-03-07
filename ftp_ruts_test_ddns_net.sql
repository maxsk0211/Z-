-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 07, 2025 at 04:53 PM
-- Server version: 5.7.44-log
-- PHP Version: 8.3.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ftp_ruts_test_ddns_net`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ไม่ใช้งาน, 1=ใช้งาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `name`, `email`, `created_at`, `updated_at`, `status`) VALUES
(1, 'admin', '$2y$10$SjpKReGN9K5MBNS1GOJFE.lJPeJibXAdH4t3viLT.HzpQNniOF8Re', 'ผู้ดูแลระบบ', 'admin@example.com', '2025-03-04 15:10:28', '2025-03-05 20:52:23', 1),
(3, 'admin1', '$2y$10$owqK.oL56v6kfqp1Mf8aMe.13GXbd6T/qB6oJyiEhmi7.lFNEnskO', 'Teerapun', 'Teerapun.moo@gmail.com', '2025-03-05 21:39:15', '2025-03-05 21:41:35', 1),
(4, 'test', '$2y$10$QgsFGhnm31jJsDsemz9Mpemt25WTkQpleeHNxo9WZDqV1zY/m7Vua', 'test', 'ahddf@df.sdf', '2025-03-07 11:07:58', '2025-03-07 11:52:41', 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_log`
--

CREATE TABLE `admin_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_log`
--

INSERT INTO `admin_log` (`log_id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin', '124.120.39.206', '2025-03-05 12:44:10'),
(2, 1, 'update_admin_with_password', 'อัปเดตข้อมูลผู้ดูแลระบบรวมถึงรหัสผ่าน: admin', '124.120.39.206', '2025-03-05 12:47:16'),
(3, 1, 'update_admin_with_password', 'อัปเดตข้อมูลผู้ดูแลระบบรวมถึงรหัสผ่าน: admin', '124.120.39.206', '2025-03-05 12:47:45'),
(4, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin', '124.120.39.206', '2025-03-05 12:52:10'),
(5, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin', '124.120.39.206', '2025-03-05 12:52:23'),
(6, 1, 'create_admin', 'สร้างผู้ดูแลระบบใหม่: admin1', '124.120.39.206', '2025-03-05 12:53:26'),
(7, 1, 'delete_admin', 'ลบผู้ดูแลระบบ: admin1 (ID: 2)', '124.120.39.206', '2025-03-05 12:58:20'),
(8, 1, 'create_semester', 'สร้างเทอมใหม่: ปีการศึกษา 2567 เทอม 2', '124.120.39.206', '2025-03-05 13:19:55'),
(9, 1, 'toggle_semester_status', 'เปลี่ยนสถานะเทอม: ปีการศึกษา 2567 เทอม 2 เป็น ไม่ใช้งาน', '124.120.39.206', '2025-03-05 13:20:02'),
(10, 1, 'toggle_semester_status', 'เปลี่ยนสถานะเทอม: ปีการศึกษา 2567 เทอม 2 เป็น ใช้งาน', '124.120.39.206', '2025-03-05 13:20:05'),
(11, 1, 'update_semester', 'อัปเดตข้อมูลเทอม: ปีการศึกษา 2567 เทอม 1', '124.120.39.206', '2025-03-05 13:20:20'),
(12, 1, 'update_semester', 'อัปเดตข้อมูลเทอม: ปีการศึกษา 2567 เทอม 1', '124.120.39.206', '2025-03-05 13:20:26'),
(13, 1, 'update_semester', 'อัปเดตข้อมูลเทอม: ปีการศึกษา 2567 ภาคฤดูร้อน', '124.120.39.206', '2025-03-05 13:20:34'),
(14, 1, 'create_semester', 'สร้างเทอมใหม่: ปีการศึกษา 2567 เทอม 2', '124.120.39.206', '2025-03-05 13:20:52'),
(15, 1, 'update_semester', 'อัปเดตข้อมูลเทอม: ปีการศึกษา 2567 ภาคฤดูร้อน', '118.173.177.106', '2025-03-05 13:22:34'),
(16, 1, 'delete_semester', 'ลบเทอม: ปีการศึกษา 2567 เทอม 2', '124.120.39.206', '2025-03-05 13:30:53'),
(17, 1, 'update_semester', 'อัปเดตข้อมูลเทอม: ปีการศึกษา 2567 ภาคฤดูร้อน', '124.120.39.206', '2025-03-05 13:31:20'),
(18, 1, 'delete_semester', 'ลบเทอม: ปีการศึกษา 2567 ภาคฤดูร้อน', '118.173.177.106', '2025-03-05 13:38:08'),
(19, 1, 'create_semester', 'สร้างเทอมใหม่: ปีการศึกษา 2567 เทอม 1', '118.173.177.106', '2025-03-05 13:38:17'),
(20, 1, 'toggle_semester_status', 'เปลี่ยนสถานะเทอม: ปีการศึกษา 2567 เทอม 1 เป็น ไม่ใช้งาน', '118.173.177.106', '2025-03-05 13:38:22'),
(21, 1, 'toggle_semester_status', 'เปลี่ยนสถานะเทอม: ปีการศึกษา 2567 เทอม 1 เป็น ใช้งาน', '118.173.177.106', '2025-03-05 13:38:28'),
(22, 1, 'create_admin', 'สร้างผู้ดูแลระบบใหม่: admin1', '118.173.177.106', '2025-03-05 13:39:15'),
(23, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '118.173.177.106', '2025-03-05 13:39:30'),
(24, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '118.173.177.106', '2025-03-05 13:40:29'),
(25, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '118.173.177.106', '2025-03-05 13:40:35'),
(26, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '118.173.177.106', '2025-03-05 13:40:46'),
(27, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '118.173.177.106', '2025-03-05 13:40:56'),
(28, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '118.173.177.106', '2025-03-05 13:41:06'),
(29, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '124.120.39.206', '2025-03-05 13:41:19'),
(30, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '124.120.39.206', '2025-03-05 13:41:26'),
(31, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: admin1', '124.120.39.206', '2025-03-05 13:41:35'),
(32, 1, 'import_students_csv', 'นำเข้านักเรียนจากไฟล์ CSV: สำเร็จ 2 รายการ, ข้อมูลซ้ำ 0 รายการ, ผิดพลาด 0 รายการ', '118.173.177.106', '2025-03-05 16:01:59'),
(33, 1, 'import_students_csv', 'นำเข้านักเรียนจากไฟล์ CSV: สำเร็จ 0 รายการ, ข้อมูลซ้ำ 2 รายการ, ผิดพลาด 0 รายการ', '124.120.39.206', '2025-03-05 16:02:00'),
(34, 1, 'import_students_csv', 'นำเข้านักเรียนจากไฟล์ CSV: สำเร็จ 0 รายการ, ข้อมูลซ้ำ 2 รายการ, ผิดพลาด 0 รายการ', '118.173.177.106', '2025-03-05 16:02:13'),
(35, 1, 'import_students_csv', 'นำเข้านักเรียนจากไฟล์ CSV: สำเร็จ 0 รายการ, ข้อมูลซ้ำ 2 รายการ, ผิดพลาด 0 รายการ', '124.120.39.206', '2025-03-05 16:02:16'),
(36, 1, 'toggle_student_status', 'เปลี่ยนสถานะนักเรียน: สมหญิง รักเรียน (รหัส: 6201234568) เป็น ไม่ใช้งาน', '124.120.39.206', '2025-03-05 16:02:54'),
(37, 1, 'toggle_student_status', 'เปลี่ยนสถานะนักเรียน: สมหญิง รักเรียน (รหัส: 6201234568) เป็น ใช้งาน', '124.120.39.206', '2025-03-05 16:02:57'),
(38, 1, 'create_student', 'เพิ่มนักเรียนใหม่: ธีรพันธ์ มูละ (รหัส: 6201234566)', '118.173.177.106', '2025-03-05 16:03:05'),
(39, 1, 'delete_student', 'ลบนักเรียน: สมหญิง รักเรียน (รหัส: 6201234568)', '124.120.39.206', '2025-03-05 16:03:19'),
(40, 1, 'delete_student', 'ลบนักเรียน: ธีรพันธ์ มูละ (รหัส: 6201234566)', '118.173.177.106', '2025-03-05 16:03:35'),
(41, 1, 'update_student', 'อัปเดตข้อมูลนักเรียน: สมชาย1 ใจดี (รหัส: 6201234567)', '118.173.177.106', '2025-03-05 16:03:53'),
(42, 1, 'import_students_csv', 'นำเข้านักเรียนจากไฟล์ CSV: สำเร็จ 2 รายการ, ข้อมูลซ้ำ 0 รายการ, ผิดพลาด 0 รายการ', '118.173.177.106', '2025-03-05 16:04:47'),
(43, 1, 'update_student', 'อัปเดตข้อมูลนักเรียน: สมชาย ใจดี (รหัส: 62012369)', '118.173.177.106', '2025-03-05 16:05:02'),
(44, 1, 'update_student', 'อัปเดตข้อมูลนักเรียน: สมชาย ใจดี (รหัส: 620123691)', '118.173.177.106', '2025-03-05 16:05:26'),
(45, 1, 'export_students_csv', 'ส่งออกข้อมูลนักเรียนเป็น CSV: ปีการศึกษา 2567 เทอม1', '118.173.177.106', '2025-03-05 16:05:50'),
(46, 1, 'create_exam_set', 'สร้างชุดข้อสอบใหม่: ข้อสอบครู', '118.173.177.106', '2025-03-05 16:42:01'),
(47, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: ภาษาไทยเพื่ออาชีพครู (ชุดข้อสอบ: ข้อสอบครู)', '118.173.177.106', '2025-03-05 16:46:30'),
(48, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: จิตวิทยาเพื่ออาชีพครู (ชุดข้อสอบ: ข้อสอบครู)', '118.173.177.106', '2025-03-05 16:47:02'),
(49, 1, 'create_exam_set', 'สร้างชุดข้อสอบใหม่: ข้อสอบครูชุดที่ 2', '118.173.177.106', '2025-03-05 16:49:19'),
(50, 1, 'update_exam_set', 'อัปเดตชุดข้อสอบ: ข้อสอบครูชุดที่ 2 เป็น ข้อสอบครูชุดที่ 2', '124.120.39.206', '2025-03-06 14:52:08'),
(51, 1, 'create_exam_set', 'สร้างชุดข้อสอบใหม่: ข้อสอบชุด 3', '118.173.176.121', '2025-03-06 14:52:31'),
(52, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: s (ชุดข้อสอบ: ข้อสอบชุด 3)', '124.120.39.206', '2025-03-06 14:58:15'),
(53, 1, 'delete_exam_topic', 'ลบหัวข้อ: s (ชุดข้อสอบ: ข้อสอบชุด 3)', '124.120.39.206', '2025-03-06 14:59:45'),
(54, 1, 'update_exam_set', 'อัปเดตชุดข้อสอบ: ข้อสอบครูชุดที่ 1 เป็น ข้อสอบครูชุดที่ 1', '118.173.176.121', '2025-03-06 15:01:23'),
(55, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: การพัฒนาระบบจัดการการสอบ RUTS TEST (ชุดข้อสอบ: ข้อสอบครูชุดที่ 2)', '118.173.176.121', '2025-03-06 15:01:44'),
(56, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: ภาษาไทยเพื่ออาชีพครูเบื้องต้น 2 (ชุดข้อสอบ: ข้อสอบครูชุดที่ 1)', '118.173.176.121', '2025-03-06 15:02:16'),
(57, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: Teerepun (ชุดข้อสอบ: ข้อสอบครูชุดที่ 1)', '118.173.176.121', '2025-03-06 15:10:48'),
(58, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: Sky-01 krabiairport (ชุดข้อสอบ: ข้อสอบครูชุดที่ 1)', '118.173.176.121', '2025-03-06 15:10:56'),
(59, 1, 'create_exam_topic', 'สร้างหัวข้อใหม่: การพัฒนาระบบจัดการการสอบ RUTS TEST (ชุดข้อสอบ: ข้อสอบครูชุดที่ 1)', '118.173.176.121', '2025-03-06 15:11:24'),
(60, 1, 'create_question', 'สร้างคำถามใหม่: <p>1</p>', '118.173.176.121', '2025-03-06 16:15:24'),
(61, 1, 'create_question', 'สร้างคำถามใหม่: <p>1</p>', '118.173.176.121', '2025-03-06 16:15:48'),
(62, 1, 'create_question', 'สร้างคำถามใหม่: <p>1</p>', '118.173.176.121', '2025-03-06 16:16:52'),
(63, 1, 'create_question', 'สร้างคำถามใหม่: <p>1</p>', '118.173.176.121', '2025-03-06 16:21:54'),
(64, 1, 'delete_question', 'ลบคำถามรหัส: 1', '118.173.176.121', '2025-03-06 16:22:08'),
(65, 1, 'delete_question', 'ลบคำถามรหัส: 2', '118.173.176.121', '2025-03-06 16:22:12'),
(66, 1, 'delete_question', 'ลบคำถามรหัส: 3', '118.173.176.121', '2025-03-06 16:22:15'),
(67, 1, 'delete_question', 'ลบคำถามรหัส: 4', '118.173.176.121', '2025-03-06 16:22:18'),
(68, 1, 'create_question', 'สร้างคำถามใหม่: <p>2</p>', '118.173.176.121', '2025-03-06 16:44:37'),
(69, 1, 'create_question', 'สร้างคำถามใหม่: <p>45</p>', '118.173.176.121', '2025-03-06 16:45:01'),
(70, 1, 'create_question', 'สร้างคำถามใหม่: <p><strong style=\"color: rgb(230, 0, 0);\">ข้อใดต่าง </strong><strong>ดแด</strong></p>', '118.173.176.121', '2025-03-06 16:54:57'),
(71, 1, 'create_question', 'สร้างคำถามใหม่: <p>1</p>', '118.173.176.121', '2025-03-06 17:49:43'),
(72, 1, 'update_question', 'อัปเดตคำถามรหัส: 5', '118.173.176.121', '2025-03-06 18:06:42'),
(73, 1, 'delete_question', 'ลบคำถามรหัส: 8', '118.173.176.121', '2025-03-06 18:07:00'),
(74, 1, 'create_question', 'สร้างคำถามใหม่: <p>ww</p>', '118.173.176.121', '2025-03-06 18:11:01'),
(75, 1, 'create_question', 'สร้างคำถามใหม่: <p>fg</p>', '118.173.176.121', '2025-03-06 18:13:00'),
(76, 1, 'create_question', 'สร้างคำถามใหม่: <p><strong style=\"color: rgb(230, 0, 0);\">ข้อใดต่าง </strong><strong>ดแด</strong></p>', '118.173.176.121', '2025-03-06 18:13:36'),
(77, 1, 'delete_question', 'ลบคำถามรหัส: 5', '118.173.176.121', '2025-03-06 18:16:39'),
(78, 1, 'delete_question', 'ลบคำถามรหัส: 7', '118.173.176.121', '2025-03-06 18:16:46'),
(79, 1, 'update_exam_set', 'อัปเดตชุดข้อสอบ: ข้อสอบครูชุดที่ 2 เป็น ข้อสอบครูชุดที่ 2', '118.173.176.121', '2025-03-06 18:33:24'),
(80, 1, 'create_question', 'สร้างคำถามใหม่: <p>ทดสอบ 1-1=0?</p>', '118.173.176.121', '2025-03-06 18:35:57'),
(81, 1, 'delete_question', 'ลบคำถามรหัส: 12', '118.173.176.121', '2025-03-06 18:37:03'),
(82, 1, 'create_question', 'สร้างคำถามใหม่: <p>กกด</p>', '118.173.176.121', '2025-03-06 18:37:42'),
(83, 1, 'create_question', 'สร้างคำถามใหม่: <p>Yy</p>', '118.173.176.121', '2025-03-06 18:41:50'),
(84, 1, 'create_admin', 'สร้างผู้ดูแลระบบใหม่: test', '184.22.158.65', '2025-03-07 03:07:58'),
(85, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: test', '184.22.158.65', '2025-03-07 03:08:55'),
(86, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบ: test', '184.22.158.65', '2025-03-07 03:09:08'),
(87, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:24:24'),
(88, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:24:42'),
(89, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:24:54'),
(90, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:32:07'),
(91, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:40:51'),
(92, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:40:58'),
(93, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:41:02'),
(94, 1, 'update_admin', 'อัปเดตข้อมูลผู้ดูแลระบบและสถานะ: test', '184.22.158.65', '2025-03-07 03:52:41'),
(95, 1, 'delete_question', 'ลบคำถามรหัส: 6', '159.192.136.152', '2025-03-07 07:44:35'),
(96, 1, 'delete_question', 'ลบคำถามรหัส: 10', '159.192.136.152', '2025-03-07 07:44:41'),
(97, 1, 'delete_question', 'ลบคำถามรหัส: 14', '159.192.136.152', '2025-03-07 07:44:46'),
(98, 1, 'delete_question', 'ลบคำถามรหัส: 9', '159.192.136.152', '2025-03-07 07:44:50'),
(99, 1, 'delete_question', 'ลบคำถามรหัส: 11', '159.192.136.152', '2025-03-07 07:44:55'),
(100, 1, 'create_question', 'สร้างคำถามใหม่: <p>ทดสแบ</p>', '159.192.136.152', '2025-03-07 07:47:13'),
(101, 1, 'update_question', 'อัปเดตคำถามรหัส: 15', '159.192.136.152', '2025-03-07 07:47:24'),
(102, 1, 'update_question', 'อัปเดตคำถามรหัส: 15', '159.192.136.152', '2025-03-07 07:47:56'),
(103, 1, 'update_question', 'อัปเดตคำถามรหัส: 15', '159.192.136.152', '2025-03-07 07:49:09'),
(104, 1, 'update_question', 'อัปเดตคำถามรหัส: 15', '159.192.136.152', '2025-03-07 07:49:20'),
(105, 1, 'create_question', 'สร้างคำถามใหม่: <p>131</p>', '159.192.136.152', '2025-03-07 07:49:44'),
(106, 1, 'update_question', 'อัปเดตคำถามรหัส: 16', '159.192.136.152', '2025-03-07 07:49:51'),
(107, 1, 'update_question', 'อัปเดตคำถามรหัส: 16', '159.192.136.152', '2025-03-07 07:50:02'),
(108, 1, 'update_question', 'อัปเดตคำถามรหัส: 16', '159.192.136.152', '2025-03-07 08:37:31'),
(109, 1, 'update_question', 'อัปเดตคำถามรหัส: 16', '159.192.136.152', '2025-03-07 08:39:42');

-- --------------------------------------------------------

--
-- Table structure for table `choice`
--

CREATE TABLE `choice` (
  `choice_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=ไม่ถูกต้อง, 1=ถูกต้อง',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `choice`
--

INSERT INTO `choice` (`choice_id`, `question_id`, `content`, `image`, `is_correct`, `created_at`, `updated_at`) VALUES
(35, 15, '1', NULL, 0, '2025-03-07 15:47:13', '2025-03-07 15:49:20'),
(36, 15, '2', NULL, 0, '2025-03-07 15:47:13', '2025-03-07 15:49:20'),
(37, 16, '121', NULL, 0, '2025-03-07 15:49:44', '2025-03-07 16:39:42'),
(38, 16, '1221', NULL, 0, '2025-03-07 15:49:44', '2025-03-07 16:39:42');

-- --------------------------------------------------------

--
-- Table structure for table `exam_result`
--

CREATE TABLE `exam_result` (
  `result_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `total_score` float NOT NULL DEFAULT '0',
  `max_score` float NOT NULL DEFAULT '0',
  `completion_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=ยังไม่ทำ, 1=กำลังทำ, 2=ทำเสร็จแล้ว',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_result_detail`
--

CREATE TABLE `exam_result_detail` (
  `id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_choice_id` int(11) DEFAULT NULL,
  `is_correct` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=ไม่ถูกต้อง, 1=ถูกต้อง',
  `score` float NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_round`
--

CREATE TABLE `exam_round` (
  `round_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `passing_percentage` float NOT NULL DEFAULT '50',
  `result_release_date` datetime NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ยกเลิก, 1=รอสอบ, 2=กำลังสอบ, 3=สอบเสร็จแล้ว',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_round_set`
--

CREATE TABLE `exam_round_set` (
  `id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `exam_set_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_set`
--

CREATE TABLE `exam_set` (
  `exam_set_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ไม่ใช้งาน, 1=ใช้งาน',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_set`
--

INSERT INTO `exam_set` (`exam_set_id`, `name`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ข้อสอบครูชุดที่ 1', 'ข้อสอบวิชาชีพครูระดับนักศึกษา', 1, 1, '2025-03-06 00:42:01', '2025-03-06 23:01:23'),
(2, 'ข้อสอบครูชุดที่ 2', 'ข้อสอบวิชาชีพครูระดับนักศึกษา2', 1, 1, '2025-03-06 00:49:19', '2025-03-07 02:33:24'),
(3, 'ข้อสอบชุด 3', 'ขอสอบ', 1, 1, '2025-03-06 22:52:31', '2025-03-06 22:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `exam_topic`
--

CREATE TABLE `exam_topic` (
  `topic_id` int(11) NOT NULL,
  `exam_set_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ไม่ใช้งาน, 1=ใช้งาน',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_topic`
--

INSERT INTO `exam_topic` (`topic_id`, `exam_set_id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'ภาษาไทยเพื่ออาชีพครู', 'ภาษาไทยเพื่ออาชีพครูเบื้องต้น', 1, '2025-03-06 00:46:30', '2025-03-06 00:46:30'),
(2, 1, 'จิตวิทยาเพื่ออาชีพครู', 'จิตวิทยาเบื้องต้น', 1, '2025-03-06 00:47:02', '2025-03-06 00:47:02'),
(4, 2, 'การพัฒนาระบบจัดการการสอบ RUTS TEST', '', 1, '2025-03-06 23:01:44', '2025-03-06 23:01:44'),
(5, 1, 'ภาษาไทยเพื่ออาชีพครูเบื้องต้น 2', '22', 1, '2025-03-06 23:02:16', '2025-03-06 23:02:16'),
(6, 1, 'Teerepun', '', 1, '2025-03-06 23:10:48', '2025-03-06 23:10:48'),
(7, 1, 'Sky-01 krabiairport', '', 1, '2025-03-06 23:10:56', '2025-03-06 23:10:56'),
(8, 1, 'การพัฒนาระบบจัดการการสอบ RUTS TEST', '', 1, '2025-03-06 23:11:24', '2025-03-06 23:11:24');

-- --------------------------------------------------------

--
-- Table structure for table `knowledge`
--

CREATE TABLE `knowledge` (
  `knowledge_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=PDF, 2=Youtube',
  `content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ซ่อน, 1=แสดง',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `news_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=รูปภาพ, 2=ไฟล์ PDF',
  `publish_date` datetime NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ซ่อน, 1=แสดง',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `question_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` float NOT NULL DEFAULT '1',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ไม่ใช้งาน, 1=ใช้งาน',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`question_id`, `topic_id`, `content`, `image`, `score`, `status`, `created_at`, `updated_at`) VALUES
(15, 8, '<p>ทดสอบ</p>', '', 1, 1, '2025-03-07 15:47:13', '2025-03-07 15:49:20'),
(16, 6, '<p>131</p>', '', 1, 1, '2025-03-07 15:49:44', '2025-03-07 16:39:42');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `registration_id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `register_start_date` datetime NOT NULL,
  `register_end_date` datetime NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ปิด, 1=เปิด',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semester`
--

CREATE TABLE `semester` (
  `semester_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `term` tinyint(4) NOT NULL COMMENT '1=เทอม 1, 2=เทอม 2, 3=ภาคฤดูร้อน',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ไม่ใช้งาน, 1=ใช้งาน',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semester`
--

INSERT INTO `semester` (`semester_id`, `year`, `term`, `status`, `created_at`, `updated_at`) VALUES
(3, 2567, 1, 1, '2025-03-05 21:38:17', '2025-03-05 21:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `student_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ไม่ใช้งาน, 1=ใช้งาน',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_code`, `username`, `password`, `firstname`, `lastname`, `email`, `phone`, `semester_id`, `status`, `created_at`, `updated_at`) VALUES
(1, '6201234567', 'student1', '$2y$10$LAGmj1R0wn.dzKsa5.9dNeIlN7u/W8E2mdpKXklLC5al0MxvmOnci', 'สมชาย1', 'ใจดี', 'somchai@example.com', '0891234567', 3, 1, '2025-03-06 00:01:59', '2025-03-06 00:03:53'),
(4, '620123691', '620123691', '$2y$10$JyMhkC54VvO2bYLwybNn0.L0/uQbBzDumZGe7DI/rx56yDGuPCAke', 'สมชาย', 'ใจดี', 'somchai@example.com', '891234567', 3, 1, '2025-03-06 00:04:47', '2025-03-06 00:05:26'),
(5, '6201234570', '6201234570', '$2y$10$soh.K0mDK8VMkMFJs.4OIeO9OF1wQeGInPjfTStTHGg2vd/AUSnfe', 'สมหญิง', 'รักเรียน', 'somying@example.com', '891234568', 3, 1, '2025-03-06 00:04:47', '2025-03-06 00:04:47');

-- --------------------------------------------------------

--
-- Table structure for table `student_registration`
--

CREATE TABLE `student_registration` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `registered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=ยกเลิก, 1=ลงทะเบียนแล้ว'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username_UNIQUE` (`username`);

--
-- Indexes for table `admin_log`
--
ALTER TABLE `admin_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_admin_log_admin_idx` (`admin_id`);

--
-- Indexes for table `choice`
--
ALTER TABLE `choice`
  ADD PRIMARY KEY (`choice_id`),
  ADD KEY `fk_choice_question_idx` (`question_id`);

--
-- Indexes for table `exam_result`
--
ALTER TABLE `exam_result`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `fk_exam_result_student_idx` (`student_id`),
  ADD KEY `fk_exam_result_exam_round_idx` (`round_id`);

--
-- Indexes for table `exam_result_detail`
--
ALTER TABLE `exam_result_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_result_detail_exam_result_idx` (`result_id`),
  ADD KEY `fk_exam_result_detail_question_idx` (`question_id`),
  ADD KEY `fk_exam_result_detail_choice_idx` (`selected_choice_id`);

--
-- Indexes for table `exam_round`
--
ALTER TABLE `exam_round`
  ADD PRIMARY KEY (`round_id`),
  ADD KEY `fk_exam_round_admin_idx` (`created_by`);

--
-- Indexes for table `exam_round_set`
--
ALTER TABLE `exam_round_set`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_round_set_round_idx` (`round_id`),
  ADD KEY `fk_exam_round_set_exam_set_idx` (`exam_set_id`);

--
-- Indexes for table `exam_set`
--
ALTER TABLE `exam_set`
  ADD PRIMARY KEY (`exam_set_id`),
  ADD KEY `fk_exam_set_admin_idx` (`created_by`);

--
-- Indexes for table `exam_topic`
--
ALTER TABLE `exam_topic`
  ADD PRIMARY KEY (`topic_id`),
  ADD KEY `fk_exam_topic_exam_set_idx` (`exam_set_id`);

--
-- Indexes for table `knowledge`
--
ALTER TABLE `knowledge`
  ADD PRIMARY KEY (`knowledge_id`),
  ADD KEY `fk_knowledge_admin_idx` (`created_by`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `fk_news_admin_idx` (`created_by`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `fk_question_exam_topic_idx` (`topic_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `fk_registration_exam_round_idx` (`round_id`),
  ADD KEY `fk_registration_admin_idx` (`created_by`);

--
-- Indexes for table `semester`
--
ALTER TABLE `semester`
  ADD PRIMARY KEY (`semester_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_code_UNIQUE` (`student_code`),
  ADD UNIQUE KEY `username_UNIQUE` (`username`),
  ADD KEY `fk_student_semester_idx` (`semester_id`);

--
-- Indexes for table `student_registration`
--
ALTER TABLE `student_registration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student_registration_registration_idx` (`registration_id`),
  ADD KEY `fk_student_registration_student_idx` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_log`
--
ALTER TABLE `admin_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `choice`
--
ALTER TABLE `choice`
  MODIFY `choice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `exam_result`
--
ALTER TABLE `exam_result`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_result_detail`
--
ALTER TABLE `exam_result_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_round`
--
ALTER TABLE `exam_round`
  MODIFY `round_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_round_set`
--
ALTER TABLE `exam_round_set`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_set`
--
ALTER TABLE `exam_set`
  MODIFY `exam_set_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exam_topic`
--
ALTER TABLE `exam_topic`
  MODIFY `topic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `knowledge`
--
ALTER TABLE `knowledge`
  MODIFY `knowledge_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semester`
--
ALTER TABLE `semester`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_registration`
--
ALTER TABLE `student_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_log`
--
ALTER TABLE `admin_log`
  ADD CONSTRAINT `fk_admin_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `choice`
--
ALTER TABLE `choice`
  ADD CONSTRAINT `fk_choice_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_result`
--
ALTER TABLE `exam_result`
  ADD CONSTRAINT `fk_exam_result_exam_round` FOREIGN KEY (`round_id`) REFERENCES `exam_round` (`round_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_result_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_result_detail`
--
ALTER TABLE `exam_result_detail`
  ADD CONSTRAINT `fk_exam_result_detail_choice` FOREIGN KEY (`selected_choice_id`) REFERENCES `choice` (`choice_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_result_detail_exam_result` FOREIGN KEY (`result_id`) REFERENCES `exam_result` (`result_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_result_detail_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_round`
--
ALTER TABLE `exam_round`
  ADD CONSTRAINT `fk_exam_round_admin` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `exam_round_set`
--
ALTER TABLE `exam_round_set`
  ADD CONSTRAINT `fk_exam_round_set_exam_set` FOREIGN KEY (`exam_set_id`) REFERENCES `exam_set` (`exam_set_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_round_set_round` FOREIGN KEY (`round_id`) REFERENCES `exam_round` (`round_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_set`
--
ALTER TABLE `exam_set`
  ADD CONSTRAINT `fk_exam_set_admin` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `exam_topic`
--
ALTER TABLE `exam_topic`
  ADD CONSTRAINT `fk_exam_topic_exam_set` FOREIGN KEY (`exam_set_id`) REFERENCES `exam_set` (`exam_set_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `knowledge`
--
ALTER TABLE `knowledge`
  ADD CONSTRAINT `fk_knowledge_admin` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `fk_news_admin` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `fk_question_exam_topic` FOREIGN KEY (`topic_id`) REFERENCES `exam_topic` (`topic_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `fk_registration_admin` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registration_exam_round` FOREIGN KEY (`round_id`) REFERENCES `exam_round` (`round_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `fk_student_semester` FOREIGN KEY (`semester_id`) REFERENCES `semester` (`semester_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_registration`
--
ALTER TABLE `student_registration`
  ADD CONSTRAINT `fk_student_registration_registration` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`registration_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_registration_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
