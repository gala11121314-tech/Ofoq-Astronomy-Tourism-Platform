
<?php
session_start();
require_once 'banner.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "reserve") {
  header("Location: login.php");
  exit;
}

/* =========================
   اتصال قاعدة البيانات
   ========================= */
$conn = new mysqli("localhost", "root", "", "aofq",3307);
if ($conn->connect_error) {
  die("فشل الاتصال بقاعدة البيانات");
}
$conn->set_charset("utf8mb4");

/* =========================
   ID المحمية من السيشن
   ========================= */
$campId = (int)($_SESSION["astronmy_camp_id"] ?? 0);

/* =========================
   افتراضيات
   ========================= */
$campName     = "محمية";
$locationText = "—";
$mapsUrl      = "";

/* =========================
   جلب (اسم المحمية + الموقع + رابط قوقل ماب) من القاعدة
   ========================= */
if ($campId > 0) {
  $stmt = $conn->prepare("
    SELECT astronmy_camp_name, location, google_maps_url
    FROM astronomical_camp
    WHERE astronmy_camp_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $campId);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    $campName     = (string)$row["astronmy_camp_name"];
    $locationText = (string)$row["location"];
    $mapsUrl      = (string)$row["google_maps_url"];
  }

  $stmt->close();
}

/* =========================
   جلب تقييمات المحمية
   ========================= */
$ratingsResult = null;

if ($campId > 0) {
  $sqlRatings = "
    SELECT r.rating_Stars, r.comment, u.Fname, s.service_name
    FROM rating r
    JOIN user u ON r.user_id = u.user_id
    LEFT JOIN services s ON r.service_id = s.service_id
    WHERE r.astronmy_camp_id = ?
    ORDER BY r.rating_id DESC
  ";

  $stmtRatings = $conn->prepare($sqlRatings);
  $stmtRatings->bind_param("i", $campId);
  $stmtRatings->execute();
  $ratingsResult = $stmtRatings->get_result();
}

/* =========================
   بيانات الصورة فقط
   ========================= */
$campsExtra = [
  4 => [ // محمية الطبيق
    "imageUrl" => "tabeg.jpeg"
  ],
  1 => [ // الملك سلمان
    "imageUrl" => "king_salman.jpeg"
  ],
  3 => [ // الإمام تركي
    "imageUrl" => "turki.jpeg"
  ],
  2 => [ // الغرميل
    "imageUrl" => "garamel.jpeg"
  ],
];

$imageUrl     = "";
$defaultImage = "tabeg.jpeg";

if ($campId > 0 && isset($campsExtra[$campId])) {
  $imageUrl = trim((string)$campsExtra[$campId]["imageUrl"]);
}

$finalImageUrl = !empty($imageUrl) ? $imageUrl : $defaultImage;
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة المحمية</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:"Tajawal","Tahoma",sans-serif;
}

body{
  overflow-x:hidden;
  background:url(./homepage.jpeg) no-repeat center center fixed;
  background-size:cover;
  color:white;
  position:relative;
}

.overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.45);
  pointer-events:none;
  z-index:0;
}

.page{
  max-width:1000px;
  margin:0 auto;
  padding:20px;
  position:relative;
  z-index:1;
  direction:rtl;
  text-align:right;
}

.card{
  background:rgba(0,0,0,0.15);
  padding:20px;
  border-radius:20px;
  margin-bottom:30px;
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,0.12);
}

