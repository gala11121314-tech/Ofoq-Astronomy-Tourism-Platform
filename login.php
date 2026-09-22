
<?php
session_start();
require_once 'banner.php';

/* ✅ اتصال قاعدة البيانات */
$servername = "localhost";
$username = "root";
$dbpass = "";
$dbname = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
  die("فشل الاتصال: " . $conn->connect_error);
}

$serverMsg = "";
$serverMsgType = "error"; // success | error

/* ✅ تسجيل دخول فعلي (للمستفيد + المحمية + المدير) */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["do_login"])) {
/*تريم دالة مقص تقص اي مسافة لو حطها المستخدم بالغلط*/
  $role = $_POST["role"] ?? "beneficiary";
  $identifier = trim($_POST["identifier"] ?? "");
  $pass = trim($_POST["password"] ?? "");

  /* =========================
     ✅ 1) المستفيد
     ========================= */
  if ($role === "beneficiary") {

    if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
      $serverMsg = "البريد الإلكتروني غير صحيح";
      $serverMsgType = "error";
    } else {

      $stmt = $conn->prepare("SELECT user_id, email, password, Fname, Lname FROM user WHERE email = ? LIMIT 1");

      if (!$stmt) {
        $serverMsg = "حصل خطأ في تجهيز الاستعلام: " . $conn->error;
        $serverMsgType = "error";
      } else {

        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
          $serverMsg = "لا يوجد حساب بهذا البريد الإلكتروني";
          $serverMsgType = "error";
        } else {
          $row = $res->fetch_assoc();

          if ($pass == (string)$row["password"]) {

            $_SESSION["role"] = "beneficiary";
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["email"] = $row["email"];
            $_SESSION["name"] = trim(($row["Fname"] ?? "") . " " . ($row["Lname"] ?? ""));

            $_SESSION["welcome"] = true;
            header("Location: your_page.php");
            exit;

          } else {
            $serverMsg = "كلمة المرور غير صحيحة";
            $serverMsgType = "error";
          }
        }

        $stmt->close();
      }
    }

  /* =========================
     ✅ 2) المحمية الفلكية
     ========================= */
  } elseif ($role === "reserve") {

    if ($identifier === "" || !preg_match('/^\d+$/', $identifier)) {
      $serverMsg = "رقم الترخيص غير صحيح";
      $serverMsgType = "error";
    } else {

      $license_number = (int)$identifier;

      $stmt = $conn->prepare("SELECT astronmy_camp_id, astronmy_camp_name, license_number, password, Accreditation_status
                              FROM astronomical_camp
                              WHERE license_number = ?
                              LIMIT 1");

      if (!$stmt) {
        $serverMsg = "خطأ في الاستعلام (تحقق من اسم الجدول/الأعمدة).";
        $serverMsgType = "error";
      } else {

        $stmt->bind_param("i", $license_number);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
          $serverMsg = "لا يوجد حساب محمية برقم الترخيص المدخل";
          $serverMsgType = "error";
        } else {

          $row = $res->fetch_assoc();
          $status = trim((string)$row["Accreditation_status"]);

          if ($pass !== (string)$row["password"]) {
            $serverMsg = "كلمة المرور غير صحيحة";
            $serverMsgType = "error";
          } else {

            if ($status === 'بانتظار التوثيق والاعتماد') {
              $serverMsg = "حسابك لا يزال بانتظار التوثيق والاعتماد من الإدارة.";
              $serverMsgType = "error";

            } elseif ($status === 'غير معتمد') {
              $serverMsg = "تم رفض اعتماد حساب المحمية.";
              $serverMsgType = "error";

            } elseif ($status === 'موثق ومعتمد') {

              $_SESSION["role"] = "reserve";
              $_SESSION["astronmy_camp_id"] = $row["astronmy_camp_id"];
              $_SESSION["license_number"] = $row["license_number"];
              $_SESSION["camp_name"] = $row["astronmy_camp_name"];

              header("Location: camp_home.php");
              exit;

            } else {
              $serverMsg = "حالة الحساب غير معروفة، يرجى مراجعة الإدارة.";
              $serverMsgType = "error";
            }
          }
        }

        $stmt->close();
      }
    }

  /* =========================
     ✅ 3) مدير النظام
     ========================= */
  } elseif ($role == "admin") {

    if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
      $serverMsg = "البريد الإلكتروني غير صحيح";
    } else {

      $stmt = $conn->prepare("SELECT admin_id, Fname, Lname, email, password FROM admin WHERE email=? LIMIT 1");
      $stmt->bind_param("s", $identifier);
      $stmt->execute();
      $res = $stmt->get_result();

      if ($res->num_rows == 0) {
        $serverMsg = "لا يوجد مدير بهذا البريد الإلكتروني";
      } else {
        $row = $res->fetch_assoc();
        if ($pass == $row["password"]) {

          $_SESSION["role"] = "admin";
          $_SESSION["admin_id"] = $row["admin_id"];
          $_SESSION["admin_email"] = $row["email"];
          $_SESSION["admin_name"] = trim($row["Fname"]." ".$row["Lname"]);

          header("Location: admin.php");
          exit;

        } else {
          $serverMsg = "كلمة المرور غير صحيحة";
        }
      }

      $stmt->close();
    }

  } else {
    $serverMsg = "نوع المستخدم غير معروف";
  }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --bg:#0b1020;
  --card:#121a33;
  --text:#eef2ff;
  --muted:#b7c0ff;
  --line:rgba(255,255,255,.12);
  --primary:#5b8cff;
  --primary2:#7aa3ff;
  --danger:#ff5b6b;
  --ok:#46d39a;
}

