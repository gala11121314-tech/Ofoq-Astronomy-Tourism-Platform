
<?php
session_start();
require_once 'banner.php';

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

$serverMsg = "";
$serverMsgType = "error";

/* للاحتفاظ بالقيم عند الخطأ */
$astronmy_camp_name   = "";
$location             = "";
$google_maps_url      = "";
$license_number       = "";

/* إعدادات رفع الملفات */
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
$maxSize = 2 * 1024 * 1024; // 2MB

function uploadSingleFile($inputName, $uploadDir, $allowedExtensions, $maxSize, &$errorMessage) {
  if (!isset($_FILES[$inputName])) {
    return null;
  }

  $fileName  = $_FILES[$inputName]['name'] ?? "";
  $tmpName   = $_FILES[$inputName]['tmp_name'] ?? "";
  $fileSize  = $_FILES[$inputName]['size'] ?? 0;
  $fileError = $_FILES[$inputName]['error'] ?? 4;

  if (trim($fileName) === "") {
    return null;
  }

  if ($fileError !== 0) {
    $errorMessage = "حدث خطأ أثناء رفع الملف.";
    return null;
  }

  $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

  if (!in_array($extension, $allowedExtensions)) {
    $errorMessage = "نوع الملف غير مسموح. المسموح فقط: PDF أو JPG أو JPEG أو PNG.";
    return null;
  }

  if ($fileSize > $maxSize) {
    $errorMessage = "حجم الملف كبير. الحد الأقصى لكل ملف هو 2MB.";
    return null;
  }

  $safeName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($fileName));
  $newFileName = time() . "_" . uniqid() . "_" . $safeName;
  $destination = $uploadDir . $newFileName;

  if (!move_uploaded_file($tmpName, $destination)) {
    $errorMessage = "تعذر حفظ الملف.";
    return null;
  }

  return $newFileName;
}

