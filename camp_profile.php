
<?php
session_start();
require_once 'banner.php';

/* السماح للمحمية فقط */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "reserve" || !isset($_SESSION["astronmy_camp_id"])) {
  header("Location: login.php");
  exit;
}

/* اتصال قاعدة البيانات */
$servername = "localhost";
$username   = "root";
$dbpass     = "";
$dbname     = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
  die("فشل الاتصال: " . $conn->connect_error);
}

$campId = (int)$_SESSION["astronmy_camp_id"];

function redirectWithMsg($type, $msg){
  header("Location: camp_profile.php?type=" . urlencode($type) . "&msg=" . urlencode($msg));
  exit;
}

/* =========================
   معالجة POST (تعديل/باسورد/حذف)
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $postedId = (int)($_POST["astronmy_camp_id"] ?? 0);

  // حماية: أي تعديل لازم يكون على نفس حسابه
  if ($postedId !== $campId) {
    redirectWithMsg("error", "طلب غير صالح.");
  }

  /* 1) حفظ التعديلات الأساسية (بدون رقم الترخيص) */
  if (isset($_POST["save_basic"])) {

    $astronmy_camp_name    = trim($_POST["astronmy_camp_name"] ?? "");
    $location              = trim($_POST["location"] ?? "");

    if ($astronmy_camp_name === "" || $location === "") {
      redirectWithMsg("error", "فضلاً عبّي كل الحقول المطلوبة.");
    }

    $stmt = $conn->prepare("UPDATE astronomical_camp
                            SET astronmy_camp_name=?, location=?
                            WHERE astronmy_camp_id=?");
    if (!$stmt) {
      redirectWithMsg("error", "خطأ في تجهيز الاستعلام: " . $conn->error);
    }

    $stmt->bind_param("ssi", $astronmy_camp_name, $location, $campId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
      // تحديث الاسم في الجلسة
      $_SESSION["camp_name"] = $astronmy_camp_name;
      $_SESSION["astronmy_camp_name"] = $astronmy_camp_name;
      redirectWithMsg("success", "تم حفظ التعديلات ✅");
    } else {
      redirectWithMsg("error", "تعذر حفظ التعديلات.");
    }
  }

  /* 2) تغيير كلمة المرور */
  if (isset($_POST["change_password"])) {

    $current = trim($_POST["current_password"] ?? "");
    $new     = trim($_POST["new_password"] ?? "");

    if ($new === "") {
      redirectWithMsg("error", "اكتبي كلمة المرور الجديدة.");
    }
	if ($current === $new) {
      redirectWithMsg("error", "كلمة المرور الجديدة لا يمكن أن تكون مطابقة للحالية.");
    }

    $stmt = $conn->prepare("SELECT password FROM astronomical_camp WHERE astronmy_camp_id=? LIMIT 1");
    if (!$stmt) {
      redirectWithMsg("error", "خطأ في الاستعلام: " . $conn->error);
    }

    $stmt->bind_param("i", $campId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
      redirectWithMsg("error", "تعذر العثور على الحساب.");
    }

    if ($current != (string)$row["password"]) {
      redirectWithMsg("error", "كلمة المرور الحالية غير صحيحة.");
    }

    $up = $conn->prepare("UPDATE astronomical_camp SET password=? WHERE astronmy_camp_id=?");
    if (!$up) {
      redirectWithMsg("error", "خطأ في تجهيز الاستعلام: " . $conn->error);
    }

    $up->bind_param("si", $new, $campId);
    $ok = $up->execute();
    $up->close();

    redirectWithMsg($ok ? "success" : "error", $ok ? "تم تغيير كلمة المرور ✅" : "تعذر تغيير كلمة المرور.");
  }

  /* 3) حذف الحساب */
  if (isset($_POST["delete_account"])) {

    /*
      منع حذف الحساب إذا كانت هناك حجوزات مستقبلية مؤكدة
      مرتبطة بخدمات هذه المحمية
    */
    $check = $conn->prepare("
      SELECT COUNT(*) AS total_future_bookings
      FROM booking b
      INNER JOIN services s ON b.service_id = s.service_id
      WHERE s.astronmy_camp_id = ?
        AND b.booking_status = 'paid'
        AND b.Service_date IS NOT NULL
        AND b.Service_date >= CURDATE()
    ");

    if (!$check) {
      redirectWithMsg("error", "خطأ في تجهيز التحقق من الحجوزات: " . $conn->error);
    }

    $check->bind_param("i", $campId);
    $check->execute();
    $checkResult = $check->get_result()->fetch_assoc();
    $check->close();

    $futureBookingsCount = (int)($checkResult["total_future_bookings"] ?? 0);

    if ($futureBookingsCount > 0) {
      redirectWithMsg(
        "error",
        "لا يمكن حذف الحساب لأن هناك حجوزات مستقبلية مرتبطة بخدمات المحمية. يجب إلغاء جميع الحجوزات أولاً من صفحة إدارة الخدمات."
      );
    }

    $stmt = $conn->prepare("DELETE FROM astronomical_camp WHERE astronmy_camp_id=?");
    if (!$stmt) {
      redirectWithMsg("error", "خطأ في تجهيز الاستعلام: " . $conn->error);
    }

    $stmt->bind_param("i", $campId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
      session_unset();
      session_destroy();
      header("Location: login.php?msg=" . urlencode("تم حذف حساب المحمية بنجاح."));
      exit;
    } else {
      redirectWithMsg("error", "تعذر حذف الحساب.");
    }
  }

  redirectWithMsg("error", "لا يوجد إجراء معروف.");
}

/* =========================
   جلب بيانات المحمية للعرض
   ========================= */
$stmt = $conn->prepare("SELECT astronmy_camp_id, license_number, astronmy_camp_name, location, Accreditation_status
                        FROM astronomical_camp
                        WHERE astronmy_camp_id=? LIMIT 1");
$stmt->bind_param("i", $campId);
$stmt->execute();
$camp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$camp) {
  session_destroy();
  header("Location: login.php");
  exit;
}

$msg  = $_GET["msg"] ?? "";
$type = $_GET["type"] ?? "success";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>ملف المحمية</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --stroke: rgba(255,255,255,.14);
  --muted: rgba(255,255,255,.75);
  --blue: #60a5fa;
  --purple:#5b21b6;
  --errBg: rgba(239,68,68,.13);
  --errBr: rgba(239,68,68,.28);
  --okBg: rgba(34,197,94,.14);
  --okBr: rgba(34,197,94,.28);
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:"Tajawal",sans-serif;
  color:#fff;
  background:
    linear-gradient(rgba(0,0,0,.60), rgba(0,0,0,.65)),
    url("jamal.jpeg")
    no-repeat center center/cover fixed;
}