*{box-sizing:border-box}

body{
  margin:0;
  font-family:"Tajawal",sans-serif;
  font-size:16px;
  color:#fff;
  background:
    linear-gradient(rgba(0,0,0,.60), rgba(0,0,0,.65)),
    url("https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3?q=80&w=1920&auto=format&fit=crop")
    no-repeat center center/cover fixed;
}

.page{
  min-height: calc(100vh - 88px);
  max-width: 1200px;
  margin: 0 auto;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:18px 14px;
}

.card{
  width:min(460px,100%);
  background:rgba(10,12,26,.72);
  border:1px solid rgba(255,255,255,.15);
  border-radius:20px;
  padding:26px 22px;
  backdrop-filter: blur(14px);
  box-shadow:0 18px 60px rgba(0,0,0,.45);
}

h1{
  margin:0 0 18px;
  text-align:center;
  font-size:24px;
  font-weight:800;
  color:#fff;
}

.role-switch{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:10px;
  margin-bottom:18px;
}

.role-btn{
  border:1px solid var(--line);
  background:rgba(255,255,255,.05);
  color:#fff;
  padding:10px 8px;
  border-radius:12px;
  cursor:pointer;
  font-weight:700;
  font-size:14px;
  text-align:center;
  transition:.2s;
}

.role-btn.is-active{
  background:linear-gradient(90deg,var(--primary),var(--primary2));
  color:#000;
  border-color:transparent;
}

.field{
  margin-bottom:14px;
}

label{
  display:block;
  margin-bottom:6px;
  font-weight:700;
  color:#fff;
}

input{
  width:100%;
  padding:12px 14px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.05);
  color:var(--text);
  font-family:"Tajawal",sans-serif;
  font-size:15px;
}

input::placeholder{
  color:rgba(238,242,255,.45);
}

input:focus{
  outline:none;
  border-color:var(--primary);
  box-shadow:0 0 0 3px rgba(91,140,255,.15);
}

.password-wrap{
  display:flex;
  align-items:center;
  gap:10px;
}

.password-wrap input{
  flex:1;
}

.toggle{
  padding:12px 14px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  cursor:pointer;
  color:var(--text);
  font-family:"Tajawal",sans-serif;
  white-space:nowrap;
}

button.primary{
  width:100%;
  padding:12px;
  border:none;
  border-radius:12px;
  background:linear-gradient(90deg,var(--primary),var(--primary2));
  color:#000;
  font-weight:800;
  font-family:"Tajawal",sans-serif;
  font-size:15px;
  cursor:pointer;
  margin-top:10px;
}

.login-note{
  margin-top:14px;
  text-align:center;
  font-size:14px;
  color:#fff;
}

.login-note a{
  color:var(--primary2);
  text-decoration:none;
  font-weight:700;
}

.login-note a:hover{
  text-decoration:underline;
}

.hidden{
  display:none;
}

.error{
  color:var(--danger);
  font-size:12px;
  min-height:16px;
  margin-top:4px;
}

.server-msg{
  margin:0 0 14px 0;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.16);
  background:rgba(0,0,0,.18);
  font-size:14px;
}

.server-msg.error{
  border-color:rgba(255,91,107,.35);
  background:rgba(255,91,107,.10);
  color:#ffd7db;
}

.server-msg.success{
  border-color:rgba(70,211,154,.35);
  background:rgba(70,211,154,.10);
  color:#d9fff1;
}

@media (max-width: 520px){
  .card{
    padding:20px 16px;
  }

  .role-switch{
    grid-template-columns:1fr;
  }

  .password-wrap{
    flex-direction:column;
    align-items:stretch;
  }

  .toggle{
    width:100%;
  }
}
</style>

