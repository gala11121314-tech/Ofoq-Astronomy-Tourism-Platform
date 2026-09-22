
<?php
session_start();
require_once 'banner.php';
// الاتصال بالقاعدة
$conn = new mysqli("localhost", "root", "", "aofq",3307);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) { die("فشل الاتصال"); }

$camp_id = isset($_GET["camp_id"]) ? (int)$_GET["camp_id"] : 0;
if ($camp_id <= 0) {
  header("Location: beneficiary_camps.php");
  exit;
}

// اسم المحمية
$stmtCamp = $conn->prepare("SELECT astronmy_camp_name FROM astronomical_camp WHERE astronmy_camp_id=? LIMIT 1");
$stmtCamp->bind_param("i", $camp_id);
$stmtCamp->execute();
$campRes = $stmtCamp->get_result();
$campName = ($campRes->num_rows > 0) ? $campRes->fetch_assoc()["astronmy_camp_name"] : "";

// جلب الخدمات والمواعيد
$stmt = $conn->prepare("
  SELECT
    s.service_id, s.service_name, s.price, s.service_description,
    s.start_date, s.end_date,
    t.time_id, t.start_time, t.end_time, t.seats_allowed
  FROM services s
  LEFT JOIN service_times t ON t.service_id = s.service_id
  WHERE s.astronmy_camp_id = ?
    AND (
      s.end_date > CURDATE()
      OR (
        s.end_date = CURDATE()
        AND t.end_time > CURTIME()
      )
    )
  ORDER BY s.start_date ASC, s.service_name ASC, t.start_time ASC
");
$stmt->bind_param("i", $camp_id);
$stmt->execute();
$res = $stmt->get_result();

$services = [];
while($row = $res->fetch_assoc()){
  $sid = (int)$row["service_id"];
  if (!isset($services[$sid])) {
    $services[$sid] = [
      "service_id"=>$sid,
      "service_name"=>$row["service_name"],
      "price"=>(int)$row["price"],
      "desc"=>$row["service_description"],
      "start_date"=>$row["start_date"],
      "end_date"=>$row["end_date"],
      "times"=>[]
    ];
  }
  if (!empty($row["time_id"])) {
    $services[$sid]["times"][] = [
      "time_id"=>(int)$row["time_id"],
      "start_time"=>$row["start_time"],
      "end_time"=>$row["end_time"],
      "remaining"=>(int)$row["seats_allowed"]
    ];
  }
}
$serviceImages = [
  "حجز ساعة مع مرشد فلكي" => "guide.jpeg",
  "حجز جلسات تصوير فلكي" => "photo.jpeg",
  "عروض توجيه الليزر ومشاهدة النجوم" => "laser.jpeg",
  "حجز مبيت في المحمية" => "overnight.jpeg",
  "رصد الظواهر الفلكية (ليلية ونهارية)" => "phenomena.jpeg",
  "باقة المبتدئين" => "package.jpeg",
];

function pickImage($name,$map){
  return $map[$name] ?? "guide.jpeg";
}
$packageText = "باقة المبتدئين تشمل:
- أمسيات فلكية
- حجز ساعة مع مرشد فلكي
- جلسات رصد الظواهر الفلكية
- حجز جلسات تصوير فلكي";
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>خدمات: <?php echo htmlspecialchars($campName); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0b1020; --text:#eef2ff; --muted:#b7c0ff;
  --line:rgba(255,255,255,.14); --card:rgba(255,255,255,.06);
}
*{box-sizing:border-box}