if (isset($_POST['submit'])) {

  $astronmy_camp_name   = trim($_POST['astronmy_camp_name'] ?? "");
  $location             = trim($_POST['location'] ?? "");
  $google_maps_url      = trim($_POST['google_maps_url'] ?? "");
  $password             = trim($_POST['password'] ?? "");
  $license_number       = trim($_POST['license_number'] ?? "");

  /* تحقق الحقول */
  if (
    $astronmy_camp_name === "" ||
    $location === "" ||
    $google_maps_url === "" ||
    $password === "" ||
    $license_number === ""
  ) {
    $serverMsg = "جميع الحقول مطلوبة.";
  }

  /* اسم المحمية: حروف فقط */
  if ($serverMsg === "" && !preg_match('/^[\p{L}\s]+$/u', $astronmy_camp_name)) {
    $serverMsg = "اسم المحمية يجب أن يحتوي على حروف فقط.";
  }

  /* رقم الترخيص: أرقام فقط */
  if ($serverMsg === "" && !preg_match('/^\d+$/', $license_number)) {
    $serverMsg = "رقم الترخيص يجب أن يحتوي على أرقام فقط.";
  }

  /* رابط Google Maps فقط */
  if ($serverMsg === "" && !filter_var($google_maps_url, FILTER_VALIDATE_URL)) {
    $serverMsg = "يجب إدخال رابط صحيح.";
  }

  if ($serverMsg === "" && !preg_match('/^https:\/\/(www\.)?(google\.com\/maps|maps\.app\.goo\.gl)\//', $google_maps_url)) {
    $serverMsg = "يجب إدخال رابط من Google Maps فقط.";
  }

  /* لازم ملف واحد على الأقل */
  if ($serverMsg === "") {
    $file1Exists = isset($_FILES['attachment1']) && trim($_FILES['attachment1']['name']) !== "";
    $file2Exists = isset($_FILES['attachment2']) && trim($_FILES['attachment2']['name']) !== "";

    if (!$file1Exists && !$file2Exists) {
      $serverMsg = "إرفاق الوثائق الرسمية إجباري، ويكفي ملف واحد على الأقل.";
    }
  }

  /* منع تكرار اسم المحمية */
  if ($serverMsg === "") {
    $checkName = $conn->prepare("SELECT 1 FROM astronomical_camp WHERE astronmy_camp_name = ? LIMIT 1");
    $checkName->bind_param("s", $astronmy_camp_name);
    $checkName->execute();
    $checkName->store_result();

    if ($checkName->num_rows > 0) {
      $serverMsg = "اسم المحمية مستخدم بالفعل، يرجى اختيار اسم آخر.";
      $serverMsgType = "error";
    }

    $checkName->close();
  }

  /* منع تكرار رقم الترخيص */
  if ($serverMsg === "") {
    $check = $conn->prepare("SELECT 1 FROM astronomical_camp WHERE license_number = ? LIMIT 1");
    $check->bind_param("s", $license_number);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      $serverMsg = "رقم الترخيص مستخدم بالفعل، لا يمكنك التسجيل به.";
      $serverMsgType = "error";
    }

    $check->close();
  }

  /* منع تكرار رابط Google Maps */
  if ($serverMsg === "") {
    $checkMap = $conn->prepare("SELECT 1 FROM astronomical_camp WHERE google_maps_url = ? LIMIT 1");
    $checkMap->bind_param("s", $google_maps_url);
    $checkMap->execute();
    $checkMap->store_result();

    if ($checkMap->num_rows > 0) {
      $serverMsg = "رابط الموقع مستخدم بالفعل لمحمية أخرى.";
      $serverMsgType = "error";
    }

    $checkMap->close();
  }

  $attachment1 = null;
  $attachment2 = null;

  if ($serverMsg === "") {
    $attachment1 = uploadSingleFile('attachment1', $uploadDir, $allowedExtensions, $maxSize, $serverMsg);

    if ($serverMsg === "") {
      $attachment2 = uploadSingleFile('attachment2', $uploadDir, $allowedExtensions, $maxSize, $serverMsg);
    }
  }

  if ($serverMsg === "") {

    $stmt = $conn->prepare("INSERT INTO astronomical_camp 
    (astronmy_camp_name, location, google_maps_url, attachment1, attachment2, Accreditation_status, password, license_number)
    VALUES (?, ?, ?, ?, ?, 'بانتظار التوثيق والاعتماد', ?, ?)");

    $stmt->bind_param(
      "sssssss",
      $astronmy_camp_name,
      $location,
      $google_maps_url,
      $attachment1,
      $attachment2,
      $password,
      $license_number
    );

    if ($stmt->execute()) {
      $serverMsg = "تم إنشاء الحساب بنجاح ✅ وحالتكم الآن بإنتظار التوثيق و الاعتماد من الإدارة.";
      $serverMsgType = "success";

      $astronmy_camp_name   = "";
      $location             = "";
      $google_maps_url      = "";
      $license_number       = "";
    } else {
      $serverMsg = "خطأ: " . $stmt->error;
      $serverMsgType = "error";

      if ($attachment1 && file_exists($uploadDir . $attachment1)) {
        unlink($uploadDir . $attachment1);
      }
      if ($attachment2 && file_exists($uploadDir . $attachment2)) {
        unlink($uploadDir . $attachment2);
      }
    }

    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إنشاء حساب محمية</title>

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
.hero{ padding:28px 0 60px; }

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

h1{ text-align:center; margin:0 0 6px; font-size:22px; }
.sub{ text-align:center; margin:0 0 14px; color:var(--muted); font-size:13px; }

.alert{
  margin:10px 0 14px;
  padding:10px 12px;
  border-radius:14px;
  font-size:13px;
}
.alert.error{ background: var(--errBg); border:1px solid var(--errBr); }
.alert.success{ background: var(--okBg); border:1px solid var(--okBr); }

.grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:12px;
}
.full{ grid-column:1 / -1; }

.field{
  display:flex;
  flex-direction:column;
  gap:6px;
}

.field label{
  font-size:13px;
  color:#fff;
  font-weight:600;
  padding-right:2px;
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
}

.field input::placeholder{
  color:rgba(255,255,255,.45);
}

.field input:focus{
  border-color: rgba(96,165,250,.45);
  box-shadow: 0 0 0 4px rgba(96,165,250,.12);
}

.password-field{
  position:relative;
}

.password-field input{
  padding-left:44px;
}

.toggle-password{
  position:absolute;
  left:12px;
  top:50%;
  transform:translateY(-50%);
  cursor:pointer;
  font-size:16px;
  color:#fff;
  opacity:.85;
  user-select:none;
}

.toggle-password:hover{
  opacity:1;
}

.upload-header{
  display:flex;
  align-items:center;
  gap:6px;
  margin-bottom:10px;
}

.upload-title{
  font-size:15px;
  font-weight:700;
  color:#fff;
}

.info-icon{
  position:relative;
  width:14px;
  height:14px;
  border-radius:50%;
  background:#ffffff18;
  color:#fff;
  font-size:9px;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  flex-shrink:0;
}

.tooltip-text{
  visibility:hidden;
  opacity:0;
  position:absolute;
  right:20px;
  top:50%;
  transform:translateY(-50%);
  background:#fff;
  color:#333;
  font-size:12px;
  padding:8px 10px;
  border-radius:8px;
  width:190px;
  line-height:1.6;
  box-shadow:0 5px 15px rgba(0,0,0,0.2);
  transition:0.3s;
  z-index:10;
}

.tooltip-text::after{
  content:"";
  position:absolute;
  left:-8px;
  top:50%;
  transform:translateY(-50%);
  border-width:8px;
  border-style:solid;
  border-color:transparent #fff transparent transparent;
}

.info-icon:hover .tooltip-text{
  visibility:visible;
  opacity:1;
}
.upload-buttons{
  display:flex;
  flex-direction:column;
  align-items:flex-start;
  gap:6px;
  width:100%;
}

.upload-btn{
  align-self:flex-start;
}
.upload-btn{
  padding:8px 14px;
  font-size:14px;
  border-radius:10px;
  background: linear-gradient(135deg, rgba(96,165,250,.35), rgba(91,33,182,.25));
  color:#fff;
  border:none;
  cursor:pointer;
  width:auto;
  font-family:"Tajawal",sans-serif;
}

.upload-btn:hover{
  filter: brightness(1.08);
}
.hidden-file-input{
  display:none;
}

.file-name{
  margin:0 0 10px;
  font-size:12px;
  color:#fff;
  line-height:1.7;
  display:none;
}

.actions{ text-align:center; margin-top:14px; }
button.main-btn{
  width:100%;
  padding:11px 14px;
  border:none;
  border-radius:14px;
  background: linear-gradient(135deg, rgba(96,165,250,.35), rgba(91,33,182,.25));
  color:#fff;
  font-size:14px;
  cursor:pointer;
  font-family:"Tajawal",sans-serif;
}
button.main-btn:hover{ filter: brightness(1.06); }

@media (max-width:600px){
  .grid{ grid-template-columns:1fr; }

  .tooltip-text{
    right:auto;
    left:0;
    top:28px;
    transform:none;
    width:220px;
  }

  .tooltip-text::after{
    left:12px;
    top:-16px;
    transform:none;
    border-width:8px;
    border-color:transparent transparent #fff transparent;
  }
}

.login-text {
  margin-top: 10px;
  text-align: center;
  color: #ffffff;
  font-size: 14px;
}

.login-text a {
  color: #60a5fa;
  text-decoration: none;
  font-weight: 600;
}

.login-text a:hover {
  text-decoration: underline;
}

</style>

</head>

<body>
<?php renderBanner('camp_register'); ?>

<main class="hero">
  <div class="container">
    <section class="card">

      <h1>إنشاء حساب محمية</h1>
      <p class="sub">كونوا جزءًا من شبكة المحميات الفلكية، وقدموا خدماتكم لآلاف المهتمين بعالم الفلك عبر منصتنا.</p>

      <?php if($serverMsg): ?>
        <div class="alert <?php echo ($serverMsgType==='success') ? 'success' : 'error'; ?>">
          <?php echo htmlspecialchars($serverMsg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="grid">

          <div class="field full">
            <label for="astronmy_camp_name">اسم المحمية</label>
            <input
              type="text"
              id="astronmy_camp_name"
              name="astronmy_camp_name"
              placeholder="أدخل اسم المحمية"
              required
              pattern="^[A-Za-z\u0600-\u06FF\s]+$"
              title="اسم المحمية يجب أن يحتوي على حروف فقط"
              value="<?php echo htmlspecialchars($astronmy_camp_name); ?>">
          </div>
<div class="field full">
  <label for="password">كلمة المرور</label>
  <div class="password-field">
    <input
      type="password"
      id="password"
      name="password"
      placeholder="أدخل كلمة المرور"
      required>
    <span class="toggle-password" onclick="togglePassword()">👁</span>
  </div>
</div>

          <div class="field full">
            <label for="license_number">رقم الترخيص</label>
            <input
              type="text"
              id="license_number"
              name="license_number"
              placeholder="أدخل رقم الترخيص"
              required
              pattern="^\d+$"
              title="رقم الترخيص يجب أن يحتوي على أرقام فقط"
              value="<?php echo htmlspecialchars($license_number); ?>">
          </div>

          <div class="field full">
            <label for="location">الموقع</label>
            <input
              type="text"
              id="location"
              name="location"
              placeholder="أدخل اسم المنطقة "
              required
              value="<?php echo htmlspecialchars($location); ?>">
          </div>
          <div class="field full">
            <label for="google_maps_url">رابط موقع المحمية في Google Maps</label>
            <input
              type="url"
              id="google_maps_url"
              name="google_maps_url"
              placeholder="أدخل رابط Google Maps"
              required
              pattern="https:\/\/(www\.)?(google\.com\/maps|maps\.app\.goo\.gl)\/.*"
              title="يجب إدخال رابط صحيح من Google Maps فقط"
              value="<?php echo htmlspecialchars($google_maps_url); ?>">
          </div>

          <div class="field full">
            <div class="upload-header">
              <span class="upload-title">إرفاق الوثائق الرسمية</span>
              <span class="info-icon">
                ?
                <span class="tooltip-text">
                  يجب أن تكون الملفات رسمية مثل: ترخيص الجهة أو وثيقة اعتماد، ويمكن رفع ملف واحد أو ملفين كحد أقصى.
                </span>
              </span>
            </div>
<div class="upload-buttons">

  <button type="button" class="upload-btn" onclick="document.getElementById('attachment1').click()">
    📎 إرفق وثيقة ترخيص المحمية
  </button>

  <button type="button" class="upload-btn" onclick="document.getElementById('attachment2').click()">
    📎 إرفق وثيقة اعتماد البلدية
  </button>

</div>

<input type="file" name="attachment1" id="attachment1" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png">
<div id="fileName1" class="file-name"></div>

<input type="file" name="attachment2" id="attachment2" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png">
<div id="fileName2" class="file-name"></div>

<div class="actions">
          <button class="main-btn" type="submit" name="submit">إنشاء الحساب</button>
          <p class="login-text">
            لديك حساب؟ <a href="login.php">تسجيل الدخول</a>
          </p>
        </div>

      </form>

    </section>
  </div>
</main>

<script>
function showSelectedFile(inputId, outputId) {
  const input = document.getElementById(inputId);
  const output = document.getElementById(outputId);

  input.addEventListener("change", function () {
    if (this.files.length > 0) {
      output.style.display = "block";
      output.textContent = "📎 " + this.files[0].name;
    } else {
      output.style.display = "none";
      output.textContent = "";
    }
  });
}

function togglePassword() {
  const pass = document.getElementById("password");
  if (pass.type === "password") {
    pass.type = "text";
  } else {
    pass.type = "password";
  }
}

showSelectedFile("attachment1", "fileName1");
showSelectedFile("attachment2", "fileName2");
</script>

</body>
</html>