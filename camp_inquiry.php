
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

if (!isset($_SESSION['license_number'])) {
    header("Location: camp_login.php");
    exit();
}

$license_number = $_SESSION['license_number'];

$stmtCamp = $conn->prepare("SELECT astronmy_camp_id, astronmy_camp_name 
                            FROM astronomical_camp 
                            WHERE license_number = ?");
$stmtCamp->bind_param("s", $license_number);
$stmtCamp->execute();
$campData = $stmtCamp->get_result()->fetch_assoc();
$stmtCamp->close();

if (!$campData) {
    die("المحمية غير موجودة.");
}

$camp_id   = $campData['astronmy_camp_id'];
$camp_name = $campData['astronmy_camp_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_inquiry'])) {

    $response   = trim($_POST['inquiry_response']);
    $inquiry_id = intval($_POST['inquiry_id']);

    if ($response !== "") {

        $check = $conn->prepare("SELECT inquiry_id 
                                 FROM inquiry 
                                 WHERE inquiry_id = ? 
                                 AND astronmy_camp_id = ?");
        $check->bind_param("ii", $inquiry_id, $camp_id);
        $check->execute();
        $exists = $check->get_result()->num_rows;
        $check->close();

        if ($exists > 0) {
            $update = $conn->prepare("UPDATE inquiry 
                                      SET inquiry_response = ? 
                                      WHERE inquiry_id = ?");
            $update->bind_param("si", $response, $inquiry_id);
            $update->execute();
            $update->close();
        }

        header("Location: camp_inquiry.php");
        exit();
    }
}

$stmt = $conn->prepare("
    SELECT i.*, u.Fname, u.Lname, u.email
    FROM inquiry i
    JOIN user u ON i.user_id = u.user_id
    WHERE i.astronmy_camp_id = ?
    ORDER BY i.inquiry_id DESC
");
$stmt->bind_param("i", $camp_id);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الرد على الاستفسارات</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --card:#121a33;
  --text:#eef2ff;
  --line:rgba(255,255,255,.12);
  --primary:#5b8cff;
  --primary2:#7aa3ff;
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
    no-repeat center center/cover fixed;}

/* ===== Page ===== */
.page{
  min-height: calc(100vh - 76px);
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
h1{text-align:center;margin-bottom:30px}
.field{margin-bottom:16px}
label{display:block;margin-bottom:6px;font-weight:bold}
textarea{
  width:100%;
  padding:12px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(0,0,0,.2);
  color:var(--text);
  min-height:120px;
  resize:none;
}
button.primary{
  width:100%;
  padding:14px;
  border:none;
  border-radius:12px;
  background:linear-gradient(90deg,var(--primary),var(--primary2));
  font-weight:bold;
  cursor:pointer;
}
.response{
  margin-top:12px;
  padding:12px;
  border-radius:12px;
  background:rgba(70,211,154,.15);
}
.empty{
  text-align:center;
  padding:40px;
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
<?php renderBanner('camp_inquiry'); ?>

<div class="page">

<h1 class="gold-title">الاستفسارات</h1>
<?php if(empty($inquiries)): ?>
  <div class="card empty">
    لا يوجد استفسارات في الوقت الحالي.
  </div>
<?php else: ?>

<?php foreach($inquiries as $i): ?>
  <div class="card">

    <h3><?= htmlspecialchars($i['inquiry_title']); ?></h3>

    <p>
      <strong>المستفيد:</strong>
      <?= htmlspecialchars($i['Fname']." ".$i['Lname']); ?>
      | <?= htmlspecialchars($i['email']); ?>
    </p>

    <p><?= nl2br(htmlspecialchars($i['inquiry_content'])); ?></p>

    <?php if(!empty($i['inquiry_response'])): ?>
      <div class="response">
        <strong>الرد المرسل :</strong><br>
        <?= nl2br(htmlspecialchars($i['inquiry_response'])); ?>
      </div>
    <?php else: ?>
      <form method="POST">
        <div class="field">
          <label>كتابة رد على الاستفسار</label>
          <textarea name="inquiry_response" required></textarea>
        </div>

        <input type="hidden" name="inquiry_id" value="<?= $i['inquiry_id']; ?>">

        <button type="submit" name="reply_inquiry" class="primary">
          حفظ الرد
        </button>
      </form>
    <?php endif; ?>

  </div>
<?php endforeach; ?>

<?php endif; ?>

</div>
</body>
</html>