</head>

<body>
<?php renderBanner('login'); ?>

<div class="page" id="login">
  <div class="card">
    <h1>تسجيل الدخول</h1>

    <?php if (!empty($serverMsg)): ?>
      <div class="server-msg <?php echo htmlspecialchars($serverMsgType); ?>">
        <?php echo htmlspecialchars($serverMsg); ?>
      </div>
    <?php endif; ?>

    <div class="role-switch">
      <button class="role-btn is-active" type="button" data-role="beneficiary">مستفيد</button>
      <button class="role-btn" type="button" data-role="reserve">محمية فلكية</button>
      <button class="role-btn" type="button" data-role="admin">مدير النظام</button>
    </div>

    <form id="loginForm" method="POST" action="">
      <input type="hidden" name="role" id="roleInput" value="beneficiary">

      <div class="field">
        <label id="idLabel">البريد الإلكتروني</label>
        <input id="identifier" name="identifier" type="email" placeholder="example@email.com" required>
        <div class="error" id="idError"></div>
      </div>

      <div class="field">
        <label>الرقم السري</label>
        <div class="password-wrap">
          <input id="password" name="password" type="password" placeholder="******" required>
          <button type="button" class="toggle" id="togglePass">إظهار</button>
        </div>
        <div class="error" id="passError"></div>
      </div>

      <button class="primary" type="submit" name="do_login" value="1">تسجيل الدخول</button>

      <div class="login-note" id="loginNote">
        ليس لديك حساب بالفعل؟
        <a id="registerLink" href="register.php">اضغط هنا</a>
      </div>
    </form>
  </div>
</div>

<script>
const roleButtons = document.querySelectorAll(".role-btn");
const identifier = document.getElementById("identifier");
const idLabel = document.getElementById("idLabel");
const idError = document.getElementById("idError");
const passError = document.getElementById("passError");
const password = document.getElementById("password");
const togglePass = document.getElementById("togglePass");
const form = document.getElementById("loginForm");
const roleInput = document.getElementById("roleInput");
const loginNote = document.getElementById("loginNote");
const registerLink = document.getElementById("registerLink");

let currentRole = "beneficiary";

const roles = {
  beneficiary: {
    label: "البريد الإلكتروني",
    type: "email",
    placeholder: "example@email.com",
    showNote: true,
    link: "register.php"
  },
  reserve: {
    label: "رقم الترخيص",
    type: "text",
    placeholder: "أدخل رقم الترخيص",
    showNote: true,
    link: "camp_register.php"
  },
  admin: {
    label: "البريد الإلكتروني",
    type: "email",
    placeholder: "example@email.com",
    showNote: false,
    link: "#"
  }
};

function setRole(role){
  currentRole = role;

  roleButtons.forEach(btn=>{
    btn.classList.toggle("is-active", btn.dataset.role===role);
  });

  idLabel.textContent = roles[role].label;
  identifier.type = roles[role].type;
  identifier.placeholder = roles[role].placeholder;

  roleInput.value = role;

  if (roles[role].showNote) {
    loginNote.classList.remove("hidden");
    registerLink.href = roles[role].link;
  } else {
    loginNote.classList.add("hidden");
  }

  identifier.value="";
  password.value="";
  idError.textContent="";
  passError.textContent="";
}

roleButtons.forEach(btn=>{
  btn.addEventListener("click", ()=>setRole(btn.dataset.role));
});

togglePass.addEventListener("click", ()=>{
  if(password.type==="password"){
    password.type="text";
    togglePass.textContent="إخفاء";
  }else{
    password.type="password";
    togglePass.textContent="إظهار";
  }
});

form.addEventListener("submit", function(e){
  idError.textContent="";
  passError.textContent="";
  let valid=true;

  const idVal = identifier.value.trim();

  if(idVal === ""){
    idError.textContent="هذا الحقل مطلوب";
    valid=false;
  } 
  else if (currentRole === "beneficiary" || currentRole === "admin") {
    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(idVal);
    if(!emailOk){
      idError.textContent="صيغة البريد الإلكتروني غير صحيحة";
      valid=false;
    }
  } 
  else if (currentRole === "reserve") {
    const numOk = /^\d+$/.test(idVal);
    if(!numOk){
      idError.textContent="لازم يكون أرقام فقط";
      valid=false;
    }
  }

  if(password.value.length < 1){
    passError.textContent="الرقم السري مطلوب";
    valid=false;
  }

  if(!valid){
    e.preventDefault();
  }
});

setRole("beneficiary");
</script>
</body>
</html>