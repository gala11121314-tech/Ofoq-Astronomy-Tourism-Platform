
<?php
session_start();
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
$service_id = isset($_GET["service_id"]) ? (int)$_GET["service_id"] : 0;
if ($service_id <= 0) { header("Location: camp_services.php"); exit; }

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

$PACKAGE_TEXT = "باقة المبتدئين:
- امسيات فلكية
- حجز ساعة مع مرشد فلكي
- جلسات رصد الظواهر الفلكية
- حجز جلسات تصوير فلكي";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function timeToMin($t){
  if(!$t) return null;
  $parts = explode(":", $t);
  $h = isset($parts[0]) ? (int)$parts[0] : 0;
  $m = isset($parts[1]) ? (int)$parts[1] : 0;
  return $h*60 + $m;
}

function isDayTime($t){
  $m = timeToMin($t);
  if($m === null) return false;
  return ($m >= 6*60 && $m <= 17*60+59);
}

function isNightTime($t){
  $m = timeToMin($t);
  if($m === null) return false;
  return ($m >= 18*60 || $m <= 5*60+59);
}

function hasBlockingReservation(mysqli $conn, int $service_id): bool {
  $sql = "
    SELECT 1
    FROM booking
    WHERE service_id = ?
      AND booking_status <> 'cancelled'
    LIMIT 1
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) return false;

  $stmt->bind_param("i", $service_id);
  $stmt->execute();
  $stmt->store_result();
  $found = $stmt->num_rows > 0;
  $stmt->close();

  return $found;
}

