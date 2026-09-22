
<?php
session_start();
require_once 'banner.php';
/* السماح للمستفيد فقط */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "beneficiary" || !isset($_SESSION["user_id"])) {
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

$userId = (int)$_SESSION["user_id"];

function redirectWithMsg($type, $msg){
  header("Location: profile.php?type=" . $type . "&msg=" . urlencode($msg));
  exit;
}

/* =========================
   معالجة POST (تعديل/باسورد/حذف)
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // حماية: ممنوع أي id غير نفسه
  $postedId = (int)($_POST["user_id"] ?? 0);
  if ($postedId !== $userId) {
    redirectWithMsg("error", "طلب غير صالح.");
  }

  /* 1) حفظ التعديلات الأساسية */
  if (isset($_POST["save_basic"])) {

    $Fname        = trim($_POST["Fname"] ?? "");
    $Lname        = trim($_POST["Lname"] ?? "");
    $city         = trim($_POST["city"] ?? "");
    $Neighborhood = trim($_POST["Neighborhood"] ?? "");
    $street       = trim($_POST["street"] ?? "");
    $email        = trim($_POST["email"] ?? "");
    $birth_date   = trim($_POST["birth_date"] ?? "");
    $gender       = $_POST["gender"] ?? "";

    // شرط العمر 18+ (نفس منطق التسجيل)
    $ageOK = false;
    if ($birth_date !== "") {
      try {
        $dob = new DateTime($birth_date);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        $ageOK = ($age >= 18);
      } catch (Exception $e) {
        $ageOK = false;
      }
    }
    if (!$ageOK) {
      redirectWithMsg("error", "عذراً: يجب ان  يكون عمرك 18 سنة أو أكثر.");
    }

    // منع تكرار الإيميل (مع استثناء نفس الحساب)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      redirectWithMsg("error", "البريد الإلكتروني غير صحيح.");
    }

    $check = $conn->prepare("SELECT 1 FROM user WHERE email = ? AND user_id <> ? LIMIT 1");
    if (!$check) {
      redirectWithMsg("error", "خطأ في الاستعلام: " . $conn->error);
    }
    $check->bind_param("si", $email, $userId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
      $check->close();
      redirectWithMsg("error", "هذا البريد مستخدم بالفعل.");
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE user
      SET Fname=?, Lname=?, city=?, Neighborhood=?, street=?, email=?, birth_date=?, gender=?
      WHERE user_id=?");
    if (!$stmt) {
      redirectWithMsg("error", "خطأ في تجهيز الاستعلام: " . $conn->error);
    }

    $stmt->bind_param("ssssssssi", $Fname, $Lname, $city, $Neighborhood, $street, $email, $birth_date, $gender, $userId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
      // تحديث السيشن للاسم والإيميل عشان الهيدر وغيره
      $_SESSION["email"] = $email;
      $_SESSION["name"]  = trim($Fname . " " . $Lname);
      redirectWithMsg("success", "تم حفظ التعديلات ✅");
    } else {
      redirectWithMsg("error", "تعذر حفظ التعديلات.");
    }
  }

  /* 2) تغيير كلمة المرور (بدون تشفير مثل نظامك) */
  if (isset($_POST["change_password"])) {

    $current = trim($_POST["current_password"] ?? "");
    $new     = trim($_POST["new_password"] ?? "");

    if ($new === "") {
      redirectWithMsg("error", "اكتبي كلمة المرور الجديدة.");
    }
if ($current === $new) {
      redirectWithMsg("error", "كلمة المرور الجديدة لا يمكن أن تكون مطابقة للحالية.");
    }
    // نجيب كلمة المرور الحالية من DB ونقارن (مثل login.php)
    $stmt = $conn->prepare("SELECT password FROM user WHERE user_id=? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
      redirectWithMsg("error", "تعذر العثور على الحساب.");
    }

    if ($current != (string)$row["password"]) {
      redirectWithMsg("error", "كلمة المرور الحالية غير صحيحة.");
    }

    // تحديث كلمة المرور (مباشر مثل نظامك)
    $up = $conn->prepare("UPDATE user SET password=? WHERE user_id=?");
    if (!$up) {
      redirectWithMsg("error", "خطأ في تجهيز الاستعلام: " . $conn->error);
    }
    $up->bind_param("si", $new, $userId);
    $ok = $up->execute();
    $up->close();

    redirectWithMsg($ok ? "success" : "error", $ok ? "تم تغيير كلمة المرور ✅" : "تعذر تغيير كلمة المرور.");
  }

  /* 3) حذف الحساب */
  if (isset($_POST["delete_account"])) {

    $stmt = $conn->prepare("DELETE FROM user WHERE user_id=?");
    if (!$stmt) {
      redirectWithMsg("error", "خطأ في تجهيز الاستعلام: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $ok = $stmt->execute();
    $stmt->close();

    session_destroy();
    header("Location: login.php?msg=" . urlencode($ok ? "تم حذف الحساب بنجاح." : "تعذر حذف الحساب."));
    exit;
  }

  redirectWithMsg("error", "لا يوجد إجراء معروف.");
}

/* =========================
   جلب البيانات للعرض
   ========================= */
$stmt = $conn->prepare("SELECT user_id, email, password, gender, birth_date, Fname, Lname, city, Neighborhood, street
                        FROM user WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
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
<title>الملف الشخصي</title>
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
background: rgba(10, 12, 26, .25);  border:1px solid var(--stroke);
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

.field input, .field select{
  width:100%;
  height:40px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.14);
  background: rgba(7,10,22,.55);
  padding:0 12px;
  font-size:13px;
  color:#fff;
  outline:none;
}

.field input:focus, .field select:focus{
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

</style>
</head>

<body>
<?php renderBanner('profile'); ?>

<main class="container">

  <section class="card">
  <h1 class="gold-title">👤 ملفي الشخصي</h1>
    <p class="sub">عرض بيانات حساب المستفيد</p>

    <?php if($msg): ?>
      <div class="alert <?php echo ($type==='success') ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <div class="kv"><div class="key">الاسم</div><div class="val"><?php echo htmlspecialchars(($user["Fname"]??"")." ".($user["Lname"]??"")); ?></div></div>
    <div class="kv"><div class="key">البريد</div><div class="val"><?php echo htmlspecialchars($user["email"]); ?></div></div>
    <div class="kv"><div class="key">المدينة</div><div class="val"><?php echo htmlspecialchars($user["city"]); ?></div></div>
    <div class="kv"><div class="key">الحي</div><div class="val"><?php echo htmlspecialchars($user["Neighborhood"]); ?></div></div>
    <div class="kv"><div class="key">الشارع</div><div class="val"><?php echo htmlspecialchars($user["street"]); ?></div></div>
    <div class="kv"><div class="key">تاريخ الميلاد</div><div class="val"><?php echo htmlspecialchars($user["birth_date"]); ?></div></div>
    <div class="kv"><div class="key">الجنس</div><div class="val"><?php echo htmlspecialchars($user["gender"]); ?></div></div>
  </section>

  <aside class="card">
    <h1 class="gold-title">ادارة الحساب</h1>

    <p class="sub">تعديل البيانات أو حذف الحساب</p>

    <!-- تعديل البيانات -->
	<form method="POST" onsubmit="return confirm('هل أنت متأكد من حفظ التعديلات؟');">
      <input type="hidden" name="user_id" value="<?php echo (int)$user["user_id"]; ?>">

      <div class="grid">
        <div class="full field">
          <div class="label">البريد الإلكتروني</div>
          <input type="email" name="email" required value="<?php echo htmlspecialchars($user["email"]); ?>">
        </div>

        <div class="field">
          <div class="label">الاسم الأول</div>
          <input type="text" name="Fname" required value="<?php echo htmlspecialchars($user["Fname"]); ?>">
        </div>
        <div class="field">
          <div class="label">الاسم الأخير</div>
          <input type="text" name="Lname" required value="<?php echo htmlspecialchars($user["Lname"]); ?>">
        </div>

        <div class="field">
          <div class="label">المدينة</div>
          <input type="text" name="city" required value="<?php echo htmlspecialchars($user["city"]); ?>">
        </div>
        <div class="field">
          <div class="label">الحي</div>
          <input type="text" name="Neighborhood" required value="<?php echo htmlspecialchars($user["Neighborhood"]); ?>">
        </div>

        <div class="full field">
          <div class="label">الشارع</div>
          <input type="text" name="street" required value="<?php echo htmlspecialchars($user["street"]); ?>">
        </div>

        <div class="full field">
          <div class="label">تاريخ الميلاد</div>
          <input type="date" name="birth_date" required value="<?php echo htmlspecialchars($user["birth_date"]); ?>">
          <div class="small">يجب ان يكون العمر +18</div>
        </div>

        <div class="full field">
          <div class="label">الجنس</div>
          <select name="gender" required>
            <option value="ذكر"  <?php echo ($user["gender"]==="ذكر")?"selected":""; ?>>ذكر</option>
            <option value="أنثى" <?php echo ($user["gender"]==="أنثى")?"selected":""; ?>>أنثى</option>
          </select>
        </div>
      </div>

      <div style="margin-top:12px">
        <button class="btn" type="submit" name="save_basic">حفظ التعديلات</button>
      </div>
    </form>

    <hr class="sep">

    <!-- تغيير كلمة المرور -->
    <form method="POST" autocomplete="off">
      <input type="hidden" name="user_id" value="<?php echo (int)$user["user_id"]; ?>">

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
    <form method="POST" onsubmit="return confirm('هل انت متأكد تريد حذف حسابك ؟ العملية لايمكن التراجع عنها.');">
      <input type="hidden" name="user_id" value="<?php echo (int)$user["user_id"]; ?>">
      <button class="btn danger" type="submit" name="delete_account">حذف الحساب نهائيًا</button>
      <div class="small">سيتم حذف بياناتك وتسجيل خروجك.</div>
    </form>
  </aside>
</main>
</body>
</html>