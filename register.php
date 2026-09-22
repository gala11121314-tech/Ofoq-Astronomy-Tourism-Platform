
<?php
session_start();
require_once 'banner.php';

/* اتصال قاعدة البيانات */
$servername = "localhost";
$username   = "root";
$dbpass      = "";
$dbname      = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
  die("فشل الاتصال: " . $conn->connect_error);
}

/* رسائل السيرفر */
$serverMsg = "";
$serverMsgType = "error"; // success | error

if (isset($_POST['submit'])) {

  $Fname        = trim($_POST['Fname'] ?? "");
  $Lname        = trim($_POST['Lname'] ?? "");
  $Street       = trim($_POST['Street'] ?? "");
  $Neighborhood = trim($_POST['Neighborhood'] ?? "");
  $City         = trim($_POST['City'] ?? "");
  $email        = trim($_POST['email'] ?? "");
  $password     = $_POST['password'] ?? "";
  $birth_date   = trim($_POST['birth_date'] ?? "");
  $gender       = $_POST['gender'] ?? "";

  /* التحقق من صيغة البريد الإلكتروني بدالة فلتر فار  */
  if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match("/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|net|org|sa|edu\.sa)$/i", $email)
  ) {
    $serverMsg = "صيغة البريد الإلكتروني غير صحيحة، يرجى إدخال بريد صحيح مثل: example@gmail.com";
    $serverMsgType = "error";
  } else {

    /* شرط العمر 18+ */
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
      $serverMsg = "عذراً لايمكنك التسجيل عمرك اقل من 18 سنه";
      $serverMsgType = "error";
    } else {

      /* منع تكرار الإيميل */
	        /*سيليكت ون يعني ياقاعدة لو لقيتي الايميل ردي ب 1 ووليمت 1 وقفي توفير للوقت*/

      $check = $conn->prepare("SELECT 1 FROM user WHERE email = ? LIMIT 1");
      if (!$check) {
        $serverMsg = "خطأ في تجهيز الاستعلام: " . $conn->error;
        $serverMsgType = "error";
      } else {
		/*بايند بارام لربط المعطيات واظهار النتيجة والاس اختصار لاسترنيق*/

        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
          $serverMsg = "هذا البريد الإلكتروني مستخدم بالفعل، لا يمكنك التسجيل به.";
          $serverMsgType = "error";
          $check->close();
        } else {
          $check->close();

          /* الإدخال */
          $stmt = $conn->prepare("INSERT INTO user (Street, Neighborhood, City, Lname, Fname, birth_date, gender, password, email)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
          if (!$stmt) {
            $serverMsg = "خطأ في تجهيز الاستعلام: " . $conn->error;
            $serverMsgType = "error";
          } else {
            $stmt->bind_param("sssssssss", $Street, $Neighborhood, $City, $Lname, $Fname, $birth_date, $gender, $password, $email);

            if ($stmt->execute()) {
              header("Location: login.php?status=success");
              exit;
            } else {
              $serverMsg = "خطأ: " . $stmt->error;
              $serverMsgType = "error";
            }
            $stmt->close();
          }
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>إنشاء حساب جديد</title>

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
    url("https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3?q=80&w=1920&auto=format&fit=crop")
    no-repeat center center/cover fixed;
}

.hero{
  padding:28px 0 60px;
}

.container{
  width:min(720px, 92vw);
  margin:0 auto;
  display:flex;
  justify-content:center;
}

.card{
  width:min(560px, 100%);
  background: rgba(10, 12, 26, .72);
  border:1px solid var(--stroke);
  border-radius:22px;
  padding:22px 18px;
  box-shadow: 0 22px 70px rgba(0,0,0,.55);
  backdrop-filter: blur(12px);
}

h1{
  text-align:center;
  margin:0 0 6px;
  font-size:22px;
}

.sub{
  text-align:center;
  margin:0 0 14px;
  color:var(--muted);
  font-size:13px;
}

.alert{
  margin:10px 0 14px;
  padding:10px 12px;
  border-radius:14px;
  font-size:13px;
  border:1px solid rgba(255,255,255,.12);
}

.alert.error{
  background: var(--errBg);
  border-color: var(--errBr);
}

.alert.success{
  background: var(--okBg);
  border-color: var(--okBr);
}

.grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:12px;
}

.full{
  grid-column:1 / -1;
}

.section-title{
  grid-column:1 / -1;
  font-weight:700;
  font-size:13px;
  color: rgba(255,255,255,.9);
  margin-top:6px;
  display:flex;
  align-items:center;
  gap:8px;
}

.section-title::before{
  content:"";
  width:8px;
  height:8px;
  border-radius:50%;
  background: linear-gradient(135deg, var(--blue), var(--purple));
}

.field input{
  width:100%;
  height:40px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.14);
  background: rgba(7,10,22,.55);
  padding:0 12px;
  font-size:13px;
  color:#fff;
  outline:none;
  font-family:"Tajawal",sans-serif;
}

