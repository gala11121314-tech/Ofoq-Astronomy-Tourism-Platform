
<?php
session_start();
date_default_timezone_set('Asia/Riyadh');

require_once 'banner.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "reserve" || !isset($_SESSION["astronmy_camp_id"])) {
  header("Location: login.php");
  exit;
}

$servername = "localhost";
$username = "root";
$dbpass = "";
$dbname = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) { die("فشل الاتصال: " . $conn->connect_error); }

$camp_id = (int)$_SESSION["astronmy_camp_id"];

$msg = "";
$type = "error";

$SERVICES = [
  "حجز ساعة مع مرشد فلكي",
  "حجز جلسات تصوير فلكي",
  "عروض توجيه الليزر ومشاهدة النجوم",
  "حجز مبيت في المحمية",
  "رصد الظواهر الفلكية (ليلية ونهارية)",
  "باقة المبتدئين"
];

$PACKAGE_TEXT = "باقة المبتدئين تشمل:
- امسيات فلكية
- حجز ساعة مع مرشد فلكي
- جلسات رصد الظواهر الفلكية
- حجز جلسات تصوير فلكي";


      // دالة تحويل الوقت الى دقائق

function timeToMin($t){
  if(!$t) return null;
  $parts = explode(":", $t);
  $h = isset($parts[0]) ? (int)$parts[0] : 0;
  $m = isset($parts[1]) ? (int)$parts[1] : 0;
  return $h*60 + $m;
}
      // لتمييز وقت النهار للخدمات النهارية

function isDayTime($t){
  $m = timeToMin($t);
  if($m === null) return false;
  return ($m >= 6*60 && $m <= 17*60+59);
}
      // لتمييز وقت الليل للخدمات اليلية

