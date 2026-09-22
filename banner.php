
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getFirstName($name) {
    $name = trim((string)$name);
    if ($name === '') return '';
    $parts = preg_split('/\s+/', $name);
    return $parts[0] ?? '';
}

function renderBanner($activePage = '') {
    $role = $_SESSION["role"] ?? null;
    $name = '';

    if ($role === 'beneficiary') {
        $name = getFirstName($_SESSION["name"] ?? '');
    } elseif ($role === 'reserve') {
        $name = getFirstName($_SESSION["camp_name"] ?? '');
    } elseif ($role === 'admin') {
        $name = getFirstName($_SESSION["admin_name"] ?? '');
    }
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap');

*{
  box-sizing: border-box;
}

html {
  overflow-y: scroll;
  scrollbar-gutter: stable;
}

.navbar{
  position: sticky;
  top: 0;
  z-index: 1000;
  height: 88px;
  background: rgba(6, 6, 68, 0.92);
  border-bottom: 1px solid rgba(255,255,255,0.12);
  backdrop-filter: blur(10px);
  font-family: Tajawal, system-ui, sans-serif;
}

.nav-inner{
  width: 100%;
  max-width: 1680px;
  height: 88px;
  margin: 0 auto;
  padding: 0 42px 0 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  direction: ltr;
  gap: 35px;
  font-family: Tajawal, system-ui, sans-serif;
}

.brand{
  display: flex;
  align-items: center;
  justify-content: flex-start;
  flex: 0 0 auto;
  margin-left: 0;
}

.brand a{
  display: flex;
  align-items: center;
  text-decoration: none;
}

.brand-logo{
  height: 62px;
  width: auto;
  object-fit: contain;
  display: block;
}

.nav-links{
  list-style: none;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 18px;
  margin: 0;
  padding: 0;
  direction: rtl;
  flex: 0 1 auto;
  white-space: nowrap;
  font-family: Tajawal, system-ui, sans-serif;
  transform: translateX(18px);
}

.nav-links li{
  list-style: none;
  margin: 0;
  padding: 0;
}

.nav-link{
  color: #eef2ff;
  text-decoration: none;
  padding: 8px 10px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  line-height: 1.2;
  display: inline-block;
  white-space: nowrap;
  transition: .2s;
  font-family: Tajawal, system-ui, sans-serif;
}

.nav-link:hover{
  background: rgba(255,255,255,0.08);
}

.hello{
  color: #c7d2fe;
  font-weight: 700;
  font-size: 14px;
  line-height: 1.2;
  white-space: nowrap;
  font-family: Tajawal, system-ui, sans-serif;
  display: inline-block;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 0 0 auto;
}

.dropdown{
  position: relative;
}

.dropdown-menu{
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  list-style: none;
  padding: 8px 0;
  margin: 0;
  background: #0d1b6d;
  border-radius: 12px;
  min-width: 170px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.35);
  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: .3s ease;
  z-index: 1001;
  font-family: Tajawal, system-ui, sans-serif;
}

.dropdown-menu li a{
  display: block;
  padding: 10px 15px;
  color: white;
  text-decoration: none;
  font-size: 14px;
  text-align: right;
  white-space: nowrap;
  font-family: Tajawal, system-ui, sans-serif;
}

.dropdown-menu li a:hover{
  background: rgba(255,255,255,0.10);
}

.dropdown:hover .dropdown-menu{
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

@media (max-width: 1100px){
  .nav-inner{
    padding: 0 22px;
    gap: 18px;
  }

  .nav-links{
    gap: 10px;
    transform: translateX(0);
  }

  .nav-link{
    font-size: 13px;
    padding: 8px 7px;
  }

  .brand-logo{
    height: 54px;
  }
}

@media (max-width: 900px){
  .navbar{
    height: auto;
  }

  .nav-inner{
    height: auto;
    min-height: 88px;
    padding: 12px 18px;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
    direction: rtl;
  }

  .nav-links{
    flex-wrap: wrap;
    justify-content: center;
    white-space: normal;
  }
}
</style>

<nav class="navbar">
  <div class="nav-inner">
    <div class="brand">
      <a href="home.php">
        <img src="logoo1.png" alt="شعار أفق" class="brand-logo">
      </a>
    </div>

    <ul class="nav-links">
      <?php if ($role === 'beneficiary'): ?>
        <li class="hello">👋مرحبا <?php echo htmlspecialchars($name); ?></li>
        <li><a class="nav-link" href="home.php">الرئيسية</a></li>
        <li><a class="nav-link" href="your_page.php">صفحتك</a></li>
        <li><a class="nav-link" href="profile.php">الملف الشخصي</a></li>
        <li><a class="nav-link" href="booking.php">حجوزاتي</a></li>
        <li><a class="nav-link" href="beneficiary_camps.php">المحميات الفلكية</a></li>
        <li><a class="nav-link" href="inquiry.php">الاستفسارات</a></li>
        <li><a class="nav-link" href="location.php">المحميات الموثقة</a></li>
        <li><a class="nav-link" href="astronomy_news.php">الاخبار الفلكية</a></li>
        <li><a class="nav-link" href="about.php">من نحن</a></li>
        <li><a class="nav-link" href="logout.php">تسجيل الخروج</a></li>

      <?php elseif ($role === 'reserve'): ?>
        <li><a class="nav-link" href="camp_home.php">الرئيسية</a></li>
        <li><a class="nav-link" href="camp_profile.php">الملف الشخصي</a></li>
        <li><a class="nav-link" href="camp_services.php">ادارة الخدمات</a></li>
        <li><a class="nav-link" href="camp_booking.php">الاطلاع على الحجوزات</a></li>
        <li><a class="nav-link" href="camp_inquiry.php">الرد على الاستفسارات</a></li>
        <li><a class="nav-link" href="astronomy_news.php">الاخبار الفلكية</a></li>
        <li><a class="nav-link" href="logout.php">تسجيل الخروج</a></li>

      <?php elseif ($role === 'admin'): ?>
        <li><a class="nav-link" href="admin.php">طلبات التوثيق والاعتماد</a></li>
        <li><a class="nav-link" href="admin_content.php">ادارة محتوى الاخبار الفلكية</a></li>
        <li><a class="nav-link" href="astronomy_news.php">الاخبار الفلكية</a></li>
        <li><a class="nav-link" href="logout.php">تسجيل الخروج</a></li>

      <?php else: ?>
        <li><a class="nav-link" href="home.php">الرئيسية</a></li>

        <li class="dropdown">
          <a class="nav-link" href="#">إنشاء حساب ▾</a>
          <ul class="dropdown-menu">
            <li><a href="register.php">كمستفيد</a></li>
            <li><a href="camp_register.php">كمحمية</a></li>
          </ul>
        </li>

        <li><a class="nav-link" href="login.php">تسجيل الدخول</a></li>
        <li><a class="nav-link" href="beneficiary_camps.php">المحميات الفلكية</a></li>
		        <li><a class="nav-link" href="location.php">المحميات الموثقة</a></li>
        <li><a class="nav-link" href="astronomy_news.php">الاخبار الفلكية</a></li>
        <li><a class="nav-link" href="about.php">من نحن</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>
<?php
}
?>