.field input::placeholder{
  color: rgba(255,255,255,.55);
}

.field input:focus{
  border-color: rgba(96,165,250,.45);
  box-shadow: 0 0 0 4px rgba(96,165,250,.12);
}

.field input[type="date"]{
  color-scheme: dark;
}

.gender{
  grid-column:1/-1;
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  font-size:13px;
  color: rgba(255,255,255,.88);
}

.gender label{
  display:flex;
  align-items:center;
  gap:6px;
  padding:8px 10px;
  border-radius:12px;
  background: rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.10);
  cursor:pointer;
}

.hint{
  grid-column:1/-1;
  font-size:12px;
  color: rgba(255,255,255,.65);
  margin-top:-4px;
}

.actions{
  text-align:center;
  margin-top:14px;
}

button.main-btn{
  width:100%;
  padding:11px 14px;
  border:none;
  border-radius:14px;
  background: linear-gradient(135deg, rgba(96,165,250,.35), rgba(91,33,182,.25));
  color:#fff;
  font-size:14px;
  cursor:pointer;
  border:1px solid rgba(255,255,255,.14);
  font-family:"Tajawal",sans-serif;
}

button.main-btn:hover{
  filter: brightness(1.06);
}

.small-links{
  margin-top:10px;
  font-size:12px;
  color: rgba(255,255,255,.72);
}

.small-links a{
  color: var(--blue);
  text-decoration:none;
}

@media (max-width:600px){
  .grid{
    grid-template-columns:1fr;
  }
}
</style>
</head>

<body>
<?php renderBanner('register'); ?>

<main class="hero">
  <div class="container">
    <section class="card">
      <h1>إنشاء حساب جديد</h1>
      <p class="sub">ابدأ رحلتك من الصحراء… إلى عمق السماء</p>

      <?php if($serverMsg): ?>
        <div class="alert <?php echo ($serverMsgType==='success') ? 'success' : 'error'; ?>">
          <?php echo htmlspecialchars($serverMsg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="on">
        <div class="grid">

          <div class="section-title">الاسم</div>
          <div class="field">
            <input type="text" name="Fname" placeholder="الاسم الأول" required value="<?php echo htmlspecialchars($_POST['Fname'] ?? ''); ?>">
          </div>

          <div class="field">
            <input type="text" name="Lname" placeholder="الاسم الأخير" required value="<?php echo htmlspecialchars($_POST['Lname'] ?? ''); ?>">
          </div>

          <div class="section-title">العنوان</div>
          <div class="field">
            <input type="text" name="City" placeholder="المدينة" required value="<?php echo htmlspecialchars($_POST['City'] ?? ''); ?>">
          </div>

          <div class="field">
            <input type="text" name="Neighborhood" placeholder="الحي" required value="<?php echo htmlspecialchars($_POST['Neighborhood'] ?? ''); ?>">
          </div>

          <div class="field full">
            <input type="text" name="Street" placeholder="الشارع" required value="<?php echo htmlspecialchars($_POST['Street'] ?? ''); ?>">
          </div>

          <div class="section-title">الدخول</div>
          <div class="field">
            <input type="password" name="password" placeholder="كلمة المرور" required>
          </div>

          <div class="field">
            <input type="email" name="email" placeholder="forexample@gmail.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>

          <div class="hint">ملاحظة: يجب أن يكون عمرك 18 سنة أو أكثر للتسجيل.</div>

          <div class="section-title">تاريخ الميلاد</div>
          <div class="field full">
            <input type="date" name="birth_date" required value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
          </div>

          <div class="section-title">الجنس</div>
          <div class="gender">
            <label>
              <input type="radio" name="gender" value="ذكر" required <?php echo (($_POST['gender'] ?? '')==='ذكر')?'checked':''; ?>>
              ذكر
            </label>

            <label>
              <input type="radio" name="gender" value="أنثى" <?php echo (($_POST['gender'] ?? '')==='أنثى')?'checked':''; ?>>
              أنثى
            </label>
          </div>

        </div>

        <div class="actions">
          <button class="main-btn" type="submit" name="submit">إنشاء الحساب</button>
          <div class="small-links">
            لديك حساب؟ <a href="login.php">تسجيل الدخول</a>
          </div>
        </div>
      </form>
    </section>
  </div>
</main>

</body>
</html>