body{
  font-family:Tajawal,system-ui;
  color:#fff;
  margin:0;
  padding:0;
  overflow-x:hidden;
  position:relative;

  background:
    radial-gradient(circle at 20% 20%, rgba(255,255,255,.12) 1px, transparent 1px),
    radial-gradient(circle at 80% 30%, rgba(255,255,255,.10) 1px, transparent 1px),
    radial-gradient(circle at 40% 70%, rgba(255,255,255,.08) 1px, transparent 1px),
    linear-gradient(180deg,#07101f 0%, #0b1020 100%);

  background-size:180px 180px, 220px 220px, 260px 260px, cover;
}

/* النجوم المتحركة */
body::before{
  content:"";
  position:fixed;
  inset:0;
  pointer-events:none;
  z-index:0;

  background:
    radial-gradient(circle, rgba(255,255,255,.9) 1px, transparent 1.5px),
    radial-gradient(circle, rgba(255,255,255,.7) 1px, transparent 1.5px),
    radial-gradient(circle, rgba(255,255,255,.55) 1px, transparent 1.5px);

  background-size:120px 120px, 180px 180px, 240px 240px;
  background-position:0 0, 40px 60px, 100px 140px;

  animation:starsMove 40s linear infinite;
  opacity:.4;
}

@keyframes starsMove{
  from{ transform: translateY(0); }
  to{ transform: translateY(120px); }
}
.page{padding:20px}
.wrap{max-width:1200px;margin:0 auto}
a.back{
  display:inline-block;
  margin:6px 0 14px;
  padding:10px 14px;
  border-radius:12px;
  text-decoration:none;
  font-weight:900;
  font-size:14px;
  color:#fff;
  background:linear-gradient(180deg, rgba(255,91,107,.28), rgba(192,57,43,.55));
  border:1px solid rgba(255,91,107,.45);
  box-shadow:0 8px 20px rgba(255,91,107,.18);
  transition:.18s ease;
}

a.back:hover{
  background:linear-gradient(180deg, rgba(255,91,107,.38), rgba(192,57,43,.72));
  transform:translateY(-1px);
}h2{margin:10px 0 6px;font-weight:900}
.sub{color:var(--muted);margin:0 0 20px}

/* Grid & Cards */
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.grid{grid-template-columns:1fr}}
.card{background:var(--card);border:1px solid var(--line);border-radius:18px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 18px 60px rgba(0,0,0,.35);}
.img{height:220px;background:#000}
.img img{width:100%;height:100%;object-fit:cover;display:block}
.body{padding:14px;flex:1;display:flex;flex-direction:column;gap:10px}
.titleRow{display:flex;justify-content:space-between;align-items:center;gap:10px}
.title{margin:0;font-weight:900;font-size:15px}
.price{color:var(--muted);font-weight:900;white-space:nowrap}
.meta{font-size:13px;color:var(--muted);line-height:1.6}
.desc{font-size:13px;line-height:1.8;white-space:pre-line;color:#e7e9ff;opacity:.95}
.timeRow{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 10px;border-radius:12px;background:rgba(0,0,0,.2);margin-top:6px;border:1px solid rgba(255,255,255,.10);}
.btn{padding:8px 12px;border-radius:12px;background:linear-gradient(90deg,#5b8cff,#7aa3ff);color:#000;text-decoration:none;font-weight:900;font-size:13px;white-space:nowrap; cursor: pointer; border:none;}
.timeRange{direction:ltr;unicode-bidi:isolate;display:inline-block;font-weight:900;}
.dateLine{direction:ltr;unicode-bidi:isolate;display:inline-block;font-weight:900;}
.gold-title{
  background: linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}

  .planet-left{
  position: fixed;
  top: 110px;
  left: -35px;
  width: 220px;
  height: 220px;
  background: url("saturn.png") no-repeat center/contain;
  opacity: .34;
  pointer-events: none;
  z-index: 0;
}

.planet-right{
  position: fixed;
  bottom: 60px;
  right: -20px;
  width: 140px;
  height: 140px;
  background: url("saturn.png") no-repeat center/contain;
  opacity: .22;
  transform: scaleX(-1);
  pointer-events: none;
  z-index: 0;
}
</style>
</head>
<body>
<?php renderBanner('services'); ?>

<div class="planet-left"></div>
<div class="planet-right"></div>

<div class="page">
  <div class="wrap">
    <a class="back" href="beneficiary_camps.php">→ رجوع للمحميات الفلكية</a>
<h1 class="gold-title">خدمات: <?php echo htmlspecialchars($campName); ?></h1>    <?php if(empty($services)): ?>
      <p>لا توجد خدمات حالياً.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach($services as $srv):
          $img = pickImage($srv["service_name"],$serviceImages);
          $desc = trim((string)$srv["desc"]);
		  if($desc==="" && $srv["service_name"]==="باقة المبتدئين"){
  $desc=$packageText;
}
          $sd = $srv["start_date"];
          $ed = $srv["end_date"];
        ?>
          <div class="card">
            <div class="img">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="">
            </div>

            <div class="body">
              <div class="titleRow">
                <div class="title"><?php echo htmlspecialchars($srv["service_name"]); ?></div>
                <div class="price"><?php echo (int)$srv["price"]; ?> ريال</div>
              </div>

              <div class="meta">
                التاريخ:
                <?php if(!empty($ed) && $ed != $sd): ?>
                  <span class="dateLine"><?php echo htmlspecialchars($sd); ?> → <?php echo htmlspecialchars($ed); ?></span>
                <?php else: ?>
                  <span class="dateLine"><?php echo htmlspecialchars($sd); ?></span>
                <?php endif; ?>
              </div>

              <?php if($desc!==""): ?>
                <div class="desc"><?php echo htmlspecialchars($desc); ?></div>
              <?php endif; ?>

              <?php foreach($srv["times"] as $t): ?>
                <div class="timeRow">
                  <div>
                    <?php if ($srv["service_name"] === "حجز مبيت في المحمية"): ?>
                      (دخول) <span class="timeRange"><?php echo htmlspecialchars($t["start_time"]); ?></span>
                      — (خروج) <span class="timeRange"><?php echo htmlspecialchars($t["end_time"]); ?></span>
                      <span class="muted">اليوم التالي</span>
                    <?php else: ?>
                      <span class="timeRange"><?php echo htmlspecialchars($t["start_time"]); ?> → <?php echo htmlspecialchars($t["end_time"]); ?></span>
                    <?php endif; ?>
                    <br>المقاعد المتبقية: <?php echo $t["remaining"]; ?>
                  </div>

                  <?php if($t["remaining"] > 0): ?>
                    <?php if(isset($_SESSION['role'])): ?>
                      <a class="btn" href="addBooking.php?service_id=<?php echo (int)$srv["service_id"]; ?>&time_id=<?php echo (int)$t["time_id"]; ?>">احجز</a>
                    <?php else: ?>
                      <a class="btn" href="login.php" onclick="alert('يجب عليك تسجيل الدخول أولاً للحجز')">احجز</a>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="btn" style="background:#555;cursor:not-allowed;">ممتلئ</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>

            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
<?php
$stmtCamp->close();
$stmt->close();
$conn->close();
?>