/* جلب الخدمة */
$stmtS = $conn->prepare("
  SELECT service_id, service_name, price, service_description,
         start_date, end_date, seats_allowed, appointments_count, astronmy_camp_id
  FROM services
  WHERE service_id = ? AND astronmy_camp_id = ?
  LIMIT 1
");
$stmtS->bind_param("ii", $service_id, $camp_id);
$stmtS->execute();
$resS = $stmtS->get_result();

if ($resS->num_rows === 0) {
  $stmtS->close();
  $conn->close();
  header("Location: camp_services.php");
  exit;
}
$service = $resS->fetch_assoc();
$stmtS->close();

/* التحقق هل الخدمة منتهية */
$isEnded = false;

$today = $conn->query("SELECT CURDATE()")->fetch_row()[0];
$now   = $conn->query("SELECT CURTIME()")->fetch_row()[0];

if (($service["service_name"] ?? "") === "حجز مبيت في المحمية") {
  if (!empty($service["end_date"])) {
    if ($service["end_date"] < $today) {
      $isEnded = true;
    } elseif ($service["end_date"] === $today && $now >= "10:00:00") {
      $isEnded = true;
    }
  }
} else {
  $stmtEnd = $conn->prepare("
    SELECT MAX(end_time) AS max_end
    FROM service_times
    WHERE service_id = ?
  ");
  $stmtEnd->bind_param("i", $service_id);
  $stmtEnd->execute();
  $resEnd = $stmtEnd->get_result();
  $rowEnd = $resEnd->fetch_assoc();
  $stmtEnd->close();

  $maxEnd = $rowEnd["max_end"] ?? null;

  if ($service["start_date"] < $today) {
    $isEnded = true;
  } elseif ($service["start_date"] === $today) {
    if ($maxEnd && $now >= $maxEnd) {
      $isEnded = true;
    } elseif (!$maxEnd) {
      $isEnded = true;
    }
  }
}

/* نوع الخدمة من جدول service_type فقط */
$time_class_db = "";

$stmtT = $conn->prepare("SELECT type_name FROM service_type WHERE service_id=? LIMIT 1");
if ($stmtT) {
  $stmtT->bind_param("i", $service_id);
  $stmtT->execute();
  $resT = $stmtT->get_result();
  if ($resT && $resT->num_rows > 0) {
    $time_class_db = $resT->fetch_assoc()["type_name"] ?? "";
  }
  $stmtT->close();
}

/* الأوقات الحالية */
$times_db = [];
$stmtTimes = $conn->prepare("
  SELECT time_id, start_time, end_time, seats_allowed
  FROM service_times
  WHERE service_id=?
  ORDER BY time_id ASC
");
$stmtTimes->bind_param("i", $service_id);
$stmtTimes->execute();
$resTimes = $stmtTimes->get_result();

while($r = $resTimes->fetch_assoc()){
  $times_db[] = [
    "time_id" => (int)$r["time_id"],
    "start_time" => $r["start_time"],
    "end_time" => $r["end_time"],
    "seats_allowed" => (int)($r["seats_allowed"] ?? 0)
  ];
}
$stmtTimes->close();

$hasReservation = hasBlockingReservation($conn, $service_id);
$blockEdit = $hasReservation || $isEnded;

if ($isEnded) {
  $msg = "لايمكن التعديل خدمة منتهيه.";
} elseif ($hasReservation) {
  $msg = "لا يمكن التعديل بوجود حجز قائم.";
}

$phenomena_mode_prefill = "";
if (($service["service_name"] ?? "") === "رصد الظواهر الفلكية (ليلية ونهارية)") {
  if ($time_class_db === "DAY" || $time_class_db === "NIGHT") {
    $phenomena_mode_prefill = $time_class_db;
  }
}
$pref_service_name = $service["service_name"];
$pref_price = (int)$service["price"];
$pref_desc = (string)$service["service_description"];
$pref_start_date = $service["start_date"];
$pref_end_date = ($time_class_db === "OVERNIGHT") ? $service["end_date"] : "";

$times_count_db = count($times_db);
if ($pref_service_name === "حجز مبيت في المحمية") {
  $pref_count = 1;
} else {
  $pref_count = $times_count_db > 0 ? $times_count_db : (int)$service["appointments_count"];
  if ($pref_count < 1) $pref_count = 1;
  if ($pref_count > 20) $pref_count = 20;
}
$pref_overnight_seats = 1;
if ($time_class_db === "OVERNIGHT" && !empty($times_db[0]["seats_allowed"])) {
  $pref_overnight_seats = (int)$times_db[0]["seats_allowed"];
}
function oldv($key, $fallback=""){
  return htmlspecialchars($_POST[$key] ?? $fallback, ENT_QUOTES, 'UTF-8');
}

function oldSelected($key, $value, $fallbackValue=""){
  $cur = $_POST[$key] ?? $fallbackValue;
  return ($cur === $value) ? "selected" : "";
}

function oldChecked($key, $value, $fallbackValue=""){
  $cur = $_POST[$key] ?? $fallbackValue;
  return ($cur === $value) ? "checked" : "";
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save"]) && !$blockEdit) {

  $service_name = trim($_POST["service_name"] ?? "");
  $price = (int)($_POST["price"] ?? 0);
  $service_description = trim($_POST["service_description"] ?? "");
  $start_date = $_POST["start_date"] ?? "";
  $end_date   = $_POST["end_date"] ?? "";
  $appointments_count = (int)($_POST["appointments_count"] ?? 0);
  $phenomena_mode = $_POST["phenomena_mode"] ?? "";
  $overnight_seats = (int)($_POST["overnight_seats"] ?? 0);

  if (!in_array($service_name, $SERVICES, true)) {
    $msg = "اختر خدمة صحيحة.";
  }
  elseif ($start_date === "" || $start_date < date("Y-m-d")) {
    $msg = "لا يمكن تعديل الخدمة بتاريخ سابق.";
  }
  elseif ($price <= 0) {
    $msg = "السعر لا يمكن أن يكون رقمًا سالبًا أو صفر.";
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
      if ($service_description === "") $service_description = $PACKAGE_TEXT;
    }

    if ($msg === "") {
      $stmtCheck = $conn->prepare("
        SELECT 1
        FROM services
        WHERE astronmy_camp_id = ?
          AND service_name = ?
          AND start_date = ?
          AND service_id <> ?
        LIMIT 1
      ");

      if (!$stmtCheck) {
        $msg = "خطأ في التحقق من تكرار الخدمة.";
      } else {
        $stmtCheck->bind_param("issi", $camp_id, $service_name, $start_date, $service_id);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
          $msg = "لا يمكن وجود نفس الخدمة بنفس التاريخ لنفس المحمية.";
        }

        $stmtCheck->close();
      }
    }

    $times = [];
    $seats_allowed = 0;

    if ($msg === "" && $time_class === "OVERNIGHT") {

      $end_date_to_save = date("Y-m-d", strtotime($start_date . " +1 day"));
      $appointments_count = 1;

      if ($overnight_seats <= 0) {
        $msg = "عدد مقاعد المبيت لازم يكون أكبر من صفر.";
      } else {
        if ($end_date !== "" && $end_date !== $end_date_to_save) {
          $msg = "المبيت ليلة واحدة فقط: تاريخ النهاية لازم يكون اليوم التالي لتاريخ البداية.";
        }

        $times = [
          [
            "start_time" => "16:00",
            "end_time" => "10:00",
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
        $msg = "عدد الأوقات لازم يكون أكبر من صفر.";
      }
      $total_seats = 0;
      if ($msg === "") {
        for ($i = 1; $i <= $appointments_count; $i++) {
          $st = trim($_POST["start_time_$i"] ?? "");
          $et = trim($_POST["end_time_$i"] ?? "");
          $time_seats = (int)($_POST["seats_allowed_$i"] ?? 0);

          if ($st === "" || $et === "") {
            $msg = "لازم تعبين وقت البداية ووقت النهاية لكل وقت (وقت رقم $i).";
            break;
          }

          if ($time_seats <= 0) {
            $msg = "عدد المقاعد لازم يكون أكبر من صفر لكل وقت (وقت رقم $i).";
            break;
          }
          $stMin = timeToMin($st);
          $etMin = timeToMin($et);

          if ($stMin === null || $etMin === null) {
            $msg = "صيغة الوقت غير صحيحة (وقت رقم $i).";
            break;
          }

          if ($start_date === $today) {
            $nowMin = timeToMin(substr($now, 0, 5));
            if ($nowMin !== null && $stMin <= $nowMin) {
              $msg = "لا يمكن إضافة وقت بدأ بالفعل في التاريخ الحالي (وقت رقم $i).";
              break;
            }
          }
          if ($etMin <= $stMin) {
            $msg = "وقت النهاية لازم يكون بعد وقت البداية (وقت رقم $i).";
            break;
          }

          if ($time_class === "DAY") {
            if (!isDayTime($st) || !isDayTime($et)) {
              $msg = "هذه خدمة نهارية: وقت رقم $i لازم يكون بين (06:00 - 17:59) للبداية والنهاية.";
              break;
            }
          }

          if ($time_class === "NIGHT") {
            if (!isNightTime($st) || !isNightTime($et)) {
              $msg = "هذه خدمة ليلية: وقت رقم $i لازم يكون ضمن (18:00 - 05:59) للبداية والنهاية.";
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
          $msg = "لازم يكون مجموع المقاعد أكبر من صفر.";
        }

        if ($msg === "") {
          $seats_allowed = $total_seats;
        }
        if ($msg === "") {
          $seen = [];
          foreach ($times as $pair) {
            $key = $pair["start_time"]."|".$pair["end_time"];
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
            if ($tmp[$i][0] < $tmp[$i-1][1]) {
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
        $stmtU = $conn->prepare("
          UPDATE services
          SET service_name=?, price=?, service_description=?, start_date=?, end_date=?,
              seats_allowed=?, appointments_count=?
          WHERE service_id=? AND astronmy_camp_id=?
          LIMIT 1
        ");

        if (!$stmtU) {
          throw new Exception("خطأ في تجهيز تحديث الخدمة: " . $conn->error);
        }
        $stmtU->bind_param(
          "sisssiiii",
          $service_name,
          $price,
          $service_description,
          $start_date,
          $end_date_to_save,
          $seats_allowed,
          $appointments_count,
          $service_id,
          $camp_id
        );
        if (!$stmtU->execute()) {
          throw new Exception("فشل تحديث الخدمة: " . $stmtU->error);
        }
        $stmtU->close();$stmtTypeDel = $conn->prepare("DELETE FROM service_type WHERE service_id=?");
if (!$stmtTypeDel) throw new Exception("خطأ في حذف نوع الخدمة: " . $conn->error);
$stmtTypeDel->bind_param("i", $service_id);
$stmtTypeDel->execute();
$stmtTypeDel->close();

$stmtTypeIn = $conn->prepare("INSERT INTO service_type (type_name, service_id) VALUES (?, ?)");
if (!$stmtTypeIn) throw new Exception("خطأ في إضافة نوع الخدمة: " . $conn->error);
$stmtTypeIn->bind_param("si", $time_class, $service_id);
$stmtTypeIn->execute();
$stmtTypeIn->close();
        $stmtDelTimes = $conn->prepare("DELETE FROM service_times WHERE service_id=?");
        if (!$stmtDelTimes) throw new Exception("خطأ في حذف الأوقات القديمة: " . $conn->error);

        $stmtDelTimes->bind_param("i", $service_id);

        if (!$stmtDelTimes->execute()) {
          throw new Exception("فشل حذف الأوقات القديمة.");
        }

        $stmtDelTimes->close();

        $stmtInsTime = $conn->prepare("
          INSERT INTO service_times (service_id, start_time, end_time, seats_allowed)
          VALUES (?, ?, ?, ?)
        ");

        if (!$stmtInsTime) {
          throw new Exception("خطأ في إضافة الأوقات: " . $conn->error);
        }
        foreach ($times as $row) {
          $st = $row["start_time"];
          $et = $row["end_time"];
          $ts = $row["seats_allowed"];

          $stmtInsTime->bind_param("issi", $service_id, $st, $et, $ts);

          if (!$stmtInsTime->execute()) {
            throw new Exception("فشل إضافة وقت.");
          }
        }
        $stmtInsTime->close();

        $conn->commit();
        header("Location: camp_services.php?updated=1");
        exit;

      } catch (Exception $e) {
        $conn->rollback();
        $msg = $e->getMessage();
      }
    }
  }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تعديل خدمة</title>
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
      <a class="back" href="camp_services.php">← رجوع لإدارة الخدمات</a>
      <h2>تعديل خدمة</h2>
    </div>

    <?php if($msg): ?>
      <div class="msg <?php echo h($type); ?>"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <form method="post" id="form">
      <div class="grid">

        <div class="field col-8">
          <label>الخدمة</label>
          <select name="service_name" id="service_name" required <?php echo $blockEdit ? 'disabled' : ''; ?>>
            <option value="">اختر</option>
            <?php foreach($SERVICES as $s): ?>
              <option value="<?php echo h($s); ?>" <?php echo oldSelected("service_name",$s,$pref_service_name); ?>>
                <?php echo h($s); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if($blockEdit): ?><input type="hidden" name="service_name" value="<?php echo h($pref_service_name); ?>"><?php endif; ?>
          <div class="hint">اختار الخدمة من القائمة.</div>
        </div>

        <div class="field col-4">
          <label>السعر</label>
          <input type="number" name="price" min="1" value="<?php echo oldv('price', (string)$pref_price); ?>" <?php echo $blockEdit ? 'disabled' : ''; ?>>
          <?php if($blockEdit): ?><input type="hidden" name="price" value="<?php echo h($pref_price); ?>"><?php endif; ?>
        </div>

        <div class="field col-12" id="countBox">
          <label>عدد المواعيد</label>
          <input type="number" name="appointments_count" id="appointments_count" min="1"
                 value="<?php echo oldv('appointments_count', (string)$pref_count); ?>" <?php echo $blockEdit ? 'disabled' : ''; ?>>
          <?php if($blockEdit): ?><input type="hidden" name="appointments_count" value="<?php echo h($pref_count); ?>"><?php endif; ?>
          <div class="hint">ادخل عدد الاوقات المتاحة وسيتم تحديث ادخال كل وقت على حدة في الاسفل.</div>
        </div>

        <div class="field col-12" id="overnightSeatsBox" style="display:none;">
          <label>عدد المقاعد للمبيت</label>
          <input type="number" name="overnight_seats" min="1" value="<?php echo oldv('overnight_seats', (string)$pref_overnight_seats); ?>" <?php echo $blockEdit ? 'disabled' : ''; ?>>
          <?php if($blockEdit): ?><input type="hidden" name="overnight_seats" value="<?php echo h($pref_overnight_seats); ?>"><?php endif; ?>
          <div class="hint">أدخل عدد المقاعد أو الوحدات المتاحة للمبيت.</div>
        </div>

        <div class="field col-6">
          <label>تاريخ البداية</label>
          <input type="date" name="start_date" required value="<?php echo oldv('start_date', (string)$pref_start_date); ?>" <?php echo $blockEdit ? 'disabled' : ''; ?>>
          <?php if($blockEdit): ?><input type="hidden" name="start_date" value="<?php echo h($pref_start_date); ?>"><?php endif; ?>
        </div>

        <div class="field col-6" id="endDateBox">
          <label>تاريخ النهاية (للمبيت فقط)</label>
          <input type="date" name="end_date" id="end_date" value="<?php echo oldv('end_date', (string)$pref_end_date); ?>" <?php echo $blockEdit ? 'disabled' : ''; ?>>
          <?php if($blockEdit): ?><input type="hidden" name="end_date" value="<?php echo h($pref_end_date); ?>"><?php endif; ?>
          <div class="hint">المبيت : سيتم احتسابه بشكل تلقائي لليوم التالي اذا تم تركه فارغا .</div>
        </div>

        <div class="field col-12" id="phenomenaBox" style="display:none;">
          <div class="box">
            <label style="margin:0 0 8px 0">نوع الرصد</label>

            <div class="radio">
              <label class="radio-card">
                <input type="radio" name="phenomena_mode" value="DAY" <?php echo oldChecked("phenomena_mode","DAY",$phenomena_mode_prefill); ?> <?php echo $blockEdit ? 'disabled' : ''; ?>>
                <span>نهاري<br>(كسوف الشمس)</span>
              </label>

              <label class="radio-card">
                <input type="radio" name="phenomena_mode" value="NIGHT" <?php echo oldChecked("phenomena_mode","NIGHT",$phenomena_mode_prefill); ?> <?php echo $blockEdit ? 'disabled' : ''; ?>>
                <span>ليلي<br>(خسوف القمر)</span>
              </label>
            </div>

            <?php if($blockEdit): ?>
              <input type="hidden" name="phenomena_mode" value="<?php echo h($phenomena_mode_prefill); ?>">
            <?php endif; ?>
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
  <textarea name="service_description" id="service_description" required <?php echo $blockEdit ? 'disabled' : ''; ?>><?php echo oldv('service_description', (string)$pref_desc); ?></textarea>
  <?php if($blockEdit): ?>
    <input type="hidden" name="service_description" value="<?php echo h($pref_desc); ?>">
  <?php endif; ?>

  <div class="hint" id="packageHint" style="display:none;">
    وصف الباقة ثابت ولا يمكن تغييره.
  </div>
</div>

        <div class="field col-12">
          <button class="btn" type="submit" name="save" value="1" <?php echo $blockEdit ? 'disabled' : ''; ?>>
            حفظ التعديلات
          </button>
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
const PACKAGE_TEXT = <?php echo json_encode($PACKAGE_TEXT, JSON_UNESCAPED_UNICODE); ?>;
const HAS_RESERVATION = <?php echo $blockEdit ? 'true' : 'false'; ?>;
const existingTimes = <?php echo json_encode($times_db, JSON_UNESCAPED_UNICODE); ?>;
const packageHint = document.getElementById("packageHint");

function isOvernightSelected(){ return serviceName.value === "حجز مبيت في المحمية"; }
function isPhenomenaSelected(){ return serviceName.value === "رصد الظواهر الفلكية (ليلية ونهارية)"; }
function isPackageSelected(){ return serviceName.value === "باقة المبتدئين"; }

function toggleSections(){
  phenomenaBox.style.display = isPhenomenaSelected() ? "block" : "none";
  overnightSeatsBox.style.display = isOvernightSelected() ? "block" : "none";

  if (!isOvernightSelected()){
    endDateInput.value = "";
    endDateInput.disabled = true;
  } else {
    endDateInput.disabled = HAS_RESERVATION ? true : false;
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

  const oldPostTimes = <?php
    $old = [];
    $postCount = (int)($_POST["appointments_count"] ?? 0);
    if ($postCount < 0) $postCount = 0;
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
    let stVal = "";
    let etVal = "";
    let seatsVal = "";

    if (oldPostTimes[i-1] && (oldPostTimes[i-1].st || oldPostTimes[i-1].et || oldPostTimes[i-1].seats)){
      stVal = oldPostTimes[i-1].st || "";
      etVal = oldPostTimes[i-1].et || "";
      seatsVal = oldPostTimes[i-1].seats || "";
    } else if (existingTimes[i-1]){
      stVal = existingTimes[i-1].start_time || "";
      etVal = existingTimes[i-1].end_time || "";
      seatsVal = existingTimes[i-1].seats_allowed || "";
    }

    const disabledAttr = HAS_RESERVATION ? "disabled" : "";
    const hiddenInputs = HAS_RESERVATION ? `
      <input type="hidden" name="start_time_${i}" value="${stVal}">
      <input type="hidden" name="end_time_${i}" value="${etVal}">
      <input type="hidden" name="seats_allowed_${i}" value="${seatsVal}">
    ` : "";

    const row = document.createElement("div");
    row.className = "timeRow";
    row.innerHTML = `
      <div class="field col-4">
        <label>وقت البداية (وقت ${i})</label>
        <input type="time" name="start_time_${i}" required value="${stVal}" ${disabledAttr}>
      </div>
      <div class="field col-4">
        <label>وقت النهاية (وقت ${i})</label>
        <input type="time" name="end_time_${i}" required value="${etVal}" ${disabledAttr}>
      </div>
      <div class="field col-4">
        <label>عدد المقاعد (وقت ${i})</label>
        <input type="number" name="seats_allowed_${i}" min="1" required value="${seatsVal}" ${disabledAttr}>
      </div>
      ${hiddenInputs}
    `;
    timesGrid.appendChild(row);
  }
}
serviceName.addEventListener("change", () => {
  if (isPhenomenaSelected()) countInput.value = "1";
  renderTimes();
});
countInput.addEventListener("input", renderTimes);
renderTimes();
</script>
</body>
</html>
<?php $conn->close(); ?>