.welcome-title{
  font-size:26px;
  font-weight:900;
  background:linear-gradient(90deg, #c2a57a, #e8d3a5);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  display:inline-block;
  position:relative;
  padding-bottom:8px;
  animation:fadeUp .8s ease;
}

.welcome-title::after{
  content:"";
  position:absolute;
  bottom:0;
  right:0;
  width:60%;
  height:3px;
  background:linear-gradient(90deg, #d2b48c, transparent);
  border-radius:10px;
}

.title{
  font-size:28px;
  font-weight:900;
  margin-bottom:18px;
  background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}

@keyframes fadeUp{
  from{
    opacity:0;
    transform:translateY(15px);
  }
  to{
    opacity:1;
    transform:translateY(0);
  }
}

.tips-grid{
  display:flex;
  justify-content:center;
  align-items:stretch;
  gap:18px;
  flex-wrap:wrap;
  width:100%;
}

.tip{
  width:260px;
  flex:0 0 260px;
}

.tip-box{
  background:linear-gradient(180deg, #ffffff, #e6efff);
  color:#10233c;
  border-radius:18px;
  padding:18px 16px;
  min-height:200px;
  box-shadow:0 14px 30px rgba(0,0,0,.35);
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  text-align:center;
  transition:.25s;
  cursor:pointer;
}

.tip-box p{
  margin:0;
  font-size:16px;
  line-height:1.8;
  font-weight:800;
}

.tip-box i{
  font-size:34px;
  margin-top:10px;
  color:#1d3d6d;
  transition:.25s ease;
}

.tip-box:hover{
  transform:translateY(-6px);
  box-shadow:0 18px 32px rgba(0,0,0,.38);
}

.tip-box:hover i{
  transform:scale(1.10);
}

.grid-2{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
}

@media (max-width:900px){
  .grid-2{
    grid-template-columns:1fr;
  }
}

.cal-wrap{
  color:#fff;
}

.cal-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:12px;
}

.cal-title{
  font-weight:900;
  font-size:16px;
}

.cal-btn{
  border:none;
  background:rgba(255,255,255,0.15);
  color:#fff;
  padding:8px 12px;
  border-radius:12px;
  cursor:pointer;
  font-weight:800;
  transition:.2s;
}

.cal-btn:hover{
  background:rgba(255,255,255,0.20);
  transform:translateY(-1px);
}

.cal-grid{
  display:grid;
  grid-template-columns:repeat(7, 1fr);
  gap:8px;
}

.cal-dow{
  text-align:center;
  font-weight:900;
  opacity:.9;
  padding:6px 0;
}

.cal-day{
  text-align:center;
  padding:10px 0;
  border-radius:14px;
  background:rgba(255,255,255,0.10);
  border:1px solid rgba(255,255,255,0.12);
  font-weight:800;
}

.cal-day.muted{
  opacity:.35;
}

.cal-day.today{
  background:rgba(0,198,255,0.80);
  border:1px solid rgba(0,198,255,0.55);
}

.weather-box{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
  flex-wrap:wrap;
}

.weather-main{
  font-weight:900;
  font-size:20px;
}

.weather-sub{
  opacity:.9;
  margin-top:6px;
  line-height:1.8;
}

.badge{
  display:inline-block;
  padding:6px 10px;
  border-radius:999px;
  background:rgba(255,255,255,0.15);
  border:1px solid rgba(255,255,255,0.14);
  font-weight:800;
  margin-left:8px;
  margin-bottom:8px;
}

.small-note{
  margin-top:10px;
  font-size:13px;
  opacity:.9;
  line-height:1.8;
}

.loc-grid{
  display:grid;
  grid-template-columns:1fr;
  gap:14px;
  align-items:stretch;
}

.loc-box{
  background:rgba(255,255,255,0.10);
  border:1px solid rgba(255,255,255,0.16);
  border-radius:18px;
  padding:16px;
  box-shadow:0 12px 26px rgba(0,0,0,0.25);
}

.loc-row{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  align-items:center;
  justify-content:space-between;
  margin-bottom:10px;
}

.red-pill{
  display:inline-block;
  padding:6px 12px;
  border-radius:999px;
  background:linear-gradient(135deg,#ff4d4d,#d62828);
  color:#fff;
  font-size:13px;
  font-weight:800;
  box-shadow:0 6px 15px rgba(255,0,0,0.25);
}

.loc-actions{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:12px;
  margin-bottom:12px;
}

.btn{
  text-decoration:none;
  padding:10px 12px;
  border-radius:12px;
  font-weight:900;
  font-size:13px;
  display:inline-flex;
  align-items:center;
  gap:8px;
  transition:.2s;
  border:1px solid rgba(255,255,255,.25);
  background:rgba(255,255,255,0.10);
  color:#fff;
  white-space:nowrap;
}

.btn:hover{
  transform:translateY(-2px);
  background:rgba(255,255,255,0.14);
}

.btn-primary{
  background:#0f1f8c;
  border-color:#0f1f8c;
}

.btn-primary:hover{
  background:#1327b5;
}

.camp-img{
  width:100%;
  height:220px;
  object-fit:cover;
  border-radius:16px;
  border:1px solid rgba(255,255,255,0.18);
  margin-top:12px;
}
</style>
</head>

<body>
<?php renderBanner('camp_home'); ?>
<div class="overlay"></div>

<div class="page">

  <div class="card">
    <h2 class="welcome-title">
      ⭐ مرحبًا <?php echo htmlspecialchars($campName, ENT_QUOTES, 'UTF-8'); ?>
    </h2>
  </div>

  <div class="card">
    <div class="title">نبذة عن منصة أُفق</div>
    منصة أُفق هي منصة رقمية متخصصة في تقديم وتنظيم التجارب والخدمات الفلكية في المملكة العربية السعودية.
    تساعد المنصة المحميات على عرض خدماتها، كما تجمع المهتمين بعلم الفلك في منصة واحدة تتيح لهم استكشاف التجارب الفلكية المختلفة.
    وتوفر المنصة للزوار إمكانية استكشاف التجارب المتاحة، وإجراء الحجوزات، والتواصل مع المحميات بسهولة.
    وتسهم منصة أُفق في دعم السياحة الفلكية وتعزيز الاهتمام بعلم الفلك، بما يتماشى مع توجهات التحول الرقمي وتنمية القطاع السياحي في المملكة.
  </div>

  <div class="card">
    <div class="title">إرشادات قد تهمك</div>

    <div class="tips-grid">
      <div class="tip">
        <div class="tip-box">
          <p>تأكد من تحديث معلومات المحمية بشكل مستمر لضمان ظهور بيانات دقيقة وواضحة للزوار.</p>
          <i class="fas fa-rotate"></i>
        </div>
      </div>

      <div class="tip">
        <div class="tip-box">
          <p>قم بإضافة الخدمات والتجارب الفلكية المتاحة بشكل منظم لتمكين الزوار من استكشافها والحجز بسهولة.</p>
          <i class="fas fa-list"></i>
        </div>
      </div>

      <div class="tip">
        <div class="tip-box">
          <p>تابع الحجوزات الجديدة بشكل مستمر لضمان جاهزية المحمية واستعدادها لاستقبال الزوار.</p>
          <i class="fas fa-calendar-check"></i>
        </div>
      </div>

      <div class="tip">
        <div class="tip-box">
          <p>احرص على الرد على استفسارات الزوار في الوقت المناسب لتعزيز رضا المستخدم وتحسين تجربته.</p>
          <i class="fas fa-comments"></i>
        </div>
      </div>

      <div class="tip">
        <div class="tip-box">
          <p>تأكد من وضوح تفاصيل الخدمات ومواعيدها لتجنب أي تعارض أو لبس في عمليات الحجز.</p>
          <i class="fas fa-circle-info"></i>
        </div>
      </div>

      <div class="tip">
        <div class="tip-box">
          <p>راجع حالة الطقس قبل مواعيد التجارب الفلكية لضمان توفر الظروف المناسبة للرصد.</p>
          <i class="fas fa-cloud-sun"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <h2 class="title">تقييمات الزوار</h2>

    <?php if ($ratingsResult && $ratingsResult->num_rows > 0): ?>
      <?php while($rating = $ratingsResult->fetch_assoc()): ?>
        <div style="background:rgba(255,255,255,0.08); padding:15px; border-radius:15px; margin-bottom:10px;">

          <div style="font-weight:900; color:#fff;">
            <?php echo htmlspecialchars($rating['Fname'], ENT_QUOTES, 'UTF-8'); ?>
          </div>

          <div style="color:#fbbf24;">
            <?php echo str_repeat("★", (int)$rating['rating_Stars']); ?>
          </div>

          <div style="font-size:14px; color:#cbd5e1;">
            الخدمة: <?php echo htmlspecialchars($rating['service_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
          </div>

          <p style="margin-top:8px;">
            <?php echo htmlspecialchars($rating['comment'], ENT_QUOTES, 'UTF-8'); ?>
          </p>

        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>لا توجد تقييمات حالياً</p>
    <?php endif; ?>
  </div>

  <div class="grid-2">

    <div class="card cal-wrap">
      <div class="title">التقويم</div>
      <div class="cal-head">
        <button class="cal-btn" type="button" id="calPrev">السابق</button>
        <div class="cal-title" id="calTitle">—</div>
        <button class="cal-btn" type="button" id="calNext">التالي</button>
      </div>
      <div class="cal-grid" id="calGrid"></div>
      <div class="small-note">التاريخ الحالي مميز باللون الأزرق.</div>
    </div>

    <div class="card">
      <div class="title">حالة الطقس</div>
      <div class="weather-box">
        <div>
          <div class="weather-main" id="wMain">جاري تحميل الطقس…</div>
          <div class="weather-sub" id="wSub">قد تحتاجين السماح بالموقع (GPS) لعرض الطقس بدقة.</div>
        </div>

        <div>
          <div class="badge" id="wTemp">—</div>
          <div class="badge" id="wWind">—</div>
          <div class="badge" id="wTodayRange">—</div>
        </div>
      </div>

      <div class="small-note" id="wNote"></div>
    </div>

  </div>

  <div class="card">
    <h2 class="title">موقع المحمية</h2>
    <div class="loc-grid">
      <div class="loc-box">

        <div class="loc-row">
          <span class="red-pill">اسم المحمية</span>
          <strong><?php echo htmlspecialchars($campName, ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>

        <div class="loc-row">
          <span class="red-pill">الموقع</span>
          <span><?php echo $locationText ? htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') : "—"; ?></span>
        </div>

        <div class="loc-actions">
          <?php if (!empty($mapsUrl)): ?>
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($mapsUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
              🗺️ Google Maps
            </a>
          <?php endif; ?>
        </div>

        <img class="camp-img"
             src="<?php echo htmlspecialchars($finalImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
             alt="صورة المحمية"
             loading="lazy"
             onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($defaultImage, ENT_QUOTES, 'UTF-8'); ?>';">

      </div>
    </div>
  </div>

</div>

<script>
/* Calendar */
(function(){
  const months = ["يناير","فبراير","مارس","أبريل","مايو","يونيو","يوليو","أغسطس","سبتمبر","أكتوبر","نوفمبر","ديسمبر"];
  const dows = ["الأحد","الإثنين","الثلاثاء","الأربعاء","الخميس","الجمعة","السبت"];

  const titleEl = document.getElementById("calTitle");
  const gridEl  = document.getElementById("calGrid");
  const prevBtn = document.getElementById("calPrev");
  const nextBtn = document.getElementById("calNext");

  const today = new Date();
  let viewY = today.getFullYear();
  let viewM = today.getMonth();

  function sameDay(a,b){
    return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate();
  }

  function render(){
    titleEl.textContent = months[viewM] + " " + viewY;
    gridEl.innerHTML = "";

    for(const d of dows){
      const el = document.createElement("div");
      el.className = "cal-dow";
      el.textContent = d;
      gridEl.appendChild(el);
    }

    const first = new Date(viewY, viewM, 1);
    const last  = new Date(viewY, viewM + 1, 0);
    const startDow = first.getDay();
    const daysInMonth = last.getDate();

    const prevLast = new Date(viewY, viewM, 0).getDate();
    for(let i = 0; i < startDow; i++){
      const dayNum = prevLast - (startDow - 1 - i);
      const el = document.createElement("div");
      el.className = "cal-day muted";
      el.textContent = dayNum;
      gridEl.appendChild(el);
    }

    for(let d = 1; d <= daysInMonth; d++){
      const el = document.createElement("div");
      const cur = new Date(viewY, viewM, d);
      el.className = "cal-day" + (sameDay(cur, today) ? " today" : "");
      el.textContent = d;
      gridEl.appendChild(el);
    }

    const totalCells = 7 + startDow + daysInMonth;
    const remainder = totalCells % 7;
    const add = (remainder === 0) ? 0 : (7 - remainder);

    for(let i = 1; i <= add; i++){
      const el = document.createElement("div");
      el.className = "cal-day muted";
      el.textContent = i;
      gridEl.appendChild(el);
    }
  }

  prevBtn.addEventListener("click", () => {
    viewM--;
    if(viewM < 0){
      viewM = 11;
      viewY--;
    }
    render();
  });

  nextBtn.addEventListener("click", () => {
    viewM++;
    if(viewM > 11){
      viewM = 0;
      viewY++;
    }
    render();
  });

  render();
})();
</script>

<script>
/* Weather */
(function(){
  const wMain  = document.getElementById("wMain");
  const wSub   = document.getElementById("wSub");
  const wTemp  = document.getElementById("wTemp");
  const wWind  = document.getElementById("wWind");
  const wRange = document.getElementById("wTodayRange");
  const wNote  = document.getElementById("wNote");

  function codeToAr(code){
    const map = {
      0:"صحو",1:"غائم جزئي",2:"غائم جزئي",3:"غائم",
      45:"ضباب",48:"ضباب",
      51:"رذاذ خفيف",53:"رذاذ",55:"رذاذ كثيف",
      61:"مطر خفيف",63:"مطر",65:"مطر غزير",
      71:"ثلج خفيف",73:"ثلج",75:"ثلج كثيف",
      80:"زخات خفيفة",81:"زخات",82:"زخات قوية",
      95:"عواصف رعدية"
    };
    return map[code] || ("حالة جوية (" + code + ")");
  }

  async function fetchWeather(lat, lon){
    const url =
      "https://api.open-meteo.com/v1/forecast"
      + "?latitude=" + encodeURIComponent(lat)
      + "&longitude=" + encodeURIComponent(lon)
      + "&current=temperature_2m,wind_speed_10m,weather_code"
      + "&daily=temperature_2m_max,temperature_2m_min"
      + "&timezone=Asia%2FRiyadh";

    const r = await fetch(url);
    if(!r.ok) throw new Error("weather_fetch_failed");
    return await r.json();
  }

  function setError(msg){
    wMain.textContent = "تعذر جلب الطقس";
    wSub.textContent  = msg;
    wTemp.textContent = "—";
    wWind.textContent = "—";
    wRange.textContent = "—";
    wNote.textContent = "";
  }

  if(!("geolocation" in navigator)){
    setError("متصفحك لا يدعم تحديد الموقع.");
    return;
  }

  navigator.geolocation.getCurrentPosition(async (pos) => {
    try{
      const lat = pos.coords.latitude;
      const lon = pos.coords.longitude;

      const data = await fetchWeather(lat, lon);
      const c = data.current;
      const desc = codeToAr(c.weather_code);

      wMain.textContent = desc;
      wSub.textContent  = "الموقع الحالي: (" + lat.toFixed(3) + ", " + lon.toFixed(3) + ")";
      wTemp.textContent = "🌡 " + Math.round(c.temperature_2m) + "°";
      wWind.textContent = "💨 " + Math.round(c.wind_speed_10m) + " كم/س";

      if(data.daily && data.daily.temperature_2m_max && data.daily.temperature_2m_min){
        const max = data.daily.temperature_2m_max[0];
        const min = data.daily.temperature_2m_min[0];
        wRange.textContent = "اليوم: " + Math.round(min) + "° - " + Math.round(max) + "°";
      } else {
        wRange.textContent = "اليوم: —";
      }

      wNote.textContent = "ملاحظة: إذا لم يظهر الطقس، تأكد من السماح بالموقع للمتصفح.";
    } catch(e){
      setError("حاول تحديث الصفحة مرة أخرى.");
    }
  }, () => {
    setError("يتطلب سماح الموقع لعرض الطقس بدقة (GPS).");
  }, { enableHighAccuracy:true, timeout:8000 });
})();
</script>

</body>
</html>