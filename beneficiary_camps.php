
<?php
session_start();
require_once 'banner.php';
$servername = "localhost";
$username = "root";
$dbpass = "";
$dbname = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) { die("فشل الاتصال: " . $conn->connect_error); }

// جلب المحميات الموثقة والمعتمدة فقط
$stmt = $conn->prepare("
  SELECT 
    c.astronmy_camp_id, 
    c.astronmy_camp_name, 
    c.location,
    (SELECT s.service_description 
     FROM services s 
     WHERE s.astronmy_camp_id = c.astronmy_camp_id 
     LIMIT 1) as service_description
  FROM astronomical_camp c
  WHERE c.Accreditation_status = 'موثق ومعتمد'
  ORDER BY c.astronmy_camp_name ASC
");
$stmt->execute();
$res = $stmt->get_result();

$imgBasePath = ""; 

$campImages = [
  "محمية الامام تركي بن عبدالله الفلكية" => "turki",
  "محمية الملك سلمان بن عبدالعزيز الفلكية" => "king_salman",
  "محمية الغراميل الفلكية" => "garamel",
  "محمية الطبيق الفلكية" => "tabeg",
];

$defaultImg = $imgBasePath . "logo.jpeg"; 

function campSnippet($name, $location, $desc){
  $desc = trim((string)$desc);
  if ($desc !== "") return $desc;
  return "محمية فلكية مهيأة لتجربة رصد مميزة تحت سماء صافية، وتقديم أنشطة مناسبة للعائلات والمهتمين بعلم الفلك.";
}

function getCampImage($campName, $campImages, $imgBasePath, $defaultImg){
  $campName = trim((string)$campName);
  if (isset($campImages[$campName])) {
    return $imgBasePath . $campImages[$campName] . ".jpeg";
  }
  return $defaultImg;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>المحميات الموثقة</title>
<style>
  body{
    font-family:Tajawal,system-ui;
    background:
      radial-gradient(circle at 20% 20%, rgba(255,255,255,.12) 1px, transparent 1px),
      radial-gradient(circle at 80% 30%, rgba(255,255,255,.10) 1px, transparent 1px),
      radial-gradient(circle at 40% 70%, rgba(255,255,255,.08) 1px, transparent 1px),
      linear-gradient(180deg,#07101f 0%, #0b1020 100%);
    background-size: 180px 180px, 220px 220px, 260px 260px, cover;
    color:#fff;
    margin:0;
    padding:0;
    overflow-x:hidden;
    position:relative;
  }

  body::before,
  body::after{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    z-index:0;
  }

  body::before{
    background:
      radial-gradient(circle, rgba(255,255,255,.9) 1px, transparent 1.5px),
      radial-gradient(circle, rgba(255,255,255,.7) 1px, transparent 1.5px),
      radial-gradient(circle, rgba(255,255,255,.55) 1px, transparent 1.5px);
    background-size: 120px 120px, 180px 180px, 240px 240px;
    background-position: 0 0, 40px 60px, 100px 140px;
    animation: starsMove 40s linear infinite;
    opacity:.45;
  }

  body::after{
    background:
      radial-gradient(circle at left center, rgba(91,140,255,.16), transparent 22%),
      radial-gradient(circle at right center, rgba(207,168,95,.18), transparent 20%);
    opacity:.8;
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
  .wrap{
    max-width:1200px;
    margin:0 auto;
    padding:20px;
    position:relative;
    z-index:1;
  }

  .page-title{margin:8px 0 18px;font-size:28px;font-weight:900}
  .subtitle{margin:0 0 18px;color:#b7c0ff;font-size:14px}

  .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
  @media(max-width:800px){.grid{grid-template-columns:1fr}}

  .card{
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.14);
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 18px 60px rgba(0,0,0,.35);
    display:flex;
    flex-direction:column;
    min-height:360px;
    backdrop-filter: blur(8px);
  }

  .img{height:190px;background:#111}
  .img img{width:100%;height:100%;object-fit:cover;display:block;filter:saturate(1.1)}

  .content{padding:14px 14px 16px;display:flex;flex-direction:column;gap:10px;flex:1}
  .meta{display:flex;flex-direction:column;gap:6px}
  .kv{font-size:13px;color:#e7e9ff;opacity:.95}
  .kv b{color:#eef2ff}
  .title{font-weight:900;margin:0;font-size:18px}

  .btnRow{margin-top:auto;display:flex;justify-content:flex-start}
  .btn{
    display:inline-block;
    padding:10px 12px;
    border-radius:12px;
    text-decoration:none;
    font-weight:900;
    background:linear-gradient(90deg,#5b8cff,#7aa3ff);
    color:#000;
  }

  .gold-title{
    background: linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow:
      0 6px 25px rgba(0,0,0,0.45),
      0 2px 8px rgba(0,0,0,0.3);
  }

  @keyframes starsMove{
    from{ transform: translateY(0); }
    to{ transform: translateY(120px); }
  }
</style>
</head>
<body>
<?php renderBanner('camps'); ?>

<div class="planet-left"></div>
<div class="planet-right"></div>

<div class="wrap">
  <h1 class="gold-title">خدمات المحميات الفلكية المتاحة</h1>

  <?php if($res->num_rows === 0): ?>
    <p>لا توجد محميات متاحة حالياً.</p>
  <?php else: ?>
    <div class="grid">
      <?php while($row = $res->fetch_assoc()): ?>
        <?php
          $campName = $row["astronmy_camp_name"] ?? "";
          $loc = $row["location"] ?? "";
          $img = getCampImage($campName, $campImages, $imgBasePath, $defaultImg);
        ?>
        <div class="card">
          <div class="img">
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($campName); ?>" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($defaultImg); ?>';">
          </div>
          <div class="content">
            <div class="meta">
              <p class="title"><?php echo htmlspecialchars($campName); ?></p>
              <div class="kv"><b>الموقع:</b> <?php echo htmlspecialchars($loc); ?></div>
            </div>
            <div class="btnRow">
              <a class="btn" href="beneficiary_services.php?camp_id=<?php echo (int)$row["astronmy_camp_id"]; ?>">
                استعرض الخدمات 
              </a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>