function isNightTime($t){
  $m = timeToMin($t);
  if($m === null) return false;
  return ($m >= 18*60 || $m <= 5*60+59);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save"])) {

  $service_name = trim($_POST["service_name"] ?? "");
  $price = (int)($_POST["price"] ?? 0);
  $service_description = trim($_POST["service_description"] ?? "");

  $start_date = $_POST["start_date"] ?? "";
  $end_date   = $_POST["end_date"] ?? "";

  $seats_allowed = 0;
  $appointments_count = (int)($_POST["appointments_count"] ?? 0);
      // لتمييز الفورم على حسب الخدمة

  $phenomena_mode = $_POST["phenomena_mode"] ?? "";
  $overnight_seats = (int)($_POST["overnight_seats"] ?? 0);

  if (!in_array($service_name, $SERVICES, true)) {
    $msg = "اختر خدمة صحيحة.";
  }
  elseif ($start_date === "" || $start_date < date("Y-m-d")) {
    $msg = "لا يمكن إضافة خدمة بتاريخ سابق.";
  }
  elseif ($price <= 0) {
    $msg = "السعر يجب يكون أكبر من صفر.";
  }
  else {

    $time_class = "ANY";

    if ($service_name === "عروض توجيه الليزر ومشاهدة النجوم") {
      $time_class = "NIGHT";

    } elseif ($service_name === "حجز مبيت في المحمية") {
      $time_class = "OVERNIGHT";

    } elseif ($service_name === "رصد الظواهر الفلكية (ليلية ونهارية)") {
      if ($phenomena_mode !== "DAY" && $phenomena_mode !== "NIGHT") {
        $msg = "اختر نوع الرصد: نهاري (كسوف الشمس) أو ليلي (خسوف القمر).";
      } else {
        $time_class = $phenomena_mode;
      }

    } elseif ($service_name === "باقة المبتدئين") {
      $time_class = "MIX";

      // وصف الباقة ثابت مهما تغير أو انمسح من الواجهة
      $service_description = $PACKAGE_TEXT;
    }

    if ($msg === "") {
      $stmtCheck = $conn->prepare("
        SELECT 1
        FROM services s
        JOIN service_times t ON s.service_id = t.service_id
        WHERE s.astronmy_camp_id = ?
          AND s.service_name = ?
          AND s.start_date = ?
          AND (
            s.start_date > CURDATE()
            OR (
              s.start_date = CURDATE()
              AND t.end_time > CURTIME()
            )
          )
        LIMIT 1
      ");

      if (!$stmtCheck) {
        $msg = "خطأ في التحقق من تكرار الخدمة.";
      } else {
        $stmtCheck->bind_param("iss", $camp_id, $service_name, $start_date);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
          $msg = "لا يمكن إضافة نفس الخدمة بنفس التاريخ لنفس المحمية لأن هناك وقتًا نشطًا أو قادمًا لها.";
        }

        $stmtCheck->close();
      }
    }
//المبيت تاريخ  نهايته
    if ($msg === "" && $time_class === "OVERNIGHT" && $end_date !== "") {
      $expected = date("Y-m-d", strtotime($start_date . " +1 day"));

      if ($end_date < $start_date) {
        $msg = "تاريخ النهاية لا يمكن أن يكون قبل تاريخ البداية.";
      } elseif ($end_date !== $expected) {
        $msg = "المبيت ليلة واحدة فقط: تاريخ النهاية يجب يكون اليوم التالي لتاريخ البداية.";
      }
    }

    if ($msg === "" && $time_class === "OVERNIGHT") {
//المبيت شرط اساسي موعد واحد
      $end_date_to_save = date("Y-m-d", strtotime($start_date . " +1 day"));
      $appointments_count = 1;

      if ($overnight_seats <= 0) {
        $msg = "عدد مقاعد المبيت يجب يكون أكبر من صفر.";
      } else {
        $times = [
          [
            "start_time" => "16:00",
            "end_time"   => "10:00",
            "seats_allowed" => $overnight_seats
          ]
        ];

        $seats_allowed = $overnight_seats;
      }

    } else {

      $end_date_to_save = $start_date;

      if ($end_date !== "" && $end_date < $start_date) {
        $msg = "تاريخ النهاية لا يمكن أن يكون قبل تاريخ البداية.";
      }

      if ($msg === "" && $service_name === "رصد الظواهر الفلكية (ليلية ونهارية)") {
        $appointments_count = 1;
      }

      if ($msg === "" && $appointments_count <= 0) {
        $msg = "عدد الأوقات يجب يكون أكبر من صفر.";
      }

      $times = [];
      $total_seats = 0;

      if ($msg === "") {

        for ($i = 1; $i <= $appointments_count; $i++) {
          $st = trim($_POST["start_time_$i"] ?? "");
          $et = trim($_POST["end_time_$i"] ?? "");
          $time_seats = (int)($_POST["seats_allowed_$i"] ?? 0);

          if ($st === "" || $et === "") {
            $msg = "يجب تعبئة وقت البداية ووقت النهاية لكل وقت (وقت رقم $i).";
            break;
          }

          if ($time_seats <= 0) {
            $msg = "عدد المقاعد يجب يكون أكبر من صفر لكل وقت (وقت رقم $i).";
            break;
          }

          $stMin = timeToMin($st);
          $etMin = timeToMin($et);

          if ($stMin === null || $etMin === null) {
            $msg = "صيغة الوقت غير صحيحة (وقت رقم $i).";
            break;
          }

          if ($etMin <= $stMin) {
            $msg = "وقت النهاية يجب يكون بعد وقت البداية (وقت رقم $i).";
            break;
          }

          if ($start_date === date("Y-m-d")) {
            $nowTime = date("H:i");
            if ($st <= $nowTime) {
              $msg = "لا يمكن إضافة وقت منتهي أو بدأ بالفعل لليوم الحالي (وقت رقم $i).";
              break;
            }
          }

          if ($time_class === "DAY") {
            if (!isDayTime($st) || !isDayTime($et)) {
              $msg = "هذه خدمة نهارية: وقت رقم $i يجب يكون بين (06:00 - 17:59) للبداية والنهاية.";
              break;
            }
          }

          if ($time_class === "NIGHT") {
            if (!isNightTime($st) || !isNightTime($et)) {
              $msg = "هذه خدمة ليلية: وقت رقم $i يجب يكون ضمن (18:00 - 05:59) للبداية والنهاية.";
              break;
            }
          }

          $times[] = [
            "start_time" => $st,
            "end_time" => $et,
            "seats_allowed" => $time_seats
          ];

          $total_seats += $time_seats;
        }

        if ($msg === "" && $total_seats <= 0) {
          $msg = "يجب يكون مجموع المقاعد أكبر من صفر.";
        }

        if ($msg === "") {
          $seats_allowed = $total_seats;
        }
//وظيفة هذا الكود هي منع إدخال نفس موعد (وقت البداية والنهاية) مرتين عبر تخزين المواعيد في مصفوفة مؤقتة والمقارنة بينها لكشف التكرار.
        if ($msg === "") {
          $seen = [];
          foreach ($times as $pair) {
            $key = $pair["start_time"] . "|" . $pair["end_time"];
            if (isset($seen[$key])) {
              $msg = "مكرر نفس الوقت أكثر من مرة داخل الإدخال.";
              break;
            }
            $seen[$key] = true;
          }
        }

        if ($msg === "" && count($times) > 1) {
          $tmp = [];
          foreach ($times as $pair) {
            $tmp[] = [ timeToMin($pair["start_time"]), timeToMin($pair["end_time"]) ];
          }

          usort($tmp, function($a,$b){ return $a[0] <=> $b[0]; });

          for ($i=1; $i<count($tmp); $i++) {
            $prevEnd = $tmp[$i-1][1];
            $curStart = $tmp[$i][0];

            if ($curStart < $prevEnd) {
              $msg = "الأوقات تتداخل: يجب ألا تبدأ الخدمة في منتصف وقت آخر.";
              break;
            }
          }
        }
      }
    }

    if ($msg === "") {

      $conn->begin_transaction();

      try {
        $stmt = $conn->prepare("
          INSERT INTO services
          (service_name, price, service_description, start_date, end_date,
           seats_allowed, appointments_count, astronmy_camp_id)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
          throw new Exception("خطأ في تجهيز استعلام الخدمة: " . $conn->error);
        }

        $stmt->bind_param(
          "sisssiii",
          $service_name,
          $price,
          $service_description,
          $start_date,
          $end_date_to_save,
          $seats_allowed,
          $appointments_count,
          $camp_id
        );

        if (!$stmt->execute()) {
          throw new Exception("فشل حفظ الخدمة: " . $stmt->error);
        }

        $service_id = $conn->insert_id;
        $stmt->close();

        $stmtType = $conn->prepare("INSERT INTO service_type (type_name, service_id) VALUES (?, ?)");

        if (!$stmtType) {
          throw new Exception("خطأ في تجهيز استعلام نوع الخدمة: " . $conn->error);
        }

        $stmtType->bind_param("si", $time_class, $service_id);

        if (!$stmtType->execute()) {
          throw new Exception("فشل حفظ نوع الخدمة: " . $stmtType->error);
        }

        $stmtType->close();

        $stmt2 = $conn->prepare("
          INSERT INTO service_times (service_id, start_time, end_time, seats_allowed)
          VALUES (?, ?, ?, ?)
        ");

        if (!$stmt2) {
          throw new Exception("خطأ في تجهيز استعلام الأوقات: " . $conn->error);
        }

        foreach ($times as $pair) {
          $st = $pair["start_time"];
          $et = $pair["end_time"];
          $time_seats = $pair["seats_allowed"];

          $stmt2->bind_param("issi", $service_id, $st, $et, $time_seats);

          if (!$stmt2->execute()) {
            throw new Exception("فشل حفظ وقت: " . $stmt2->error);
          }
        }

        $stmt2->close();

        $conn->commit();
        header("Location: camp_services.php?added=1");
        exit;

      } catch (Exception $e) {
        $conn->rollback();
        $msg = $e->getMessage();
      }
    }
  }
}

function old($key, $default=""){
  return htmlspecialchars($_POST[$key] ?? $default);
}

function oldChecked($key, $value){
  return (($_POST[$key] ?? "") === $value) ? "checked" : "";
}

function oldSelected($key, $value){
  return (($_POST[$key] ?? "") === $value) ? "selected" : "";
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>إضافة خدمة</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#0b1020;
    --text:#eef2ff;
    --muted:#b7c0ff;
    --line:rgba(255,255,255,.12);
    --card:rgba(255,255,255,.06);
    --card2:rgba(0,0,0,.18);
    --danger:#ff5b6b;
  }
  *{box-sizing:border-box}
  body{font-family:"Tajawal",system-ui;background:var(--bg);color:#fff;margin:0}
  .page{padding:22px}
  .card{
    max-width:980px;margin:0 auto;background:var(--card);
    border:1px solid rgba(255,255,255,.14);border-radius:18px;padding:18px;
    box-shadow:0 18px 60px rgba(0,0,0,.35);backdrop-filter: blur(12px);
  }
  .header{
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    margin-bottom:12px;
    min-height:54px;
  }
  .back{
    position:absolute;
    right:0;
    top:0;
    color:#fff;
    text-decoration:none;
    font-weight:800;
    font-size:12px;
    padding:6px 10px;
    border-radius:8px;
    background:#c0392b;
    border:none;
    box-shadow:none;
    transition:.2s;
  }
  .back:hover{
    background:#e74c3c;
    transform:translateY(-1px);
  }
  h2{
    margin:0;
    font-size:35px;
    width:100%;
    text-align:center;
    background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
  }
  .msg{padding:10px 12px;border-radius:12px;margin:12px 0;border:1px solid rgba(255,255,255,.14)}
  .msg.error{background:rgba(255,91,107,.10);border-color:rgba(255,91,107,.35);color:#ffd7db}
  form{margin-top:10px}
  .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:14px}
  .field{grid-column:span 12;display:flex;flex-direction:column;gap:8px;min-width:0}
  .col-6{grid-column:span 6}
  .col-4{grid-column:span 4}
  .col-8{grid-column:span 8}
  .col-12{grid-column:span 12}
  @media (max-width: 820px){.col-6,.col-4,.col-8,.col-12{grid-column:span 12}}
  label{
    font-weight:800;
    background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
  }
  input,select,textarea{
    width:100%;padding:12px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.14);
    background:rgba(0,0,0,.22);color:var(--text);outline:none;
  }
  input:focus,select:focus,textarea:focus{border-color: rgba(91,140,255,.7);box-shadow:0 0 0 3px rgba(91,140,255,.12)}
  textarea{min-height:120px;resize:vertical}
  textarea[readonly]{
    opacity:.9;
    cursor:not-allowed;
  }
  .hint{color:var(--muted);font-size:13px;line-height:1.5}
  .box{border:1px solid rgba(255,255,255,.12);background:var(--card2);border-radius:16px;padding:14px}
  .radio{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
  .radio label{
    font-weight:700;display:flex;gap:8px;align-items:center;padding:8px 10px;border-radius:12px;
    border:1px solid rgba(255,255,255,.12);background:rgba(0,0,0,.12);cursor:pointer;
  }
  .timesGrid{display:grid;gap:12px;margin-top:10px}
  .timeRow{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
  .timeRow .field{margin:0}
  .timeRow .col-4{grid-column:span 4}
  @media (max-width: 820px){.timeRow .col-4{grid-column:span 12}}
  .btn{
    margin-top:16px;width:100%;padding:14px;border:0;border-radius:14px;font-weight:900;cursor:pointer;
    background:linear-gradient(90deg,#5b8cff,#7aa3ff);color:#000;
  }
  select{
    appearance:none;
    background-image:
      linear-gradient(45deg,transparent 50%,rgba(255,255,255,.7) 50%),
      linear-gradient(135deg,rgba(255,255,255,.7) 50%,transparent 50%);
    background-position:14px 18px, 20px 18px;
    background-size:6px 6px,6px 6px;
    background-repeat:no-repeat;
    padding-left:38px;
  }
  select option {
    background-color: #0b1020;
    color: #ffffff;
  }
  @media (max-width: 820px){
    .header{
      min-height:auto;
      padding-top:56px;
    }
    .back{
      top:0;
      right:0;
    }
    h2{
      font-size:24px;
    }
  }
  #phenomenaBox .radio label{
    background: rgba(0,0,0,.12) !important;
    -webkit-background-clip: border-box !important;
    -webkit-text-fill-color: #ffffff !important;
    color: #ffffff !important;
    font-weight: 800;
  }
</style>
</head>
<body>
<?php renderBanner('camp_services'); ?>

<div class="page">
  <div class="card">

    <div class="header">
      <a class="back" href="camp_services.php">→ رجوع لإدارة الخدمات</a>
      <h2>إضافة خدمة</h2>
    </div>

    <?php if($msg): ?>
      <div class="msg <?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <form method="post" id="form">
      <div class="grid">

        <div class="field col-8">
          <label>الخدمة</label>
          <select name="service_name" id="service_name" required>
            <option value="">اختر</option>
            <?php foreach($SERVICES as $s): ?>
              <option value="<?php echo htmlspecialchars($s); ?>" <?php echo oldSelected("service_name",$s); ?>>
                <?php echo htmlspecialchars($s); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="hint">اختار الخدمة من القائمة.</div>
        </div>

        <div class="field col-4">
          <label>السعر</label>
          <input type="number" name="price" min="1" value="<?php echo old('price','1'); ?>">
          <div class="hint">السعر يجب يكون أكبر من صفر.</div>
        </div>

        <div class="field col-12" id="countBox">
          <label>عدد المواعيد</label>
          <input type="number" name="appointments_count" id="appointments_count" min="1" value="<?php echo old('appointments_count','1'); ?>">
          <div class="hint">ادخل عدد الاوقات المتاحة وسيتم تحديث ادخال كل وقت على حدة في الاسفل.</div>
        </div>

        <div class="field col-12" id="overnightSeatsBox" style="display:none;">
          <label>عدد المقاعد للمبيت</label>
          <input type="number" name="overnight_seats" min="1" value="<?php echo old('overnight_seats','1'); ?>">
          <div class="hint">أدخل عدد المقاعد أو الوحدات المتاحة للمبيت.</div>
        </div>

        <div class="field col-6">
          <label>تاريخ البداية</label>
          <input type="date" name="start_date" required value="<?php echo old('start_date',''); ?>">
        </div>

        <div class="field col-6" id="endDateBox">
          <label>تاريخ النهاية (للمبيت فقط)</label>
          <input type="date" name="end_date" id="end_date" value="<?php echo old('end_date',''); ?>">
          <div class="hint">المبيت : سيتم احتسابه بشكل تلقائي لليوم التالي اذا تم تركه فارغا .</div>
        </div>

        <div class="field col-12" id="phenomenaBox" style="display:none;">
          <div class="box">
            <label style="margin:0 0 8px 0">نوع الرصد</label>
            <div class="radio">
              <label><input type="radio" name="phenomena_mode" value="DAY" <?php echo oldChecked("phenomena_mode","DAY"); ?>> نهاري (كسوف الشمس)</label>
              <label><input type="radio" name="phenomena_mode" value="NIGHT" <?php echo oldChecked("phenomena_mode","NIGHT"); ?>> ليلي (خسوف القمر)</label>
            </div>
          </div>
        </div>

        <div class="field col-12" id="timesBox">
          <div class="box">
            <div style="font-weight:900;margin-bottom:6px">الأوقات</div>
            <div class="hint">يجب عدم تكرار نفس الوقت او تداخل الاوقات</div>
            <div class="timesGrid" id="timesGrid"></div>
          </div>
        </div>

        <div class="field col-12">
          <label>وصف الخدمة</label>
          <textarea name="service_description" id="service_description"required><?php echo old('service_description',''); ?></textarea>
          <div class="hint" id="packageHint" style="display:none;">وصف الباقة ثابت ولا يمكن تغييره.</div>
        </div>

        <div class="field col-12">
          <button class="btn" type="submit" name="save" value="1">حفظ</button>
        </div>

      </div>
    </form>

  </div>
</div>

<script>
const serviceName = document.getElementById("service_name");
const phenomenaBox = document.getElementById("phenomenaBox");
const countBox = document.getElementById("countBox");
const timesBox = document.getElementById("timesBox");
const timesGrid = document.getElementById("timesGrid");
const countInput = document.getElementById("appointments_count");
const descBox = document.getElementById("service_description");
const endDateInput = document.getElementById("end_date");
const overnightSeatsBox = document.getElementById("overnightSeatsBox");
const packageHint = document.getElementById("packageHint");
const PACKAGE_TEXT = <?php echo json_encode($PACKAGE_TEXT, JSON_UNESCAPED_UNICODE); ?>;

function isOvernightSelected(){
  return serviceName.value === "حجز مبيت في المحمية";
}
function isPhenomenaSelected(){
  return serviceName.value === "رصد الظواهر الفلكية (ليلية ونهارية)";
}
function isPackageSelected(){
  return serviceName.value === "باقة المبتدئين";
}

function toggleSections(){
  phenomenaBox.style.display = isPhenomenaSelected() ? "block" : "none";
  overnightSeatsBox.style.display = isOvernightSelected() ? "block" : "none";

  if (!isOvernightSelected()){
    endDateInput.value = "";
    endDateInput.disabled = true;
  } else {
    endDateInput.disabled = false;
  }

  if (isOvernightSelected()){
    countBox.style.display = "none";
    timesBox.style.display = "none";
    return;
  }

  if (isPackageSelected()){
    descBox.value = PACKAGE_TEXT;
    descBox.readOnly = true;
    packageHint.style.display = "block";
  } else {
    descBox.readOnly = false;
    packageHint.style.display = "none";
  }

  if (isPhenomenaSelected()){
    countBox.style.display = "none";
    countInput.value = "1";
  } else {
    countBox.style.display = "block";
  }

  timesBox.style.display = "block";
}

function renderTimes(){
  toggleSections();

  if (isOvernightSelected()){
    timesGrid.innerHTML = "";
    return;
  }

  let n = parseInt(countInput.value || "1", 10);
  if (isNaN(n) || n < 1) n = 1;
  if (n > 20) n = 20;
  if (isPhenomenaSelected()) n = 1;

  timesGrid.innerHTML = "";

  const oldTimes = <?php
    $old = [];
    $postCount = (int)($_POST["appointments_count"] ?? 1);
    if ($postCount < 1) $postCount = 1;
    if ($postCount > 20) $postCount = 20;
    for ($i=1; $i<=$postCount; $i++){
      $old[] = [
        "st" => $_POST["start_time_$i"] ?? "",
        "et" => $_POST["end_time_$i"] ?? "",
        "seats" => $_POST["seats_allowed_$i"] ?? ""
      ];
    }
    echo json_encode($old, JSON_UNESCAPED_UNICODE);
  ?>;

  for(let i=1; i<=n; i++){
    const stVal = (oldTimes[i-1] && oldTimes[i-1].st) ? oldTimes[i-1].st : "";
    const etVal = (oldTimes[i-1] && oldTimes[i-1].et) ? oldTimes[i-1].et : "";
    const seatsVal = (oldTimes[i-1] && oldTimes[i-1].seats) ? oldTimes[i-1].seats : "";

    const row = document.createElement("div");
    row.className = "timeRow";
    row.innerHTML = `
      <div class="field col-4">
        <label>وقت البداية (وقت ${i})</label>
        <input type="time" name="start_time_${i}" required value="${stVal}">
      </div>
      <div class="field col-4">
        <label>وقت النهاية (وقت ${i})</label>
        <input type="time" name="end_time_${i}" required value="${etVal}">
      </div>
      <div class="field col-4">
        <label>عدد المقاعد (وقت ${i})</label>
        <input type="number" name="seats_allowed_${i}" min="1" required value="${seatsVal}">
      </div>
    `;
    timesGrid.appendChild(row);
  }
}

serviceName.addEventListener("change", () => {
  if (isPhenomenaSelected()) countInput.value = "1";
  renderTimes();
});

descBox.addEventListener("input", () => {
  if (isPackageSelected()){
    descBox.value = PACKAGE_TEXT;
  }
});

countInput.addEventListener("input", renderTimes);
renderTimes();
</script>

</body>
</html>