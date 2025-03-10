<!-- 1 -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
  <div class="container-xxl d-flex h-100">
    <ul class="menu-inner">
      <!-- Dashboard -->
      <li class="menu-item">
        <a href="dashboard.php" class="menu-link">
          <i class="menu-icon tf-icons ri-home-smile-line"></i>
          <div data-i18n="หน้าหลัก">หน้าหลัก</div>
        </a>
      </li>

      <!-- จัดการข้อมูลพื้นฐาน (Dropdown) -->
      <li class="menu-item">
        <a href="javascript:void(0)" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons ri-database-2-line"></i>
          <div data-i18n="จัดการข้อมูลพื้นฐาน">จัดการข้อมูลพื้นฐาน</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="semester-management.php" class="menu-link">
              <i class="menu-icon tf-icons ri-calendar-line"></i>
              <div data-i18n="จัดการเทอม">จัดการเทอม</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="student-management.php" class="menu-link">
              <i class="menu-icon tf-icons ri-user-2-line"></i>
              <div data-i18n="จัดการข้อมูลนักเรียน">จัดการข้อมูลนักเรียน</div>
            </a>
          </li>
          <!-- เพิ่มเมนูย่อยอื่นๆ ในอนาคต -->
        </ul>
      </li>

      <!-- จัดการการสอบ (เพิ่มใหม่) -->
      <li class="menu-item">
        <a href="javascript:void(0)" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons ri-file-list-3-line"></i>
          <div data-i18n="จัดการการสอบ">จัดการการสอบ</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="exam-set-management.php" class="menu-link">
              <i class="menu-icon tf-icons ri-booklet-line"></i>
              <div data-i18n="จัดการชุดข้อสอบ">จัดการชุดข้อสอบ</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="exam-topic-management.php" class="menu-link">
              <i class="menu-icon tf-icons ri-list-check-2"></i>
              <div data-i18n="จัดการหัวข้อการสอบ">จัดการหัวข้อการสอบ</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="question-management.php" class="menu-link">
              <i class="menu-icon tf-icons ri-question-line"></i>
              <div data-i18n="จัดการคำถาม">จัดการคำถาม</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="exam-round-management.php" class="menu-link">
              <i class="menu-icon tf-icons ri-time-line"></i>
              <div data-i18n="จัดการรอบการสอบ">จัดการรอบการสอบ</div>
            </a>
          </li>
        </ul>
      </li>

      <!-- จัดการผู้ดูแลระบบ -->
      <li class="menu-item">
        <a href="admin-management.php" class="menu-link">
          <i class="menu-icon tf-icons ri-user-settings-line"></i>
          <div data-i18n="จัดการผู้ดูแลระบบ">จัดการผู้ดูแลระบบ</div>
        </a>
      </li>
    </ul>
  </div>
</aside>