.container{
  width:min(980px, 92vw);
  margin:22px auto 60px;
  display:grid;
  grid-template-columns: 1.2fr .8fr;
  gap:14px;
}
.card{
  background: rgba(10, 12, 26, .25);
  border:1px solid var(--stroke);
  border-radius:22px;
  padding:18px;
  box-shadow: 0 22px 70px rgba(0,0,0,.55);
  backdrop-filter: blur(4px);
}

h1{ margin:0 0 6px; font-size:24px; }
.sub{ margin:0 0 12px; color:var(--muted); font-size:13px; }

.alert{
  margin:10px 0 14px;
  padding:10px 12px;
  border-radius:14px;
  font-size:13px;
  border:1px solid rgba(255,255,255,.12);
  line-height: 1.9;
}
.alert.error{ background: var(--errBg); border-color: var(--errBr); }
.alert.success{ background: var(--okBg); border-color: var(--okBr); }

.kv{
  display:grid;
  grid-template-columns: 160px 1fr;
  gap:10px;
  padding:10px 0;
  border-bottom:1px solid rgba(255,255,255,.08);
}
.kv:last-child{ border-bottom:none; }

.key{
  color: rgba(255,255,255,.75);
  font-weight:700;
  font-size:16px;
}

.val{
  color:#fff;
  font-size:16px;
  line-height:1.9;
}

.grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:10px;
}
.full{ grid-column: 1 / -1; }

.label{
  font-size:12px;
  color: rgba(255,255,255,.75);
  margin:8px 2px 6px;
  font-weight:700;
}
.field input,
.field select,
.field textarea{
  width:100%;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.14);
  background: rgba(7,10,22,.55);
  padding:10px 12px;
  font-size:13px;
  color:#fff;
  outline:none;
  font-family:"Tajawal",sans-serif;
}

.field input,
.field select{
  height:40px;
}

.field textarea{
  min-height:120px;
  resize:vertical;
  line-height:1.8;
}

.field input:focus,
.field select:focus,
.field textarea:focus{
  border-color: rgba(96,165,250,.45);
  box-shadow: 0 0 0 4px rgba(96,165,250,.12);
}

.btn{
  width:100%;
  padding:11px 14px;
  border:none;
  border-radius:14px;
  cursor:pointer;
  color:#fff;
  font-size:14px;
  border:1px solid rgba(255,255,255,.14);
  background: linear-gradient(135deg, rgba(96,165,250,.35), rgba(91,33,182,.25));
}
.btn:hover{ filter: brightness(1.06); }

