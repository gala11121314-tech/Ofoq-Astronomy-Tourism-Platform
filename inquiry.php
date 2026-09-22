
<?php
session_start();
require_once 'banner.php';
$servername = "localhost";
$username = "root";
$dbpass = "";
$dbname = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
  die("فشل الاتصال: " . $conn->connect_error);
}

$msg = "";
$msgType = "error";

// إرسال الاستفسار
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {

    $camp_id = (int)$_POST['camp_id'];
    $title = trim($_POST['inquiry_title']);
    $content = trim($_POST['inquiry_content']);
    $user_id = $_SESSION['user_id'];

    if ($title === "" || $content === "") {
        $msg = "يجب تعبئة كل الحقول.";
        $msgType = "error";
    } else {

        $stmt = $conn->prepare("INSERT INTO inquiry (inquiry_title, inquiry_content, user_id, astronmy_camp_id) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssii", $title, $content, $user_id, $camp_id);
            if ($stmt->execute()) {
                $msg = "تم إرسال الاستفسار بنجاح!";
                $msgType = "success";
            } else {
                $msg = "حدث خطأ أثناء الإرسال: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// جلب المحميات
$campStmt = $conn->query("SELECT astronmy_camp_id, astronmy_camp_name FROM astronomical_camp WHERE Accreditation_status='موثق ومعتمد'");
$camps = $campStmt->fetch_all(MYSQLI_ASSOC);

// جلب استفسارات المستخدم
$user_id = $_SESSION['user_id'];
$inqStmt = $conn->prepare("
    SELECT i.inquiry_title, i.inquiry_content, i.inquiry_response,
           c.astronmy_camp_name
    FROM inquiry i
    JOIN astronomical_camp c ON i.astronmy_camp_id = c.astronmy_camp_id
    WHERE i.user_id = ?
    ORDER BY i.inquiry_id DESC
");
$inqStmt->bind_param("i", $user_id);
$inqStmt->execute();
$inquiries = $inqStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inqStmt->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إرسال استفسار</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>

:root{
  --card:#121a33;
  --text:#eef2ff;
  --line:rgba(255,255,255,.12);
  --primary:#5b8cff;
  --primary2:#7aa3ff;
  --danger:#ff5b6b;
  --ok:#46d39a;
}
*{box-sizing:border-box}
body {
  margin: 0;
  font-family: "Tajawal", sans-serif;
  color: var(--text);
   background:
    linear-gradient(rgba(0,0,0,.60), rgba(0,0,0,.65)),
    url("yourpage.jpeg")
    no-repeat center center/cover fixed;
}

/* ===== Page ===== */
.page{
  min-height: calc(100vh - 76px);
  display:grid;
  place-items:center;
  padding:24px;
}
.card {
  width: min(700px, 100%);
  margin: 0 auto 24px auto;
  /* التعديلات أدناه تجعل البطاقة بنفس شكل الخدمات */
  background: rgba(255, 255, 255, 0.06); 
  border: 1px solid rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(12px);
  border-radius: 18px;
  padding: 28px;
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.35);
}
h1{text-align:center;margin-bottom:20px}
.field{margin-bottom:16px}
label{display:block;margin-bottom:6px;font-weight:bold}
input,select,textarea{
  width:100%;
  padding:12px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(0,0,0,.2);
  color:var(--text);
  font-family:"Tajawal",sans-serif;
}
textarea{min-height:120px;resize:none}
button.primary{
  width:100%;
  padding:14px;
  border:none;
  border-radius:12px;
  background:linear-gradient(90deg,var(--primary),var(--primary2));
  font-weight:bold;
  cursor:pointer;
}
.server-msg{padding:10px;border-radius:12px;margin-bottom:14px}
.server-msg.error{background:rgba(255,91,107,.15)}
.server-msg.success{background:rgba(70,211,154,.15)}
.response{margin-top:12px;padding:12px;border-radius:12px;background:rgba(70,211,154,.15)}
.no-response{margin-top:12px;padding:12px;border-radius:12px;background:rgba(255,91,107,.15)}
.gold-title{
  background: linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}
select option {
  background-color: #0b1020; /* لون غامق */
  color: #ffffff; /* نص أبيض واضح */
}
</style>
</head>

<body>
<?php renderBanner('inquiry'); ?>

<div class="page">
  <div class="card">
<h1 class="gold-title">ارسل استفسارك</h1>
    <?php if($msg !== ""): ?>
      <div class="server-msg <?php echo $msgType; ?>">
        <?php echo $msg; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>اختيار المحمية</label>
        <select name="camp_id" required>
          <option value="">اختر المحمية</option>
          <?php foreach($camps as $c): ?>
            <option value="<?= $c['astronmy_camp_id']; ?>">
              <?= htmlspecialchars($c['astronmy_camp_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>عنوان الاستفسار</label>
        <input type="text" name="inquiry_title" required>
      </div>

      <div class="field">
        <label>نص الاستفسار</label>
        <textarea name="inquiry_content" required></textarea>
      </div>

      <button type="submit" name="send_inquiry" class="primary">إرسال الاستفسار</button>
    </form>
  </div>

  <?php foreach($inquiries as $i): ?>
    <div class="card">
      <h3><?= htmlspecialchars($i['inquiry_title']); ?></h3>
      <p><strong>المحمية:</strong> <?= htmlspecialchars($i['astronmy_camp_name']); ?></p>
      <p><?= nl2br(htmlspecialchars($i['inquiry_content'])); ?></p>

      <?php if(!empty($i['inquiry_response'])): ?>
        <div class="response"><strong>رد المحمية:</strong><br><?= nl2br(htmlspecialchars($i['inquiry_response'])); ?></div>
      <?php else: ?>
        <div class="no-response">لم يتم الرد بعد.</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

</body>
</html>