.btn.danger{
  background: rgba(239,68,68,.18);
  border-color: rgba(239,68,68,.30);
}
.btn.danger:hover{ filter: brightness(1.08); }

.small{
  font-size:12px;
  color: rgba(255,255,255,.72);
  margin-top:8px;
  line-height: 1.8;
}

hr.sep{
  border:none;
  border-top:1px solid rgba(255,255,255,.10);
  margin:14px 0;
}

@media (max-width:900px){
  .container{ grid-template-columns: 1fr; }
}
.gold-title{
  background: linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}
.readonly{
  cursor: not-allowed;
  opacity: .7;
  background: rgba(255,255,255,.08);
}
</style>
</head>

<body>
<?php renderBanner('camp_profile'); ?>

<main class="container">

  <section class="card">
    <h1 class="gold-title">الملف الشخصي للمحمية</h1>
    <p class="sub">عرض بيانات حساب المحمية</p>

    <?php if($msg): ?>
      <div class="alert <?php echo ($type==='success') ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <div class="kv"><div class="key">اسم المحمية</div><div class="val"><?php echo htmlspecialchars($camp["astronmy_camp_name"]); ?></div></div>
    <div class="kv"><div class="key">الموقع</div><div class="val"><?php echo htmlspecialchars($camp["location"]); ?></div></div>
    <div class="kv"><div class="key">رقم الترخيص</div><div class="val"><?php echo htmlspecialchars($camp["license_number"]); ?> (غير قابل للتعديل)</div></div>
    <div class="kv"><div class="key">حالة الاعتماد</div><div class="val"><?php echo htmlspecialchars($camp["Accreditation_status"]); ?></div></div>
  </section>

  <aside class="card">
    <h1 class="gold-title">إدارة الحساب</h1>
    <p class="sub">تعديل بيانات المحمية أو حذف الحساب</p>

    <!-- تعديل البيانات -->
	<form method="POST" onsubmit="return confirm('هل أنت متأكد من حفظ التعديلات؟');">
      <input type="hidden" name="astronmy_camp_id" value="<?php echo (int)$camp["astronmy_camp_id"]; ?>">

      <div class="grid">
        <div class="full field">
          <div class="label">رقم الترخيص (غير قابل للتعديل)</div>
          <input class="readonly" type="text" value="<?php echo htmlspecialchars($camp["license_number"]); ?>" readonly>
        </div>

        <div class="full field">
          <div class="label">اسم المحمية</div>
          <input type="text" name="astronmy_camp_name" required value="<?php echo htmlspecialchars($camp["astronmy_camp_name"]); ?>">
        </div>

        <div class="full field">
          <div class="label">الموقع</div>
          <input type="text" name="location" required value="<?php echo htmlspecialchars($camp["location"]); ?>">
        </div>

      </div>

      <div style="margin-top:12px">
        <button class="btn" type="submit" name="save_basic">حفظ التعديلات</button>
      </div>
    </form>

    <hr class="sep">

    <!-- تغيير كلمة المرور -->
    <form method="POST" autocomplete="off">
      <input type="hidden" name="astronmy_camp_id" value="<?php echo (int)$camp["astronmy_camp_id"]; ?>">

      <div class="label">كلمة المرور الحالية</div>
      <div class="field"><input type="password" name="current_password" required></div>

      <div class="label">كلمة المرور الجديدة</div>
      <div class="field"><input type="password" name="new_password" required></div>

      <div style="margin-top:10px">
        <button class="btn" type="submit" name="change_password">تغيير كلمة المرور</button>
      </div>
    </form>

    <hr class="sep">

    <!-- حذف الحساب -->
    <form method="POST" onsubmit="return confirm('لا يمكن حذف الحساب إذا كانت هناك حجوزات مستقبلية. هل أنت متأكد أنك تريد المتابعة؟');">
      <input type="hidden" name="astronmy_camp_id" value="<?php echo (int)$camp["astronmy_camp_id"]; ?>">
      <button class="btn danger" type="submit" name="delete_account">حذف الحساب نهائيًا</button>
      <div class="small">لن يتم حذف الحساب إذا كانت هناك حجوزات مستقبلية مرتبطة بخدمات المحمية. يجب إلغاء الحجوزات أولاً من صفحة إدارة الخدمات.</div>
    </form>

  </aside>

</main>

</body>
</html>
<?php $conn->close(); ?>